<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Rol;

class UpdatePacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Admin siempre puede. Paciente solo si es el dueño (se valida en el controller o aquí si pasas el modelo)
        // Para simplificar, dejamos true y que el controller verifique propiedad, 
        // o mejor aún: confiamos en que el Controller ya filtró el acceso en edit/update.
        return true; 
    }

    public function rules(): array
    {
        // Reglas para el Administrador
        if ($this->user()->hasRole(Rol::ADMINISTRADOR)) {
            $id = $this->route('paciente');
            return [
                'nombre'           => 'required|string|max:255',
                'apellido'         => 'required|string|max:255',
                'telefono'         => 'nullable|string|max:20',
                'id_obra_social'   => 'required|exists:obras_sociales,id_obra_social',
                'dni'              => 'required|string|max:20|unique:pacientes,dni,' . $id . ',id_paciente',
                'fecha_nacimiento' => 'required|date',
                'id_usuario'       => 'required|exists:usuarios,id_usuario',
            ];
        }

        // Reglas para el Paciente (Solo teléfono)
        return [
            'telefono' => 'nullable|string|max:20',
        ];
    }
}