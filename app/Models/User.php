<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'id_usuario';
    protected $table = 'usuarios'; 

    protected $fillable = [
        'nombre', 
        'apellido',
        'dni',
        'fecha_nacimiento',
        'obra_social',
        'email',
        'password',
        'telefono',
        'id_rol',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    public function pacientes()
    {
        return $this->hasMany(Paciente::class, 'id_usuario', 'id_usuario');
    }

    public function medico()
    {
        return $this->hasOne(Medico::class, 'id_usuario', 'id_usuario');
    }
}
