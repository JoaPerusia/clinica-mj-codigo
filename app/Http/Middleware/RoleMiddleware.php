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
     * @param  string[]  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Verificar si el usuario está autenticado
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user(); // Obtiene el usuario autenticado

        // 2. Verificar si el usuario tiene al menos uno de los roles permitidos
        // $roles ahora contiene los NOMBRES de rol que pasamos al middleware (ej: 'Administrador')
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                // Si el usuario tiene el rol, le permite el acceso
                return $next($request);
            }
        }

        // 3. Si el bucle termina y el usuario no tiene ninguno de los roles permitidos
        // Aborta con un error 403 (Acceso no autorizado)
        abort(403, 'Acceso no autorizado. No tienes los permisos necesarios para esta sección.');
    }
}