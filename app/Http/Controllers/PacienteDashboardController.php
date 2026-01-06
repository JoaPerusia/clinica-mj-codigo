<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PacienteDashboardController extends Controller
{
    public function index(DashboardService $dashboardService)
    {
        $usuario = Auth::user();
        $stats = $dashboardService->getPacienteStats($usuario);

        return view('paciente.dashboard', compact('stats'));
    }
}