<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ObraSocial;

class Medico extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'medicos';

    protected $primaryKey = 'id_medico';

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'nombre',
        'apellido',
        'horario_disponible',
        'id_usuario', 
        'tiempo_turno',
        'precio_particular',
    ];

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

    public function obrasSociales()
    {
        return $this->belongsToMany(ObraSocial::class, 'medico_obra_social', 'id_medico', 'id_obra_social')
                    ->withPivot('instrucciones')
                    ->withTimestamps();
    }

    public function horariosFechas()
    {
        return $this->hasMany(MedicoHorarioFecha::class, 'id_medico');
    }
}