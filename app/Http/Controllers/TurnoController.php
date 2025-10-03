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
        $usuario              = Auth::user();
        $estado_filtro        = $request->input('estado_filtro', 'pendiente');
        $dni_filtro_paciente  = $request->input('dni_filtro_paciente');
        $dni_filtro_medico    = $request->input('dni_filtro_medico');
        $fecha_filtro         = $request->input('fecha_filtro');      // Fecha única
        $fecha_inicio         = $request->input('fecha_inicio');      // Rango: desde
        $fecha_fin            = $request->input('fecha_fin');         // Rango: hasta
        $especialidad_filtro  = $request->input('especialidad_filtro');
        $nombre_filtro        = $request->input('nombre_filtro');
        $perPage              = 10;
        $especialidades = Especialidad::orderBy('nombre_especialidad')->get();

        // Consulta base
        $query = Turno::with([
            'paciente' => function ($q) { $q->withTrashed(); },
            'medico'   => function ($q) { $q->withTrashed(); },
            'medico.usuario',
            'medico.especialidades',
        ]);

        // Filtro por DNI (paciente)
        if (!empty($dni_filtro_paciente)) {
            $query->whereHas('paciente', function ($q) use ($dni_filtro_paciente) {
                $q->withTrashed()
                ->where('dni', 'like', '%' . $dni_filtro_paciente . '%');
            });
        }

        // Filtro por DNI (médico → usuario)
        if (!empty($dni_filtro_medico)) {
            $query->whereHas('medico', function ($q) use ($dni_filtro_medico) {
                $q->withTrashed()
                ->whereHas('usuario', function ($u) use ($dni_filtro_medico) {
                    $u->where('dni', 'like', '%' . $dni_filtro_medico . '%');
                });
            });
        }

        // Filtro por especialidad (id_especialidad)
        if (!empty($especialidad_filtro)) {
            $query->whereHas('medico.especialidades', function ($q) use ($especialidad_filtro) {
                $q->where('id_especialidad', $especialidad_filtro);
            });
        }

        // Filtro por nombre (paciente o médico)
        if (!empty($nombre_filtro)) {
            $query->where(function ($outer) use ($nombre_filtro) {
                // Paciente: nombre o apellido
                $outer->whereHas('paciente', function ($q) use ($nombre_filtro) {
                    $q->withTrashed()
                    ->where(function ($w) use ($nombre_filtro) {
                        $w->where('nombre', 'like', '%' . $nombre_filtro . '%')
                            ->orWhere('apellido', 'like', '%' . $nombre_filtro . '%');
                    });
                })
                // Médico (usuario): nombre o apellido
                ->orWhereHas('medico.usuario', function ($u) use ($nombre_filtro) {
                    $u->where(function ($w) use ($nombre_filtro) {
                        $w->where('nombre', 'like', '%' . $nombre_filtro . '%')
                        ->orWhere('apellido', 'like', '%' . $nombre_filtro . '%');
                    });
                });
            });
        }

        // Filtro por rol en ruta
        if ($request->routeIs('medico.*')) {
            $medico = $usuario->medico;
            if (!$medico) {
                return redirect()->route('medico.dashboard')
                    ->with('error', 'Tu perfil de médico no está configurado.');
            }
            $query->where('id_medico', $medico->id_medico);

        } elseif ($request->routeIs('paciente.*')) {
            $pacientes_ids = $usuario->pacientes->pluck('id_paciente');
            $query->whereIn('id_paciente', $pacientes_ids);
        }

        // Variables para la vista
        $turnosHoy        = collect();
        $turnosManana     = collect();
        $turnosProximos   = collect();
        $turnosPaginados  = null;

        // Decisión de agrupado vs. paginado:
        // Agrupamos solo si es estado pendiente y NO hay filtros de fecha (ni única ni rango)
        $hayRangoFechas = !empty($fecha_inicio) || !empty($fecha_fin);
        if ($estado_filtro === 'pendiente' && empty($fecha_filtro) && !$hayRangoFechas) {
            // Pendientes agrupados por hoy/mañana/próximos
            $turnos = $query->where('estado', 'pendiente')
                            ->orderBy('fecha', 'asc')
                            ->orderBy('hora', 'asc')
                            ->get();

            $hoy    = Carbon::today();
            $manana = Carbon::tomorrow();

            $turnosHoy = $turnos->filter(fn($t) => Carbon::parse($t->fecha)->isSameDay($hoy));
            $turnosManana = $turnos->filter(fn($t) => Carbon::parse($t->fecha)->isSameDay($manana));
            $turnosProximos = $turnos->filter(fn($t) => Carbon::parse($t->fecha)->isAfter($manana));

        } else {
            // Paginado con estado + filtros (incluye fecha única o rango)
            $subquery = clone $query;

            // Estado
            if ($estado_filtro === 'todos') {
                $subquery->whereIn('estado', ['realizado', 'atendido', 'pendiente', 'cancelado', 'ausente']);
            } elseif ($estado_filtro === 'realizado') {
                $subquery->whereIn('estado', ['realizado', 'atendido']);
            } else {
                $subquery->where('estado', $estado_filtro);
            }

            // Fecha única tiene prioridad sobre rango si ambos vienen
            if (!empty($fecha_filtro)) {
                $subquery->whereDate('fecha', $fecha_filtro);
            } else {
                if (!empty($fecha_inicio) && !empty($fecha_fin)) {
                    $subquery->whereBetween('fecha', [$fecha_inicio, $fecha_fin]);
                } elseif (!empty($fecha_inicio)) {
                    $subquery->whereDate('fecha', '>=', $fecha_inicio);
                } elseif (!empty($fecha_fin)) {
                    $subquery->whereDate('fecha', '<=', $fecha_fin);
                }
            }

            $turnosPaginados = $subquery->orderBy('fecha', 'desc')
                                        ->orderBy('hora', 'desc')
                                        ->paginate($perPage)
                                        ->withQueryString();
        }

        return view('turnos.index', [
            'turnosHoy'           => $turnosHoy,
            'turnosManana'        => $turnosManana,
            'turnosProximos'      => $turnosProximos,
            'turnosPaginados'     => $turnosPaginados,
            'estado_filtro'       => $estado_filtro,
            'dni_filtro_paciente' => $dni_filtro_paciente,
            'dni_filtro_medico'   => $dni_filtro_medico,
            'fecha_filtro'        => $fecha_filtro,
            'fecha_inicio'        => $fecha_inicio,
            'fecha_fin'           => $fecha_fin,
            'especialidad_filtro' => $especialidad_filtro,
            'nombre_filtro'       => $nombre_filtro,
            'especialidades'      => $especialidades,
        ]);
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
        if ($usuario->hasRole('Administrador')) {
            $pacientes = Paciente::all();
        }
        // Si es paciente, solo ve los que registró él mismo
        elseif ($usuario->hasRole('Paciente')) {
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
                if ($usuario->hasRole('Paciente')) {
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
        if ($usuario->hasRole('Administrador')) { // Administrador
            return redirect()->route('admin.turnos.index')->with('success', 'Turno agendado con éxito por el administrador.');
        } elseif ($usuario->hasRole('Paciente')) { // Paciente
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
        if ($usuario->hasRole('Administrador') ||
            ($usuario->hasRole('Paciente') && $turno->paciente && $turno->paciente->id_usuario == $usuario->id_usuario) ||
            ($usuario->hasRole('Medico') && $turno->medico && $turno->medico->id_usuario == $usuario->id_usuario)) {
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
        if ($usuario->hasRole('Administrador') ||
            ($usuario->hasRole('Paciente') && $turno->paciente && $turno->paciente->id_usuario == $usuario->id_usuario) ||
            ($usuario->hasRole('Medico') && $turno->medico && $turno->medico->id_usuario == $usuario->id_usuario)) {

            $pacientes = ($usuario->hasRole('Administrador')) ? Paciente::all() : $usuario->pacientes;
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
            ($usuario->hasRole('Administrador')) || // Administrador
            ($usuario->hasRole('Medico') && $turno->id_medico == $usuario->medico->id_medico) || // Médico
            ($usuario->hasRole('Paciente') && $turno->id_paciente == $usuario->paciente->id_paciente) // Paciente
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
            if ($usuario->hasRole('Administrador')) { // Administrador
                return redirect()->route('admin.turnos.index')->with('success', 'Estado del turno actualizado con éxito por el administrador.');
            } elseif ($usuario->hasRole('Medico')) { // Médico
                return redirect()->route('medico.turnos.index')->with('success', 'Estado de tu turno ha sido actualizado con éxito.');
            } else { // Paciente (hasRole('Paciente'))
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
        if ($user->hasRole('Administrador')) {
            $turno->update(['estado' => 'cancelado']); // Lo marcamos como cancelado en lugar de borrarlo
            return redirect()->route('admin.turnos.index')->with('success', 'Turno cancelado con éxito por el administrador.');
        }

        // Paciente solo puede cancelar sus propios turnos
        if ($user->hasRole('Paciente') && $turno->paciente && $turno->paciente->id_usuario == $user->id_usuario) {
            $turno->update(['estado' => 'cancelado']); // Lo marcamos como cancelado
            return redirect()->route('paciente.turnos.index')->with('success', 'Turno cancelado con éxito.');
        }

        // Médico puede cancelar sus propios turnos (si se decide permitir)
        // Según nuestra discusión, el médico NO debería cancelar directamente, solo editar estado.
        // Si quieres que el médico pueda cancelar, descomenta y ajusta esta lógica.
        /*
        if ($user->hasRole('Medico') && $turno->medico && $turno->medico->id_usuario == $user->id_usuario) {
            $turno->update(['estado' => 'cancelado']);
            return redirect()->route('medico.turnos.index')->with('success', 'Tu turno ha sido cancelado con éxito.');
        }
        */

        // Si el usuario es médico y no tiene permiso para cancelar, o si es un rol no manejado
        if ($user->hasRole('Medico')) {
             return redirect()->route('medico.turnos.index')->with('error', 'No tienes permiso para cancelar este turno. Solo puedes cambiar su estado.');
        }


        return redirect()->route('dashboard')->with('error', 'No tienes permiso para cancelar este turno.');
    }


    /**
     * Muestra los turnos de un paciente autenticado.
     */
    public function misTurnos()
    {
        return $this->index(request());
    }

    /**
     * Muestra los turnos de un médico autenticado.
     */
    public function misTurnosMedico()
    {
        return $this->index(request());
    }

    /**
     * Obtiene los médicos disponibles por especialidad.
     * Devuelve un array vacío si no hay especialidad seleccionada.
     */
    public function getMedicosByEspecialidad(Request $request)
    {
        $id_especialidad = $request->input('id_especialidad');

        if (!$id_especialidad) {
            return response()->json([], 200); 
        }

        $medicos = Medico::with('usuario') // Paso 1: Cargar la relación con el usuario
            ->whereHas('especialidades', function ($query) use ($id_especialidad) {
                $query->where('especialidades.id_especialidad', $id_especialidad);
            })
            ->get();

        // Paso 2: Mapear los resultados para devolver solo la información necesaria
        $medicos_formateados = $medicos->map(function ($medico) {
            return [
                'id_medico' => $medico->id_medico,
                'nombre' => $medico->usuario->nombre,
                'apellido' => $medico->usuario->apellido,
            ];
        });

        return response()->json($medicos_formateados);
    }

    /**
     * Obtiene los horarios disponibles de un médico para una fecha específica.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHorariosDisponibles(Request $request)
    {
        $id_medico    = $request->input('id_medico');
        $fecha_str    = $request->input('fecha');
        $except_id    = $request->input('except_turno_id');
        $intervalo    = 30; // minutos por turno

        if (! $id_medico || ! $fecha_str) {
            return response()->json([
                'horarios' => [],
                'mensaje'  => 'Selecciona un médico y una fecha.'
            ]);
        }

        $fecha = Carbon::parse($fecha_str);
        $dia   = $fecha->dayOfWeek;

        // 1) bloqueo full-day
        $bloqueoDia = Bloqueo::where('id_medico', $id_medico)
            ->where('fecha_inicio','<=',$fecha)
            ->where('fecha_fin','>=',$fecha)
            ->whereNull('hora_inicio')
            ->whereNull('hora_fin')
            ->first();

        if ($bloqueoDia) {
            $motivo = $bloqueoDia->motivo ?: 'Bloqueo';
            return response()->json([
                'horarios' => [],
                'mensaje'  => "El médico está ausente para esta fecha ({$motivo})."
            ]);
        }

        // 2) traigo todos los bloques de trabajo para ese día
        $bloquesTrabajo = HorarioMedico::where('id_medico', $id_medico)
            ->where('dia_semana', $dia)
            ->get();

        if ($bloquesTrabajo->isEmpty()) {
            return response()->json([
                'horarios' => [],
                'mensaje'  => 'El médico no trabaja en la fecha seleccionada.'
            ]);
        }

        // 3) turnos ya ocupados
        $turnosOcupados = Turno::where('id_medico', $id_medico)
            ->where('fecha', $fecha)
            ->where('estado', '!=', 'Cancelado')
            ->when($except_id, fn($q) => $q->where('id_turno','!=',$except_id))
            ->pluck('hora')
            ->map(fn($h) => Carbon::parse($h)->format('H:i'))
            ->toArray();

        // 4) bloqueos parciales (por horas)
        $bloqueosHoras = Bloqueo::where('id_medico',$id_medico)
            ->where('fecha_inicio','<=',$fecha)
            ->where('fecha_fin','>=',$fecha)
            ->whereNotNull('hora_inicio')
            ->whereNotNull('hora_fin')
            ->get();

        $horariosDisponibles = [];

        // 5) para cada bloque de trabajo genero sus slots
        foreach ($bloquesTrabajo as $bloque) {
            $inicio = Carbon::parse($bloque->hora_inicio);
            $fin    = Carbon::parse($bloque->hora_fin);

            for ($hora = $inicio->copy(); $hora->lt($fin); $hora->addMinutes($intervalo)) {
                $slot = $hora->format('H:i');

                // 5.1) salto si ya está ocupado
                if (in_array($slot, $turnosOcupados)) {
                    continue;
                }

                // 5.2) salto si entra en un bloqueo parcial
                $enBloqueo = $bloqueosHoras->contains(fn($b) => 
                    Carbon::parse($slot)->between(
                        Carbon::parse($b->hora_inicio),
                        Carbon::parse($b->hora_fin)
                    )
                );
                if ($enBloqueo) {
                    continue;
                }

                // 5.3) salto si es hoy y ya pasó la hora
                if ($fecha->isToday() && $hora->lt(Carbon::now())) {
                    continue;
                }

                $horariosDisponibles[] = $slot;
            }
        }

        // 6) limpio duplicados y ordeno ascendente
        $horariosDisponibles = array_unique($horariosDisponibles);
        sort($horariosDisponibles);

        // 7) mensaje si quedó vacío
        $mensaje = null;
        if (empty($horariosDisponibles)) {
            if ($bloqueosHoras->isNotEmpty()) {
                $motivo = $bloqueosHoras->first()->motivo ?: 'Bloqueo';
                $mensaje = "El médico está ausente para esta fecha ({$motivo}).";
            } else {
                $mensaje = 'Todos los horarios disponibles para esta fecha fueron ocupados.';
            }
        }

        return response()->json([
            'horarios' => $horariosDisponibles,
            'mensaje'  => $mensaje
        ]);
    }

    public function cambiarEstado(Request $request, Turno $turno)
    {
        $usuario = Auth::user();
        $nuevoEstado = $request->input('estado');

        // Validar el nuevo estado
        if (!in_array($nuevoEstado, ['realizado', 'ausente', 'cancelado'])) {
            return back()->with('error', 'Estado de turno inválido.');
        }

        // Autorización y lógica de actualización por rol
        if ($usuario->hasRolActivo('Administrador')) {
            $turno->estado = $nuevoEstado;
        } elseif ($usuario->hasRolActivo('Medico')) {
            // Un médico solo puede cambiar el estado de sus propios turnos
            if ($turno->id_medico !== $usuario->medico->id_medico) {
                return back()->with('error', 'No tienes permiso para modificar este turno.');
            }

            if ($nuevoEstado === 'realizado' || $nuevoEstado === 'ausente') {
                $turno->estado = $nuevoEstado;
            } else {
                return back()->with('error', 'No tienes permiso para realizar esta acción.');
            }
        } elseif ($usuario->hasRolActivo('Paciente')) {
            // Un paciente solo puede cancelar sus propios turnos
            $paciente = $usuario->pacientes()->where('id_paciente', $turno->id_paciente)->first();
            if (!$paciente) {
                return back()->with('error', 'No tienes permiso para cancelar este turno.');
            }

            if ($nuevoEstado === 'cancelado') {
                $turno->estado = $nuevoEstado;
            } else {
                return back()->with('error', 'No tienes permiso para realizar esta acción.');
            }
        } else {
            return back()->with('error', 'No tienes permiso para realizar esta acción.');
        }

        $turno->save();
        return back()->with('success', 'Estado del turno actualizado correctamente.');
    }
}
