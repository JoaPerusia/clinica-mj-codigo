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
use App\Models\ObraSocial;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $obras_sociales = ObraSocial::where('habilitada', true)->orderBy('nombre')->get();
        return view('auth.register', compact('obras_sociales'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'dni' => ['required', 'string', 'max:20', 'unique:usuarios'], 
            'fecha_nacimiento' => ['required', 'date'],
            'id_obra_social' => ['required', 'exists:obras_sociales,id_obra_social'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:usuarios'], 
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        DB::beginTransaction();

        try {
            $pacienteExistente = Paciente::where('dni', $request->dni)->first();

            $user = User::create([
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'email' => $request->email,
                'dni' => $request->dni,
                'password' => Hash::make($request->password),
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'telefono' => $request->telefono,
            ]);

            $pacienteRol = Rol::where('rol', Rol::PACIENTE)->first();
            if ($pacienteRol) {
                $user->roles()->attach($pacienteRol->id_rol);
            }

            if ($pacienteExistente) {
                $pacienteExistente->id_usuario = $user->id_usuario;
                $pacienteExistente->id_obra_social = $request->id_obra_social;
                $pacienteExistente->save();
            } else {
                Paciente::create([
                    'nombre' => $user->nombre,
                    'apellido' => $user->apellido,
                    'dni' => $user->dni,
                    'fecha_nacimiento' => $user->fecha_nacimiento,
                    'telefono' => $user->telefono,
                    'id_usuario' => $user->id_usuario,
                    'id_obra_social' => $request->id_obra_social, 
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
        if ($user->hasRole(Rol::ADMINISTRADOR)) {
            session(['rol_activo' => Rol::ADMINISTRADOR]);
            return Redirect::route('admin.dashboard');
        } elseif ($user->hasRole(Rol::MEDICO)) {
            session(['rol_activo' => Rol::MEDICO]);
            return Redirect::route('medico.dashboard');
        } elseif ($user->hasRole(Rol::PACIENTE)) {
            session(['rol_activo' => Rol::PACIENTE]);
            return Redirect::route('paciente.dashboard');
        }

        return Redirect::route('dashboard');
    }
}