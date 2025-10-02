<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class RoleController extends Controller
{
    /**
     * Acción de setear el rol activo tras la selección.
     */
    public function setRolActivo(Request $request)
    {
        $user = Auth::user();
        $rol  = $request->input('rol');

        // Obtengo solo los roles “vivos” del usuario
        $allowed = $user->active_roles->pluck('rol')->contains($rol);

        if (! $allowed) {
            return redirect()->route('dashboard')
                             ->with('error', 'No podés ingresar con ese rol.');
        }

        // Guardo en sesión el rol activo
        Session::put('rol_activo', $rol);

        // Redirijo al dashboard correspondiente
        return match($rol) {
            'Administrador' => redirect()->route('admin.dashboard'),
            'Medico'        => redirect()->route('medico.dashboard'),
            'Paciente'      => redirect()->route('paciente.dashboard'),
        };
    }
}