<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MedicoDashboardController extends Controller
{
    public function index()
    {
        return view('medico.dashboard');
    }
}
