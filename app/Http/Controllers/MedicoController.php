<?php

namespace App\Http\Controllers;

use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use App\Models\Medico;
use App\Models\Especialidad;
use App\Models\HorarioMedico;
use App\Models\User;
use App\Models\Turno;
use App\Models\Rol;
use App\Mail\TurnoCanceladoMailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail; 
use Illuminate\Support\Facades\Log; 
use Carbon\Carbon;

class MedicoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $dni_filtro = $request->input('dni_filtro');
        $perPage = 10;

        // Cargar paciente y médico, además de las especialidades del médico
        $query = Medico::with('especialidades', 'horariosTrabajo', 'usuario');

        // Aplicar filtro por DNI si se ha proporcionado
        if ($dni_filtro) {
            $query->whereHas('usuario', function ($q) use ($dni_filtro) {
                $q->where('dni', 'like', '%' . $dni_filtro . '%');
            });
        }
        
        $medicos = $query->paginate($perPage)->withQueryString();

        return view('medicos.index', compact('medicos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $especialidades = Especialidad::all();
        // Carga solo a los usuarios con el rol de Paciente, ya que se los convertirá en médicos
        $usuarios = User::whereHas('roles', function ($query) {
            $query->where('rol', 'Paciente');
        })->get();
        $diasSemana = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
        
        return view('medicos.create', compact('especialidades', 'usuarios', 'diasSemana'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'id_usuario' => 'required|exists:usuarios,id_usuario', 
            'especialidades' => 'required|array',
            'especialidades.*' => 'exists:especialidades,id_especialidad',
            'horarios' => 'nullable|array',
            'horarios.*' => 'array',
            'horarios.*.*.dia_semana' => 'required|string', 
            'horarios.*.*.hora_inicio' => 'required|date_format:H:i',
            'horarios.*.*.hora_fin' => 'required|date_format:H:i|after:horarios.*.*.hora_inicio',
        ]);

        try {
            DB::beginTransaction();

            $usuario = User::findOrFail($validatedData['id_usuario']);

            // Buscar médico incluso si está soft-deleted
            $medicoExistente = Medico::withTrashed()
                ->where('id_usuario', $usuario->id_usuario)
                ->first();

            if ($medicoExistente) {
                if ($medicoExistente->trashed()) {
                    $medicoExistente->restore();
                    $medicoExistente->especialidades()->sync($validatedData['especialidades']);

                    if ($request->has('horarios')) {
                        $medicoExistente->horariosTrabajo()->delete(); // limpiar anteriores
                        foreach ($validatedData['horarios'] as $dias) {
                            foreach ($dias as $horario) {
                                $medicoExistente->horariosTrabajo()->create($horario);
                            }
                        }
                    }

                    // Asegurar que tenga el rol
                    if (!$usuario->hasRole('Medico')) {
                        $medicoRol = Rol::where('rol', 'Medico')->first();
                        if (!$medicoRol) {
                            DB::rollBack();
                            return back()->withInput()->with('error', 'El rol "Medico" no fue encontrado.');
                        }
                        $usuario->roles()->attach($medicoRol->id_rol);
                    }

                    DB::commit();
                    return redirect()->route('admin.medicos.index')->with('success', 'Médico restaurado y actualizado correctamente.');
                }

                // Si ya existe activo
                return back()->withInput()->with('error', 'El usuario ya está registrado como médico.');
            }

            // Crear nuevo médico
            $medico = Medico::create([
                'id_usuario' => $usuario->id_usuario,
            ]);

            $medico->especialidades()->sync($validatedData['especialidades']);

            if ($request->has('horarios')) {
                foreach ($validatedData['horarios'] as $dias) {
                    foreach ($dias as $horario) {
                        $medico->horariosTrabajo()->create($horario);
                    }
                }
            }

            // Asignar rol si no lo tiene
            if (!$usuario->hasRole('Medico')) {
                $medicoRol = Rol::where('rol', 'Medico')->first();
                if (!$medicoRol) {
                    DB::rollBack();
                    return back()->withInput()->with('error', 'El rol "Medico" no fue encontrado.');
                }
                $usuario->roles()->attach($medicoRol->id_rol);
            }

            DB::commit();
            return redirect()->route('admin.medicos.index')->with('success', 'Médico creado y rol asignado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Hubo un error al crear el médico: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // No implementado para esta versión
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Carga el médico y sus relaciones 'especialidades', 'horariosTrabajo' y 'usuario'.
        // Aquí cargamos 'bloqueos' para que esté disponible en la vista.
        $medico = Medico::with('especialidades', 'horariosTrabajo', 'usuario')->findOrFail($id);
        
        // Obtener los bloqueos del médico de forma separada y ordenados para la visualización.
        $bloqueos = $medico->bloqueos()->orderBy('fecha_inicio', 'desc')->get();
        
        $especialidades = Especialidad::all();
        $diasSemana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        
        // Pasar la variable $bloqueos a la vista.
        return view('medicos.edit', compact('medico', 'especialidades', 'diasSemana', 'bloqueos'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $medico = Medico::findOrFail($id);

        $validatedData = $request->validate([
            'especialidades' => 'required|array',
            'especialidades.*' => 'exists:especialidades,id_especialidad',
            'horarios' => 'required|array',
            'horarios.*' => 'array',
            'horarios.*.*.dia_semana'  => 'required',
            'horarios.*.*.hora_inicio' => 'required|date_format:H:i',
            'horarios.*.*.hora_fin'    => 'required|date_format:H:i|after:horarios.*.*.hora_inicio',
        ]);

        DB::beginTransaction();

        try {
            // Normalizar horarios
            $rawHorarios = collect($validatedData['horarios'])->flatten(1)->values();

            $toDayIndex = function ($val) {
                if (is_numeric($val)) {
                    $n = (int) $val;
                    if ($n >= 0 && $n <= 6) return $n;
                    if ($n >= 1 && $n <= 7) return $n % 7;
                }
                $map = [
                    'domingo' => 0, 'lunes' => 1, 'martes' => 2,
                    'miercoles' => 3, 'miércoles' => 3,
                    'jueves' => 4, 'viernes' => 5,
                    'sabado' => 6, 'sábado' => 6,
                ];
                return $map[mb_strtolower(trim($val))] ?? null;
            };

            $nuevosHorarios = $rawHorarios->map(fn($h) => [
                    'dia_semana'  => $toDayIndex($h['dia_semana'] ?? null),
                    'hora_inicio' => $h['hora_inicio'],
                    'hora_fin'    => $h['hora_fin'],
                ])
                ->filter(fn($h) => $h['dia_semana'] !== null)
                ->values();

            // Normalizar especialidades a ints y detectar cambio
            $nuevasEspecialidades = collect($validatedData['especialidades'])
                ->map(fn($id) => (int) $id)
                ->sort()
                ->values()
                ->toArray();

            $especialidadesOriginales = $medico->especialidades
                ->pluck('id_especialidad')
                ->sort()
                ->values()
                ->toArray();

            $especialidadesCambiaron = $especialidadesOriginales !== $nuevasEspecialidades;

            // Traer turnos pendientes
            $turnosPendientes = $medico->turnos()
                ->where('estado', 'Pendiente')
                ->where('fecha', '>=', Carbon::today())
                ->get();

            $turnosAfectados = 0;

            // Validar cada turno
            foreach ($turnosPendientes as $turno) {
                $esValido = true;
                $motivo = '';

                // 1) Si cambiaron especialidades, cancelo todos
                if ($especialidadesCambiaron) {
                    $esValido = false;
                    $motivo  = 'El médico cambió sus especialidades.';
                }

                // 2) Si no, chequear horarios
                if ($esValido) {
                    $fechaTurno = Carbon::parse($turno->fecha);
                    $horaTurno  = Carbon::parse($turno->hora)
                        ->setDate($fechaTurno->year, $fechaTurno->month, $fechaTurno->day);

                    $coincide = false;
                    foreach ($nuevosHorarios as $nh) {
                        if ($nh['dia_semana'] === $fechaTurno->dayOfWeek) {
                            $inicio = Carbon::createFromFormat('H:i', $nh['hora_inicio'])
                                ->setDate($fechaTurno->year, $fechaTurno->month, $fechaTurno->day);
                            $fin = Carbon::createFromFormat('H:i', $nh['hora_fin'])
                                ->setDate($fechaTurno->year, $fechaTurno->month, $fechaTurno->day);

                            if ($horaTurno->between($inicio, $fin, true)) {
                                $coincide = true;
                                break;
                            }
                        }
                    }

                    if (! $coincide) {
                        $esValido = false;
                        $motivo  = 'El turno ya no coincide con el nuevo horario del médico.';
                    }
                }

                // 3) Cancelar si corresponde
                if (! $esValido) {
                    $turno->estado = 'Cancelado';
                    $turno->save();
                    $turnosAfectados++;

                    try {
                        $turno->load('paciente.usuario', 'medico.usuario');
                        Mail::to($turno->paciente->usuario->email)
                            ->send(new TurnoCanceladoMailable($turno, $motivo));
                    } catch (\Exception $e) {
                        Log::error("Error al enviar correo de cancelación turno ID {$turno->id_turno}: {$e->getMessage()}");
                    }
                }
            }

            // 4) Solo después sincronizar y recrear horarios
            $medico->especialidades()->sync($nuevasEspecialidades);
            $medico->horariosTrabajo()->delete();
            foreach ($nuevosHorarios as $h) {
                $medico->horariosTrabajo()->create($h);
            }

            DB::commit();

            $mensaje = 'Médico actualizado correctamente.';
            if ($turnosAfectados > 0) {
                $mensaje .= " Se han cancelado {$turnosAfectados} turno(s) debido a los cambios.";
            }

            return redirect()
                ->route('admin.medicos.index')
                ->with('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error FATAL al actualizar médico y turnos: ' . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Hubo un error al actualizar el médico: ' . $e->getMessage());
        }
    }


    public function destroy(string $id)
    {
        $medico = Medico::with('usuario')->findOrFail($id);

        DB::beginTransaction();
        try {
            // 1) Cancelar sus turnos pendientes o futuros y contar cuántos
            $turnosCancelados = $medico->turnos()
                ->where('estado', 'Pendiente')
                ->where('fecha', '>=', Carbon::today())
                ->update(['estado' => 'Cancelado']);

            // 2) Soft delete del médico
            $medico->delete();

            // 3) Quitar el rol “Medico” del usuario
            $rolMedico = Rol::where('rol', 'Medico')->first();
            if ($rolMedico) {
                $medico->usuario->roles()->detach($rolMedico->id_rol);
            }

            DB::commit();

            // 4) Armar mensaje dinámico
            $mensaje = 'Médico eliminado correctamente.';
            if ($turnosCancelados > 0) {
                $mensaje .= " Se han cancelado {$turnosCancelados} turno(s) futuros.";
            }

            return redirect()
                ->route('admin.medicos.index')
                ->with('success', $mensaje);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'No se pudo eliminar el médico: ' . $e->getMessage());
        }
    }

}