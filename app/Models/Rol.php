<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User; 

class Rol extends Model
{
    use HasFactory;

    protected $table = 'roles';
    protected $primaryKey = 'id_rol';
    protected $fillable = ['rol'];

    public const ADMINISTRADOR = 'Administrador';
    public const MEDICO        = 'Medico';
    public const PACIENTE      = 'Paciente';

    public $timestamps = false;

    
    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user', 'id_rol', 'id_usuario');
    }
}