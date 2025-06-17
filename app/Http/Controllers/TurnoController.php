<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Turno;
use App\Models\Paciente;
use App\Models\Medico;
use App\Models\Bloqueo;
use Illuminate\Support\Facades\Auth;

class TurnoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $usuario = Auth::user();

        // Admin ve todos
        if ($usuario->id_rol == 1) {
            $turnos = Turno::with('paciente', 'medico')->get();
        }
        // Médico ve solo sus turnos
        elseif ($usuario->id_rol == 2) {
            $turnos = Turno::where('id_medico', $usuario->id)
                        ->with('paciente', 'medico')
                        ->get();
        }
        // Paciente ve solo sus turnos
        else {
            $pacientes = $usuario->pacientes->pluck('id');
            $turnos = Turno::whereIn('id_paciente', $pacientes)
                        ->with('paciente', 'medico')
                        ->get();
        }

        return view('turnos.index', compact('turnos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $usuario = auth()->user();

        // Si es admin, ve todos los pacientes
        if ($usuario->id_rol == 1) {
            $pacientes = Paciente::all();
        } 
        // Si es paciente, solo ve los que registró él mismo
        elseif ($usuario->id_rol == 3) {
            $pacientes = $usuario->pacientes;
        } 
        // Los médicos no pueden reservar turnos
        else {
            return redirect()->route('turnos.index')->with('warning', 'Los médicos no pueden crear turnos.');
        }

        $medicos = Medico::all();

        return view('turnos.create', compact('pacientes', 'medicos'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_paciente' => 'required|exists:Pacientes,id',
            'id_medico' => 'required|exists:Medicos,id',
            'fecha' => 'required|date',
            'hora' => 'required',
        ]);

        // Verificar que el paciente pertenece al usuario actual
        $paciente = \App\Models\Paciente::find($request->id_paciente);
        if (!$paciente || $paciente->id_usuario !== auth()->user()->id) {
            return redirect()->back()->with('warning', 'No podés sacar un turno para este paciente.');
        }

        // Verificar si el médico está bloqueado ese día
        $bloqueado = Bloqueo::where('id_medico', $request->id_medico)
                            ->where('fecha', $request->fecha)
                            ->exists();
        if ($bloqueado) {
            return redirect()->back()->with('warning', 'El médico no atiende ese día.');
        }

        // Verificar si ya hay un turno para ese médico en ese horario
        $ocupado = Turno::where('id_medico', $request->id_medico)
                        ->where('fecha', $request->fecha)
                        ->where('hora', $request->hora)
                        ->exists();
        if ($ocupado) {
            return redirect()->back()->with('warning', 'El horario ya está ocupado.');
        }

        Turno::create([
            'fecha' => $request->fecha,
            'hora' => $request->hora,
            'estado' => 'pendiente',
            'id_paciente' => $request->id_paciente,
            'id_medico' => $request->id_medico,
        ]);

        return redirect()->route('turnos.index')->with('success', 'Turno reservado con éxito.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $turno = Turno::findOrFail($id);

        if (auth()->user()->id_rol != 1) {
            return redirect()->route('turnos.index')->with('warning', 'Solo los administradores pueden editar turnos.');
        }

        $pacientes = Paciente::all();
        $medicos = Medico::all();

        return view('turnos.edit', compact('turno', 'pacientes', 'medicos'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (auth()->user()->id_rol != 1) {
            return redirect()->route('turnos.index')->with('warning', 'Solo los administradores pueden actualizar turnos.');
        }

        $request->validate([
            'id_paciente' => 'required|exists:Pacientes,id',
            'id_medico' => 'required|exists:Medicos,id',
            'fecha' => 'required|date',
            'hora' => 'required',
            'estado' => 'required|in:pendiente,realizado,cancelado',
        ]);

        $turno = Turno::findOrFail($id);

        $turno->update([
            'fecha' => $request->fecha,
            'hora' => $request->hora,
            'estado' => $request->estado,
            'id_paciente' => $request->id_paciente,
            'id_medico' => $request->id_medico,
        ]);

        return redirect()->route('turnos.index')->with('success', 'Turno actualizado con éxito.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $turno = Turno::findOrFail($id);

        $user = auth()->user();

        if ($user->id_rol == 1) {
            // Admin puede cancelar cualquier turno
            $turno->delete();
            return redirect()->route('turnos.index')->with('success', 'Turno cancelado con éxito.');
        }

        if ($user->id_rol == 3 && $turno->paciente->id_usuario == $user->id) {
            // Paciente solo puede cancelar sus propios turnos
            $turno->delete();
            return redirect()->route('turnos.index')->with('success', 'Turno cancelado con éxito.');
        }

        return redirect()->route('turnos.index')->with('warning', 'No tienes permisos para cancelar este turno.');
    }

}
