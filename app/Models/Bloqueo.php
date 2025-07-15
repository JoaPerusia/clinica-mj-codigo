<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bloqueo extends Model
{
    use HasFactory;

    protected $table = 'bloqueos';
    protected $primaryKey = 'id_bloqueo';

    protected $fillable = [
        'id_medico',
        'fecha_inicio',
        'fecha_fin',
        'hora_inicio', 
        'hora_fin',    
        'motivo',
    ];

    // Caster las fechas a objetos Carbon para facilitar su manipulación
    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'hora_inicio' => 'datetime',
        'hora_fin' => 'datetime',
    ];

    public function medico()
    {
        return $this->belongsTo(Medico::class, 'id_medico', 'id_medico');
    }
}