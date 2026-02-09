<?php

namespace App\Services;

use App\Models\Medico;
use App\Models\User;
use App\Models\Rol;
use App\Models\Turno;
use App\Mail\TurnoCanceladoMailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use App\Models\MedicoHorarioFecha;

class MedicoService
{
    /**
     * Crea un nuevo médico o restaura uno eliminado.
     */
    public function createMedico(array $data)
    {
        return DB::transaction(function () use ($data) {
            $usuario = User::findOrFail($data['id_usuario']);

            // 1. Verificar si ya existe (incluso eliminado)
            $medico = Medico::withTrashed()->where('id_usuario', $usuario->id_usuario)->first();

            if ($medico) {
                if (!$medico->trashed()) {
                    throw new Exception('El usuario ya está registrado como médico activo.');
                }
                // Restaurar y actualizar solo el tiempo de turno
                $medico->restore();
                $medico->update([
                    'tiempo_turno' => $data['tiempo_turno'] ?? 30, 
                ]);
            } else {
                // Crear nuevo solo con ID y tiempo_turno
                $medico = Medico::create([
                    'id_usuario' => $usuario->id_usuario,
                    'tiempo_turno' => $data['tiempo_turno'] ?? 30, 
                ]);
            }

            // 2. Asignar Especialidades
            if (isset($data['especialidades'])) {
                $medico->especialidades()->sync($data['especialidades']);
            }

            // 3. Crear Horarios
            if (isset($data['horarios'])) {
                $medico->horariosTrabajo()->delete(); 
                foreach ($data['horarios'] as $dia => $bloques) {
                    foreach ($bloques as $bloque) {
                        $medico->horariosTrabajo()->create([
                            'dia_semana' => $bloque['dia_semana'],
                            'hora_inicio' => $bloque['hora_inicio'],
                            'hora_fin' => $bloque['hora_fin'],
                        ]);
                    }
                }
            }

            // --- 4. GUARDAR FECHAS PUNTUALES
            if (isset($data['fechas_nuevas']) && is_array($data['fechas_nuevas'])) {
                foreach ($data['fechas_nuevas'] as $fechaData) {
                    $medico->horariosFechas()->create([
                        'fecha'       => $fechaData['fecha'],
                        'hora_inicio' => $fechaData['hora_inicio'],
                        'hora_fin'    => $fechaData['hora_fin'],
                    ]);
                }
            }

            return $medico;
        });
    }

    /**
     * Actualiza médico y gestiona conflictos de agenda.
     */
    public function updateMedico(Medico $medico, array $data)
    {
        return DB::transaction(function () use ($medico, $data) {
            $medico->update(['tiempo_turno' => $data['tiempo_turno']]);
            $nuevosHorarios = $this->normalizarHorarios($data['horarios']);
            if ($nuevosHorarios instanceof \Illuminate\Support\Collection) {
                $nuevosHorarios = $nuevosHorarios->toArray();
            }
            $nuevasEspecialidades = collect($data['especialidades'])->map(fn($id) => (int)$id)->sort()->values()->toArray();
            $idsEliminar = !empty($data['fechas_eliminar']) ? explode(',', $data['fechas_eliminar']) : [];
            $turnosAfectados = $this->gestionarConflictosTurnos(
                $medico, 
                $nuevosHorarios, 
                $idsEliminar, 
                $data['fechas_nuevas'] ?? null
            );
            $medico->especialidades()->sync($nuevasEspecialidades);
            $medico->horariosTrabajo()->delete();
            foreach ($nuevosHorarios as $h) {
                $medico->horariosTrabajo()->create($h);
            }
            if (!empty($idsEliminar)) {
                MedicoHorarioFecha::whereIn('id', $idsEliminar)->delete();
            }
            if (!empty($data['fechas_nuevas'])) {
                $this->syncFechasPuntuales($medico, $data);
            }

            return $turnosAfectados;
        });
    }

    /**
     * Procesa y guarda las fechas puntuales ingresadas por lote.
     * Método auxiliar privado para mantener la limpieza del servicio.
     */
    private function syncFechasPuntuales(Medico $medico, array $data)
    {
        $fechas = explode(', ', $data['fechas_nuevas']);

        foreach ($fechas as $fecha) {
            if (!strtotime($fecha)) continue;

            MedicoHorarioFecha::firstOrCreate(
                [
                    'id_medico' => $medico->id_medico,
                    'fecha' => $fecha,
                    'hora_inicio' => $data['hora_inicio_fecha'],
                ],
                [
                    'hora_fin' => $data['hora_fin_fecha']
                ]
            );
        }
    }

