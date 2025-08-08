<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Paciente;
use App\Models\User; // Asegúrate de que este import esté presente
use Illuminate\Validation\ValidationException;

class PacienteController extends Controller
{
    public function index()
    {
        $perPage = 10;
        $usuario = Auth::user();
        $pacientes = collect();

        if ($usuario->id_rol == 1) {
            $pacientes = Paciente::paginate($perPage);
        } elseif ($usuario->id_rol == 3) {
            $pacientes = Paciente::where('id_usuario', $usuario->id_usuario)->paginate($perPage);
        } else {
            abort(403, 'Acceso no autorizado.');
        }

        /*  if ($usuario->id_rol == 1) {
            $pacientes = Paciente::all();
        } elseif ($usuario->id_rol == 3) {
            $pacientes = Paciente::where('id_usuario', $usuario->id_usuario)->get();
        } else {
            abort(403, 'Acceso no autorizado.');
        } */

        return view('pacientes.index', compact('pacientes'));
    }

    public function create()
    {
        $usuario = Auth::user();

        if ($usuario->id_rol == 1 || $usuario->id_rol == 3) {
            return view('pacientes.create');
        }

        abort(403, 'Acceso no autorizado para crear pacientes.');
    }

    public function store(Request $request)
    {
        $usuario = Auth::user();

        $rules = [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'dni' => 'required|string|max:20|unique:pacientes,dni',
            'fecha_nacimiento' => 'required|date',
            'telefono' => 'nullable|string|max:20',
            'obra_social' => 'required|string|max:255', // *** AÑADIDO: Regla para obra_social ***
        ];

        if ($usuario->id_rol == 1) {
            $rules['id_usuario'] = 'required|exists:usuarios,id_usuario';
        }

        try {
            $validatedData = $request->validate($rules);
            $data = $request->all();

            if ($usuario->id_rol == 3) {
                $data['id_usuario'] = $usuario->id_usuario;
            }

            Paciente::create($data);

            if ($usuario->id_rol == 1) {
                return redirect()->route('admin.pacientes.index')->with('success', 'Paciente creado con éxito por el administrador.');
            } elseif ($usuario->id_rol == 3) {
                return redirect()->route('paciente.pacientes.index')->with('success', 'Familiar/Paciente añadido con éxito.');
            } else {
                return redirect()->route('dashboard')->with('success', 'Paciente creado con éxito.');
            }

        } catch (ValidationException $e) {
            $redirectRoute = '';
            if ($usuario->id_rol == 1) {
                $redirectRoute = 'admin.pacientes.create';
            } elseif ($usuario->id_rol == 3) {
                $redirectRoute = 'paciente.pacientes.create';
            } else {
                $redirectRoute = 'dashboard';
            }

            return redirect()->route($redirectRoute)
                             ->withErrors($e->errors())
                             ->withInput();
        }
    }

    public function show(string $id)
    {
        $paciente = Paciente::findOrFail($id);
        $usuario = Auth::user();

        if ($usuario->id_rol == 1 || ($usuario->id_rol == 3 && $paciente->id_usuario == $usuario->id_usuario)) {
            return view('pacientes.show', compact('paciente'));
        }

        abort(403, 'Acceso no autorizado para ver este paciente.');
    }

    public function edit(string $id)
    {
        $paciente = Paciente::findOrFail($id);
        $usuario = Auth::user();

        if ($usuario->id_rol != 1 && $paciente->id_usuario != $usuario->id_usuario) {
            return redirect()->route('pacientes.index')->with('warning', 'No tenés permiso para realizar esta acción.');
        }

        return view('pacientes.edit', compact('paciente'));
    }

    public function update(Request $request, string $id)
    {
        $paciente = Paciente::findOrFail($id);
        $usuario = Auth::user();

        if ($usuario->id_rol != 1 && $paciente->id_usuario != $usuario->id_usuario) {
            return redirect()->route('pacientes.index')->with('warning', 'No tenés permiso para editar este paciente.');
        }

        $rules = [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'dni' => 'required|string|max:20|unique:pacientes,dni,' . $id . ',id_paciente',
            'fecha_nacimiento' => 'required|date',
            'telefono' => 'nullable|string|max:20',
        ];

        if ($usuario->id_rol == 1) {
            // *** CAMBIO CRUCIAL AQUÍ: Usar 'usuarios,id_usuario' si tu tabla de usuarios es 'usuarios' y su PK es 'id_usuario' ***
            $rules['id_usuario'] = 'required|exists:usuarios,id_usuario';
        }

        $validatedData = $request->validate($rules);

        $data = $request->only(['nombre', 'apellido', 'dni', 'fecha_nacimiento', 'telefono']);

        if ($usuario->id_rol == 1 && $request->has('id_usuario')) {
            $data['id_usuario'] = $request->id_usuario;
        }

        $paciente->update($data);

        if ($usuario->id_rol == 1) {
            return redirect()->route('admin.pacientes.index')->with('success', 'Paciente actualizado correctamente por el administrador.');
        } elseif ($usuario->id_rol == 3) {
            return redirect()->route('paciente.pacientes.index')->with('success', 'Paciente actualizado correctamente.');
        } else {
            return redirect()->route('dashboard')->with('success', 'Paciente actualizado correctamente.');
        }
    }

    public function destroy(string $id)
    {
        $paciente = Paciente::findOrFail($id);
        $usuario = Auth::user();

        if ($usuario->id_rol != 1 && $paciente->id_usuario != $usuario->id_usuario) {
            $redirectRoute = '';
            if ($usuario->id_rol == 1) {
                $redirectRoute = 'admin.pacientes.index';
            } elseif ($usuario->id_rol == 3) {
                $redirectRoute = 'paciente.pacientes.index';
            } else {
                $redirectRoute = 'dashboard';
            }
            return redirect()->route($redirectRoute)->with('warning', 'No tenés permiso para eliminar este paciente.');
        }

        $paciente->delete();

        $redirectRoute = '';
        if ($usuario->id_rol == 1) {
            $redirectRoute = 'admin.pacientes.index';
        } elseif ($usuario->id_rol == 3) {
            $redirectRoute = 'paciente.pacientes.index';
        } else {
            $redirectRoute = 'dashboard';
        }

        return redirect()->route($redirectRoute)->with('success', 'Paciente eliminado correctamente.');
    }
}
