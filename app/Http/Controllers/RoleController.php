<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class RoleController extends Controller
{
    public function setRolActivo(Request $request)
    {
        $rol = $request->input('rol');

        // Verifica que el usuario realmente tiene el rol que está seleccionando
        if (Auth::user()->hasRole($rol)) {
            // Guarda el nombre del rol en la sesión
            Session::put('rol_activo', $rol);

            // Redirige al dashboard del rol seleccionado
            if ($rol === 'Administrador') {
                return redirect()->route('admin.dashboard');
            } elseif ($rol === 'Medico') {
                return redirect()->route('medico.dashboard');
            } elseif ($rol === 'Paciente') {
                return redirect()->route('paciente.dashboard');
            }
        }

        // Si el rol no es válido, redirige a la pantalla de selección de rol
        return redirect()->route('rol.cambiar')->with('error', 'Rol inválido.');
    }
}