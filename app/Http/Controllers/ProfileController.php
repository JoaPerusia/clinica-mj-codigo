<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Models\Paciente;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load('roles');

        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        if ($user->hasRole('Administrador')) {
            // Lógica para el Administrador
            $validatedData = $request->validate([
                'nombre' => ['required', 'string', 'max:255'],
                'apellido' => ['required', 'string', 'max:255'],
                'dni' => ['required', 'string', 'max:20', 'unique:usuarios,dni,'.$user->id_usuario.',id_usuario'],
                'fecha_nacimiento' => ['required', 'date'],
                'obra_social' => ['nullable', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:usuarios,email,'.$user->id_usuario.',id_usuario'],
                'telefono' => ['nullable', 'string', 'max:20'],
            ]);
            
            $user->fill($validatedData);

        } else { // Lógica para usuarios regulares
            // Los usuarios regulares solo pueden actualizar su email y teléfono
            $validatedData = $request->validate([
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:usuarios,email,'.$user->id_usuario.',id_usuario'],
                'telefono' => ['nullable', 'string', 'max:20'],
            ]);
            
            $user->fill([
                'email' => $validatedData['email'],
                'telefono' => $validatedData['telefono'],
            ]);
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // LÓGICA AGREGADA PARA SINCRONIZAR LA TABLA DE PACIENTES
        if ($user->hasRole('Paciente')) {
            // CORRECCIÓN CLAVE: Buscar el registro del paciente que tiene este id_usuario
            $paciente = Paciente::where('id_usuario', $user->id_usuario)->first();
            if ($paciente) {
                $paciente->update([
                    'email' => $user->email,
                    'telefono' => $user->telefono,
                ]);
            }
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }
    
    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
