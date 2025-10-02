<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Rol;
use App\Models\Paciente;
use App\Models\Medico;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

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

    /** Un usuario puede tener varios roles */
    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'role_user', 'id_usuario', 'id_rol');
    }

    /** Relación con pacientes (1 a muchos) */
    public function pacientes()
    {
        return $this->hasMany(Paciente::class, 'id_usuario', 'id_usuario');
    }

    /** Relación con médico (1 a 1) */
    public function medico()
    {
        return $this->hasOne(Medico::class, 'id_usuario', 'id_usuario');
    }

    /** Chequeo de rol */
    public function hasRole($rol)
    {
        return $this->roles()->where('rol', $rol)->exists();
    }

    /** Roles activos (filtrando soft deletes) */
    public function getActiveRolesAttribute()
    {
        return $this->roles->filter(function($r) {
            return match($r->rol) {
                'Medico'    => $this->medico()->whereNull('deleted_at')->exists(),
                'Paciente'  => $this->pacientes()->whereNull('deleted_at')->exists(),
                default     => true,
            };
        });
    }

    /** Rol activo en sesión */
    public function hasRolActivo($rol)
    {
        return session('rol_activo') === $rol;
    }

    public function getDashboardRoute(): string
    {
        $roles = $this->activeRoles;

        if ($roles->count() > 1) {
            return 'dashboard';
        }

        return match($roles->first()->rol) {
            'Administrador' => 'admin.dashboard',
            'Medico'        => 'medico.dashboard',
            'Paciente'      => 'paciente.dashboard',
            default         => 'dashboard',
        };
    }
    
    /** Hook para reasignar pacientes a administración si se borra el usuario */
    protected static function booted()
    {
        static::deleting(function ($user) {
            if ($user->isForceDeleting()) {
                // Si es un borrado físico, reasignamos pacientes a admin (id_usuario = 1)
                $user->pacientes()->update(['id_usuario' => 1]);
            } else {
                // Si es soft delete, también reasignamos a admin
                $user->pacientes()->update(['id_usuario' => 1]);
            }
        });
    }
}