<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'Roles';

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'id_rol');
    }
}
