<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Turno;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ActualizarEstadosTurnos extends Command
{
    /**
     * El nombre para llamar al comando.
     */
    protected $signature = 'turnos:actualizar-estados';

    /**
     * La descripción del comando.
     */
    protected $description = 'Busca turnos pendientes pasados y los marca como realizados';

    /**
     * Aquí va la lógica del robot.
     */
    public function handle()
    {
        // 1. Definir "Ahora"
        $ahora = Carbon::now();

        // 2. Buscar turnos que:
        //    - Sean 'pendientes'
        //    - Su fecha/hora sea MENOR a ahora (ya pasó)
        $turnosAfectados = Turno::where('estado', Turno::PENDIENTE)
            ->where(function ($query) use ($ahora) {
                $query->whereDate('fecha', '<', $ahora->toDateString()) // Fechas anteriores a hoy
                      ->orWhere(function ($q) use ($ahora) {
                          // O es hoy, pero la hora ya pasó
                          $q->whereDate('fecha', $ahora->toDateString())
                            ->where('hora', '<', $ahora->format('H:i:s'));
                      });
            })
            ->update(['estado' => Turno::REALIZADO]); // <--- AQUÍ CAMBIA LA BASE DE DATOS

        // 3. Informar en la consola o Logs lo que pasó
        if ($turnosAfectados > 0) {
            $this->info("¡Éxito! Se marcaron {$turnosAfectados} turnos como 'Realizados'.");
            Log::info("Scheduler: Se actualizaron {$turnosAfectados} turnos a Realizado.");
        } else {
            $this->info("No hay turnos para actualizar.");
        }
    }
}