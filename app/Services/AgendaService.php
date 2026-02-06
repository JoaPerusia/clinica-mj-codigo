<?php

namespace App\Services;

use App\Models\Bloqueo;
use App\Models\HorarioMedico;
use App\Models\MedicoHorarioFecha;
use App\Models\Turno;
use Carbon\Carbon;
use App\Models\Medico;

class AgendaService
{
    /**
     * Calcula los horarios disponibles para un médico en una fecha específica.
     */
    public function obtenerHorariosDisponibles($id_medico, $fecha_str, $except_turno_id = null)
    {
        $medico = Medico::findOrFail($id_medico);
        $intervalo = (int) ($medico->tiempo_turno ?? 30); 
        $fecha = Carbon::parse($fecha_str);
        $dia   = $fecha->dayOfWeek;

        // 1. Verificar bloqueo de día completo
        $bloqueoDia = Bloqueo::where('id_medico', $id_medico)
            ->where('fecha_inicio', '<=', $fecha->format('Y-m-d'))
            ->where('fecha_fin', '>=', $fecha->format('Y-m-d'))
            ->whereNull('hora_inicio')
            ->whereNull('hora_fin')
            ->first();

        if ($bloqueoDia) {
            $motivo = $bloqueoDia->motivo ?: 'Bloqueo administrativo';
            return [
                'horarios' => [],
                'mensaje'  => "El médico no atiende en esta fecha ({$motivo})."
            ];
        }

        // 2a. Obtener horarios regulares (Semanales)
        $bloquesRegulares = HorarioMedico::where('id_medico', $id_medico)
            ->where('dia_semana', $dia)
            ->get();

        // 2b. Obtener horarios puntuales (Fechas Específicas)
        $bloquesPuntuales = MedicoHorarioFecha::where('id_medico', $id_medico)
            ->where('fecha', $fecha->format('Y-m-d'))
            ->get();

        // Si no hay horarios ni regulares ni puntuales, no trabaja hoy
        if ($bloquesRegulares->isEmpty() && $bloquesPuntuales->isEmpty()) {
            return [
                'horarios' => [],
                'mensaje'  => 'El médico no trabaja los ' . $fecha->locale('es')->dayName . ' ni tiene horario especial asignado.'
            ];
        }

        // Fusionamos ambas colecciones
        $todosLosBloques = $bloquesRegulares->concat($bloquesPuntuales);


        // 3. Obtener turnos ya ocupados en esa fecha
        $turnosOcupados = Turno::where('id_medico', $id_medico)
            ->whereDate('fecha', $fecha)
            ->whereIn('estado', [Turno::PENDIENTE, Turno::REALIZADO]) 
            ->when($except_turno_id, fn($q) => $q->where('id_turno', '!=', $except_turno_id))
            ->pluck('hora')
            ->map(fn($h) => Carbon::parse($h)->format('H:i'))
            ->toArray();

        // 4. Obtener bloqueos parciales (por horas)
        $bloqueosHoras = Bloqueo::where('id_medico', $id_medico)
            ->where('fecha_inicio', '<=', $fecha->format('Y-m-d'))
            ->where('fecha_fin', '>=', $fecha->format('Y-m-d'))
            ->whereNotNull('hora_inicio')
            ->whereNotNull('hora_fin')
            ->get();

        $horariosDisponibles = [];

        // 5. Generar slots de tiempo (Iteramos sobre la colección fusionada)
        foreach ($todosLosBloques as $bloque) {
            $inicio = Carbon::parse($bloque->hora_inicio);
            $fin    = Carbon::parse($bloque->hora_fin);

            for ($hora = $inicio->copy(); $hora->lt($fin); $hora->addMinutes($intervalo)) {
                $finTurno = (clone $hora)->addMinutes($intervalo);
                if ($finTurno->gt($fin)) {
                    continue;
                }
                $slot = $hora->format('H:i');
                // 5.1 Saltar si ya está ocupado por un turno
                if (in_array($slot, $turnosOcupados)) {
                    continue;
                }

                // 5.2 Saltar si cae dentro de un bloqueo parcial
                $enBloqueo = $bloqueosHoras->contains(function ($b) use ($hora, $finTurno) {
                    $bInicio = Carbon::parse($b->hora_inicio);
                    $bFin    = Carbon::parse($b->hora_fin);
                    
                    return ($hora->gte($bInicio) && $hora->lt($bFin)) || 
                           ($finTurno->gt($bInicio) && $finTurno->lte($bFin));
                });

                if ($enBloqueo) {
                    continue;
                }

                // 5.3 Saltar si es hoy y el horario ya pasó
                if ($fecha->isToday() && $hora->lt(Carbon::now()->addMinutes(15))) {
                    continue;
                }

                $horariosDisponibles[] = $slot;
            }
        }

        // 6. Ordenar y limpiar duplicados
        $horariosDisponibles = array_values(array_unique($horariosDisponibles));
        sort($horariosDisponibles);

        $mensaje = null;
        if (empty($horariosDisponibles)) {
            $mensaje = 'No hay horarios disponibles para esta fecha.';
        }

        return [
            'horarios' => $horariosDisponibles,
            'mensaje'  => $mensaje
        ];
    }

