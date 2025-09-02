<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        if ($user->hasRole('Administrador')) { // Lógica para el Administrador
            // El administrador puede actualizar todos los campos del usuario
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'apellido' => ['required', 'string', 'max:255'],
                'dni' => ['required', 'string', 'max:20', 'unique:users,dni,'.$user->id],
                'fecha_nacimiento' => ['required', 'date'],
                'obra_social' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$user->id],
                'telefono' => ['nullable', 'string', 'max:20'],
            ]);

            $user->fill($request->validated());

        } else { // Lógica para usuarios regulares
            // Los usuarios regulares solo pueden actualizar su email y teléfono
            $request->validate([
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$user->id],
                'telefono' => ['nullable', 'string', 'max:20'],
            ]);
            
            $user->fill([
                'email' => $request->validated()['email'],
                'telefono' => $request->validated()['telefono'],
            ]);
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

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
