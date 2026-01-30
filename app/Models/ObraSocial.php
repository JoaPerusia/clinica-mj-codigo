<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObraSocial extends Model
{
    use HasFactory;

    protected $table = 'obras_sociales';
    protected $primaryKey = 'id_obra_social';

    protected $fillable = ['nombre', 'siglas', 'habilitada'];

    // Una obra social tiene muchos pacientes
    public function pacientes()
    {
        return $this->hasMany(Paciente::class, 'id_obra_social', 'id_obra_social');
    }
}