    /**
     * Devuelve el estado de cada día del mes para un calendario visual.
     */
    public function obtenerEstadoMes($id_medico, $mes, $anio)
    {
        $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfDay();
        $finMes    = $inicioMes->copy()->endOfMonth();

        // 1. Obtener días laborales regulares (0=Domingo, ..., 6=Sábado)
        $diasLaborales = HorarioMedico::where('id_medico', $id_medico)
            ->pluck('dia_semana')
            ->unique()
            ->toArray();

        // 2. NUEVO: Obtener fechas puntuales en este mes
        $fechasPuntuales = MedicoHorarioFecha::where('id_medico', $id_medico)
            ->whereBetween('fecha', [$inicioMes->format('Y-m-d'), $finMes->format('Y-m-d')])
            ->pluck('fecha')
            ->toArray();

        // 3. Obtener bloqueos de día completo
        $bloqueos = Bloqueo::where('id_medico', $id_medico)
            ->where(function ($q) use ($inicioMes, $finMes) {
                 $q->whereBetween('fecha_inicio', [$inicioMes, $finMes])
                    ->orWhereBetween('fecha_fin', [$inicioMes, $finMes])
                    ->orWhere(function ($sub) use ($inicioMes, $finMes) {
                        $sub->where('fecha_inicio', '<', $inicioMes)
                            ->where('fecha_fin', '>', $finMes);
                    });
            })
            ->whereNull('hora_inicio')
            ->get();

        $estados = [];
        $fecha = $inicioMes->copy();

        // 4. Recorrer día por día el mes
        while ($fecha->lte($finMes)) {
            $fechaStr = $fecha->format('Y-m-d');
            $estado = null; 

            // A. ¿Es día laboral? (Ya sea por semana o por fecha puntual)
            $esDiaLaboral = in_array($fecha->dayOfWeek, $diasLaborales);
            $esFechaPuntual = in_array($fechaStr, $fechasPuntuales);

            if ($esDiaLaboral || $esFechaPuntual) {
                $estado = 'disponible'; 
            }

            // B. ¿Está bloqueado? (El bloqueo gana sobre la disponibilidad)
            $esBloqueado = $bloqueos->contains(function ($bloqueo) use ($fecha) {
                $inicio = Carbon::parse($bloqueo->fecha_inicio)->startOfDay();
                $fin    = Carbon::parse($bloqueo->fecha_fin)->endOfDay();
                return $fecha->between($inicio, $fin);
            });

            if ($esBloqueado) {
                $estado = 'bloqueado';
            }

            if ($estado) {
                 $estados[] = [
                    'fecha' => $fechaStr,
                    'estado' => $estado
                ];
            }

            $fecha->addDay();
        }

        return $estados;
    }
}