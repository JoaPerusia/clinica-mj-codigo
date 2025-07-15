<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Medico extends Model
{
    use HasFactory;

    protected $table = 'medicos';

    protected $primaryKey = 'id_medico';

    protected $fillable = [
        'nombre',
        'apellido',
        'horario_disponible',
        'id_usuario', 
    ];

    // Relación con el usuario asociado al médico
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    public function turnos()
    {
        return $this->hasMany(Turno::class, 'id_medico');
    }

    public function especialidades()
    {
        return $this->belongsToMany(Especialidad::class, 'medico_especialidad', 'id_medico', 'id_especialidad');
    }

    public function bloqueos()
    {
        return $this->hasMany(Bloqueo::class, 'id_medico');
    }

    public function horariosTrabajo()
    {
        return $this->hasMany(HorarioMedico::class, 'id_medico', 'id_medico');
    }
}