<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Medico;
use App\Models\Especialidad;
use App\Models\HorarioMedico;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
        // Carga el médico y sus relaciones 'especialidades' y 'horariosTrabajo'
        $medico = Medico::with('especialidades', 'horariosTrabajo', 'usuario')->findOrFail($id);
        $especialidades = Especialidad::all();
        $diasSemana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        
        return view('medicos.edit', compact('medico', 'especialidades', 'diasSemana'));
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
            'horarios.*.*.dia_semana' => 'required|string', 
            'horarios.*.*.hora_inicio' => 'required|date_format:H:i',
            'horarios.*.*.hora_fin' => 'required|date_format:H:i|after:horarios.*.*.hora_inicio',
        ]);

        try {
            DB::beginTransaction();

            $medico->especialidades()->sync($validatedData['especialidades']);

            $medico->horariosTrabajo()->delete();
            foreach ($validatedData['horarios'] as $dias) {
                foreach ($dias as $horario) {
                    $medico->horariosTrabajo()->create($horario);
                }
            }

            DB::commit();

            return redirect()->route('admin.medicos.index')->with('success', 'Médico actualizado correctamente.');
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