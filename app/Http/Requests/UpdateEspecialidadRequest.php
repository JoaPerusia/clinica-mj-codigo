<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Rol;

class UpdateEspecialidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(Rol::ADMINISTRADOR);
    }

    public function rules(): array
    {
        $id = $this->route('especialidade')->id_especialidad; // Laravel binding usa el nombre del parametro en ruta
        
        return [
            'nombre_especialidad' => 'required|string|max:255|unique:especialidades,nombre_especialidad,' . $id . ',id_especialidad',
        ];
    }
}