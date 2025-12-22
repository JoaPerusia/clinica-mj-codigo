<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Models\Rol;
use App\Models\User;

class StoreTurnoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Permitimos que cualquiera autenticado intente crear (la lógica de roles la validamos abajo o en middleware)
        return true; 
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $usuario = $this->user();

        return [
            'id_paciente' => [
                'required', 
                'exists:pacientes,id_paciente', 
                function ($attribute, $value, $fail) use ($usuario) {
                    // Si el usuario es un paciente, verificar que el id_paciente seleccionado le pertenezca
                    // Usamos la relación 'pacientes' que ya tienes definida en tu modelo User
                    if ($usuario->hasRole(Rol::PACIENTE)) {
                        if (!$usuario->pacientes->contains('id_paciente', $value)) {
                            $fail('El paciente seleccionado no te pertenece.');
                        }
                    }
                }
            ],
            'id_medico' => 'required|exists:medicos,id_medico',
            'fecha'     => 'required|date|after_or_equal:today', // La fecha no puede ser en el pasado
            'hora'      => 'required|date_format:H:i', // Formato de hora HH:MM
            'estado'    => 'nullable|in:pendiente,realizado,cancelado',
        ];
    }

    public function messages()
    {
        return [
            'id_paciente.required' => 'Debes seleccionar un paciente.',
            'id_medico.required'   => 'Debes seleccionar un médico.',
            'fecha.after_or_equal' => 'No puedes reservar turnos en el pasado.',
        ];
    }
}