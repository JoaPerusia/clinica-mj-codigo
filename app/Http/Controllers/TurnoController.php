<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Turno;
use App\Models\Paciente;
use App\Models\Medico;
use App\Models\Bloqueo;
use App\Models\Especialidad; 
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon; // Para trabajar con fechas y horas

class TurnoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $usuario = Auth::user();

        // Admin ve todos
        if ($usuario->id_rol == 1) {
            $turnos = Turno::with('paciente', 'medico')->get();
        }
        // Médico ve solo sus turnos
        elseif ($usuario->id_rol == 2) {
            $medico = $usuario->medico; // Relación: $user->medico (asegúrate de que exista en User.php)

            if (!$medico) {
                // Si el usuario no tiene un perfil de médico, redirige o maneja el error
                return redirect()->route('medico.dashboard')->with('error', 'Tu perfil de médico no está configurado.');
            }

            $turnos = Turno::where('id_medico', $medico->id_medico) // Usa id_medico del objeto Medico
                        ->with('paciente', 'medico')
                        ->get();
        }
        // Paciente ve solo sus turnos
        elseif ($usuario->id_rol == 3) {
            $pacientes_ids = $usuario->pacientes->pluck('id_paciente');
            $turnos = Turno::whereIn('id_paciente', $pacientes_ids)
                        ->with('paciente', 'medico')
                        ->get();
        } else {
            // Si el rol no está definido o es inesperado
            $turnos = collect(); // Colección vacía
        }


        return view('turnos.index', compact('turnos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $usuario = auth()->user();
        $pacientes = collect(); // Inicializa como colección vacía
        $medicos = Medico::with('especialidades')->get(); // Carga los médicos con sus especialidades
        $especialidades = Especialidad::all(); // Carga todas las especialidades

        // Si es admin, ve todos los pacientes
        if ($usuario->id_rol == 1) {
            $pacientes = Paciente::all();
        }
        // Si es paciente, solo ve los que registró él mismo
        elseif ($usuario->id_rol == 3) {
            $pacientes = $usuario->pacientes;
        }
        // Los médicos no pueden crear turnos a través de este flujo (a menos que la lógica lo permita)
        else {
            return redirect()->route('turnos.index')->with('warning', 'Solo administradores y pacientes pueden crear turnos.');
        }

        return view('turnos.create', compact('pacientes', 'medicos', 'especialidades'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $usuario = Auth::user();

        $request->validate([
            'id_paciente' => ['required', 'exists:pacientes,id_paciente', function ($attribute, $value, $fail) use ($usuario) {
                // Si el usuario es un paciente, verificar que el id_paciente seleccionado le pertenezca
                if ($usuario->id_rol == 3) {
                    if (!$usuario->pacientes->contains('id_paciente', $value)) {
                        $fail('El paciente seleccionado no te pertenece.');
                    }
                }
            }],
            'id_medico' => 'required|exists:medicos,id_medico',
            'fecha' => 'required|date|after_or_equal:today', // La fecha no puede ser en el pasado
            'hora' => 'required|date_format:H:i', // Formato de hora HH:MM
            'estado' => 'nullable|in:pendiente,realizado,cancelado', // Por defecto será 'pendiente'
        ]);

        $fecha = Carbon::parse($request->fecha)->toDateString();
        $hora = $request->hora;
        $id_medico = $request->id_medico;

        // 1. Verificar si la hora está dentro del horario disponible del médico (horario_disponible)
        // Esto asume que 'horario_disponible' es un campo de texto simple como '09:00-17:00'
        $medico = Medico::find($id_medico);
        if ($medico && $medico->horario_disponible) {
            list($hora_inicio_medico, $hora_fin_medico) = explode('-', $medico->horario_disponible);
            $turno_hora_carbon = Carbon::parse($hora);

            if ($turno_hora_carbon->lt(Carbon::parse($hora_inicio_medico)) || $turno_hora_carbon->gt(Carbon::parse($hora_fin_medico))) {
                return back()->withInput()->withErrors(['hora' => 'La hora seleccionada está fuera del horario de disponibilidad del médico.']);
            }
        } else {
             // Considerar si es un error o si el médico no tiene horario_disponible definido
                return back()->withInput()->withErrors(['medico' => 'El médico seleccionado no tiene un horario de disponibilidad definido.']);
        }


        // 2. Verificar Bloqueos del Médico
        $bloqueoExistente = Bloqueo::where('id_medico', $id_medico)
                                   ->where('fecha_inicio', '<=', $fecha)
                                   ->where('fecha_fin', '>=', $fecha)
                                   ->first();

        if ($bloqueoExistente) {
            return back()->withInput()->withErrors(['fecha' => 'El médico no está disponible en la fecha seleccionada debido a un bloqueo.']);
        }

        // 3. Verificar si ya existe un turno a esa hora para ese médico
        $turnoExistente = Turno::where('id_medico', $id_medico)
                               ->where('fecha', $fecha)
                               ->where('hora', $hora)
                               ->whereIn('estado', ['pendiente', 'realizado']) // Considerar también turnos ya realizados si no queremos superposiciones
                               ->first();

        if ($turnoExistente) {
            return back()->withInput()->withErrors(['hora' => 'Ya existe un turno reservado para este médico en la fecha y hora seleccionadas.']);
        }

        // Si todas las verificaciones pasan, crea el turno
        $turno = Turno::create([
            'id_paciente' => $request->id_paciente,
            'id_medico' => $request->id_medico,
            'fecha' => $fecha,
            'hora' => $hora,
            'estado' => $request->estado ?? 'pendiente', // Por defecto 'pendiente'
        ]);

        return redirect()->route('paciente.turnos.index')->with('success', 'Turno agendado con éxito.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $turno = Turno::with('paciente', 'medico')->findOrFail($id);

        // Asegurar que solo el admin o el paciente/médico asociado puedan ver el turno
        $usuario = Auth::user();
        if ($usuario->id_rol == 1 ||
            ($usuario->id_rol == 3 && $turno->paciente && $turno->paciente->id_usuario == $usuario->id_usuario) ||
            ($usuario->id_rol == 2 && $turno->medico && $turno->medico->id_usuario == $usuario->id_usuario)) {
            return view('turnos.show', compact('turno'));
        }

        abort(403, 'Acceso no autorizado.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $turno = Turno::with('paciente', 'medico')->findOrFail($id);
        $usuario = Auth::user();

        // Solo admin, o el paciente dueño, o el médico del turno pueden editar
        if ($usuario->id_rol == 1 ||
            ($usuario->id_rol == 3 && $turno->paciente && $turno->paciente->id_usuario == $usuario->id_usuario) ||
            ($usuario->id_rol == 2 && $turno->medico && $turno->medico->id_usuario == $usuario->id_usuario)) {

            $pacientes = ($usuario->id_rol == 1) ? Paciente::all() : $usuario->pacientes;
            $medicos = Medico::all(); // Puedes cargar solo los médicos relevantes si quieres

            return view('turnos.edit', compact('turno', 'pacientes', 'medicos'));
        }

        abort(403, 'Acceso no autorizado para editar este turno.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $usuario = Auth::user();

        $request->validate([
            'id_paciente' => 'required|exists:pacientes,id_paciente',
            'id_medico' => 'required|exists:medicos,id_medico',
            'fecha' => 'required|date|after_or_equal:today',
            'hora' => 'required|date_format:H:i',
            'estado' => 'required|in:pendiente,realizado,cancelado',
        ]);

        $turno = Turno::findOrFail($id);

        // Control de acceso para actualizar
        if ($usuario->id_rol == 1 ||
            ($usuario->id_rol == 3 && $turno->paciente && $turno->paciente->id_usuario == $usuario->id_usuario) ||
            ($usuario->id_rol == 2 && $turno->medico && $turno->medico->id_usuario == $usuario->id_usuario)) {

            // Aquí podrías añadir verificaciones de disponibilidad similares a 'store' si se cambia la fecha/hora/médico
            // Si el estado es 'cancelado', no se necesitan más validaciones de disponibilidad.

            $turno->update([
                'fecha' => $request->fecha,
                'hora' => $request->hora,
                'estado' => $request->estado,
                'id_paciente' => $request->id_paciente,
                'id_medico' => $request->id_medico,
            ]);

            return redirect()->route('paciente.turnos.index')->with('success', 'Turno actualizado con éxito.');
        }

        abort(403, 'Acceso no autorizado para actualizar este turno.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $turno = Turno::findOrFail($id);
        $user = auth()->user();

        if ($user->id_rol == 1) {
            // Admin puede cancelar cualquier turno
            $turno->update(['estado' => 'cancelado']); // Lo marcamos como cancelado en lugar de borrarlo
            return redirect()->route('admin.turnos.index')->with('success', 'Turno cancelado con éxito por el administrador.');
        }

        // CAMBIO: Aseguramos que se use id_usuario
        if ($user->id_rol == 3 && $turno->paciente && $turno->paciente->id_usuario == $user->id_usuario) {
            // Paciente solo puede cancelar sus propios turnos
            $turno->update(['estado' => 'cancelado']); // Lo marcamos como cancelado
            return redirect()->route('paciente.turnos.index')->with('success', 'Turno cancelado con éxito.');
        }

        // CAMBIO: Aseguramos que se use id_usuario
        if ($user->id_rol == 2 && $turno->medico && $turno->medico->id_usuario == $user->id_usuario) {
            // Médico puede cancelar sus propios turnos
            $turno->update(['estado' => 'cancelado']); // Lo marcamos como cancelado
            return redirect()->route('medico.turnos.index')->with('success', 'Tu turno ha sido cancelado con éxito.');
        }

        return redirect()->route('dashboard')->with('error', 'No tienes permiso para cancelar este turno.');
    }


    /**
     * Muestra los turnos de un paciente autenticado.
     */
    public function misTurnos()
    {
        $usuario = Auth::user();

        if ($usuario->id_rol != 3) {
            abort(403, 'Acceso no autorizado. Esta sección es solo para pacientes.');
        }

        $pacientes_ids = $usuario->pacientes->pluck('id_paciente');
        $turnos = Turno::whereIn('id_paciente', $pacientes_ids)
                    ->with('paciente', 'medico')
                    ->orderBy('fecha', 'desc')
                    ->orderBy('hora', 'desc')
                    ->get();

        return view('turnos.mis-turnos', compact('turnos'));
    }

    /**
     * Muestra los turnos de un médico autenticado.
     */
    public function misTurnosMedico()
    {
        $usuario = Auth::user();

        if ($usuario->id_rol != 2) {
            abort(403, 'Acceso no autorizado. Esta sección es solo para médicos.');
        }

        $medico = $usuario->medico; // Relación: $user->medico

        if (!$medico) {
            return redirect()->route('medico.dashboard')->with('error', 'Tu perfil de médico no está configurado.');
        }

        $turnos = Turno::where('id_medico', $medico->id_medico)
                    ->with('paciente', 'medico')
                    ->orderBy('fecha', 'desc')
                    ->orderBy('hora', 'desc')
                    ->get();

        return view('turnos.mis-turnos-medico', compact('turnos'));
    }

    public function getHorariosDisponibles(Request $request)
    {
        $request->validate([
            'id_medico' => 'required|exists:medicos,id_medico',
            'fecha' => 'required|date_format:Y-m-d|after_or_equal:today',
            'except_turno_id' => 'nullable|exists:turnos,id', // ¡NUEVO: Para excluir el turno que se está editando!
        ]);

        $id_medico = $request->id_medico;
        $fecha = Carbon::parse($request->fecha);
        $except_turno_id = $request->except_turno_id; // Obtener el ID del turno a excluir

        $medico = Medico::find($id_medico);

        if (!$medico || !$medico->horario_disponible) {
            return response()->json(['message' => 'Horario de médico no definido o médico no encontrado.'], 404);
        }

        list($hora_inicio_str, $hora_fin_str) = explode('-', $medico->horario_disponible);

        $intervalo_minutos = 30; // Intervalo de turnos, puedes ajustarlo
        $horarios_disponibles = [];

        $current_time = Carbon::parse($hora_inicio_str);
        $end_time = Carbon::parse($hora_fin_str);

        // Generar todos los posibles horarios en el rango del médico
        while ($current_time->lt($end_time)) { // Reajustado para que el último turno quepa completamente
            $horarios_disponibles[] = $current_time->format('H:i');
            $current_time->addMinutes($intervalo_minutos);
        }

        // Obtener turnos ya agendados para ese médico en esa fecha (pendientes y realizados)
        $turnosOcupadosQuery = Turno::where('id_medico', $id_medico)
            ->where('fecha', $fecha->toDateString())
            ->whereIn('estado', ['pendiente', 'realizado']);

        // Si se está editando un turno, excluirlo de la comprobación de ocupados
        if ($except_turno_id) {
            $turnosOcupadosQuery->where('id', '!=', $except_turno_id);
        }

        $turnosOcupados = $turnosOcupadosQuery->pluck('hora')->toArray();


        // Obtener bloqueos para ese médico en esa fecha
        $bloqueos = Bloqueo::where('id_medico', $id_medico)
            ->where('fecha_inicio', '<=', $fecha->toDateString())
            ->where('fecha_fin', '>=', $fecha->toDateString())
            ->get();

        // Filtrar horarios: Quitar los ocupados por turnos o por bloqueos
        $horarios_filtrados = array_filter($horarios_disponibles, function ($hora) use ($turnosOcupados, $bloqueos, $fecha, $intervalo_minutos) {
            // Si el horario ya está ocupado por un turno existente
            if (in_array($hora, $turnosOcupados)) {
                return false;
            }

            // Verificar si el horario cae dentro de un bloqueo
            $hora_carbon = Carbon::parse($hora);
            foreach ($bloqueos as $bloqueo) {
                $bloqueo_fecha_inicio_carbon = Carbon::parse($bloqueo->fecha_inicio);
                $bloqueo_fecha_fin_carbon = Carbon::parse($bloqueo->fecha_fin);
                $bloqueo_hora_inicio_carbon = $bloqueo->hora_inicio ? Carbon::parse($bloqueo->hora_inicio) : null;
                $bloqueo_hora_fin_carbon = $bloqueo->hora_fin ? Carbon::parse($bloqueo->hora_fin) : null;

                // Si el bloqueo cubre todo el día o el turno cae dentro del rango de horas del bloqueo
                if ($fecha->between($bloqueo_fecha_inicio_carbon, $bloqueo_fecha_fin_carbon)) {
                    if (!$bloqueo_hora_inicio_carbon || !$bloqueo_hora_fin_carbon) { // Bloqueo de día completo
                        return false;
                    }
                    // Bloqueo por horas
                    $turno_fin_carbon = clone $hora_carbon;
                    $turno_fin_carbon->addMinutes($intervalo_minutos);

                    if ($hora_carbon->lt($bloqueo_hora_fin_carbon) && $turno_fin_carbon->gt($bloqueo_hora_inicio_carbon)) {
                         return false; // Hay superposición con un bloqueo
                    }
                }
            }

            return true; // Si no está ocupado ni bloqueado
        });

        // Asegurarse de que el horario no sea anterior a la hora actual para turnos de hoy
        if ($fecha->isToday()) {
            $horarios_filtrados = array_filter($horarios_filtrados, function ($hora) {
                return Carbon::parse($hora)->gt(Carbon::now()->addMinutes(15)); // Un pequeño buffer de tiempo
            });
        }

        return response()->json(array_values($horarios_filtrados)); // Devolver los horarios disponibles
    }
}