<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Http\Controllers\PacienteController;

Route::resource('pacientes', PacienteController::class);
