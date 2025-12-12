<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        // Reglas para el Administrador (Todo editable)
        if ($this->user()->hasRole('Administrador')) {
            // Obtenemos el ID del paciente de la ruta para ignorar su propio DNI en la validación unique
            $id = $this->route('paciente'); // Asegúrate que en la ruta se llame 'paciente'

            return [
                'nombre'           => 'required|string|max:255',
                'apellido'         => 'required|string|max:255',
                'telefono'         => 'nullable|string|max:20',
                'obra_social'      => 'required|string|max:255',
                'dni'              => 'required|string|max:20|unique:pacientes,dni,' . $id . ',id_paciente',
                'fecha_nacimiento' => 'required|date',
                'id_usuario'       => 'required|exists:usuarios,id_usuario',
            ];
        }

        // Reglas para el Paciente (Solo teléfono editable)
        return [
            'telefono' => 'nullable|string|max:20',
        ];
    }
}