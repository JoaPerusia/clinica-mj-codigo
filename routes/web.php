<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminDashboardController; 
use App\Http\Controllers\MedicoDashboardController; 
use App\Http\Controllers\PacienteDashboardController; 
use App\Http\Controllers\EspecialidadController;


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

    // --- RUTAS PROTEGIDAS POR ROL (MODIFICADAS AQUÍ) ---

        // Rutas para Administrador (solo si id_rol es 1)
        Route::middleware(['role:1'])->prefix('admin')->name('admin.')->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
            
            Route::resource('especialidades', EspecialidadController::class)->names([
            'index' => 'especialidades.index',
            'create' => 'especialidades.create',
            'store' => 'especialidades.store',
            'show' => 'especialidades.show',
            'edit' => 'especialidades.edit',
            'update' => 'especialidades.update',
            'destroy' => 'especialidades.destroy',
        ]);
    });

        

    // Rutas para Médico (solo si id_rol es 2)
    Route::middleware(['role:2'])->prefix('medico')->name('medico.')->group(function () {
        Route::get('/dashboard', [MedicoDashboardController::class, 'index'])->name('dashboard');
        // Aquí irán las demás rutas específicas del médico
    });

    // Rutas para Paciente (solo si id_rol es 3)
    Route::middleware(['role:3'])->prefix('paciente')->name('paciente.')->group(function () {
        Route::get('/dashboard', [PacienteDashboardController::class, 'index'])->name('dashboard');
        // Aquí irán las demás rutas específicas del paciente
    });

require __DIR__.'/auth.php';
