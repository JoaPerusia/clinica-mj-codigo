<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Página de bienvenida / inicio del sitio.
     */
    public function welcome()
    {
        return view('home'); // O 'welcome', según vista principal
    }

    /**
     * Página "Sobre Nosotros".
     */
    public function about()
    {
        return view('about');
    }

    /**
     * Dashboard genérico (landing page tras login).
     * Desde aquí el usuario navega a su dashboard específico o ve información general.
     */
    public function dashboard()
    {
        return view('dashboard');
    }
}