    /**
     * Elimina una fecha puntual y cancela los turnos asociados.
     * Retorna la cantidad de turnos cancelados.
     */
    public function deleteFechaPuntual($id)
    {
        return DB::transaction(function () use ($id) {
            $fechaPuntual = MedicoHorarioFecha::findOrFail($id);

            // 1. Buscamos turnos PENDIENTES que caigan en ese día y rango horario
            $turnosAfectados = Turno::where('id_medico', $fechaPuntual->id_medico)
                ->where('fecha', $fechaPuntual->fecha)
                ->where('estado', 'pendiente') 
                ->where(function ($query) use ($fechaPuntual) {
                    $query->whereBetween('hora', [$fechaPuntual->hora_inicio, $fechaPuntual->hora_fin]);
                })
                ->with(['paciente.usuario', 'medico.usuario']) // Traemos datos para el mail
                ->get();

            $cantidadCancelados = $turnosAfectados->count();

            // 2. Cancelamos los turnos y notificamos
            if ($cantidadCancelados > 0) {
                
                // Definimos el mensaje que le llegará al paciente
                $motivoParaEmail = "El profesional ha eliminado su disponibilidad para la fecha " . \Carbon\Carbon::parse($fechaPuntual->fecha)->format('d/m/Y') . ".";

                foreach ($turnosAfectados as $turno) {
                    // A. Actualizar estado en BD
                    $turno->estado = 'cancelado';
                    $turno->observaciones = $motivoParaEmail; 
                    $turno->save();

                    // B. Enviar Email
                    if ($turno->paciente && $turno->paciente->usuario && $turno->paciente->usuario->email) {
                        try {
                            // Usamos queue() ya que tu Mailable tiene "use Queueable"
                            // Le pasamos el $turno y el $motivo tal como espera tu constructor
                            Mail::to($turno->paciente->usuario->email)
                                ->queue(new TurnoCanceladoMailable($turno, $motivoParaEmail));
                        } catch (\Exception $e) {
                            \Log::error("Error enviando mail cancelación turno {$turno->id_turno}: " . $e->getMessage());
                        }
                    }
                }
            }

            // 3. Eliminamos la disponibilidad de la base de datos
            $fechaPuntual->delete();

            return $cantidadCancelados;
        });
    }

    /**
     * Elimina médico y cancela sus turnos futuros.
     */
    public function deleteMedico(Medico $medico)
    {
        return DB::transaction(function () use ($medico) {
            // 1. Cancelar turnos pendientes
            $turnosCancelados = $medico->turnos()
                ->where('estado', Turno::PENDIENTE)
                ->where('fecha', '>=', Carbon::today())
                ->update(['estado' => Turno::CANCELADO]);

            // 2. Eliminar médico (Soft Delete)
            $medico->delete();

            // 3. Quitar rol de usuario
            $rolMedico = Rol::where('rol', Rol::MEDICO)->first();
            if ($rolMedico) {
                $medico->usuario->roles()->detach($rolMedico->id_rol);
            }

            return $turnosCancelados;
        });
    }

    // --- Helpers Privados ---

    private function normalizarHorarios(array $rawHorarios)
    {
        $listaPlana = collect($rawHorarios)->flatten(1)->values();
        
        $toDayIndex = function ($val) {
            if (is_numeric($val)) return (int)$val % 7; // Asegura 0-6
            $map = [
                'domingo' => 0, 'lunes' => 1, 'martes' => 2, 'miercoles' => 3, 
                'miércoles' => 3, 'jueves' => 4, 'viernes' => 5, 'sabado' => 6, 'sábado' => 6
            ];
            return $map[mb_strtolower(trim($val))] ?? null;
        };

        return $listaPlana->map(fn($h) => [
            'dia_semana'  => $toDayIndex($h['dia_semana']),
            'hora_inicio' => $h['hora_inicio'],
            'hora_fin'    => $h['hora_fin'],
        ])->filter(fn($h) => $h['dia_semana'] !== null)->values();
    }

