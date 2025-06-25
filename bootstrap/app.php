<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Registra el middleware 'role' con su clase correspondiente
        // Esto lo hace disponible para usarlo en tus rutas como 'role:1'
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // Aquí también podrías añadir middlewares globales o por grupo si fuera necesario
        // Por ejemplo, si quisieras que 'role' se aplicara a todo un grupo de rutas siempre:
        // $middleware->web(append: [
        //     \App\Http\Middleware\EncryptCookies::class,
        //     \App\Http\Middleware\VerifyCsrfToken::class,
        //     \Illuminate\Routing\Middleware\SubstituteBindings::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();