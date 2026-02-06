<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Models\Rol;
use App\Models\User;
use App\Models\HorarioMedico;
use App\Models\MedicoHorarioFecha;
use Carbon\Carbon;

class StoreTurnoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        $usuario = $this->user();

        return [
            'id_paciente' => [
                'required', 
                'exists:pacientes,id_paciente', 
                function ($attribute, $value, $fail) use ($usuario) {
                    if ($usuario->hasRole(Rol::PACIENTE)) {
                        if (!$usuario->pacientes->contains('id_paciente', $value)) {
                            $fail('El paciente seleccionado no te pertenece.');
                        }
                    }
                }
            ],
            'id_medico' => 'required|exists:medicos,id_medico',
            'fecha' => [
                'required',
                'date',
                'after_or_equal:today',
                function ($attribute, $value, $fail) {
                    $idMedico = $this->input('id_medico');
                    if (!$idMedico) return;

                    $fecha = Carbon::parse($value);
                    $diaSemana = $fecha->dayOfWeek;

                    // 1. ¿Trabaja ese día de la semana?
                    $atiendeSemanal = HorarioMedico::where('id_medico', $idMedico)
                        ->where('dia_semana', $diaSemana)
                        ->exists();

                    // 2. ¿Tiene una fecha puntual habilitada?
                    $atiendePuntual = MedicoHorarioFecha::where('id_medico', $idMedico)
                        ->where('fecha', $value)
                        ->exists();

                    if (!$atiendeSemanal && !$atiendePuntual) {
                        $fail('El médico no tiene agenda disponible para la fecha seleccionada.');
                    }
                }
            ],
            'hora' => 'required|date_format:H:i',
            'estado' => 'nullable|in:pendiente,realizado,cancelado',
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