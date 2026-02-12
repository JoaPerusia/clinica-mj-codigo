<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Turno;
use App\Services\NotificacionService;
use Carbon\Carbon;

class EnviarRecordatoriosTurnos extends Command
{
    protected $signature = 'turnos:recordar';
    protected $description = 'Envía recordatorios a los pacientes con turnos mañana';

    public function handle(NotificacionService $notificador)
    {
        $manana = Carbon::tomorrow()->format('Y-m-d');
        
        $this->info("Buscando turnos para: {$manana}...");

        $turnos = Turno::where('fecha', $manana)
            ->where('estado', Turno::PENDIENTE) 
            ->with(['paciente.usuario', 'medico.usuario'])
            ->get();

        if ($turnos->isEmpty()) {
            $this->info("No hay turnos para mañana.");
            return;
        }

        $bar = $this->output->createProgressBar(count($turnos));

        foreach ($turnos as $turno) {
            $notificador->notificarRecordatorio($turno);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Recordatorios enviados exitosamente.");
    }
}