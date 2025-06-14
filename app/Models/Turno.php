<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    protected $table = 'Turnos';

    protected $fillable = [
        'fecha',
        'hora',
        'estado',
        'id_paciente',
        'id_medico',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function medico()
    {
        return $this->belongsTo(Medico::class, 'id_medico');
    }
}

