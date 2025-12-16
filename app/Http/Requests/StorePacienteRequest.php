<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Rol;

class StorePacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Admin o Paciente pueden crear (el Paciente crea familiares)
        return $this->user()->hasRole(Rol::ADMINISTRADOR) || $this->user()->hasRole(Rol::PACIENTE);
    }

    public function rules(): array
    {
        $rules = [
            'nombre'           => 'required|string|max:255',
            'apellido'         => 'required|string|max:255',
            'dni'              => 'required|string|max:20|unique:pacientes,dni',
            'fecha_nacimiento' => 'required|date',
            'telefono'         => 'nullable|string|max:20',
            'obra_social'      => 'nullable|string|max:255',
        ];

        // Si es Admin, el ID de usuario es obligatorio (lo selecciona de una lista)
        if ($this->user()->hasRole(Rol::ADMINISTRADOR)) {
            $rules['id_usuario'] = 'required|exists:usuarios,id_usuario';
        }

        return $rules;
    }
}