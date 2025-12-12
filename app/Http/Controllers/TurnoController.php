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
        $usuario              = Auth::user();
        
        // Parámetros de filtro
        $estado_filtro        = $request->input('estado_filtro', 'pendiente');
        $dni_filtro_paciente  = $request->input('dni_filtro_paciente');
        $dni_filtro_medico    = $request->input('dni_filtro_medico');
        $fecha_filtro         = $request->input('fecha_filtro');
        $fecha_inicio         = $request->input('fecha_inicio');
        $fecha_fin            = $request->input('fecha_fin');
        $especialidad_filtro  = $request->input('especialidad_filtro');
        
        $perPage = 10;
        $especialidades = Especialidad::orderBy('nombre_especialidad')->get();

        // 1. Iniciar Query Base
        $query = Turno::with([
            'paciente' => fn($q) => $q->withTrashed(),
            'medico'   => fn($q) => $q->withTrashed(),
            'medico.usuario',
            'medico.especialidades',
        ]);

        // 2. Aplicar Filtros (Usando los Scopes que creamos)
        $query->filtrarPorPaciente($dni_filtro_paciente)
              ->filtrarPorMedico($dni_filtro_medico)
              ->filtrarPorEspecialidad($especialidad_filtro);

        // 3. Restricciones por Rol (Seguridad)
        if ($request->routeIs('medico.*')) {
            if (!$usuario->medico) {
                return redirect()->route('medico.dashboard')->with('error', 'Perfil de médico no encontrado.');
            }
            $query->where('id_medico', $usuario->medico->id_medico);

        } elseif ($request->routeIs('paciente.*')) {
            $pacientes_ids = $usuario->pacientes->pluck('id_paciente');
            $query->whereIn('id_paciente', $pacientes_ids);
        }

        // 4. Lógica de Vistas (Tableros vs Listados)
        $hayRangoFechas = !empty($fecha_inicio) || !empty($fecha_fin);
        $esVistaDefault = ($estado_filtro === 'pendiente' && empty($fecha_filtro) && !$hayRangoFechas);

        $turnosHoy = collect();
        $turnosManana = collect();
        $turnosProximos = collect();
        $turnosPaginados = null;

        if ($esVistaDefault) {
            // VISTA DE TABLERO (Hoy, Mañana, Próximos)
            $turnosHoy = (clone $query)->where('estado', 'pendiente')
                ->whereDate('fecha', Carbon::today())->orderBy('hora')->paginate($perPage, ['*'], 'page_hoy')->withQueryString();

            $turnosManana = (clone $query)->where('estado', 'pendiente')
                ->whereDate('fecha', Carbon::tomorrow())->orderBy('hora')->paginate($perPage, ['*'], 'page_manana')->withQueryString();

            $turnosProximos = (clone $query)->where('estado', 'pendiente')
                ->whereDate('fecha', '>', Carbon::tomorrow())->orderBy('fecha')->orderBy('hora')
                ->paginate($perPage, ['*'], 'page_proximos')->withQueryString();
        } else {
            // VISTA DE LISTADO (Búsquedas y filtros específicos)
            $turnosPaginados = $query
                ->filtrarPorEstado($estado_filtro) // Usamos el Scope de estado
                ->filtrarPorFecha($fecha_filtro, $fecha_inicio, $fecha_fin) // Usamos el Scope de fecha
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

        if ($usuario->hasRole('Administrador')) {
            // Admin ve todos los pacientes
            $pacientes = Paciente::orderBy('apellido')->orderBy('nombre')->get();
        } elseif ($usuario->hasRole('Paciente')) {
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
    public function store(StoreTurnoRequest $request) // <--- ¡Mira el cambio aquí!
    {
        $usuario = Auth::user();

        // --- AQUÍ BORRAMOS LA VALIDACIÓN MANUAL ---
        // Laravel ya validó todo gracias a StoreTurnoRequest.
        // Si algo falla, ni siquiera entra a esta función.

        $fecha = Carbon::parse($request->fecha);
        $hora = $request->hora; 
        $id_medico = $request->id_medico;

        // Cargar el médico con sus horarios de trabajo para la validación lógica
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

            $turno_fin_carbon = (clone $turno_hora_carbon)->addMinutes(30); 

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
                               ->where('fecha', $fecha->toDateString())
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
            'fecha' => $fecha->toDateString(), 
            'hora' => $hora,
            'estado' => $request->estado ?? 'pendiente',
        ]);

        // Redireccionamiento dinámico
        if ($usuario->hasRole('Administrador')) {
            return redirect()->route('admin.turnos.index')->with('success', 'Turno agendado con éxito por el administrador.');
        } elseif ($usuario->hasRole('Paciente')) {
            return redirect()->route('paciente.turnos.index')->with('success', 'Turno agendado con éxito.');
        } else {
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
