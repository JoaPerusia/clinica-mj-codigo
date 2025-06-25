<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Verificar si el usuario está autenticado
        if (!Auth::check()) {
            // Si no está logueado, redirige a la página de login
            return redirect('/login');
        }

        $user = Auth::user(); // Obtiene el usuario autenticado

        // 2. Verificar si el id_rol del usuario está entre los roles permitidos
        // $roles es un array con los IDs de rol que pasamos al middleware (ej: [1])
        if (!in_array($user->id_rol, $roles)) {
            // Si el rol del usuario NO está en la lista de roles permitidos,
            // aborta con un error 403 (Acceso no autorizado) o redirige.
            abort(403, 'Acceso no autorizado. No tienes los permisos necesarios para esta sección.');
            // O podrías redirigir a su dashboard por defecto:
            // return redirect(route('paciente.dashboard')); // Ejemplo
        }

        // Si el usuario está autenticado y tiene el rol correcto, permite el acceso a la ruta
        return $next($request);
    }
}
