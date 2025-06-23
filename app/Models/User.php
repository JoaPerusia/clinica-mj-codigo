<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'Usuarios';

    protected $primaryKey = 'id_usuario';

    protected $fillable = [
        'nombre', 'email', 'password', 'telefono', 'id_rol'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    // 👇 importante: Laravel usa esta propiedad para el login
    public function getAuthPassword()
    {
        return $this->password;
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol');
    }

    public function paciente()
    {
        return $this->hasOne(Paciente::class, 'id_usuario');
    }
}
