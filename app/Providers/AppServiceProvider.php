<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Compartimos $roles y $dashboardRoute en todas las vistas
        View::composer('*', function ($view) {
            $user = Auth::user();
            $roles = $user 
                ? collect($user->active_roles) 
                : collect();
            $dashboardRoute = $user 
                ? $user->getDashboardRoute() 
                : null;

            $view->with('roles', $roles)
                ->with('dashboardRoute', $dashboardRoute);
        });
    }

}
