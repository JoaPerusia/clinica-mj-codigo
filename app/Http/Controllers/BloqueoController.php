<?php

namespace App\Http\Controllers;

use App\Models\Bloqueo;
use App\Models\Medico;
use App\Models\Turno;
use App\Models\Rol;
use App\Mail\TurnoCanceladoMailable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests\StoreBloqueoRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Notifications\TurnoCancelado;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use App\Services\NotificacionService;

class BloqueoController extends Controller
{
    /**
     * Muestra una lista de todos los bloqueos de agenda.
     * Permite filtrar por DNI del médico.
     */
    public function index(Request $request)
    {
        $query = Bloqueo::with('medico.usuario')
            ->orderBy('fecha_inicio', 'desc');

        // Filtro por médico: DNI, nombre o apellido (en usuarios)
        if ($request->filled('dni_filtro')) {
            $filtro = $request->input('dni_filtro');

            $query->whereHas('medico.usuario', function ($q) use ($filtro) {
                $q->where(function ($w) use ($filtro) {
                    $w->where('usuarios.dni', 'like', "%{$filtro}%")
                    ->orWhere('usuarios.nombre', 'like', "%{$filtro}%")
                    ->orWhere('usuarios.apellido', 'like', "%{$filtro}%");
                });
            });
        }

        $bloqueos = $query->paginate(10)->withQueryString();

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
    public function store(StoreBloqueoRequest $request, NotificacionService $notificador)
    {
        DB::beginTransaction();
        try {
            // Crear el nuevo bloqueo
            $bloqueo = Bloqueo::create([
                'id_medico'    => $request->id_medico,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin'    => $request->fecha_fin,
                'hora_inicio'  => $request->hora_inicio,
                'hora_fin'     => $request->hora_fin,
                'motivo'       => $request->motivo,
            ]);

            // --- Lógica de cancelación automática (se mantiene igual) ---
            $turnosSuperpuestos = Turno::where('id_medico', $bloqueo->id_medico)
                ->where('estado', Turno::PENDIENTE)
                ->where(function ($query) use ($bloqueo) {
                    $query->whereBetween('fecha', [$bloqueo->fecha_inicio, $bloqueo->fecha_fin]);
                });

            if ($bloqueo->hora_inicio && $bloqueo->hora_fin) {
                $turnosSuperpuestos->where(function ($query) use ($bloqueo) {
                    $query->whereTime('hora', '>=', $bloqueo->hora_inicio)
                        ->whereTime('hora', '<=', $bloqueo->hora_fin);
                });
            }

            $turnosACancelar = $turnosSuperpuestos->get();
            $turnosAfectadosCount = $turnosACancelar->count();

            foreach ($turnosACancelar as $turno) {
                $turno->estado = Turno::CANCELADO;
                $turno->save();
                
                // Enviar notificación (email)
                $notificador->notificarCancelacion($turno, $bloqueo->motivo);
            }

            DB::commit();

            $mensaje = 'Bloqueo de agenda creado exitosamente.';
            if ($turnosAfectadosCount > 0) {
                $mensaje .= " Se han cancelado {$turnosAfectadosCount} turno(s) superpuesto(s).";
            }

            return redirect()->route('admin.bloqueos.index')->with('success', $mensaje);

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