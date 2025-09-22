<?php

namespace App\Http\Controllers;

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
        $diasSemana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        
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

            if ($usuario->hasRole('Medico')) {
                return back()->withInput()->with('error', 'El usuario seleccionado ya es un médico.');
            }

            $medicoRol = Rol::where('rol', 'Medico')->first();

            if (!$medicoRol) {
                DB::rollBack();
                return back()->withInput()->with('error', 'El rol "Medico" no fue encontrado.');
            }

            $usuario->roles()->attach($medicoRol->id_rol);

            $medico = Medico::create([
                'id_usuario' => $usuario->id_usuario,
            ]);

            $medico->especialidades()->sync($validatedData['especialidades']);

            // 2. Lógica para guardar los horarios SÓLO si existen
            // Utilizamos el método `has` de la request para verificar si el campo `horarios` fue enviado.
            if ($request->has('horarios')) {
                // Este bucle ahora solo se ejecuta si hay horarios en el formulario
                foreach ($validatedData['horarios'] as $dias) {
                    foreach ($dias as $horario) {
                        $medico->horariosTrabajo()->create($horario);
                    }
                }
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
    // En tu MedicoController.php

    public function update(Request $request, string $id)
    {
        $medico = Medico::findOrFail($id);

        $validatedData = $request->validate([
            'especialidades' => 'required|array',
            'especialidades.*' => 'exists:especialidades,id_especialidad',
            'horarios' => 'required|array',
            'horarios.*' => 'array',
            'horarios.*.*.dia_semana' => 'required|integer', 
            'horarios.*.*.hora_inicio' => 'required|date_format:H:i',
            'horarios.*.*.hora_fin' => 'required|date_format:H:i|after:horarios.*.*.hora_inicio',
        ]);

        DB::beginTransaction();
        try {
            // Guardar las especialidades y los horarios del médico
            $medico->especialidades()->sync($validatedData['especialidades']);
            $medico->horariosTrabajo()->delete();
            foreach ($validatedData['horarios'] as $dias) {
                foreach ($dias as $horario) {
                    $medico->horariosTrabajo()->create($horario);
                }
            }

            // Obtener la colección de los nuevos horarios del médico
            $nuevosHorarios = collect($validatedData['horarios'])->flatten(1);

            // Lógica de cancelación y notificación de turnos en conflicto
            $turnosSuperpuestos = Turno::where('id_medico', $medico->id_medico)
                ->where('estado', 'Pendiente')
                ->where('fecha', '>=', Carbon::today())
                ->get();
            
            $turnosAfectados = 0;
            foreach ($turnosSuperpuestos as $turno) {
                $esValido = false;

                // 1. Validar por especialidad: El turno debe tener una de las nuevas especialidades del médico
                $especialidadValida = $medico->especialidades()->where('id_especialidad', $turno->id_especialidad)->exists();
                if (!$especialidadValida) {
                    $motivo = 'La especialidad del turno ya no corresponde con el médico.';
                }

                // 2. Validar por horario de trabajo: El turno debe caer en uno de los nuevos horarios del médico
                if ($especialidadValida) {
                    $diaSemanaTurno = Carbon::parse($turno->fecha)->dayOfWeek;
                    $horaTurno = Carbon::parse($turno->hora);

                    foreach ($nuevosHorarios as $nuevoHorario) {
                        if ($nuevoHorario['dia_semana'] == $diaSemanaTurno) {
                            $horaInicio = Carbon::parse($nuevoHorario['hora_inicio']);
                            $horaFin = Carbon::parse($nuevoHorario['hora_fin']);

                            if ($horaTurno->between($horaInicio, $horaFin)) {
                                $esValido = true;
                                break;
                            }
                        }
                    }

                    if (!$esValido) {
                        $motivo = 'El turno ya no coincide con el nuevo horario de trabajo del médico.';
                    }
                }

                // Si el turno no es válido por especialidad o por horario, lo cancelamos
                if (!$esValido) {
                    $turno->estado = 'Cancelado';
                    $turno->save();
                    $turnosAfectados++;
                    
                    // Enviar notificación por correo
                    try {
                        $turno->load('paciente.usuario', 'medico.usuario');
                        Mail::to($turno->paciente->usuario->email)
                            ->send(new TurnoCanceladoMailable($turno, $motivo));
                    } catch (\Exception $e) {
                        Log::error('Error al enviar correo de cancelación de turno por edición de médico: ' . $e->getMessage());
                    }
                }
            }
            
            DB::commit();

            $mensaje = 'Médico actualizado correctamente.';
            if ($turnosAfectados > 0) {
                $mensaje .= " Se han cancelado {$turnosAfectados} turno(s) superpuesto(s) debido a los cambios de agenda.";
            }

            return redirect()->route('admin.medicos.index')->with('success', $mensaje);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Hubo un error al actualizar el médico: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $medico = Medico::findOrFail($id);
        $medico->delete();

        return redirect()->route('admin.medicos.index')->with('success', 'Médico eliminado correctamente.');
    }
}