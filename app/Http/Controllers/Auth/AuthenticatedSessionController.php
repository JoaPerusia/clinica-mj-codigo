<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        
        $user = $request->user();

        // Obtener los roles del usuario
        $roles = $user->roles;
        $roleCount = $roles->count();
        
        // Si tiene un solo rol, lo establecemos automáticamente en la sesión
        if ($roleCount === 1) {
            $singleRole = $roles->first();
            $request->session()->put('rol_activo', $singleRole->name);
        }

        // Redirigir según el rol activo
        $activeRole = $request->session()->get('rol_activo');

        if ($activeRole === 'Administrador') {
            return redirect()->route('admin.dashboard');
        } elseif ($activeRole === 'Medico') {
            return redirect()->route('medico.dashboard');
        } elseif ($activeRole === 'Paciente') {
            return redirect()->route('paciente.dashboard');
        }
        
        // Si el usuario tiene múltiples roles (y no se ha establecido un rol activo), lo enviamos a la página de selección de roles.
        return redirect()->route('dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $request->session()->flush();
        
        return redirect('/');
    }
}
