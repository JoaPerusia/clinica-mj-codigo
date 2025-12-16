<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Rol;

class StoreEspecialidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(Rol::ADMINISTRADOR);
    }

    public function rules(): array
    {
        return [
            'nombre_especialidad' => 'required|string|max:255|unique:especialidades',
        ];
    }
}