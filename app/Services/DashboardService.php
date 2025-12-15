<?php

namespace App\Services;

use App\Models\Paciente;
use App\Models\Medico;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardService
{
    public function getAdminStats()
    {
        return [
            'total_medicos'   => Medico::count(),
            'total_pacientes' => Paciente::count(),
            'turnos_hoy'      => Turno::whereDate('fecha', Carbon::today())
                                    ->where('estado', '!=', 'cancelado')
                                    ->count(),
            'turnos_pendientes_total' => Turno::where('estado', 'pendiente')
                                    ->where('fecha', '>=', Carbon::now())
                                    ->count(),
        ];
    }

    public function getMedicoStats(User $usuario)
    {
        if (!$usuario->medico) {
            return [];
        }

        $medicoId = $usuario->medico->id_medico;

        return [
            'turnos_hoy' => Turno::where('id_medico', $medicoId)
                                ->whereDate('fecha', Carbon::today())
                                ->where('estado', '!=', 'cancelado')
                                ->count(),
            'proximos_turnos' => Turno::where('id_medico', $medicoId)
                                ->where('estado', 'pendiente')
                                ->where('fecha', '>', Carbon::now())
                                ->count(),
        ];
    }

    public function getPacienteStats(User $usuario)
    {
        // Obtener IDs de todos los pacientes asociados al usuario (grupo familiar)
        $pacientesIds = $usuario->pacientes->pluck('id_paciente');

        return [
            'mis_turnos_pendientes' => Turno::whereIn('id_paciente', $pacientesIds)
                                        ->where('estado', 'pendiente')
                                        ->where('fecha', '>=', Carbon::now())
                                        ->count(),
            'historial_turnos' => Turno::whereIn('id_paciente', $pacientesIds)
                                        ->where('fecha', '<', Carbon::now())
                                        ->where('estado', '!=', 'cancelado')
                                        ->count(),
        ];
    }
}