    /**
     * Verifica conflictos considerando Horarios Semanales Y Fechas Puntuales.
     */
    private function gestionarConflictosTurnos(Medico $medico, array $nuevosHorarios, array $idsEliminar, ?string $fechasNuevasStr)
    {
        // 1. Buscamos TODOS los turnos futuros pendientes
        $turnosPendientes = Turno::where('id_medico', $medico->id_medico)
            ->where('estado', 'pendiente')
            ->whereDate('fecha', '>=', now())
            ->get();

        $contadorCancelados = 0;

        // Recuperamos los horarios NUEVOS del request para validar estrictamente
        $horaInicioNueva = request('hora_inicio_fecha');
        $horaFinNueva = request('hora_fin_fecha');
        
        $fechasNuevasArray = [];
        if ($fechasNuevasStr) {
            $rawFechas = explode(', ', $fechasNuevasStr);
            foreach($rawFechas as $f) {
                // Guardamos solo fechas válidas
                if(strtotime($f)) $fechasNuevasArray[] = \Carbon\Carbon::parse($f)->format('Y-m-d');
            }
        }

        foreach ($turnosPendientes as $turno) {
            $fechaTurno = \Carbon\Carbon::parse($turno->fecha);
            $horaTurno = \Carbon\Carbon::parse($turno->hora);
            $horaTurnoStr = $horaTurno->format('H:i:s');
            $diaSemana = $fechaTurno->dayOfWeek;

            $turnoSalvado = false;

            // --- CHECK 1: Horario Semanal (Lunes, Martes...) ---
            foreach ($nuevosHorarios as $horario) {
                if ($horario['dia_semana'] == $diaSemana) {
                    $inicio = \Carbon\Carbon::parse($horario['hora_inicio']);
                    $fin = \Carbon\Carbon::parse($horario['hora_fin']);
                    
                    // Comparamos horas estrictamente
                    if ($horaTurno->format('H:i') >= $inicio->format('H:i') && 
                        $horaTurno->format('H:i') < $fin->format('H:i')) {
                        $turnoSalvado = true;
                        break; 
                    }
                }
            }
            if ($turnoSalvado) continue; 

            // --- CHECK 2: Fechas Puntuales NUEVAS (Las que estás agregando ahora) ---
            if (in_array($fechaTurno->format('Y-m-d'), $fechasNuevasArray)) {
                // Si agregamos fecha nueva, verificamos que el turno entre en el NUEVO horario
                if ($horaInicioNueva && $horaFinNueva) {
                    $inicio = \Carbon\Carbon::parse($horaInicioNueva);
                    $fin = \Carbon\Carbon::parse($horaFinNueva);
                    
                    if ($horaTurno->format('H:i') >= $inicio->format('H:i') && 
                        $horaTurno->format('H:i') < $fin->format('H:i')) {
                        $turnoSalvado = true;
                    }
                }
            }
            if ($turnoSalvado) continue;

            // --- CHECK 3: Fechas Puntuales EXISTENTES (Base de Datos) ---
            // Buscamos si hay una fecha en BD que cubra este turno, EXCLUYENDO las que se van a borrar
            $fechaDB = MedicoHorarioFecha::where('id_medico', $medico->id_medico)
                ->where('fecha', $fechaTurno->format('Y-m-d'))
                ->whereNotIn('id', $idsEliminar)
                ->where(function($q) use ($horaTurnoStr) {
                    $q->whereTime('hora_inicio', '<=', $horaTurnoStr)
                      ->whereTime('hora_fin', '>', $horaTurnoStr);
                })
                ->exists();

            if ($fechaDB) {
                $turnoSalvado = true;
            }

            if (!$turnoSalvado) {
                $turno->estado = 'cancelado';
                $turno->observaciones = "El profesional ha modificado sus horarios y este turno ya no es válido.";
                $turno->save();
                
                // Enviar Mail
                if ($turno->paciente && $turno->paciente->usuario && $turno->paciente->usuario->email) {
                    try {
                        Mail::to($turno->paciente->usuario->email)
                            ->queue(new TurnoCanceladoMailable($turno, $turno->observaciones));
                    } catch (\Exception $e) {}
                }

                $contadorCancelados++;
            }
        }

        return $contadorCancelados;
    }
}