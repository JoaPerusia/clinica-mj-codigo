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
        // Cargar explícitamente la relación 'rol' para el usuario autenticado
        $user = $request->user()->load('rol'); // <<-- MODIFICACIÓN AQUÍ

        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request, string $id)
    {
        $paciente = Paciente::findOrFail($id);
        $usuario = Auth::user();

        // Verificación de permisos: solo el admin o el dueño del perfil puede editar.
        if ($usuario->id_rol != 1 && $paciente->id_usuario != $usuario->id_usuario) {
            return redirect()->route('pacientes.index')->with('warning', 'No tienes permiso para editar este paciente.');
        }

        // Definir las reglas de validación de forma condicional
        $rules = [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
        ];

        if ($usuario->id_rol == 1) {
            // Para el Administrador, se añaden las reglas para los campos restringidos
            $rules['dni'] = 'required|string|max:20|unique:pacientes,dni,' . $id . ',id_paciente';
            $rules['fecha_nacimiento'] = 'required|date';
            $rules['obra_social'] = 'required|string|max:255';
            $rules['id_usuario'] = 'required|exists:usuarios,id_usuario';
        }

        // Validar los datos de la petición con las reglas específicas para cada rol
        $validatedData = $request->validate($rules);

        // Actualizar los campos
        $paciente->update($validatedData);

        // Redireccionamiento dinámico
        if ($usuario->id_rol == 1) {
            return redirect()->route('admin.pacientes.index')->with('success', 'Paciente actualizado correctamente por el administrador.');
        } else { // Rol 3
            return redirect()->route('paciente.pacientes.index')->with('success', 'Paciente actualizado correctamente.');
        }
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
