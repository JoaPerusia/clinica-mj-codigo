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

            return $medico;
        });
    }

    /**
     * Actualiza médico y gestiona conflictos de agenda.
     */
    public function updateMedico(Medico $medico, array $data)
    {
        return DB::transaction(function () use ($medico, $data) {
            
            // 0. guardamos el nuevo tiempo de turno
            $medico->update([
                'tiempo_turno' => $data['tiempo_turno']
            ]);

            // 1. Normalizar y Preparar Nuevos Horarios
            $nuevosHorarios = $this->normalizarHorarios($data['horarios']);

            // 2. Detectar cambios en especialidades
            $nuevasEspecialidades = collect($data['especialidades'])->map(fn($id) => (int)$id)->sort()->values()->toArray();
            $especialidadesOriginales = $medico->especialidades->pluck('id_especialidad')->sort()->values()->toArray();
            $especialidadesCambiaron = ($especialidadesOriginales !== $nuevasEspecialidades);

            // 3. Gestionar Conflictos con Turnos Pendientes
            $turnosAfectados = $this->gestionarConflictosTurnos($medico, $nuevosHorarios, $especialidadesCambiaron);

            // 4. Persistir Cambios
            $medico->especialidades()->sync($nuevasEspecialidades);
            $medico->horariosTrabajo()->delete();
            foreach ($nuevosHorarios as $h) {
                $medico->horariosTrabajo()->create($h);
            }

            return $turnosAfectados;
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

    private function gestionarConflictosTurnos(Medico $medico, $nuevosHorarios, bool $especialidadesCambiaron)
    {
        $turnosPendientes = $medico->turnos()
            ->where('estado', Turno::PENDIENTE)
            ->where('fecha', '>=', Carbon::today())
            ->get();

        $contador = 0;

        foreach ($turnosPendientes as $turno) {
            $esValido = true;
            $motivo = '';

            // Caso A: Cambió de especialidad (Invalidación total)
            if ($especialidadesCambiaron) {
                $esValido = false;
                $motivo = 'El médico cambió sus especialidades/servicios.';
            }

            // Caso B: Verificar si el turno encaja en los nuevos horarios
            if ($esValido) {
                $fechaTurno = Carbon::parse($turno->fecha);
                $horaTurno = Carbon::parse($turno->hora)->setDateFrom($fechaTurno);
                
                $coincide = false;
                foreach ($nuevosHorarios as $nh) {
                    if ($nh['dia_semana'] === $fechaTurno->dayOfWeek) {
                        $inicio = Carbon::createFromFormat('H:i', $nh['hora_inicio'])->setDateFrom($fechaTurno);
                        $fin    = Carbon::createFromFormat('H:i', $nh['hora_fin'])->setDateFrom($fechaTurno);

                        if ($horaTurno->between($inicio, $fin, true)) {
                            $coincide = true;
                            break;
                        }
                    }
                }
                
                if (!$coincide) {
                    $esValido = false;
                    $motivo = 'El médico modificó sus horarios de atención.';
                }
            }

            if (!$esValido) {
                $turno->update(['estado' => Turno::CANCELADO]);
                $contador++;
                
                // Enviar Email
                try {
                    $turno->load('paciente.usuario', 'medico.usuario');
                    Mail::to($turno->paciente->usuario->email)
                        ->send(new TurnoCanceladoMailable($turno, $motivo));
                } catch (Exception $e) {
                    Log::error("Fallo envío mail turno {$turno->id_turno}: " . $e->getMessage());
                }
            }
        }

        return $contador;
    }
}