<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Rol; 

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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Un usuario puede tener varios roles.
     */
    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'role_user', 'id_usuario', 'id_rol');
    }

    // El resto de tus métodos (pacientes, medico) no necesitan cambios
    public function pacientes()
    {
        return $this->hasMany(Paciente::class, 'id_usuario', 'id_usuario');
    }

    public function medico()
    {
        return $this->hasOne(Medico::class, 'id_usuario', 'id_usuario');
    }

    public function hasRole($rol)
    {
        return $this->roles()->where('rol', $rol)->exists();
    }

    public function hasRolActivo($rol)
    {
        return session('rol_activo') === $rol;
    }
}