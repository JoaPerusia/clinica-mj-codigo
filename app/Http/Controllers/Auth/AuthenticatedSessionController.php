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
       $user = $request->user();

        if ($user->roles->count() > 1) {
            return redirect()->route('dashboard');
        } elseif ($user->hasRole('Administrador')) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('Medico')) {
            return redirect()->route('medico.dashboard');
        } elseif ($user->hasRole('Paciente')) {
            return redirect()->route('paciente.dashboard');
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
