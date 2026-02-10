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
     * Calcula los horarios disponibles.
     * Muestra SOLO el primer turno libre de cada bloque de atención configurado.
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

        // 2. Obtener configuración de horarios (Semanales + Puntuales)
        $bloques = HorarioMedico::where('id_medico', $id_medico)
            ->where('dia_semana', $dia)
            ->get();

        $fechasPuntuales = MedicoHorarioFecha::where('id_medico', $id_medico)
            ->where('fecha', $fecha->format('Y-m-d'))
            ->get();

        $todosLosBloques = $bloques->concat($fechasPuntuales);

        if ($todosLosBloques->isEmpty()) {
            return [
                'horarios' => [],
                'mensaje'  => 'El médico no tiene horarios configurados para este día.'
            ];
        }

        // 3. Generar TODOS los slots posibles (Configuración pura)
        $slotsConfigurados = [];
        foreach ($todosLosBloques as $bloque) {
            $inicio = Carbon::parse($fecha->format('Y-m-d') . ' ' . $bloque->hora_inicio);
            $fin    = Carbon::parse($fecha->format('Y-m-d') . ' ' . $bloque->hora_fin);

            while ($inicio->copy()->addMinutes($intervalo)->lte($fin)) {
                // Validación "Hoy": No listar horarios ya pasados
                if ($fecha->isToday() && $inicio->lt(Carbon::now()->addMinutes(15))) {
                    $inicio->addMinutes($intervalo);
                    continue;
                }

                $horaStr = $inicio->format('H:i');
                if (!in_array($horaStr, $slotsConfigurados)) {
                    $slotsConfigurados[] = $horaStr;
                }
                $inicio->addMinutes($intervalo);
            }
        }
        
        sort($slotsConfigurados);

        if (empty($slotsConfigurados)) {
            return [
                'horarios' => [],
                'mensaje'  => 'No hay turnos disponibles para hoy (horario finalizado).'
            ];
        }

        // 4. Agrupar slots en "Grandes Bloques de Atención"
        // Si hay un salto mayor al intervalo se separa en otro grupo.
        $gruposAtencion = [];
        if (count($slotsConfigurados) > 0) {
            $grupoActual = [$slotsConfigurados[0]];
            
            for ($i = 0; $i < count($slotsConfigurados) - 1; $i++) {
                $actual = Carbon::parse($slotsConfigurados[$i]);
                $siguiente = Carbon::parse($slotsConfigurados[$i + 1]);
                
                // Si la diferencia es mayor al intervalo (ej: 30), es un corte de horario
                if ($actual->diffInMinutes($siguiente) > $intervalo) {
                    $gruposAtencion[] = $grupoActual;
                    $grupoActual = [];
                }
                $grupoActual[] = $slotsConfigurados[$i + 1];
            }
            $gruposAtencion[] = $grupoActual;
        }

        // 5. Preparar datos de Ocupación (Turnos y Bloqueos Parciales)
        $turnosOcupados = Turno::where('id_medico', $id_medico)
            ->where('fecha', $fecha->format('Y-m-d'))
            ->whereIn('estado', ['pendiente', 'realizado'])
            ->when($except_turno_id, function($q) use ($except_turno_id) {
                $q->where('id_turno', '!=', $except_turno_id);
            })
            ->pluck('hora')
            ->map(fn($h) => Carbon::parse($h)->format('H:i'))
            ->toArray();

        $bloqueosParciales = Bloqueo::where('id_medico', $id_medico)
            ->where('fecha_inicio', '<=', $fecha->format('Y-m-d'))
            ->where('fecha_fin', '>=', $fecha->format('Y-m-d'))
            ->whereNotNull('hora_inicio')
            ->get();

        // 6. Filtrar: ELEGIR SOLO EL PRIMERO DISPONIBLE DE CADA GRUPO
        $horariosFinales = [];

        foreach ($gruposAtencion as $grupo) {
            // Recorremos el grupo buscando el PRIMER hueco libre
            foreach ($grupo as $slot) {
                
                // A. Check Turnos ocupados
                if (in_array($slot, $turnosOcupados)) {
                    continue; // Está ocupado, probamos el siguiente del grupo
                }

                // B. Check Bloqueos Parciales
                $slotInicio = Carbon::parse($fecha->format('Y-m-d') . ' ' . $slot);
                $slotFin    = $slotInicio->copy()->addMinutes($intervalo);
                
                $bloqueado = false;
                foreach ($bloqueosParciales as $bp) {
                    $bInicio = Carbon::parse($fecha->format('Y-m-d') . ' ' . $bp->hora_inicio);
                    $bFin    = Carbon::parse($fecha->format('Y-m-d') . ' ' . $bp->hora_fin);

                    if (($slotInicio->gte($bInicio) && $slotInicio->lt($bFin)) || 
                        ($slotFin->gt($bInicio) && $slotFin->lte($bFin))) {
                        $bloqueado = true;
                        break;
                    }
                }

                if ($bloqueado) {
                    continue; // Está bloqueado, pruebo el siguiente
                }

                // C. ENCONTRADO 
                // Si llegamos aquí, este es el primer horario libre de este grupo.
                $horariosFinales[] = $slot;
                break; 
            }
        }

        return [
            'horarios' => $horariosFinales,
            'mensaje'  => empty($horariosFinales) ? 'No hay disponibilidad.' : 'Horarios disponibles.'
        ];
    }

    /**
     * Calcula el estado visual del calendario mes a mes.
     */
    public function obtenerEstadoMes($id_medico, $mes, $anio)
    {
        $inicioMes = Carbon::createFromDate($anio, $mes, 1)->startOfDay();
        $finMes    = $inicioMes->copy()->endOfMonth()->endOfDay();

        $diasLaborales = HorarioMedico::where('id_medico', $id_medico)->pluck('dia_semana')->unique()->toArray();
        
        $fechasPuntuales = MedicoHorarioFecha::where('id_medico', $id_medico)
            ->whereBetween('fecha', [$inicioMes->format('Y-m-d'), $finMes->format('Y-m-d')])
            ->pluck('fecha')->toArray();

        $bloqueos = Bloqueo::where('id_medico', $id_medico)
            ->where(function ($query) use ($inicioMes, $finMes) {
                $query->whereBetween('fecha_inicio', [$inicioMes, $finMes])
                    ->orWhereBetween('fecha_fin', [$inicioMes, $finMes])
                    ->orWhere(function ($sub) use ($inicioMes, $finMes) {
                        $sub->where('fecha_inicio', '<', $inicioMes)->where('fecha_fin', '>', $finMes);
                    });
            })->whereNull('hora_inicio')->get();

        $estados = [];
        $fecha = $inicioMes->copy();

        while ($fecha->lte($finMes)) {
            $fechaStr = $fecha->format('Y-m-d');
            $estado = null; 

            if (in_array($fecha->dayOfWeek, $diasLaborales) || in_array($fechaStr, $fechasPuntuales)) {
                $estado = 'disponible'; 
            }

            $esBloqueado = $bloqueos->contains(function ($bloqueo) use ($fecha) {
                return $fecha->between(Carbon::parse($bloqueo->fecha_inicio)->startOfDay(), Carbon::parse($bloqueo->fecha_fin)->endOfDay());
            });

            if ($esBloqueado) $estado = 'bloqueado';

            if ($estado) $estados[] = ['fecha' => $fechaStr, 'estado' => $estado];
            
            $fecha->addDay();
        }

        return $estados;
    }
}