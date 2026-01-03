<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PageController; 
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\MedicoDashboardController;
use App\Http\Controllers\PacienteDashboardController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\MedicoController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\BloqueoController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- PÁGINAS PÚBLICAS Y GENERALES ---
Route::controller(PageController::class)->group(function () {
    Route::get('/', 'welcome')->name('home'); // Unificamos '/' y '/home'
    Route::get('/home', 'welcome');           // Redirección o alias
    Route::get('/about', 'about')->name('about');
    Route::get('/dashboard', 'dashboard')
        ->middleware(['auth', 'verified'])
        ->name('dashboard');
});

// --- RUTAS AUTENTICADAS COMUNES ---
Route::middleware('auth')->group(function () {
    // Perfil de Usuario
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Cambio de Rol
    Route::post('/cambiar-rol', [RoleController::class, 'setRolActivo'])->name('rol.setActivo');
    
    // APIs internas para selectores dinámicos
    Route::get('/api/medicos-by-especialidad', [TurnoController::class, 'getMedicosByEspecialidad'])->name('api.medicos.by-especialidad');
    Route::get('/api/turnos/disponibles', [TurnoController::class, 'getHorariosDisponibles'])->name('api.turnos.disponibles');
    Route::get('/api/agenda/mes', [TurnoController::class, 'obtenerAgendaMes'])->name('api.agenda.mes');
});


// --- RUTAS POR ROL (MIDDLEWARES ESPECÍFICOS) ---

// 🛡️ Administrador
Route::middleware(['auth', 'role:Administrador'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // Resources completos
    Route::resource('medicos', MedicoController::class);
    Route::resource('pacientes', PacienteController::class);
    Route::resource('especialidades', EspecialidadController::class);
    Route::resource('turnos', TurnoController::class);
    Route::resource('bloqueos', BloqueoController::class); // Gestión general de bloqueos

    // Acciones extra
    Route::patch('/turnos/{turno}/cambiar-estado', [TurnoController::class, 'cambiarEstado'])->name('turnos.cambiar-estado');
    
    // Bloqueos específicos de un médico
    Route::get('/medicos/{medico}/bloqueos', [BloqueoController::class, 'index'])->name('medicos.bloqueos.index');
    Route::post('/medicos/{medico}/bloqueos', [BloqueoController::class, 'store'])->name('medicos.bloqueos.store');
    Route::delete('/medicos/{medico}/bloqueos/{bloqueo}', [BloqueoController::class, 'destroy'])->name('medicos.bloqueos.destroy');
});


// 🩺 Médico
Route::middleware(['auth', 'role:Medico'])->prefix('medico')->name('medico.')->group(function () {
    Route::get('/dashboard', [MedicoDashboardController::class, 'index'])->name('dashboard');

    // Gestión de Turnos (Vista limitada por Policies/Controller)
    Route::resource('turnos', TurnoController::class);
    Route::get('/mis-turnos', [TurnoController::class, 'misTurnosMedico'])->name('mis_turnos');
    Route::patch('/turnos/{turno}/cambiar-estado', [TurnoController::class, 'cambiarEstado'])->name('turnos.cambiar-estado');
});


// 🤒 Paciente
Route::middleware(['auth', 'role:Paciente'])->prefix('paciente')->name('paciente.')->group(function () {
    Route::get('/dashboard', [PacienteDashboardController::class, 'index'])->name('dashboard');

    // Gestión de Turnos
    Route::resource('turnos', TurnoController::class);
    Route::get('/mis-turnos', [TurnoController::class, 'misTurnos'])->name('mis_turnos');
    Route::patch('/turnos/{turno}/cambiar-estado', [TurnoController::class, 'cambiarEstado'])->name('turnos.cambiar-estado');

    // Gestión de Familiares (Pacientes asociados)
    // Usamos 'only' para restringir lo que el paciente puede hacer con otros pacientes (familiares)
    Route::resource('pacientes', PacienteController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
});

require __DIR__.'/auth.php';