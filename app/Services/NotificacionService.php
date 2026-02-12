<?php

namespace App\Services;

use App\Models\Turno;
use App\Mail\TurnoCanceladoMailable;
use App\Mail\RecordatorioTurnoMailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificacionService
{
    /**
     * Notifica cancelación (Email + WhatsApp)
     */
    public function notificarCancelacion(Turno $turno, string $motivo)
    {
        $this->cargarRelaciones($turno);

        $usuario = $turno->paciente->usuario ?? null;
        if (!$usuario) return;

        // 1. EMAIL
        if ($usuario->email) {
            try {
                Mail::to($usuario->email)->queue(new TurnoCanceladoMailable($turno, $motivo));
            } catch (\Exception $e) {
                Log::error("Error email cancelación: " . $e->getMessage());
            }
        }

        $nombreMedico = $turno->medico->usuario->apellido ?? '-';
        // 2. WHATSAPP
        if (!empty($turno->paciente->telefono)) {
            $fecha = \Carbon\Carbon::parse($turno->fecha)->format('d/m/Y');
            $hora = \Carbon\Carbon::parse($turno->hora)->format('H:i');
            
            $mensaje = "Hola {$usuario->nombre}, tu turno del {$fecha} a las {$hora}hs con el Dr/a. {$nombreMedico} fue CANCELADO. Motivo: {$motivo}.";
            $this->enviarWhatsapp($turno->paciente->telefono, $mensaje);
        }
    }

    /**
     * Notifica recordatorio (Email + WhatsApp)
     */
    public function notificarRecordatorio(Turno $turno)
    {
        $this->cargarRelaciones($turno);

        $usuario = $turno->paciente->usuario ?? null;
        if (!$usuario) return;

        $fecha = \Carbon\Carbon::parse($turno->fecha)->format('d/m/Y');
        $hora = \Carbon\Carbon::parse($turno->hora)->format('H:i');
        
        // Obtener nombre del médico
        $nombreMedico = $turno->medico->usuario->apellido ?? '-';
        $nombreEspecialidad = 'Consulta General'; // Valor por defecto

        if ($turno->medico && $turno->medico->especialidades && $turno->medico->especialidades->isNotEmpty()) {
            $nombreEspecialidad = $turno->medico->especialidades->first()->nombre_especialidad;
        }

        // 1. EMAIL
        if ($usuario->email) {
             try {
                Mail::to($usuario->email)->queue(new RecordatorioTurnoMailable($turno));
             } catch (\Exception $e) {
                Log::error("Error mail recordatorio: " . $e->getMessage());
             }
        }

        // 2. WHATSAPP
        if ($turno->paciente->telefono) {
            $mensaje = "Hola {$usuario->nombre}, recordá tu turno de *{$nombreEspecialidad}* con el Dr/a. *{$nombreMedico}* para mañana {$fecha} a las {$hora}hs.";
            
            $this->enviarWhatsapp($turno->paciente->telefono, $mensaje);
        }
    }

    /**
     * Helper para cargar todas las relaciones necesarias
     */
    private function cargarRelaciones(Turno $turno)
    {
        // Forzamos la carga de especialidades
        $turno->loadMissing([
            'paciente.usuario', 
            'medico.usuario', 
            'medico.especialidades' 
        ]);
    }

    private function enviarWhatsapp($telefono, $mensaje)
    {
        Log::channel('daily')->info("📲 [WHATSAPP SIMULADO] Para: {$telefono} | Mensaje: {$mensaje}");
    }
}