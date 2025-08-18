<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Turno;
use App\Models\Paciente;
use App\Models\Medico;
use App\Models\Bloqueo;
use App\Models\HorarioMedico;
use App\Models\Especialidad;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Para depuración si es necesario
use Carbon\Carbon; // Para trabajar con fechas y horas

class TurnoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $usuario = Auth::user();
        $estado_filtro = $request->input('estado_filtro', 'pendiente'); // Por defecto, mostrar 'pendiente'
        $dni_filtro = $request->input('dni_filtro'); // Nuevo: Obtener el valor del filtro de DNI
        $perPage = 10; // Número de turnos por página

        // Cargar paciente y médico, además de las especialidades del médico
        $query = Turno::with('paciente', 'medico.especialidades');

        // Aplicar filtros según el rol y el estado_filtro
        if ($usuario->id_rol == 1) {
            // Admin ve todos los turnos, pero aplica el filtro de estado si está presente
            if ($estado_filtro === 'todos') {
                $query->whereIn('estado', ['realizado', 'atendido', 'pendiente', 'cancelado', 'ausente']);
            } elseif ($estado_filtro === 'realizado_atendido') {
                $query->whereIn('estado', ['realizado', 'atendido']);
            } else {
                $query->where('estado', $estado_filtro);
            }
        } elseif ($usuario->id_rol == 2) {
            // Médico ve solo sus turnos
            $medico = $usuario->medico;
            if (!$medico) {
                return redirect()->route('medico.dashboard')->with('error', 'Tu perfil de médico no está configurado.');
            }
            $query->where('id_medico', $medico->id_medico);

            if ($estado_filtro === 'todos') {
                $query->whereIn('estado', ['realizado', 'atendido', 'pendiente', 'cancelado', 'ausente']);
            } elseif ($estado_filtro === 'realizado_atendido') {
                $query->whereIn('estado', ['realizado', 'atendido']);
            } else {
                $query->where('estado', $estado_filtro);
            }
        } elseif ($usuario->id_rol == 3) {
            // Paciente ve solo sus turnos
            $pacientes_ids = $usuario->pacientes->pluck('id_paciente');
            $query->whereIn('id_paciente', $pacientes_ids);

            if ($estado_filtro === 'todos') {
                $query->whereIn('estado', ['realizado', 'atendido', 'pendiente', 'cancelado', 'ausente']);
            } elseif ($estado_filtro === 'realizado_atendido') {
                $query->whereIn('estado', ['realizado', 'atendido']);
            } else {
                $query->where('estado', $estado_filtro);
            }
        } else {
            // Si el rol no está definido o es inesperado
            $turnos = collect(); // Colección vacía
            return view('turnos.index', compact('turnos', 'estado_filtro'));
        }

        // Nuevo: Aplicar filtro por DNI si se proporciona
        if ($dni_filtro) {
            $query->whereHas('paciente', function ($q) use ($dni_filtro) {
                $q->where('dni', 'like', '%' . $dni_filtro . '%');
            });
        }

        // Ordenar por fecha y hora descendente para que los más recientes aparezcan primero
        // Usar paginate() en lugar de get()
        $turnos = $query->orderBy('fecha', 'asc')
                        ->orderBy('hora', 'asc')
                        ->paginate($perPage)
                        ->withQueryString(); // Esto es crucial para mantener los parámetros de filtro en la paginación

        return view('turnos.index', compact('turnos', 'estado_filtro', 'dni_filtro'));
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
        if (
            ($usuario->id_rol == 1) || // Administrador
            ($usuario->id_rol == 2 && $turno->id_medico == $usuario->medico->id_medico) || // Médico
            ($usuario->id_rol == 3 && $turno->id_paciente == $usuario->paciente->id_paciente) // Paciente
        ) {
            // Solo validamos el campo 'estado'. Otros campos no serán modificables.
            $rules = [
                'estado' => 'required|in:pendiente,realizado,cancelado,ausente',
            ];

            $request->validate($rules);

            // --- Lógica para controlar el cambio de estado ---
            $estado_solicitado = $request->input('estado');
            $estado_actual = $turno->estado;

            // Estados finales que no deberían ser modificables
            $estados_finales = ['realizado', 'cancelado', 'ausente'];

            // Si el turno ya está en un estado final y se intenta cambiar el estado,
            // o si se intenta cambiar de un estado final a 'pendiente', se rechaza.
            if (in_array($estado_actual, $estados_finales) && $estado_solicitado !== $estado_actual) {
                return back()->withInput()->withErrors(['estado' => 'No se puede cambiar el estado de un turno que ya está ' . $estado_actual . '.']);
            }
            // Si el turno no está pendiente y se intenta cambiar a pendiente, se rechaza
            if ($estado_actual !== 'pendiente' && $estado_solicitado === 'pendiente') {
                return back()->withInput()->withErrors(['estado' => 'No se puede revertir un turno a "Pendiente" desde su estado actual de "' . $estado_actual . '".']);
            }
            // --- FIN Lógica para controlar el cambio de estado ---

            // Actualizar solo el campo 'estado'
            $turno->update([
                'estado' => $estado_solicitado,
            ]);

            // Redireccionamiento dinámico basado en el rol del usuario
            if ($usuario->id_rol == 1) { // Administrador
                return redirect()->route('admin.turnos.index')->with('success', 'Estado del turno actualizado con éxito por el administrador.');
            } elseif ($usuario->id_rol == 2) { // Médico
                return redirect()->route('medico.turnos.index')->with('success', 'Estado de tu turno ha sido actualizado con éxito.');
            } else { // Paciente (id_rol == 3)
                return redirect()->route('paciente.turnos.index')->with('success', 'Estado del turno actualizado con éxito.');
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
        $perPage = 10; // Número de turnos por página
        $estado_filtro = request()->input('estado_filtro', 'pendiente'); // Obtener filtro

        if ($usuario->id_rol != 3) {
            abort(403, 'Acceso no autorizado. Esta sección es solo para pacientes.');
        }

        $pacientes_ids = $usuario->pacientes->pluck('id_paciente');
        $query = Turno::whereIn('id_paciente', $pacientes_ids)
                    ->with('paciente', 'medico');

        if ($estado_filtro === 'todos') {
            // No aplicar filtro de estado
        } elseif ($estado_filtro === 'realizado_atendido') {
            $query->whereIn('estado', ['realizado', 'atendido']);
        } else {
            $query->where('estado', $estado_filtro);
        }

        $turnos = $query->orderBy('fecha', 'asc')
                        ->orderBy('hora', 'asc') // Ordenar por fecha ascendente y hora ascendente
                        ->paginate($perPage)
                        ->withQueryString();

        return view('turnos.mis-turnos', compact('turnos', 'estado_filtro'));
    }

    /**
     * Muestra los turnos de un médico autenticado.
     */
    public function misTurnosMedico()
    {
        $usuario = Auth::user();
        $perPage = 10; // Número de turnos por página
        $estado_filtro = request()->input('estado_filtro', 'pendiente'); // Obtener filtro

        if ($usuario->id_rol != 2) {
            abort(403, 'Acceso no autorizado. Esta sección es solo para médicos.');
        }

        $medico = $usuario->medico; // Relación: $user->medico

        if (!$medico) {
            return redirect()->route('medico.dashboard')->with('error', 'Tu perfil de médico no está configurado.');
        }

        $query = Turno::where('id_medico', $medico->id_medico)
                    ->with('paciente', 'medico');

        if ($estado_filtro === 'todos') {
            // No aplicar filtro de estado
        } elseif ($estado_filtro === 'realizado_atendido') {
            $query->whereIn('estado', ['realizado', 'atendido']);
        } else {
            $query->where('estado', $estado_filtro);
        }

        $turnos = $query->orderBy('fecha', 'asc')
                        ->orderBy('hora', 'asc') // Ordenar por fecha ascendente y hora ascendente
                        ->paginate($perPage)
                        ->withQueryString();

        return view('turnos.mis-turnos-medico', compact('turnos', 'estado_filtro'));
    }

    /**
     * Obtiene los médicos disponibles por especialidad.
     * Devuelve un array vacío si no hay especialidad seleccionada.
     */
    public function getMedicosByEspecialidad(Request $request)
    {
        $id_especialidad = $request->input('id_especialidad');

        if (!$id_especialidad) {
            return response()->json([], 200); // Devuelve un array vacío si no hay especialidad seleccionada
        }

        $medicos = Medico::whereHas('especialidades', function ($query) use ($id_especialidad) {
            $query->where('especialidades.id_especialidad', $id_especialidad);
        })->get(['id_medico', 'nombre', 'apellido']); // Solo selecciona las columnas necesarias

        return response()->json($medicos);
    }

   /**
     * Obtiene los horarios disponibles de un médico para una fecha específica.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHorariosDisponibles(Request $request)
    {
        $id_medico = $request->input('id_medico');
        $fecha_str = $request->input('fecha');
        $except_turno_id = $request->input('except_turno_id');

        if (!$id_medico || !$fecha_str) {
            return response()->json([], 400); // Bad Request
        }

        $fecha = Carbon::parse($fecha_str);
        
        // --- MODIFICACION CLAVE AQUI ---
        // Obtener el día de la semana como un número (0=domingo, 1=lunes, etc.)
        $dia_semana_numero = $fecha->dayOfWeek;

        // 1. Obtener el horario de trabajo del médico para ese día usando el número
        $horarioTrabajo = HorarioMedico::where('id_medico', $id_medico)
            ->where('dia_semana', $dia_semana_numero)
            ->first();

        if (!$horarioTrabajo) {
            return response()->json([]);
        }

        $hora_inicio = Carbon::parse($horarioTrabajo->hora_inicio);
        $hora_fin = Carbon::parse($horarioTrabajo->hora_fin);
        $intervalo_minutos = 30; // Intervalo de turnos

        $horariosDisponibles = [];

        // 2. Obtener turnos ocupados y bloqueos
        $turnosOcupados = Turno::where('id_medico', $id_medico)
            ->where('fecha', $fecha)
            ->where('estado', '!=', 'cancelado')
            ->when($except_turno_id, function ($query) use ($except_turno_id) {
                return $query->where('id_turno', '!=', $except_turno_id);
            })
            ->pluck('hora')
            ->toArray();

        $bloqueosDelDia = Bloqueo::where('id_medico', $id_medico)
            ->where('fecha_inicio', '<=', $fecha)
            ->where('fecha_fin', '>=', $fecha)
            ->get();


        // 3. Generar y filtrar los horarios
        for ($hora = $hora_inicio; $hora->lt($hora_fin); $hora->addMinutes($intervalo_minutos)) {
            $hora_formateada = $hora->format('H:i');

            // 4. Validar si el horario está disponible
            $es_disponible = true;

            // a) Filtrar por turnos ocupados
            if (in_array($hora_formateada, $turnosOcupados)) {
                $es_disponible = false;
            }

            // b) Filtrar por bloqueos
            if ($es_disponible) {
                foreach ($bloqueosDelDia as $bloqueo) {
                    $bloqueo_inicio = Carbon::parse($bloqueo->hora_inicio);
                    $bloqueo_fin = Carbon::parse($bloqueo->hora_fin);
                    $turno_fin = $hora->copy()->addMinutes($intervalo_minutos);

                    if ($bloqueo_inicio && $bloqueo_fin) {
                         // Comprobar solapamiento de horarios con un rango
                        if ($hora->lt($bloqueo_fin) && $turno_fin->gt($bloqueo_inicio)) {
                            $es_disponible = false;
                            break;
                        }
                    } else {
                         // Bloqueo de día completo
                        $es_disponible = false;
                        break;
                    }
                }
            }

            // c) Asegurarse de que el horario no sea anterior a la hora actual para turnos de hoy
            if ($es_disponible && $fecha->isToday()) {
                if ($hora->lt(Carbon::now()->addMinutes(15))) {
                    $es_disponible = false;
                }
            }

            if ($es_disponible) {
                $horariosDisponibles[] = $hora_formateada;
            }
        }
        
        return response()->json($horariosDisponibles);
    }
}
