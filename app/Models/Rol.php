<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Rol extends Model
{
    use HasFactory;
    protected $table = 'roles';
    protected $primaryKey = 'id_rol';
    protected $fillable = [
        'rol',
    ];

    public function usuarios()
    {
        // Asegúrate de que la clave foránea y la clave local sean correctas
        return $this->hasMany(User::class, 'id_rol', 'id_rol');
    }
}