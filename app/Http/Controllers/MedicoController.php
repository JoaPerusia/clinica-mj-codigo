<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicoRequest;
use App\Http\Requests\UpdateMedicoRequest;
use App\Services\MedicoService;
use App\Models\Medico;
use App\Models\Especialidad;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Http\Request;

class MedicoController extends Controller
{
    protected $medicoService;

    public function __construct(MedicoService $medicoService)
    {
        $this->medicoService = $medicoService;
    }

    public function index(Request $request)
    {
        $dni_filtro = $request->input('dni_filtro');
        
        $query = Medico::with('especialidades', 'horariosTrabajo', 'usuario');

        if (!empty($dni_filtro)) {
            $query->whereHas('usuario', function ($q) use ($dni_filtro) {
                $q->where('dni', 'like', "%{$dni_filtro}%")
                  ->orWhere('nombre', 'like', "%{$dni_filtro}%")
                  ->orWhere('apellido', 'like', "%{$dni_filtro}%");
            });
        }

        $medicos = $query->paginate(10)->withQueryString();

        return view('medicos.index', compact('medicos'));
    }

    public function create()
    {
        $especialidades = Especialidad::all();
        // Usuarios 'Paciente' candidatos a ser médico
        $usuarios = User::whereHas('roles', function ($q) {
            $q->where('rol', Rol::PACIENTE);
        })->get();
        
        $diasSemana = ['domingo', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
        
        return view('medicos.create', compact('especialidades', 'usuarios', 'diasSemana'));
    }

    public function store(StoreMedicoRequest $request)
    {
        try {
            $this->medicoService->createMedico($request->validated());
            
            return redirect()->route('admin.medicos.index')
                             ->with('success', 'Médico gestionado correctamente.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $medico = Medico::with('especialidades', 'horariosTrabajo', 'usuario')->findOrFail($id);
        $bloqueos = $medico->bloqueos()->orderBy('fecha_inicio', 'desc')->get();
        $especialidades = Especialidad::all();
        $diasSemana = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        
        return view('medicos.edit', compact(Rol::MEDICO, 'especialidades', 'diasSemana', 'bloqueos'));
    }

    public function update(UpdateMedicoRequest $request, $id)
    {
        try {
            $medico = Medico::findOrFail($id);
            $turnosAfectados = $this->medicoService->updateMedico($medico, $request->validated());

            $mensaje = 'Médico actualizado correctamente.';
            if ($turnosAfectados > 0) {
                $mensaje .= " Se cancelaron {$turnosAfectados} turno(s) por conflicto de horario.";
            }

            return redirect()->route('admin.medicos.index')->with('success', $mensaje);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $medico = Medico::findOrFail($id);
            $cancelados = $this->medicoService->deleteMedico($medico);

            $mensaje = 'Médico eliminado correctamente.';
            if ($cancelados > 0) {
                $mensaje .= " Se cancelaron {$cancelados} turno(s) futuros.";
            }

            return redirect()->route('admin.medicos.index')->with('success', $mensaje);
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo eliminar: ' . $e->getMessage());
        }
    }
}