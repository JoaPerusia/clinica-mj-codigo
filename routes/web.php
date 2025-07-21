<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\MedicoDashboardController;
use App\Http\Controllers\PacienteDashboardController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\TurnoController; 
use App\Http\Controllers\PacienteController;


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

// --- RUTAS PROTEGIDAS POR ROL ---

    // Rutas para Administrador (solo si id_rol es 1)
    Route::middleware(['role:1'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/turnos/disponibles', [TurnoController::class, 'getHorariosDisponibles'])->name('turnos.disponibles');

        Route::resource('turnos', TurnoController::class)->names([
            'index' => 'turnos.index',
            'create' => 'turnos.create',
            'store' => 'turnos.store',
            'show' => 'turnos.show',
            'edit' => 'turnos.edit',
            'update' => 'turnos.update',
            'destroy' => 'turnos.destroy',
        ]);
        

        Route::resource('especialidades', EspecialidadController::class)->names([
            'index' => 'especialidades.index',
            'create' => 'especialidades.create',
            'store' => 'especialidades.store',
            'show' => 'especialidades.show',
            'edit' => 'especialidades.edit',
            'update' => 'especialidades.update',
            'destroy' => 'especialidades.destroy',
        ]);

       
        Route::resource('pacientes', PacienteController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->names([
            'index' => 'pacientes.index',
            'create' => 'pacientes.create',
            'store' => 'pacientes.store', 
            'show' => 'pacientes.show',
            'edit' => 'pacientes.edit',
            'update' => 'pacientes.update',
            'destroy' => 'pacientes.destroy',
        ]); 
    });


    // Rutas para Médico (solo si id_rol es 2)
    Route::middleware(['role:2'])->prefix('medico')->name('medico.')->group(function () {
        Route::get('/dashboard', [MedicoDashboardController::class, 'index'])->name('dashboard');
        Route::resource('turnos', TurnoController::class)->names([
            'index' => 'turnos.index',
            'create' => 'turnos.create',
            'store' => 'turnos.store',
            'show' => 'turnos.show',
            'edit' => 'turnos.edit',
            'update' => 'turnos.update',
            'destroy' => 'turnos.destroy',
        ]);
        Route::get('/mis-turnos', [TurnoController::class, 'misTurnosMedico'])->name('mis_turnos');
    });

    // Rutas para Paciente (solo si id_rol es 3)
    Route::middleware(['role:3'])->prefix('paciente')->name('paciente.')->group(function () {
        Route::get('/dashboard', [PacienteDashboardController::class, 'index'])->name('dashboard');
        Route::get('/turnos/disponibles', [TurnoController::class, 'getHorariosDisponibles'])->name('turnos.disponibles');

        Route::resource('turnos', TurnoController::class)->names([
            'index' => 'turnos.index',
            'create' => 'turnos.create',
            'store' => 'turnos.store',
            'show' => 'turnos.show',
            'edit' => 'turnos.edit',
            'update' => 'turnos.update',
            'destroy' => 'turnos.destroy',
        ]);

        Route::get('/mis-turnos', [TurnoController::class, 'misTurnos'])->name('mis_turnos');

        
        Route::resource('pacientes', PacienteController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->names([
            'index' => 'pacientes.index',
            'create' => 'pacientes.create',
            'store' => 'pacientes.store', 
            'show' => 'pacientes.show',
            'edit' => 'pacientes.edit',
            'update' => 'pacientes.update',
            'destroy' => 'pacientes.destroy',
        ]);
    });


require __DIR__.'/auth.php';