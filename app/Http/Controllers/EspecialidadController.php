<?php

namespace App\Http\Controllers;

use App\Models\Especialidad; 
use Illuminate\Http\Request;

class EspecialidadController extends Controller
{
    /**
     * Display a listing of the resource.
     * Muestra una lista de todas las especialidades.
     */
    public function index()
    {
        $especialidades = Especialidad::all(); // Obtener todas las especialidades
        return view('admin.especialidades.index', compact('especialidades'));
    }

    /**
     * Show the form for creating a new resource.
     * Muestra el formulario para crear una nueva especialidad.
     */
    public function create()
    {
        return view('admin.especialidades.create');
    }

    /**
     * Store a newly created resource in storage.
     * Almacena una nueva especialidad en la base de datos.
     */
    public function store(Request $request)
    {
        // Validación de los datos del formulario
        $request->validate([
            'nombre_especialidad' => 'required|string|max:255|unique:especialidades',
        ]);

        // Crear la nueva especialidad
        Especialidad::create([
            'nombre_especialidad' => $request->nombre_especialidad,
        ]);

        return redirect()->route('admin.especialidades.index')->with('Completado', 'Especialidad creada exitosamente.');
    }

    /**
     * Display the specified resource.
     * Muestra una especialidad específica.
     */
    public function show(Especialidad $especialidade) // Laravel inyecta el modelo por el ID de la ruta
    {
        // Redirigir a la vista de edición directamente
        return redirect()->route('admin.especialidades.edit', $especialidade->id_especialidad);
    }

    /**
     * Show the form for editing the specified resource.
     * Muestra el formulario para editar una especialidad existente.
     */
    public function edit(Especialidad $especialidade) 
    {
        return view('admin.especialidades.edit', compact('especialidade'));
    }

    /**
     * Update the specified resource in storage.
     * Actualiza una especialidad existente en la base de datos.
     */
    public function update(Request $request, Especialidad $especialidade) 
    {
        // Validación de los datos del formulario (excluyendo el propio nombre para unique)
        $request->validate([
            'nombre_especialidad' => 'required|string|max:255|unique:especialidades,nombre_especialidad,' . $especialidade->id_especialidad . ',id_especialidad',
        ]);

        // Actualizar la especialidad
        $especialidade->update([
            'nombre_especialidad' => $request->nombre_especialidad,
        ]);

        return redirect()->route('admin.especialidades.index')->with('Completado', 'Especialidad actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     * Elimina una especialidad de la base de datos.
     */
    public function destroy(Especialidad $especialidade) 
    {
        try {
            $especialidade->delete();
            return redirect()->route('admin.especialidades.index')->with('Completado', 'Especialidad eliminada exitosamente.');
        } catch (\Exception $e) {
            // Manejo de errores si, por ejemplo, hay médicos asociados a esta especialidad
            // y la base de datos no permite la eliminación en cascada.
            return redirect()->route('admin.especialidades.index')->with('error', 'No se pudo eliminar la especialidad. Puede haber registros asociados.');
        }
    }
}