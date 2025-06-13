<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Especialidad extends Model
{
    protected $table = 'Especialidades';

    public function medicos()
    {
        return $this->hasMany(Medico::class, 'id_especialidad');
    }
}
