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
    public function update(Request $request, Turno $turno)
    {
        $usuario = auth()->user();

        
        if (
            ($usuario->id_rol == 1) || // Administrador
            ($usuario->id_rol == 2 && $turno->id_medico == $usuario->medico->id_medico) || // Médico
            ($usuario->id_rol == 3 && $turno->id_paciente == $usuario->paciente->id_paciente) // Paciente
        ) {
            $rules = [
                'estado' => 'required|in:pendiente,realizado,cancelado,ausente', // Añadido 'ausente'
            ];

            // Reglas adicionales para Administrador y Paciente
            if ($usuario->id_rol == 1 || $usuario->id_rol == 3) {
                $rules['id_paciente'] = 'required|exists:pacientes,id_paciente';
                $rules['id_medico'] = 'required|exists:medicos,id_medico';
                $rules['fecha'] = 'required|date|after_or_equal:today';
                $rules['hora'] = 'required|date_format:H:i';
            }

            $request->validate($rules);

            // Lógica de actualización basada en el rol
            if ($usuario->id_rol == 2) { // Médico: solo puede cambiar el estado
                $turno->update([
                    'estado' => $request->estado,
                ]);
                return redirect()->route('medico.turnos.index')->with('success', 'El estado de tu turno ha sido actualizado con éxito.');
            } else { // Administrador o Paciente: pueden cambiar todos los campos validados

                $fecha = Carbon::parse($request->fecha)->format('Y-m-d');
                $hora = Carbon::parse($request->hora)->format('H:i:s');

                // Verificar disponibilidad del médico para la nueva fecha y hora (solo si se cambian)
                if (
                    $turno->fecha != $fecha ||
                    $turno->hora != $hora ||
                    $turno->id_medico != $request->id_medico
                ) {
                    $medico = Medico::find($request->id_medico);
                    if (!$medico) {
                        return back()->withErrors(['id_medico' => 'Médico no encontrado.'])->withInput();
                    }

                    // Verificar si el médico trabaja en ese horario
                    $diaSemana = Carbon::parse($fecha)->dayOfWeek; // 0 (Domingo) a 6 (Sábado)
                    $horariosTrabajo = $medico->horariosTrabajo->where('dia_semana', $diaSemana);

                    $trabajaEnHorario = false;
                    foreach ($horariosTrabajo as $horario) {
                        if ($hora >= $horario->hora_inicio && $hora < $horario->hora_fin) { // Importante: < hora_fin para que el último turno no se solape
                            $trabajaEnHorario = true;
                            break;
                        }
                    }

                    if (!$trabajaEnHorario) {
                        return back()->withErrors(['hora' => 'El médico no trabaja en el horario seleccionado para esta fecha.'])->withInput();
                    }

                    // Verificar bloqueos del médico para la nueva fecha y hora
                    $bloqueoExistente = Bloqueo::where('id_medico', $request->id_medico)
                        ->where('fecha', $fecha)
                        ->where(function ($query) use ($hora) {
                            $query->where('hora_inicio', '<=', $hora)
                                  ->where('hora_fin', '>', $hora); // Si el bloqueo es hasta las 10:00, un turno a las 10:00 es válido
                        })
                        ->first();

                    if ($bloqueoExistente) {
                        return back()->withErrors(['hora' => 'El médico tiene un bloqueo en el horario seleccionado.'])->withInput();
                    }

                    // Verificar si ya existe un turno ocupado en la nueva fecha y hora para el médico (excluyendo el turno actual que se está editando)
                    $turnoOcupado = Turno::where('id_medico', $request->id_medico)
                        ->where('fecha', $fecha)
                        ->where('hora', $hora)
                        ->where('id_turno', '!=', $turno->id_turno) // Excluir el turno actual
                        ->whereIn('estado', ['pendiente', 'realizado']) // Considerar solo turnos activos
                        ->first();

                    if ($turnoOcupado) {
                        return back()->withErrors(['hora' => 'El horario seleccionado ya está ocupado para este médico.'])->withInput();
                    }
                }


                // Actualizar todos los campos para Admin y Paciente
                $turno->update([
                    'fecha' => $fecha,
                    'hora' => $hora,
                    'estado' => $request->estado,
                    'id_paciente' => $request->id_paciente,
                    'id_medico' => $request->id_medico,
                ]);

                // Redireccionamiento dinámico basado en el rol del usuario
                if ($usuario->id_rol == 1) { // Administrador
                    return redirect()->route('admin.turnos.index')->with('success', 'Turno actualizado con éxito por el administrador.');
                } else { // Paciente (id_rol == 3)
                    return redirect()->route('paciente.turnos.index')->with('success', 'Turno actualizado con éxito.');
                }
            }
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
            'fecha' => 'required|date_format:Y-m-d',
            'except_turno_id' => 'nullable|exists:turnos,id_turno', // Para excluir el turno actual en edición
        ]);

        $idMedico = $request->input('id_medico');
        $fecha = Carbon::parse($request->input('fecha'));
        $exceptTurnoId = $request->input('except_turno_id');

        $medico = Medico::find($idMedico);
        if (!$medico) {
            return response()->json(['message' => 'Médico no encontrado.'], 404);
        }

        $diaSemana = $fecha->dayOfWeek; // 0 (Domingo) a 6 (Sábado)
        $horariosTrabajo = $medico->horariosTrabajo->where('dia_semana', $diaSemana);

        if ($horariosTrabajo->isEmpty()) {
            return response()->json([]); // El médico no trabaja ese día
        }

        $horariosGenerados = [];
        $intervalo_minutos = 30; // Define el intervalo de los turnos, ej. 30 minutos

        foreach ($horariosTrabajo as $horario) {
            $inicio = Carbon::parse($horario->hora_inicio);
            $fin = Carbon::parse($horario->hora_fin);

            while ($inicio->lt($fin)) {
                $horariosGenerados[] = $inicio->format('H:i');
                $inicio->addMinutes($intervalo_minutos);
            }
        }

        // Obtener turnos ocupados para este médico y fecha (excluyendo el turno actual si se está editando)
        $turnosOcupados = Turno::where('id_medico', $idMedico)
            ->where('fecha', $fecha->format('Y-m-d'))
            ->whereIn('estado', ['pendiente', 'realizado'])
            ->when($exceptTurnoId, function ($query) use ($exceptTurnoId) {
                return $query->where('id_turno', '!=', $exceptTurnoId);
            })
            ->pluck('hora')
            ->toArray();

        // Obtener bloqueos del médico para este día
        $bloqueos = Bloqueo::where('id_medico', $idMedico)
            ->where('fecha', $fecha->format('Y-m-d'))
            ->get();

        // Filtrar horarios
        $horarios_filtrados = array_filter($horariosGenerados, function ($hora) use ($turnosOcupados, $bloqueos, $fecha, $intervalo_minutos) {
            // 1. Filtrar turnos ocupados
            if (in_array($hora, $turnosOcupados)) {
                return false;
            }

            // 2. Filtrar bloqueos
            $hora_carbon = Carbon::parse($hora);
            foreach ($bloqueos as $bloqueo) {
                // Parsear las horas de inicio y fin del bloqueo
                $bloqueo_hora_inicio_carbon = $bloqueo->hora_inicio ? Carbon::parse($bloqueo->hora_inicio) : null;
                $bloqueo_hora_fin_carbon = $bloqueo->hora_fin ? Carbon::parse($bloqueo->hora_fin) : null;

                // Si el bloqueo es de día completo (no tiene horas específicas) o el turno cae dentro del rango de horas del bloqueo
                if (!$bloqueo_hora_inicio_carbon || !$bloqueo_hora_fin_carbon) { // Bloqueo de día completo
                    return false;
                }
                // Bloqueo por horas
                $turno_fin_carbon = clone $hora_carbon;
                $turno_fin_carbon->addMinutes($intervalo_minutos);

                // Comprobar si el inicio del turno está dentro del bloqueo O el fin del turno está dentro del bloqueo O el bloqueo está completamente dentro del turno
                if (
                    ($hora_carbon->gte($bloqueo_hora_inicio_carbon) && $hora_carbon->lt($bloqueo_hora_fin_carbon)) || // Inicio del turno dentro del bloqueo
                    ($turno_fin_carbon->gt($bloqueo_hora_inicio_carbon) && $turno_fin_carbon->lte($bloqueo_hora_fin_carbon)) || // Fin del turno dentro del bloqueo
                    ($bloqueo_hora_inicio_carbon->gte($hora_carbon) && $bloqueo_hora_fin_carbon->lte($turno_fin_carbon)) // Bloqueo completamente dentro del turno
                ) {
                    return false; // Hay superposición con un bloqueo
                }
            }

            // 3. Asegurarse de que el horario no sea anterior a la hora actual para turnos de hoy
            if ($fecha->isToday()) {
                // Un pequeño buffer de tiempo, digamos 15 minutos para evitar turnos casi inmediatos
                if ($hora_carbon->lt(Carbon::now()->addMinutes(15))) {
                    return false;
                }
            }

            return true; // Si no está ocupado ni bloqueado y es a futuro
        });

        // Devolver los horarios disponibles
        return response()->json(array_values($horarios_filtrados));
    }
}