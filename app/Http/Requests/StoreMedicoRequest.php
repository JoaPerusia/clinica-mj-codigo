<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Rol;

class StoreMedicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(Rol::ADMINISTRADOR);
    }

    public function rules(): array
    {
        return [
            'id_usuario'               => 'required|exists:usuarios,id_usuario',
            'tiempo_turno' => 'required|integer|min:5|max:120',
            'especialidades'           => 'required|array',
            'especialidades.*'         => 'exists:especialidades,id_especialidad',
            'horarios'                 => 'nullable|array',
            'horarios.*'               => 'array',
            'horarios.*.*.dia_semana'  => 'required|string', 
            'horarios.*.*.hora_inicio' => 'required|date_format:H:i',
            'horarios.*.*.hora_fin'    => 'required|date_format:H:i|after:horarios.*.*.hora_inicio',
            'fechas_nuevas' => ['nullable', 'array'],
            'fechas_nuevas.*.fecha'       => ['required', 'date'],
            'fechas_nuevas.*.hora_inicio' => ['required', 'date_format:H:i'],
            'fechas_nuevas.*.hora_fin'    => ['required', 'date_format:H:i', 'after:fechas_nuevas.*.hora_inicio'],
        ];
    }
}