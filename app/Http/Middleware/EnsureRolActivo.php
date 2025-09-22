<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class EnsureRolActivo
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Solo ejecutamos esto si el usuario está autenticado y no hay un rol activo en la sesión.
        if (Auth::check() && !Session::has('rol_activo')) {
            $user = Auth::user();

            // Si el usuario tiene solo un rol, lo establecemos automáticamente.
            if ($user->roles->count() === 1) {
                $singleRole = $user->roles->first();
                Session::put('rol_activo', $singleRole->name);
            }
        }

        return $next($request);
    }
}