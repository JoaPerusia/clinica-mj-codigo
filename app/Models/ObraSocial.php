<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Medico;

class ObraSocial extends Model
{
    use HasFactory;

    protected $table = 'obras_sociales';
    protected $primaryKey = 'id_obra_social';

    protected $fillable = ['nombre', 'siglas', 'habilitada'];

    public function pacientes()
    {
        return $this->hasMany(Paciente::class, 'id_obra_social', 'id_obra_social');
    }

    public function medicos()
    {
        return $this->belongsToMany(Medico::class, 'medico_obra_social', 'id_obra_social', 'id_medico')
                    ->withPivot('costo', 'instrucciones')
                    ->withTimestamps();
    }
}