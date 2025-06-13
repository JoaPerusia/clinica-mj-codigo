<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Paciente;

class PacienteController extends Controller
{
    public function index()
    {
        $pacientes = Paciente::all();
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

        $idUsuario = Auth::id();

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
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
