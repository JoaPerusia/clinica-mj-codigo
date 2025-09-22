<?php

namespace App\Http\Controllers;

use App\Models\Bloqueo;
use App\Models\Medico;
use App\Models\Turno;
use App\Mail\TurnoCanceladoMailable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Notifications\TurnoCancelado;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class BloqueoController extends Controller
{
    /**
     * Muestra una lista de todos los bloqueos de agenda.
     * Permite filtrar por DNI del médico.
     */
    public function index(Request $request)
    {
        // Iniciar la consulta con la relación 'medico.usuario' para cargar los datos necesarios
        $query = Bloqueo::with('medico.usuario')->orderBy('fecha_inicio', 'desc');

        // Lógica para el filtro por DNI del médico
        if ($request->has('dni_filtro') && !empty($request->dni_filtro)) {
            $dniFiltro = $request->dni_filtro;
            
            // Unir la tabla de médicos y usuarios para filtrar por DNI
            $query->whereHas('medico.usuario', function ($q) use ($dniFiltro) {
                $q->where('dni', 'like', '%' . $dniFiltro . '%');
            });
        }

        $bloqueos = $query->paginate(10); // Paginar los resultados

        return view('admin.bloqueos.index', compact('bloqueos'));
    }

    /**
     * Muestra el formulario para crear un nuevo bloqueo.
     */
    public function create()
    {
        // Obtener todos los médicos para el selector del formulario, ordenados por nombre
        $medicos = Medico::with('usuario')->get()->sortBy(function($medico) {
            return $medico->usuario->apellido . ' ' . $medico->usuario->nombre;
        });
        
        return view('admin.bloqueos.create', compact('medicos'));
    }

    /**
     * Almacena un nuevo bloqueo en la base de datos.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $rules = [
                'id_medico' => 'required|exists:medicos,id_medico',
                'fecha_inicio' => 'required|date|after_or_equal:today',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
                'hora_inicio' => 'nullable|date_format:H:i',
                'hora_fin' => 'nullable|date_format:H:i|after:hora_inicio',
                'motivo' => 'nullable|string|max:255',
            ];

            $request->validate($rules);

            // Crear el nuevo bloqueo
            $bloqueo = Bloqueo::create([
                'id_medico' => $request->id_medico,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'hora_inicio' => $request->hora_inicio,
                'hora_fin' => $request->hora_fin,
                'motivo' => $request->motivo,
            ]);

            // Lógica de cancelación de turnos superpuestos
            $turnosSuperpuestos = Turno::where('id_medico', $bloqueo->id_medico)
                ->where('estado', 'Pendiente')
                ->where(function ($query) use ($bloqueo) {
                    // Rango de fechas del bloqueo
                    $query->whereBetween('fecha', [$bloqueo->fecha_inicio, $bloqueo->fecha_fin]);
                });

            // Lógica de rango de horas para bloqueos parciales
            if ($bloqueo->hora_inicio && $bloqueo->hora_fin) {
                $turnosSuperpuestos->where(function ($query) use ($bloqueo) {
                    $query->whereTime('hora', '>=', $bloqueo->hora_inicio)
                        ->whereTime('hora', '<=', $bloqueo->hora_fin);
                });
            }

            // Obtener los turnos superpuestos para la cancelación
            $turnosACancelar = $turnosSuperpuestos->get();
            $turnosAfectadosCount = $turnosACancelar->count();

            // Actualizar el estado de los turnos y enviar correos
            foreach ($turnosACancelar as $turno) {
                $turno->estado = 'Cancelado';
                $turno->save();
                
                // Enviar notificación por correo
                try {
                    // Cargar la relación 'paciente' si no está cargada
                    if (!$turno->relationLoaded('paciente')) {
                        $turno->load('paciente.usuario');
                    }
                    
                    Mail::to($turno->paciente->usuario->email)
                        ->send(new TurnoCanceladoMailable($turno, $bloqueo->motivo));
                } catch (\Exception $e) {
                    Log::error('Error al enviar correo de cancelación: ' . $e->getMessage());
                    // Podrías registrar el error, pero la transacción de DB no debe fallar por esto.
                }
            }

            DB::commit();

            $mensaje = 'Bloqueo de agenda creado exitosamente.';
            if ($turnosAfectadosCount > 0) {
                $mensaje .= " Se han cancelado {$turnosAfectadosCount} turno(s) superpuesto(s).";
            }

            return redirect()->route('admin.bloqueos.index')->with('success', $mensaje);
        } catch (ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->validator->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Ocurrió un error al crear el bloqueo: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Elimina un bloqueo específico de la base de datos.
     */
    public function destroy($id_bloqueo)
    {
        // Iniciar una transacción de base de datos para garantizar la atomicidad
        DB::beginTransaction();
        try {
            $bloqueo = Bloqueo::findOrFail($id_bloqueo);

            // Lógica para verificar si el bloqueo ya ha pasado
            $fechaFin = Carbon::parse($bloqueo->fecha_fin);
            $horaFin = $bloqueo->hora_fin ? Carbon::parse($bloqueo->hora_fin) : Carbon::now()->endOfDay();
            $fechaHoraFin = $fechaFin->setTimeFrom($horaFin);

            if ($fechaHoraFin->isPast()) {
                DB::rollBack(); // Deshacer la transacción si ya ha pasado
                return back()->with('error', 'No se puede cancelar un bloqueo que ya ha finalizado.');
            }

            $bloqueo->delete();
            
            DB::commit(); // Confirmar la transacción
            return back()->with('success', 'El bloqueo ha sido cancelado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack(); // Deshacer la transacción si hay un error
            return back()->with('error', 'Ocurrió un error al cancelar el bloqueo. Por favor, inténtelo de nuevo.');
        }
    }
}