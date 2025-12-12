<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('Administrador');
    }

    public function rules(): array
    {
        return [
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