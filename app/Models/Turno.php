<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; 

class Turno extends Model
{
    use HasFactory; /

    protected $table = 'turnos'; 
    
    protected $primaryKey = 'id_turno'; 

    protected $fillable = [
        'fecha',
        'hora',
        'estado',
        'id_paciente',
        'id_medico',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente'); 
    }

    public function medico()
    {
        return $this->belongsTo(Medico::class, 'id_medico', 'id_medico'); 
    }
}