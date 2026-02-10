<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreTurnoRequest;
use App\Models\Turno;
use App\Models\Paciente;
use App\Models\Medico;
use App\Models\Bloqueo;
use App\Models\HorarioMedico;
use App\Models\Especialidad;
use App\Models\Rol;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Para depuración si es necesario
use Carbon\Carbon; // Para trabajar con fechas y horas
use App\Services\AgendaService;

class TurnoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $usuario = Auth::user();
        
        $estado_filtro        = $request->input('estado_filtro', Turno::PENDIENTE);
        $dni_filtro_paciente  = $request->input('dni_filtro_paciente');
        $dni_filtro_medico    = $request->input('dni_filtro_medico');
        $fecha_filtro         = $request->input('fecha_filtro');
        $fecha_inicio         = $request->input('fecha_inicio');
        $fecha_fin            = $request->input('fecha_fin');
        $especialidad_filtro  = $request->input('especialidad_filtro');
        
        $perPage = 10;
        $especialidades = \App\Models\Especialidad::orderBy('nombre_especialidad')->get();

        $query = Turno::with([
            'paciente' => fn($q) => $q->withTrashed(),
            'medico'   => fn($q) => $q->withTrashed(),
            'medico.usuario',
            'medico.especialidades',
        ]);

        $query->filtrarPorPaciente($dni_filtro_paciente)
              ->filtrarPorMedico($dni_filtro_medico)
              ->filtrarPorEspecialidad($especialidad_filtro);

        if ($request->routeIs('medico.*')) {
            if (!$usuario->medico) {
                return redirect()->route('medico.dashboard')->with('error', 'Perfil de médico no encontrado.');
            }
            $query->where('id_medico', $usuario->medico->id_medico);

        } elseif ($request->routeIs('paciente.*')) {
            if ($usuario->pacientes->isNotEmpty()) {
                $pacientes_ids = $usuario->pacientes->pluck('id_paciente');
                $query->whereIn('id_paciente', $pacientes_ids);
            } else {
                $query->where('id_paciente', -1);
            }
        }

        $hayRangoFechas = !empty($fecha_inicio) || !empty($fecha_fin);
        $hayFiltrosActivos = !empty($dni_filtro_paciente) || !empty($dni_filtro_medico) || !empty($fecha_filtro) || $hayRangoFechas || !empty($especialidad_filtro);
        
        $esVistaDefault = ($estado_filtro === Turno::PENDIENTE && !$hayFiltrosActivos);

        $turnosHoy       = collect();
        $turnosManana    = collect();
        $turnosProximos  = collect();
        $turnosPaginados = collect(); 

        if ($esVistaDefault) {
            $now = Carbon::now();

            $turnosHoy = (clone $query)->where('estado', Turno::PENDIENTE)
                ->whereDate('fecha', $now->toDateString())
                ->where('hora', '>=', $now->toTimeString()) // Solo futuros
                ->orderBy('hora')
                ->paginate($perPage, ['*'], 'page_hoy')->withQueryString();

            $turnosManana = (clone $query)->where('estado', Turno::PENDIENTE)
                ->whereDate('fecha', $now->copy()->addDay()->toDateString())
                ->orderBy('hora')
                ->paginate($perPage, ['*'], 'page_manana')->withQueryString();

            $turnosProximos = (clone $query)->where('estado', Turno::PENDIENTE)
                ->whereDate('fecha', '>', $now->copy()->addDay()->toDateString())
                ->orderBy('fecha')->orderBy('hora')
                ->paginate($perPage, ['*'], 'page_proximos')->withQueryString();
        } else {
            // VISTA DE LISTADO (Búsquedas y filtros específicos)
            $turnosPaginados = $query
                ->filtrarPorEstado($estado_filtro)
                ->filtrarPorFecha($fecha_filtro, $fecha_inicio, $fecha_fin)
                ->orderBy('fecha', 'desc')->orderBy('hora', 'desc')
                ->paginate($perPage)->withQueryString();
        }

        return view('turnos.index', compact(
            'turnosHoy', 'turnosManana', 'turnosProximos', 'turnosPaginados',
            'estado_filtro', 'dni_filtro_paciente', 'dni_filtro_medico',
            'fecha_filtro', 'fecha_inicio', 'fecha_fin', 'especialidad_filtro', 'especialidades'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $usuario = auth()->user();

        $medicos = Medico::with('especialidades')->get(); // médicos con especialidades
        $especialidades = Especialidad::all();            // todas las especialidades
        $pacientes = collect();                           // inicializa vacío

        if ($usuario->hasRole(Rol::ADMINISTRADOR)) {
            // Admin ve todos los pacientes
            $pacientes = Paciente::orderBy('apellido')->orderBy('nombre')->get();
        } elseif ($usuario->hasRole(Rol::PACIENTE)) {
            // Paciente solo ve los que registró él mismo
            $pacientes = $usuario->pacientes;
        } else {
            // Médicos no pueden crear turnos desde aquí
            return redirect()
                ->route('turnos.index')
                ->with('warning', 'Solo administradores y pacientes pueden crear turnos.');
        }

        return view('turnos.create', compact('pacientes', 'medicos', 'especialidades'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTurnoRequest $request) 
    {
        $usuario = Auth::user();

        $fecha = Carbon::parse($request->fecha);
        $hora = $request->hora; 
        $id_medico = $request->id_medico;

        // Cargar el médico con TODOS sus horarios (semanales y puntuales) y bloqueos
        $medico = Medico::with(['horariosTrabajo', 'horariosFechas', 'bloqueos'])->find($id_medico);
        
        if (!$medico) {
            return back()->withInput()->withErrors(['id_medico' => 'Médico no encontrado.']);
        }

        // VALIDACIÓN DE HORA (HÍBRIDA)        
        $turno_hora_carbon = Carbon::parse($hora);
        // Asumimos duración del turno (podría venir de $medico->tiempo_turno)
        $duracionTurno = (int) ($medico->tiempo_turno ?? 30); 
        $turno_fin_carbon = (clone $turno_hora_carbon)->addMinutes($duracionTurno);

        $trabajaEnHorario = false;

        // 1. Revisar Horarios Semanales (Regular)
        $diaSemanaNumero = $fecha->dayOfWeek;
        $horariosSemanal = $medico->horariosTrabajo->where('dia_semana', $diaSemanaNumero);

        // 2. Revisar Horarios Puntuales (Fecha específica)
        $horariosPuntual = $medico->horariosFechas->where('fecha', $fecha->format('Y-m-d'));

        // Fusionamos ambas colecciones para iterar una sola vez
        $todosLosHorarios = $horariosSemanal->concat($horariosPuntual);

        // Verificamos si la hora cae en alguno de los bloques
        foreach ($todosLosHorarios as $horario) {
            $inicio = Carbon::parse($horario->hora_inicio);
            $fin = Carbon::parse($horario->hora_fin);

            // ¿El turno entra completo en este bloque?
            if ($turno_hora_carbon->gte($inicio) && $turno_fin_carbon->lte($fin)) {
                $trabajaEnHorario = true;
                break; // Encontramos un hueco válido, salimos.
            }
        }

        if (!$trabajaEnHorario) {
            return back()->withInput()->withErrors(['hora' => 'La hora seleccionada está fuera del rango de atención del médico para este día.']);
        }

        // 2. Verificar Bloqueos (Esto queda casi igual)
        $bloqueosDelDia = $medico->bloqueos
            ->where('fecha_inicio', '<=', $fecha->format('Y-m-d'))
            ->where('fecha_fin', '>=', $fecha->format('Y-m-d'));

        foreach ($bloqueosDelDia as $bloqueo) {
            // Bloqueo total
            if (empty($bloqueo->hora_inicio) || empty($bloqueo->hora_fin)) {
                return back()->withInput()->withErrors(['fecha' => 'El médico tiene un bloqueo administrativo (día completo) en esta fecha.']);
            }

            // Bloqueo parcial
            $bloqueo_inicio = Carbon::parse($bloqueo->hora_inicio);
            $bloqueo_fin = Carbon::parse($bloqueo->hora_fin);

            // Verificamos solapamiento
            if (
                ($turno_hora_carbon->gte($bloqueo_inicio) && $turno_hora_carbon->lt($bloqueo_fin)) ||
                ($turno_fin_carbon->gt($bloqueo_inicio) && $turno_fin_carbon->lte($bloqueo_fin)) ||
                ($bloqueo_inicio->gte($turno_hora_carbon) && $bloqueo_fin->lte($turno_fin_carbon))
            ) {
                return back()->withInput()->withErrors(['hora' => 'El horario seleccionado coincide con un bloqueo administrativo parcial.']);
            }
        }

        // 3. Validar margen de tiempo para turnos de "hoy"
        if ($fecha->isToday()) {
            if ($turno_hora_carbon->lt(Carbon::now()->addMinutes(15))) {
                return back()->withInput()->withErrors(['hora' => 'La hora seleccionada debe ser al menos 15 minutos en el futuro.']);
            }
        }

        // 4. Verificar si ya existe un turno (Esto queda igual)
        $turnoExistente = Turno::where('id_medico', $id_medico)
                               ->where('fecha', $fecha->toDateString())
                               ->where('hora', $hora)
                               ->whereIn('estado', [Turno::PENDIENTE, Turno::REALIZADO])
                               ->first();

        if ($turnoExistente) {
            return back()->withInput()->withErrors(['hora' => 'Ese horario ya fue reservado por otro paciente mientras completabas el formulario.']);
        }

        // === CREACIÓN DEL TURNO ===
        $turno = Turno::create([
            'id_paciente' => $request->id_paciente,
            'id_medico' => $request->id_medico,
            'fecha' => $fecha->toDateString(), 
            'hora' => $hora,
            'estado' => $request->estado ?? Turno::PENDIENTE,
        ]);

        // Redireccionamiento según rol
        if ($usuario->hasRole(Rol::ADMINISTRADOR)) {
            return redirect()->route('admin.turnos.index')->with('success', 'Turno agendado con éxito.');
        } elseif ($usuario->hasRole(Rol::PACIENTE)) {
            return redirect()->route('paciente.turnos.index')->with('success', 'Turno reservado exitosamente.');
        } else {
            return redirect()->route('dashboard')->with('success', 'Turno agendado.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $turno = Turno::with(Rol::PACIENTE, Rol::MEDICO)->findOrFail($id);

        // Asegurar que solo el admin o el paciente/médico asociado puedan ver el turno
        $usuario = Auth::user();
        if ($usuario->hasRole(Rol::ADMINISTRADOR) ||
            ($usuario->hasRole(Rol::PACIENTE) && $turno->paciente && $turno->paciente->id_usuario == $usuario->id_usuario) ||
            ($usuario->hasRole(Rol::MEDICO) && $turno->medico && $turno->medico->id_usuario == $usuario->id_usuario)) {
            return view('turnos.show', compact('turno'));
        }

        abort(403, 'Acceso no autorizado.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $turno = Turno::with(Rol::PACIENTE, Rol::MEDICO)->findOrFail($id);
        $usuario = Auth::user();

        // Solo admin, o el paciente dueño, o el médico del turno pueden editar
        if ($usuario->hasRole(Rol::ADMINISTRADOR) ||
            ($usuario->hasRole(Rol::PACIENTE) && $turno->paciente && $turno->paciente->id_usuario == $usuario->id_usuario) ||
            ($usuario->hasRole(Rol::MEDICO) && $turno->medico && $turno->medico->id_usuario == $usuario->id_usuario)) {

            $pacientes = ($usuario->hasRole(Rol::ADMINISTRADOR)) ? Paciente::all() : $usuario->pacientes;
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
            ($usuario->hasRole(Rol::ADMINISTRADOR)) || // Administrador
            ($usuario->hasRole(Rol::MEDICO) && $turno->id_medico == $usuario->medico->id_medico) || // Médico
            ($usuario->hasRole(Rol::PACIENTE) && $turno->id_paciente == $usuario->paciente->id_paciente) // Paciente
        ) {
            // Solo validamos el campo 'estado'. Otros campos no serán modificables.
            $rules = [
                'estado' => 'required|in:pendiente,realizado,cancelado',
            ];

            $request->validate($rules);

            // --- Lógica para controlar el cambio de estado ---
            $estado_solicitado = $request->input('estado');
            $estado_actual = $turno->estado;

            // Estados finales que no deberían ser modificables
            $estados_finales = [Turno::REALIZADO, Turno::CANCELADO];

            // Si el turno ya está en un estado final y se intenta cambiar el estado,
            // o si se intenta cambiar de un estado final a 'pendiente', se rechaza.
            if (in_array($estado_actual, $estados_finales) && $estado_solicitado !== $estado_actual) {
                return back()->withInput()->withErrors(['estado' => 'No se puede cambiar el estado de un turno que ya está ' . $estado_actual . '.']);
            }
            // Si el turno no está pendiente y se intenta cambiar a pendiente, se rechaza
            if ($estado_actual !== Turno::PENDIENTE && $estado_solicitado === Turno::PENDIENTE) {
                return back()->withInput()->withErrors(['estado' => 'No se puede revertir un turno a "Pendiente" desde su estado actual de "' . $estado_actual . '".']);
            }
            // --- FIN Lógica para controlar el cambio de estado ---

            // Actualizar solo el campo 'estado'
            $turno->update([
                'estado' => $estado_solicitado,
            ]);

            // Redireccionamiento dinámico basado en el rol del usuario
            if ($usuario->hasRole(Rol::ADMINISTRADOR)) { // Administrador
                return redirect()->route('admin.turnos.index')->with('success', 'Estado del turno actualizado con éxito por el administrador.');
            } elseif ($usuario->hasRole(Rol::MEDICO)) { // Médico
                return redirect()->route('medico.turnos.index')->with('success', 'Estado de tu turno ha sido actualizado con éxito.');
            } else { // Paciente (hasRole(Rol::PACIENTE))
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
        if ($user->hasRole(Rol::ADMINISTRADOR)) {
            $turno->update(['estado' => Turno::CANCELADO]); // Lo marcamos como cancelado en lugar de borrarlo
            return redirect()->route('admin.turnos.index')->with('success', 'Turno cancelado con éxito por el administrador.');
        }

        // Paciente solo puede cancelar sus propios turnos
        if ($user->hasRole(Rol::PACIENTE) && $turno->paciente && $turno->paciente->id_usuario == $user->id_usuario) {
            $turno->update(['estado' => Turno::CANCELADO]); // Lo marcamos como cancelado
            return redirect()->route('paciente.turnos.index')->with('success', 'Turno cancelado con éxito.');
        }

        // Si el usuario es médico y no tiene permiso para cancelar, o si es un rol no manejado
        if ($user->hasRole(Rol::MEDICO)) {
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
     * Obtiene los horarios disponibles delegando la lógica al AgendaService.
     */
    public function getHorariosDisponibles(Request $request, AgendaService $agendaService)
    {
        $id_medico = $request->input('id_medico');
        $fecha     = $request->input('fecha');
        $except_id = $request->input('except_turno_id');

        // Validación básica
        if (!$id_medico || !$fecha) {
            return response()->json([
                'horarios' => [],
                'mensaje'  => 'Faltan datos para consultar la agenda.'
            ]);
        }

        $resultado = $agendaService->obtenerHorariosDisponibles($id_medico, $fecha, $except_id);

        return response()->json($resultado);
    }


    public function cambiarEstado(Request $request, Turno $turno)
    {
        $usuario = Auth::user();
        $nuevoEstado = $request->input('estado');

        if ($nuevoEstado !== Turno::CANCELADO) {
            return back()->with('error', 'Acción no permitida. Solo se permite cancelar.');
        }

        if ($turno->estado !== Turno::PENDIENTE) {
            return back()->with('error', 'Solo se pueden cancelar turnos pendientes.');
        }

        if ($usuario->hasRole(Rol::ADMINISTRADOR)) {
            $turno->estado = Turno::CANCELADO;
            $turno->save();
            return back()->with('success', 'Turno cancelado por administración.');
        }

        if ($turno->paciente && $turno->paciente->id_usuario == $usuario->id_usuario) {
            $turno->estado = Turno::CANCELADO;
            $turno->save();
            return back()->with('success', 'Su turno ha sido cancelado correctamente.');
        }

        return back()->with('error', 'No tiene permiso para realizar esta acción.');
    }


    public function obtenerAgendaMes(Request $request, AgendaService $agendaService)
    {
        $id_medico = $request->input('id_medico');
        $mes       = $request->input('mes');
        $anio      = $request->input('anio');

        if (!$id_medico || !$mes || !$anio) {
            return response()->json([]);
        }

        $estados = $agendaService->obtenerEstadoMes($id_medico, $mes, $anio);

        return response()->json($estados);
    }


    // API Interna: Obtiene costo e instrucciones según Médico y Paciente (Obra Social).
    public function getInfoCosto(Request $request)
    {
        $id_medico = $request->input('id_medico');
        $id_paciente = $request->input('id_paciente');

        // Si es el propio paciente logueado quien pide la info
        if (!$id_paciente && auth()->user()->hasRole(Rol::PACIENTE)) {
            // Buscamos el ID del paciente asociado al usuario
            $paciente = auth()->user()->pacientes->first();
            $id_paciente = $paciente ? $paciente->id_paciente : null;
        }

        if (!$id_medico || !$id_paciente) {
            return response()->json(['status' => 'error', 'message' => 'Faltan datos']);
        }

        // Buscamos los modelos
        $paciente = Paciente::with('obraSocial')->find($id_paciente);
        $medico = Medico::find($id_medico);

        if (!$paciente || !$medico) {
            return response()->json(['status' => 'error', 'message' => 'Datos inválidos']);
        }

        $obraSocial = $paciente->obraSocial;

        // CASO 1: PACIENTE PARTICULAR (Sin obra social o ID especial si lo tienes definido así)
        if (!$obraSocial || stripos($obraSocial->nombre, 'Particular') !== false) {
            return response()->json([
                'status' => 'ok',
                'obra_social' => 'Particular',
                'costo' => $medico->precio_particular, // Precio fijo del médico
                'instrucciones' => 'Abonar en administración al llegar.'
            ]);
        }

        // CASO 2: TIENE OBRA SOCIAL
        // Buscamos si el médico atiende esa obra social en la tabla pivote
        $datoPivot = $medico->obrasSociales()->where('medico_obra_social.id_obra_social', $obraSocial->id_obra_social)->first();

        if ($datoPivot) {
            return response()->json([
                'status' => 'ok',
                'obra_social' => $obraSocial->nombre,
                'costo' => 'Cubierto', 
                'instrucciones' => $datoPivot->pivot->instrucciones ?? 'Sin instrucciones especiales.'
            ]);
        }

        // CASO 3: NO ATIENDE ESA OBRA SOCIAL
        return response()->json([
            'status' => 'warning',
            'obra_social' => $obraSocial->nombre,
            'costo' => $medico->precio_particular, 
            'mensaje' => 'El médico NO atiende por esta Obra Social. Se aplica tarifa particular.'
        ]);
    }
}
