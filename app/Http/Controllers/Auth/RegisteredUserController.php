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
use Illuminate\Support\Facades\Redirect;

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
            'name' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'dni' => ['required', 'string', 'max:20', 'unique:usuarios,dni'], 
            'fecha_nacimiento' => ['required', 'date'],
            'obra_social' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:20'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:usuarios'], 
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'nombre' => $request->name,
            'apellido' => $request->apellido,
            'dni' => $request->dni,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'obra_social' => $request->obra_social,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'telefono' => $request->telefono,
            'id_rol' => 3, // Siempre asigna el rol 3 (Paciente) para el registro público
        ]);

        event(new Registered($user));

        // Auto-crear el perfil de Paciente para el usuario recién registrado
        Paciente::create([
            'nombre' => $user->nombre,
            'apellido' => $user->apellido,
            'dni' => $user->dni,
            'fecha_nacimiento' => $user->fecha_nacimiento,
            'obra_social' => $user->obra_social,
            'telefono' => $user->telefono,
            'id_usuario' => $user->id_usuario,
        ]);

        Auth::login($user);

        // --- Lógica de redirección por rol directamente aquí ---
        if ($user->id_rol == 1) { // Administrador
            return Redirect::route('admin.dashboard');
        } elseif ($user->id_rol == 2) { // Médico
            return Redirect::route('medico.dashboard');
        } elseif ($user->id_rol == 3) { // Paciente
            return Redirect::route('paciente.dashboard');
        }

        // Redirección por defecto si el rol no coincide con ninguno (debería ser el dashboard genérico)
        return Redirect::route('dashboard');
    }
}
