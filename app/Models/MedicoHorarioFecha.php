<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicoHorarioFecha extends Model
{
    use HasFactory;

    protected $table = 'medico_horarios_fechas';

    protected $fillable = [
        'id_medico',
        'fecha',
        'hora_inicio',
        'hora_fin',
    ];

    public function medico()
    {
        return $this->belongsTo(Medico::class, 'id_medico');
    }
}