<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Turno;
use Illuminate\Support\Facades\Auth;

class TurnoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $usuario = Auth::user();

        // Admin ve todos
        if ($usuario->id_rol == 1) {
            $turnos = Turno::with('paciente', 'medico')->get();
        }
        // Médico ve solo sus turnos
        elseif ($usuario->id_rol == 2) {
            $turnos = Turno::where('id_medico', $usuario->id)
                        ->with('paciente', 'medico')
                        ->get();
        }
        // Paciente ve solo sus turnos
        else {
            $pacientes = $usuario->pacientes->pluck('id');
            $turnos = Turno::whereIn('id_paciente', $pacientes)
                        ->with('paciente', 'medico')
                        ->get();
        }

        return view('turnos.index', compact('turnos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
