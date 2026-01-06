<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Rol;

class StoreBloqueoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Solo los administradores pueden crear bloqueos. 
        // Esto ya lo protege el Middleware en la ruta, pero por seguridad doble:
        return $this->user()->hasRole(Rol::ADMINISTRADOR);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id_medico'    => 'required|exists:medicos,id_medico',
            'fecha_inicio' => 'required|date|after_or_equal:today',
            'fecha_fin'    => 'required|date|after_or_equal:fecha_inicio',
            'hora_inicio'  => 'nullable|date_format:H:i',
            // Si hay hora de inicio, la hora fin es obligatoria y debe ser posterior
            'hora_fin'     => 'nullable|required_with:hora_inicio|date_format:H:i|after:hora_inicio',
            'motivo'       => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'fecha_inicio.after_or_equal' => 'El bloqueo no puede comenzar en el pasado.',
            'fecha_fin.after_or_equal'    => 'La fecha de fin no puede ser anterior a la de inicio.',
            'hora_fin.after'              => 'La hora de fin debe ser posterior a la de inicio.',
        ];
    }
}