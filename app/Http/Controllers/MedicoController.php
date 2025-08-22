<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Medico;
use App\Models\Especialidad;
use App\Models\HorarioMedico;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MedicoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $medicos = Medico::with('especialidades', 'horariosTrabajo', 'usuario')
                          ->paginate(10);
        return view('medicos.index', compact('medicos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $especialidades = Especialidad::all();
        $usuarios = User::where('id_rol', 2)->get(); // Solo usuarios con rol de médico
        $diasSemana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        
        return view('medicos.create', compact('especialidades', 'usuarios', 'diasSemana'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'id_usuario' => 'required|exists:usuarios,id_usuario|unique:medicos,id_usuario',
            'especialidades' => 'required|array',
            'especialidades.*' => 'exists:especialidades,id_especialidad',
            'horarios' => 'required|array',
            'horarios.*.dia_semana' => 'required|string',
            'horarios.*.hora_inicio' => 'required|date_format:H:i',
            'horarios.*.hora_fin' => 'required|date_format:H:i|after:horarios.*.hora_inicio',
        ]);

        try {
            DB::beginTransaction();

            $medico = Medico::create([
                'nombre' => $validatedData['nombre'],
                'apellido' => $validatedData['apellido'],
                'id_usuario' => $validatedData['id_usuario'],
            ]);

            // Sincronizar especialidades
            $medico->especialidades()->sync($validatedData['especialidades']);

            // Guardar horarios
            foreach ($validatedData['horarios'] as $horario) {
                $medico->horariosTrabajo()->create($horario);
            }

            DB::commit();

            return redirect()->route('admin.medicos.index')->with('success', 'Médico creado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Hubo un error al crear el médico: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $medico = Medico::with('especialidades', 'horariosTrabajo')->findOrFail($id);
        $especialidades = Especialidad::all();
        $diasSemana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        
        return view('medicos.edit', compact('medico', 'especialidades', 'diasSemana'));
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
            'horarios.*.dia_semana' => 'required|string',
            'horarios.*.hora_inicio' => 'required|date_format:H:i',
            'horarios.*.hora_fin' => 'required|date_format:H:i|after:horarios.*.hora_inicio',
        ]);

        try {
            DB::beginTransaction();

            // Sincronizar especialidades
            $medico->especialidades()->sync($validatedData['especialidades']);

            // Eliminar horarios existentes y guardar los nuevos
            $medico->horariosTrabajo()->delete();
            foreach ($validatedData['horarios'] as $horario) {
                $medico->horariosTrabajo()->create($horario);
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