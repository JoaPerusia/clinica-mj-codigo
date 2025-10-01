<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paciente extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'pacientes';

    protected $primaryKey = 'id_paciente';

    protected $dates = ['deleted_at']; 

    protected $fillable = [
    'nombre',
    'apellido',
    'dni',
    'fecha_nacimiento',
    'telefono',
    'obra_social',
    'id_usuario',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function turnos()
    {
        return $this->hasMany(Turno::class, 'id_paciente');
    }
}
