<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePacienteRequest;
use App\Http\Requests\UpdatePacienteRequest;
use App\Models\Paciente;
use App\Models\User; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PacienteController extends Controller
{
    public function index(Request $request)
    {
        $usuario = Auth::user();
        $filtro  = $request->input('dni_filtro'); 

        $query = Paciente::query();

        if ($usuario->hasRole('Paciente')) {
            $query->where('id_usuario', $usuario->id_usuario);
        } elseif (!$usuario->hasRole('Administrador')) {
            abort(403, 'Acceso no autorizado.');
        }

        if (!empty($filtro)) {
            $query->where(function ($q) use ($filtro) {
                $q->where('dni', 'like', "%{$filtro}%")
                  ->orWhere('nombre', 'like', "%{$filtro}%")
                  ->orWhere('apellido', 'like', "%{$filtro}%");
            });
        }

        $pacientes = $query->paginate(10)->withQueryString();

        return view('pacientes.index', compact('pacientes'));
    }

    public function create()
    {
        $usuario = Auth::user();

        if ($usuario->hasRole('Administrador') || $usuario->hasRole('Paciente')) {
            $usuarios = $usuario->hasRole('Administrador') ? User::orderBy('nombre')->get() : null;
            return view('pacientes.create', compact('usuarios'));
        }

        abort(403, 'Acceso no autorizado.');
    }
    
    public function store(StorePacienteRequest $request)
    {
        $usuario = Auth::user();
        $data = $request->validated();

        // Si es Paciente, forzamos su ID
        if ($usuario->hasRole('Paciente')) {
            $data['id_usuario'] = $usuario->id_usuario;
        }

        Paciente::create($data);

        // Redirección inteligente
        $route = $usuario->hasRole('Administrador') ? 'admin.pacientes.index' : 'paciente.pacientes.index';
        return redirect()->route($route)->with('success', 'Paciente creado con éxito.');
    }

    public function edit(string $id)
    {
        $paciente = Paciente::findOrFail($id);
        $usuario = Auth::user();

        if ($usuario->hasRole('Administrador') || ($usuario->hasRole('Paciente') && $paciente->id_usuario == $usuario->id_usuario)) {
            return view('pacientes.edit', compact('paciente'));
        }

        abort(403, 'Acceso no autorizado.');
    }
    
    public function update(UpdatePacienteRequest $request, string $id)
    {
        $paciente = Paciente::findOrFail($id);
        $usuario = Auth::user();

        // Autorización de propiedad
        if (!$usuario->hasRole('Administrador') && $paciente->id_usuario != $usuario->id_usuario) {
            abort(403, 'No tienes permiso para editar este paciente.');
        }

        // Actualización (Las reglas ya se validaron en el Request según el rol)
        $paciente->update($request->validated());

        $route = $usuario->hasRole('Administrador') ? 'admin.pacientes.index' : 'paciente.pacientes.index';
        return redirect()->route($route)->with('success', 'Paciente actualizado correctamente.');
    }

    public function destroy(string $id)
    {
        $paciente = Paciente::findOrFail($id);
        $usuario = Auth::user();

        if (!$usuario->hasRole('Administrador') && $paciente->id_usuario != $usuario->id_usuario) {
             abort(403, 'No tienes permiso para eliminar este paciente.');
        }

        // Cancelar turnos futuros
        $paciente->turnos()
            ->where('estado', 'pendiente')
            ->where('fecha', '>=', now()->toDateString())
            ->update(['estado' => 'cancelado']);

        $paciente->delete();

        $route = $usuario->hasRole('Administrador') ? 'admin.pacientes.index' : 'paciente.pacientes.index';
        return redirect()->route($route)->with('success', 'Paciente eliminado y turnos cancelados.');
    }
    
    // show no parece usarse mucho en tu código actual o es igual a edit, lo simplifico o lo quito si no lo usas
    public function show(string $id) {
         $paciente = Paciente::findOrFail($id);
         return view('pacientes.show', compact('paciente'));
    }
}