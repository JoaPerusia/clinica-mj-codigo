<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminDashboardController; // Importa tu nuevo controlador
use App\Http\Controllers\MedicoDashboardController; // Importa tu nuevo controlador
use App\Http\Controllers\PacienteDashboardController; // Importa tu nuevo controlador

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// --- NUEVAS RUTAS PARA LOS PANELES POR ROL ---

    // Ruta para el Panel de Administración
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // Ruta para el Panel de Médico
    Route::get('/medico/dashboard', [MedicoDashboardController::class, 'index'])->name('medico.dashboard');

    // Ruta para el Panel de Paciente
    Route::get('/paciente/dashboard', [PacienteDashboardController::class, 'index'])->name('paciente.dashboard');


require __DIR__.'/auth.php';
