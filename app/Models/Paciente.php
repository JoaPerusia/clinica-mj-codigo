<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    protected $fillable = [
    'nombre',
    'apellido',
    'dni',
    'fecha_nacimiento',
    'obra_social',
    'id_usuario',
    ];

    protected $table = 'Pacientes';

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function turnos()
    {
        return $this->hasMany(Turno::class, 'id_paciente');
    }
}
