<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Especialidad extends Model
{
    use HasFactory;

    protected $table = 'especialidades'; 
    protected $primaryKey = 'id_especialidad'; 

    protected $fillable = [
        'nombre_especialidad',
    ];

    /**
     * Relación N:N con Medicos a través de Medico_especialidad
     */
    public function medicos()
    {
        return $this->belongsToMany(Medico::class, 'medico_especialidad', 'id_especialidad', 'id_medico');
    }
}