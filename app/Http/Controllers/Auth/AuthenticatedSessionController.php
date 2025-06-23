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
    public function store(LoginRequest $request): RedirectResponse // ¡Cambia el tipo de Request aquí!
    {
        // El método authenticate() ahora está disponible a través de LoginRequest
        $request->authenticate();

        $request->session()->regenerate();

        // Obtén el usuario autenticado
        $user = Auth::user();

        // Redirige según el id_rol del usuario
        switch ($user->id_rol) {
            case 1: // Si 1 es el ID para 'Administrador'
                return redirect()->intended(route('admin.dashboard'));
            case 2: // Si 2 es el ID para 'Médico'
                return redirect()->intended(route('medico.dashboard'));
            case 3: // Si 3 es el ID para 'Paciente' (y predeterminado si el rol no coincide)
            default:
                return redirect()->intended(route('paciente.dashboard'));
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
