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
            'name' => ['required', 'string', 'max:255'], // Validamos el campo 'name' del formulario
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'telefono' => ['required', 'string', 'max:20'], // Nueva validación para el campo 'telefono'
        ]);

        $user = User::create([
            'nombre' => $request->name, // Aquí mapeamos el 'name' del formulario a tu columna 'nombre'
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'telefono' => $request->telefono, // Asignamos el valor del campo 'telefono'
            'id_rol' => 3, // Asignamos directamente el rol 3 (Paciente) como predeterminado
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}
