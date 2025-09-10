<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\MedicoDashboardController;
use App\Http\Controllers\PacienteDashboardController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\MedicoController;
use App\Http\Controllers\RoleController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('home');
});

Route::get('/home', function () {
    return view('home');
})->name('home');

Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/cambiar-rol', [RoleController::class, 'setRolActivo'])->name('rol.setActivo');
    Route::get('/api/medicos-by-especialidad', [TurnoController::class, 'getMedicosByEspecialidad'])->name('api.medicos.by-especialidad');
    Route::get('/api/turnos/disponibles', [TurnoController::class, 'getHorariosDisponibles'])->name('api.turnos.disponibles');
});


// --- RUTAS PROTEGIDAS POR ROL ---

// Rutas para Administrador
Route::middleware(['role:Administrador'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('medicos', MedicoController::class);
    Route::resource('pacientes', PacienteController::class);
    Route::resource('especialidades', EspecialidadController::class);
    Route::resource('turnos', TurnoController::class);
    Route::patch('/turnos/{turno}/cambiar-estado', [TurnoController::class, 'cambiarEstado'])->name('turnos.cambiar-estado');
});


// Rutas para Médico
Route::middleware(['role:Medico'])->prefix('medico')->name('medico.')->group(function () {
    Route::get('/dashboard', [MedicoDashboardController::class, 'index'])->name('dashboard');

    // Rutas de paciente accesibles para el médico
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

    Route::patch('/turnos/{turno}/cambiar-estado', [TurnoController::class, 'cambiarEstado'])->name('turnos.cambiar-estado');
});

// Rutas para Paciente
Route::middleware(['role:Paciente'])->prefix('paciente')->name('paciente.')->group(function () {
    Route::get('/dashboard', [PacienteDashboardController::class, 'index'])->name('dashboard');
    // Route::get('/turnos/disponibles', [TurnoController::class, 'getHorariosDisponibles'])->name('turnos.disponibles');

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

    Route::patch('/turnos/{turno}/cambiar-estado', [TurnoController::class, 'cambiarEstado'])->name('turnos.cambiar-estado');
});


require __DIR__.'/auth.php';