<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PacienteDashboardController extends Controller
{
    public function index()
    {
        return view('paciente.dashboard');
    }
}