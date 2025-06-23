<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    
    protected $table = 'Usuarios';

    
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol');
    }

    public function paciente()
    {
        return $this->hasOne(Paciente::class, 'id_usuario');
    }
}
