<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(DashboardService $dashboardService)
    {
        // Delegamos los cálculos al servicio
        $stats = $dashboardService->getAdminStats();
        
        return view('admin.dashboard', compact('stats'));
    }
}