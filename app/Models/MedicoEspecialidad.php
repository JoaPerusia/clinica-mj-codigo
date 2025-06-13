<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicoEspecialidad extends Model
{
    protected $table = 'Medico_Especialidad';
    public $timestamps = false;

    public function medico()
    {
        return $this->belongsTo(Medico::class, 'id_medico');
    }

    public function especialidad()
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad');
    }
}
