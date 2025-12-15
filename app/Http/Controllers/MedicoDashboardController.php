<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MedicoDashboardController extends Controller
{
    public function index(DashboardService $dashboardService)
    {
        $usuario = Auth::user();
        $stats = $dashboardService->getMedicoStats($usuario);

        return view('medico.dashboard', compact('stats'));
    }
}