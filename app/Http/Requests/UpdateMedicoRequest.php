<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Rol;

class UpdateMedicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(Rol::ADMINISTRADOR);
    }

    public function rules(): array
    {
        return [
            'tiempo_turno' => 'required|integer|min:5|max:120',
            'especialidades'           => 'required|array',
            'especialidades.*'         => 'exists:especialidades,id_especialidad',
            'horarios'                 => 'required|array',
            'horarios.*'               => 'array',
            'horarios.*.*.dia_semana'  => 'required',
            'horarios.*.*.hora_inicio' => 'required|date_format:H:i',
            'horarios.*.*.hora_fin'    => 'required|date_format:H:i|after:horarios.*.*.hora_inicio',
        ];
    }
}