<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\MedicoDashboardController;
use App\Http\Controllers\PacienteDashboardController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\TurnoController; 


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

        Route::resource('especialidades', EspecialidadController::class)->names([
            'index' => 'admin.especialidades.index',
            'create' => 'admin.especialidades.create',
            'store' => 'admin.especialidades.store',
            'show' => 'admin.especialidades.show',
            'edit' => 'admin.especialidades.edit',
            'update' => 'admin.especialidades.update',
            'destroy' => 'admin.especialidades.destroy',
        ]);
    });


    // Rutas para Médico (solo si id_rol es 2)
    Route::middleware(['role:2'])->prefix('medico')->name('medico.')->group(function () {
        Route::get('/dashboard', [MedicoDashboardController::class, 'index'])->name('dashboard');
        
        Route::resource('turnos', TurnoController::class)->names([
            'index' => 'medico.turnos.index',
            'create' => 'medico.turnos.create', // Un médico podría crear un turno directamente para un paciente
            'store' => 'medico.turnos.store',
            'show' => 'medico.turnos.show',
            'edit' => 'medico.turnos.edit',
            'update' => 'medico.turnos.update',
            'destroy' => 'medico.turnos.destroy',
        ]);

        Route::get('/mis-turnos', [TurnoController::class, 'misTurnosMedico'])->name('mis_turnos');
    });

    // Rutas para Paciente (solo si id_rol es 3)
    Route::middleware(['role:3'])->prefix('paciente')->name('paciente.')->group(function () {
        Route::get('/dashboard', [PacienteDashboardController::class, 'index'])->name('dashboard');

        // ¡NUEVAS RUTAS PARA LA GESTIÓN DE TURNOS DEL PACIENTE!
        Route::resource('turnos', TurnoController::class)->names([
            'index' => 'paciente.turnos.index',
            'create' => 'paciente.turnos.create',
            'store' => 'paciente.turnos.store',
            'show' => 'paciente.turnos.show',
            'edit' => 'paciente.turnos.edit', 
            'update' => 'paciente.turnos.update',
            'destroy' => 'paciente.turnos.destroy', 
        ]);

        Route::get('/mis-turnos', [TurnoController::class, 'misTurnos'])->name('mis_turnos');

        // ruta para obtener horarios disponibles!
        Route::get('/turnos/disponibles', [TurnoController::class, 'getHorariosDisponibles'])->name('turnos.disponibles');
    });


require __DIR__.'/auth.php';