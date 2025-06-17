<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\TurnoController;


Route::get('/', [TurnoController::class, 'index']);

// Proteger rutas con middleware de autenticación
Route::middleware(['auth'])->group(function () {
    Route::resource('pacientes', PacienteController::class);
});

//ruta protegida Turno
Route::middleware(['auth'])->group(function () {
    Route::resource('turnos', TurnoController::class);
});