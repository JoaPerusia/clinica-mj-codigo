<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEspecialidadRequest;
use App\Http\Requests\UpdateEspecialidadRequest;
use App\Models\Especialidad; 
use Illuminate\Http\Request;

class EspecialidadController extends Controller
{
    public function index()
    {
        $especialidades = Especialidad::paginate(10);
        return view('admin.especialidades.index', compact('especialidades'));
    }

    public function create()
    {
        return view('admin.especialidades.create');
    }

    public function store(StoreEspecialidadRequest $request)
    {
        Especialidad::create($request->validated());
        return redirect()->route('admin.especialidades.index')->with('success', 'Especialidad creada exitosamente.');
    }

    public function edit(Especialidad $especialidade) 
    {
        return view('admin.especialidades.edit', compact('especialidade'));
    }

    public function update(UpdateEspecialidadRequest $request, Especialidad $especialidade) 
    {
        $especialidade->update($request->validated());
        return redirect()->route('admin.especialidades.index')->with('success', 'Especialidad actualizada exitosamente.');
    }

    public function destroy(Especialidad $especialidade) 
    {
        try {
            $especialidade->delete();
            return redirect()->route('admin.especialidades.index')->with('success', 'Especialidad eliminada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('admin.especialidades.index')->with('error', 'No se puede eliminar: tiene médicos asociados.');
        }
    }
}