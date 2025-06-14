<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Paciente;

class PacienteController extends Controller
{
    public function index()
    {
        $usuario = Auth::user();

        if ($usuario->id_rol == 1) {
            // Admin: ve todos los pacientes
            $pacientes = Paciente::all();
        } else {
            // Usuario comun: ve solo sus pacientes
            $pacientes = Paciente::where('id_usuario', $usuario->id)->get();
        }

        return view('pacientes.index', compact('pacientes'));
    }

    public function create()
    {
        return view('pacientes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'apellido' => 'required',
            'dni' => 'required|numeric',
            'fecha_nacimiento' => 'required|date',
            'obra_social' => 'required',
        ]);

        $idUsuario = Auth::user();

        $pacienteExistente = Paciente::where('dni', $request->dni)->first();

        if ($pacienteExistente) {
            return redirect()->route('pacientes.index')->with('warning', 'Ese paciente ya está registrado.');
        }

        Paciente::create([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'dni' => $request->dni,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'obra_social' => $request->obra_social,
            'id_usuario' => $idUsuario,
        ]);

        return redirect()->route('pacientes.index')->with('success', 'Paciente agregado correctamente.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        $paciente = Paciente::findOrFail($id);
        $usuario = Auth::user();

    // El admin (rol 1) puede todo
    if ($usuario->id_rol != 1 && $paciente->id_usuario != $usuario->id) {
            return redirect()->route('pacientes.index')->with('warning', 'No tenés permiso para realizar esta acción.');
        }
        
        return view('pacientes.edit', compact('paciente'));
    }

    public function update(Request $request, string $id)
    {
        $paciente = Paciente::findOrFail($id);
        $usuario = Auth::user();

        // Solo el admin o el dueño del paciente pueden actualizarlo
        if ($usuario->id_rol != 1 && $paciente->id_usuario != $usuario->id) {
            return redirect()->route('pacientes.index')->with('warning', 'No tenés permiso para editar este paciente.');
        }

        $paciente->update([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'dni' => $request->dni,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'obra_social' => $request->obra_social,
        ]);

        return redirect()->route('pacientes.index')->with('success', 'Paciente actualizado correctamente.');
    }



    public function destroy(string $id)
    {
        $paciente = Paciente::findOrFail($id);
        $usuario = Auth::user();

        // Solo el admin o el dueño del paciente pueden eliminarlo
        if ($usuario->id_rol != 1 && $paciente->id_usuario != $usuario->id) {
            return redirect()->route('pacientes.index')->with('warning', 'No tenés permiso para eliminar este paciente.');
        }

        $paciente->delete();

        return redirect()->route('pacientes.index')->with('success', 'Paciente eliminado correctamente.');
    }


}
