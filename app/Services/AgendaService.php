<?php

namespace App\Services;

use App\Models\Bloqueo;
use App\Models\HorarioMedico;
use App\Models\Turno;
use Carbon\Carbon;

class AgendaService
{
    /**
     * Calcula los horarios disponibles para un médico en una fecha específica.
     */
    public function obtenerHorariosDisponibles($id_medico, $fecha_str, $except_turno_id = null)
    {
        $intervalo = 30; // minutos por turno
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

        // 2. Obtener horarios de trabajo del médico para ese día de la semana
        $bloquesTrabajo = HorarioMedico::where('id_medico', $id_medico)
            ->where('dia_semana', $dia)
            ->get();

        if ($bloquesTrabajo->isEmpty()) {
            return [
                'horarios' => [],
                'mensaje'  => 'El médico no trabaja los ' . $fecha->locale('es')->dayName . '.'
            ];
        }

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

        // 5. Generar slots de tiempo
        foreach ($bloquesTrabajo as $bloque) {
            $inicio = Carbon::parse($bloque->hora_inicio);
            $fin    = Carbon::parse($bloque->hora_fin);

            // Generar intervalos de 30 mins
            for ($hora = $inicio->copy(); $hora->lt($fin); $hora->addMinutes($intervalo)) {
                
                // El turno termina 30 mins después del inicio
                $finTurno = (clone $hora)->addMinutes($intervalo);

                // Si el turno termina después de que el médico se vaya, no es válido (opcional, depende de tu regla)
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
                    
                    // Lógica de superposición de horarios
                    return ($hora->gte($bInicio) && $hora->lt($bFin)) || 
                           ($finTurno->gt($bInicio) && $finTurno->lte($bFin));
                });

                if ($enBloqueo) {
                    continue;
                }

                // 5.3 Saltar si es hoy y el horario ya pasó (damos 15 mins de margen)
                if ($fecha->isToday() && $hora->lt(Carbon::now()->addMinutes(15))) {
                    continue;
                }

                $horariosDisponibles[] = $slot;
            }
        }

        // 6. Ordenar y limpiar
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
}