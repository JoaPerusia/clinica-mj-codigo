<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Turno;
use App\Models\Paciente;
use App\Models\Medico;
use App\Models\Bloqueo;
use App\Models\Especialidad;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Para depuración si es necesario
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

        // Validar las reglas comunes a todos los roles que pueden crear turnos
        $rules = [
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
            'estado' => 'nullable|in:pendiente,realizado,cancelado,ausente', // Añadido 'ausente' aquí también
        ];

        $request->validate($rules);

        $fecha = Carbon::parse($request->fecha); // Ahora es un objeto Carbon
        $hora = $request->hora; // String HH:MM
        $id_medico = $request->id_medico;

        // Cargar el médico con sus horarios de trabajo para la validación
        $medico = Medico::with('horariosTrabajo', 'bloqueos')->find($id_medico);
        if (!$medico) {
            return back()->withInput()->withErrors(['id_medico' => 'Médico no encontrado.']);
        }

        // 1. Verificar si el médico trabaja en el día y horario seleccionados
        $diaSemanaNumero = $fecha->dayOfWeek; // 0 (Domingo) a 6 (Sábado)
        $horariosTrabajoDelDia = $medico->horariosTrabajo->where('dia_semana', $diaSemanaNumero);

        if ($horariosTrabajoDelDia->isEmpty()) {
            return back()->withInput()->withErrors(['fecha' => 'El médico no trabaja en la fecha seleccionada.']);
        }

        $turno_hora_carbon = Carbon::parse($hora);
        $trabajaEnHorario = false;
        foreach ($horariosTrabajoDelDia as $horario) {
            $horario_inicio_carbon = Carbon::parse($horario->hora_inicio);
            $horario_fin_carbon = Carbon::parse($horario->hora_fin);

            // Verificar si la hora del turno está dentro de algún bloque de trabajo del médico
            // El turno tiene una duración de 30 minutos (asumiendo)
            $turno_fin_carbon = (clone $turno_hora_carbon)->addMinutes(30);

            if ($turno_hora_carbon->gte($horario_inicio_carbon) && $turno_fin_carbon->lte($horario_fin_carbon)) {
                $trabajaEnHorario = true;
                break;
            }
        }

        if (!$trabajaEnHorario) {
            return back()->withInput()->withErrors(['hora' => 'La hora seleccionada está fuera del horario de disponibilidad del médico para este día.']);
        }

        // 2. Verificar Bloqueos del Médico
        $bloqueosDelDia = $medico->bloqueos
            ->where('fecha_inicio', '<=', $fecha->format('Y-m-d'))
            ->where('fecha_fin', '>=', $fecha->format('Y-m-d'));

        foreach ($bloqueosDelDia as $bloqueo) {
            // Si el bloqueo no tiene hora de inicio/fin, es un bloqueo de día completo
            if (empty($bloqueo->hora_inicio) || empty($bloqueo->hora_fin)) {
                return back()->withInput()->withErrors(['fecha' => 'El médico tiene un bloqueo de día completo en la fecha seleccionada.']);
            }

            // Bloqueo por horas: Verificar si el turno se solapa con el bloqueo
            $bloqueo_hora_inicio_carbon = Carbon::parse($bloqueo->hora_inicio);
            $bloqueo_hora_fin_carbon = Carbon::parse($bloqueo->hora_fin);

            $turno_fin_carbon = (clone $turno_hora_carbon)->addMinutes(30); // Asumiendo 30 minutos de duración del turno

            if (
                ($turno_hora_carbon->gte($bloqueo_hora_inicio_carbon) && $turno_hora_carbon->lt($bloqueo_hora_fin_carbon)) ||
                ($turno_fin_carbon->gt($bloqueo_hora_inicio_carbon) && $turno_fin_carbon->lte($bloqueo_hora_fin_carbon)) ||
                ($bloqueo_hora_inicio_carbon->gte($turno_hora_carbon) && $bloqueo_hora_fin_carbon->lte($turno_fin_carbon))
            ) {
                return back()->withInput()->withErrors(['hora' => 'El médico tiene un bloqueo en el horario seleccionado.']);
            }
        }

        // 3. Asegurarse de que el horario no sea anterior a la hora actual para turnos de hoy
        if ($fecha->isToday()) {
            if ($turno_hora_carbon->lt(Carbon::now()->addMinutes(15))) {
                return back()->withInput()->withErrors(['hora' => 'La hora seleccionada debe ser al menos 15 minutos en el futuro.']);
            }
        }

        // 4. Verificar si ya existe un turno a esa hora para ese médico
        $turnoExistente = Turno::where('id_medico', $id_medico)
                               ->where('fecha', $fecha->toDateString()) // Usar toDateString() para la comparación
                               ->where('hora', $hora)
                               ->whereIn('estado', ['pendiente', 'realizado'])
                               ->first();

        if ($turnoExistente) {
            return back()->withInput()->withErrors(['hora' => 'Ya existe un turno reservado para este médico en la fecha y hora seleccionadas.']);
        }

        // Si todas las verificaciones pasan, crea el turno
        $turno = Turno::create([
            'id_paciente' => $request->id_paciente,
            'id_medico' => $request->id_medico,
            'fecha' => $fecha->toDateString(), // Guardar como string YYYY-MM-DD
            'hora' => $hora,
            'estado' => $request->estado ?? 'pendiente', // Por defecto 'pendiente'
        ]);

        // Redireccionamiento dinámico basado en el rol del usuario
        if ($usuario->id_rol == 1) { // Administrador
            return redirect()->route('admin.turnos.index')->with('success', 'Turno agendado con éxito por el administrador.');
        } elseif ($usuario->id_rol == 3) { // Paciente
            return redirect()->route('paciente.turnos.index')->with('success', 'Turno agendado con éxito.');
        } else { // Otros roles (aunque ya filtramos en create(), por seguridad)
            return redirect()->route('dashboard')->with('success', 'Turno agendado con éxito.');
        }
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

        // Validar que el usuario tiene permiso para editar este turno
        // Administrador puede editar cualquier turno
        // Médico solo puede editar sus propios turnos
        // Paciente solo puede editar sus propios turnos
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

                $fecha = Carbon::parse($request->fecha); // Ahora es un objeto Carbon
                $hora = $request->hora; // String HH:MM
                $id_medico = $request->id_medico;

                // Verificar disponibilidad del médico para la nueva fecha y hora (solo si se cambian)
                if (
                    $turno->fecha != $fecha->toDateString() || // Comparar strings de fecha
                    $turno->hora != $hora ||
                    $turno->id_medico != $id_medico
                ) {
                    $medico = Medico::with('horariosTrabajo', 'bloqueos')->find($id_medico); // Cargar relaciones aquí
                    if (!$medico) {
                        return back()->withErrors(['id_medico' => 'Médico no encontrado.'])->withInput();
                    }

                    // Verificar si el médico trabaja en ese horario
                    $diaSemanaNumero = $fecha->dayOfWeek; // 0 (Domingo) a 6 (Sábado)
                    $horariosTrabajoDelDia = $medico->horariosTrabajo->where('dia_semana', $diaSemanaNumero);

                    if ($horariosTrabajoDelDia->isEmpty()) {
                        return back()->withInput()->withErrors(['fecha' => 'El médico no trabaja en la fecha seleccionada.'])->withInput();
                    }

                    $turno_hora_carbon = Carbon::parse($hora);
                    $trabajaEnHorario = false;
                    foreach ($horariosTrabajoDelDia as $horario) {
                        $horario_inicio_carbon = Carbon::parse($horario->hora_inicio);
                        $horario_fin_carbon = Carbon::parse($horario->hora_fin);
                        $turno_fin_carbon = (clone $turno_hora_carbon)->addMinutes(30); // Asumiendo 30 minutos de duración del turno

                        if ($turno_hora_carbon->gte($horario_inicio_carbon) && $turno_fin_carbon->lte($horario_fin_carbon)) {
                            $trabajaEnHorario = true;
                            break;
                        }
                    }

                    if (!$trabajaEnHorario) {
                        return back()->withErrors(['hora' => 'La hora seleccionada está fuera del horario de disponibilidad del médico para este día.'])->withInput();
                    }

                    // Verificar bloqueos del médico para la nueva fecha y hora
                    $bloqueosDelDia = $medico->bloqueos
                        ->where('fecha_inicio', '<=', $fecha->format('Y-m-d'))
                        ->where('fecha_fin', '>=', $fecha->format('Y-m-d'));

                    foreach ($bloqueosDelDia as $bloqueo) {
                        // Si el bloqueo no tiene hora de inicio/fin, es un bloqueo de día completo
                        if (empty($bloqueo->hora_inicio) || empty($bloqueo->hora_fin)) {
                            return back()->withInput()->withErrors(['fecha' => 'El médico tiene un bloqueo de día completo en la fecha seleccionada.'])->withInput();
                        }

                        // Bloqueo por horas: Verificar si el turno se solapa con el bloqueo
                        $bloqueo_hora_inicio_carbon = Carbon::parse($bloqueo->hora_inicio);
                        $bloqueo_hora_fin_carbon = Carbon::parse($bloqueo->hora_fin);

                        $turno_fin_carbon = (clone $turno_hora_carbon)->addMinutes(30);

                        if (
                            ($turno_hora_carbon->gte($bloqueo_hora_inicio_carbon) && $turno_hora_carbon->lt($bloqueo_hora_fin_carbon)) ||
                            ($turno_fin_carbon->gt($bloqueo_hora_inicio_carbon) && $turno_fin_carbon->lte($bloqueo_hora_fin_carbon)) ||
                            ($bloqueo_hora_inicio_carbon->gte($turno_hora_carbon) && $bloqueo_hora_fin_carbon->lte($turno_fin_carbon))
                        ) {
                            return back()->withErrors(['hora' => 'El médico tiene un bloqueo en el horario seleccionado.'])->withInput();
                        }
                    }

                    // Verificar si ya existe un turno ocupado en la nueva fecha y hora para el médico (excluyendo el turno actual que se está editando)
                    $turnoOcupado = Turno::where('id_medico', $id_medico)
                        ->where('fecha', $fecha->toDateString())
                        ->where('hora', $hora)
                        ->where('id_turno', '!=', $turno->id_turno) // Excluir el turno actual
                        ->whereIn('estado', ['pendiente', 'realizado'])
                        ->first();

                    if ($turnoOcupado) {
                        return back()->withErrors(['hora' => 'El horario seleccionado ya está ocupado para este médico.'])->withInput();
                    }
                }

                // Actualizar todos los campos para Admin y Paciente
                $turno->update([
                    'fecha' => $fecha->toDateString(),
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

        // Admin puede cancelar cualquier turno
        if ($user->id_rol == 1) {
            $turno->update(['estado' => 'cancelado']); // Lo marcamos como cancelado en lugar de borrarlo
            return redirect()->route('admin.turnos.index')->with('success', 'Turno cancelado con éxito por el administrador.');
        }

        // Paciente solo puede cancelar sus propios turnos
        if ($user->id_rol == 3 && $turno->paciente && $turno->paciente->id_usuario == $user->id_usuario) {
            $turno->update(['estado' => 'cancelado']); // Lo marcamos como cancelado
            return redirect()->route('paciente.turnos.index')->with('success', 'Turno cancelado con éxito.');
        }

        // Médico puede cancelar sus propios turnos (si se decide permitir)
        // Según nuestra discusión, el médico NO debería cancelar directamente, solo editar estado.
        // Si quieres que el médico pueda cancelar, descomenta y ajusta esta lógica.
        /*
        if ($user->id_rol == 2 && $turno->medico && $turno->medico->id_usuario == $user->id_usuario) {
            $turno->update(['estado' => 'cancelado']);
            return redirect()->route('medico.turnos.index')->with('success', 'Tu turno ha sido cancelado con éxito.');
        }
        */

        // Si el usuario es médico y no tiene permiso para cancelar, o si es un rol no manejado
        if ($user->id_rol == 2) {
             return redirect()->route('medico.turnos.index')->with('error', 'No tienes permiso para cancelar este turno. Solo puedes cambiar su estado.');
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

    /**
     * Obtiene los horarios disponibles para un médico en una fecha específica.
     * Excluye turnos ya reservados y bloqueos del médico.
     */
    public function getHorariosDisponibles(Request $request)
    {
        $request->validate([
            'id_medico' => 'required|exists:medicos,id_medico',
            'fecha' => 'required|date_format:Y-m-d',
            'except_turno_id' => 'nullable|exists:turnos,id_turno',
        ]);

        $idMedico = $request->input('id_medico');
        $fecha = Carbon::parse($request->input('fecha'));
        $exceptTurnoId = $request->input('except_turno_id');

        $medico = Medico::with('horariosTrabajo')->find($idMedico); // No necesitas cargar 'bloqueos' aquí si los vas a buscar por fecha
        if (!$medico) {
            return response()->json(['message' => 'Médico no encontrado.'], 404);
        }

        $diaSemanaNumero = $fecha->dayOfWeek; // 0 (Domingo) a 6 (Sábado)

        $horariosTrabajoDelDia = $medico->horariosTrabajo->where('dia_semana', $diaSemanaNumero);

        if ($horariosTrabajoDelDia->isEmpty()) {
            return response()->json([]);
        }

        $horariosGenerados = [];
        $intervalo_minutos = 30;

        foreach ($horariosTrabajoDelDia as $horario) {
            $inicio = Carbon::parse($horario->hora_inicio);
            $fin = Carbon::parse($horario->hora_fin);

            while ($inicio->lt($fin)) {
                $horariosGenerados[] = $inicio->format('H:i');
                $inicio->addMinutes($intervalo_minutos);
            }
        }

        $turnosOcupados = Turno::where('id_medico', $idMedico)
            ->where('fecha', $fecha->format('Y-m-d'))
            ->whereIn('estado', ['pendiente', 'realizado'])
            ->when($exceptTurnoId, function ($query) use ($exceptTurnoId) {
                return $query->where('id_turno', '!=', $exceptTurnoId);
            })
            ->pluck('hora')
            ->toArray();

        // Obtener bloqueos para este médico que se superpongan con la fecha seleccionada
        $bloqueosDelDia = Bloqueo::where('id_medico', $idMedico)
            ->where(function($query) use ($fecha) {
                $query->where('fecha_inicio', '<=', $fecha->format('Y-m-d'))
                      ->where('fecha_fin', '>=', $fecha->format('Y-m-d'));
            })
            ->get(); // Obtener los bloqueos que cubren esta fecha

        // Filtrar horarios generados
        $horarios_filtrados = array_filter($horariosGenerados, function ($hora) use ($turnosOcupados, $bloqueosDelDia, $fecha, $intervalo_minutos) {
            $hora_carbon = Carbon::parse($hora);

            // 1. Filtrar turnos ocupados
            if (in_array($hora, $turnosOcupados)) {
                return false;
            }

            // 2. Filtrar bloqueos
            foreach ($bloqueosDelDia as $bloqueo) {
                // Si el bloqueo no tiene hora de inicio/fin, es un bloqueo de día completo
                if (empty($bloqueo->hora_inicio) || empty($bloqueo->hora_fin)) {
                    return false; // Bloqueo de día completo: toda la fecha está bloqueada
                }

                // Bloqueo por horas: Verificar si el turno se solapa con el bloqueo
                $bloqueo_hora_inicio_carbon = Carbon::parse($bloqueo->hora_inicio);
                $bloqueo_hora_fin_carbon = Carbon::parse($bloqueo->hora_fin);

                $turno_fin_carbon = (clone $hora_carbon)->addMinutes($intervalo_minutos);

                // Check for overlap:
                // Is the start of the slot within the block?
                // Is the end of the slot within the block?
                // Does the block fully contain the slot?
                if (
                    ($hora_carbon->gte($bloqueo_hora_inicio_carbon) && $hora_carbon->lt($bloqueo_hora_fin_carbon)) ||
                    ($turno_fin_carbon->gt($bloqueo_hora_inicio_carbon) && $turno_fin_carbon->lte($bloqueo_hora_fin_carbon)) ||
                    ($bloqueo_hora_inicio_carbon->gte($hora_carbon) && $bloqueo_hora_fin_carbon->lte($turno_fin_carbon))
                ) {
                    return false; // Hay superposición con un bloqueo de hora
                }
            }

            // 3. Asegurarse de que el horario no sea anterior a la hora actual para turnos de hoy
            if ($fecha->isToday()) {
                if ($hora_carbon->lt(Carbon::now()->addMinutes(15))) {
                    return false;
                }
            }

            return true;
        });

        return response()->json(array_values($horarios_filtrados));
    }
}
