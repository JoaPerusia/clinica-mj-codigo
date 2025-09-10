<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use App\Models\Paciente; 
use App\Models\Rol;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'dni' => ['required', 'string', 'max:20', 'unique:usuarios'], 
            'fecha_nacimiento' => ['required', 'date'],
            'obra_social' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:usuarios'], 
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        DB::beginTransaction();

        try {
            // 1. Buscar si ya existe un paciente con ese DNI.
            $pacienteExistente = Paciente::where('dni', $request->dni)->first();

            // 2. Crear el nuevo usuario
            $user = User::create([
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'email' => $request->email,
                'dni' => $request->dni,
                'password' => Hash::make($request->password),
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'obra_social' => $request->obra_social,
                'telefono' => $request->telefono,
            ]);

            // 3. Asignar el rol de Paciente al usuario
            $pacienteRol = Rol::where('rol', 'Paciente')->first();
            if ($pacienteRol) {
                $user->roles()->attach($pacienteRol->id_rol);
            }

            // 4. Lógica de vinculación: Si el paciente ya existe, vincularlo al usuario.
            // Si no, crear un nuevo registro de paciente.
            if ($pacienteExistente) {
                $pacienteExistente->id_usuario = $user->id_usuario;
                $pacienteExistente->save();
            } else {
                Paciente::create([
                    'nombre' => $user->nombre,
                    'apellido' => $user->apellido,
                    'dni' => $user->dni,
                    'fecha_nacimiento' => $user->fecha_nacimiento,
                    'obra_social' => $user->obra_social,
                    'telefono' => $user->telefono,
                    'id_usuario' => $user->id_usuario,
                ]);
            }
            
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Hubo un error en el registro: ' . $e->getMessage());
        }

        event(new Registered($user));

        Auth::login($user);

        // --- Lógica de redirección por rol ---
        if ($user->hasRole('Administrador')) {
            session(['rol_activo' => 'Administrador']);
            return Redirect::route('admin.dashboard');
        } elseif ($user->hasRole('Medico')) {
            session(['rol_activo' => 'Medico']);
            return Redirect::route('medico.dashboard');
        } elseif ($user->hasRole('Paciente')) {
            session(['rol_activo' => 'Paciente']);
            return Redirect::route('paciente.dashboard');
        }

        return Redirect::route('dashboard');
    }
}