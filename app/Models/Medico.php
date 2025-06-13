<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medico extends Model
{
    protected $table = 'Medicos';

    public function especialidad()
    {
        return $this->belongsTo(Especialidad::class, 'id_especialidad');
    }

    public function turnos()
    {
        return $this->hasMany(Turno::class, 'id_medico');
    }

    public function especialidades()
    {
        return $this->belongsToMany(Especialidad::class, 'Medico_Especialidad', 'id_medico', 'id_especialidad');
    }

    public function bloqueos()
    {
        return $this->hasMany(Bloqueo::class, 'id_medico');
    }
}
 
