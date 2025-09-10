<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Paciente;
use App\Models\User; 
use Illuminate\Validation\ValidationException;

class PacienteController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 10;
        $usuario = Auth::user();
        $dni_filtro = $request->input('dni_filtro');

        $query = Paciente::query();

        if ($usuario->hasRole('Administrador')) {
            // El administrador ve todos los pacientes
        } elseif ($usuario->hasRole('Paciente')) {
            // El paciente solo ve sus propios pacientes
            $query->where('id_usuario', $usuario->id_usuario);
        } else {
            // Para otros roles, no se muestran pacientes
            abort(403, 'Acceso no autorizado.');
        }

        // Aplicar filtro por DNI si se ha proporcionado
        if ($dni_filtro) {
            $query->where('dni', 'like', '%' . $dni_filtro . '%');
        }

        $pacientes = $query->paginate($perPage)->withQueryString();

        return view('pacientes.index', compact('pacientes'));
    }

    public function create()
    {
        $usuario = Auth::user();

        if ($usuario->hasRole('Administrador') || $usuario->hasRole('Paciente')) {
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
            'obra_social' => 'nullable|string|max:255', 
        ];

        if ($usuario->hasRole('Administrador')) {
            $rules['id_usuario'] = 'required|exists:usuarios,id_usuario';
        }

        try {
            $validatedData = $request->validate($rules);
            $data = $request->all();

            if ($usuario->hasRole('Paciente')) {
                $data['id_usuario'] = $usuario->id_usuario;
            }

            Paciente::create($data);

            if ($usuario->hasRole('Administrador')) {
                return redirect()->route('admin.pacientes.index')->with('success', 'Paciente creado con éxito por el administrador.');
            } elseif ($usuario->hasRole('Paciente')) {
                return redirect()->route('paciente.pacientes.index')->with('success', 'Familiar/Paciente añadido con éxito.');
            } else {
                return redirect()->route('dashboard')->with('success', 'Paciente creado con éxito.');
            }

        } catch (ValidationException $e) {
            $redirectRoute = '';
            if ($usuario->hasRole('Administrador')) {
                $redirectRoute = 'admin.pacientes.create';
            } elseif ($usuario->hasRole('Paciente')) {
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

        if ($usuario->hasRole('Administrador') || ($usuario->hasRole('Paciente') && $paciente->id_usuario == $usuario->id_usuario)) {
            return view('pacientes.show', compact('paciente'));
        }

        abort(403, 'Acceso no autorizado para ver este paciente.');
    }

    public function edit(string $id)
    {
        $paciente = Paciente::findOrFail($id);
        $usuario = Auth::user();

        // Permitir la edición si el usuario es un administrador o si es el dueño del perfil del paciente.
        if ($usuario->hasRole('Administrador')) {
            // Un administrador puede editar cualquier paciente.
            return view('pacientes.edit', compact('paciente'));
        } elseif ($usuario->hasRole('Paciente') && $paciente->id_usuario == $usuario->id_usuario) {
            // Un paciente puede editarse a sí mismo.
            return view('pacientes.edit', compact('paciente'));
        } else {
            // En cualquier otro caso, denegar el acceso.
            abort(403, 'Acceso no autorizado.');
        }
    }
    
    public function update(Request $request, string $id)
    {
        $paciente = Paciente::findOrFail($id);
        $usuario = Auth::user();

        // Verificación de permisos: el admin puede editar cualquier paciente, el paciente solo el suyo.
        if (!$usuario->hasRole('Administrador') && $paciente->id_usuario != $usuario->id_usuario) {
            return redirect()->route('pacientes.index')->with('warning', 'No tienes permiso para editar este paciente.');
        }

        // Reglas de validación según el rol
        if ($usuario->hasRole('Administrador')) {
            // Reglas para el Administrador (puede modificar todos los campos)
            $rules = [
                'nombre' => 'required|string|max:255',
                'apellido' => 'required|string|max:255',
                'telefono' => 'nullable|string|max:20',
                'obra_social' => 'required|string|max:255',
                'dni' => 'required|string|max:20|unique:pacientes,dni,' . $id . ',id_paciente',
                'fecha_nacimiento' => 'required|date',
                'id_usuario' => 'required|exists:usuarios,id_usuario',
            ];

            // Validar todos los datos
            $validatedData = $request->validate($rules);

            // Actualizar todos los campos
            $paciente->update($validatedData);

            return redirect()->route('admin.pacientes.index')->with('success', 'Paciente actualizado correctamente por el administrador.');
        } else {
            // Reglas para el Paciente (solo puede modificar el teléfono)
            $rules = [
                'telefono' => 'nullable|string|max:20',
            ];

            // Validar solo los campos permitidos
            $validatedData = $request->validate($rules);
            
            // Actualizar solo el campo de teléfono
            $paciente->update([
                'telefono' => $validatedData['telefono'],
            ]);

            return redirect()->route('paciente.pacientes.index')->with('success', 'Paciente actualizado correctamente.');
        }
    }

    
    public function destroy(string $id)
    {
        $paciente = Paciente::findOrFail($id);
        $usuario = Auth::user();

        if (!$usuario->hasRole('Administrador') && $paciente->id_usuario != $usuario->id_usuario) {
            $redirectRoute = '';
            if ($usuario->hasRole('Administrador')) {
                $redirectRoute = 'admin.pacientes.index';
            } elseif ($usuario->hasRole('Paciente')) {
                $redirectRoute = 'paciente.pacientes.index';
            } else {
                $redirectRoute = 'dashboard';
            }
            return redirect()->route($redirectRoute)->with('warning', 'No tenés permiso para eliminar este paciente.');
        }

        $paciente->delete();

        $redirectRoute = '';
        if ($usuario->hasRole('Administrador')) {
            $redirectRoute = 'admin.pacientes.index';
        } elseif ($usuario->hasRole('Paciente')) {
            $redirectRoute = 'paciente.pacientes.index';
        } else {
            $redirectRoute = 'dashboard';
        }

        return redirect()->route($redirectRoute)->with('success', 'Paciente eliminado correctamente.');
    }
}
