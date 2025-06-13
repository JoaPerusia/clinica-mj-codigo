<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bloqueo extends Model
{
    protected $table = 'Bloqueos';

    public function medico()
    {
        return $this->belongsTo(Medico::class, 'id_medico');
    }
}
