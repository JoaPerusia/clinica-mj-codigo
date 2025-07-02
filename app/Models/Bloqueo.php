<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; 

class Bloqueo extends Model
{
    use HasFactory; 

    protected $table = 'bloqueos'; 
    protected $primaryKey = 'id_bloqueo'; 

    protected $fillable = [ 
        'fecha',
        'motivo',
        'id_medico',
    ];

    public function medico()
    {
        return $this->belongsTo(Medico::class, 'id_medico', 'id_medico'); // Especificar FK 
    }
}