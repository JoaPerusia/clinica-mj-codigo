<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; 
use App\Models\Rol;

class Turno extends Model
{
    use HasFactory; 

    protected $table = 'turnos'; 
    
    protected $primaryKey = 'id_turno'; 

    protected $fillable = [
        'fecha',
        'hora',
        'estado',
        'id_paciente',
        'id_medico',
    ];

    // --- CONSTANTES DE ESTADO ---
    public const PENDIENTE = 'pendiente';
    public const REALIZADO = 'realizado';
    public const CANCELADO = 'cancelado';
    public const AUSENTE   = 'ausente';
    public const ATENDIDO  = 'atendido';

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente'); 
    }

    public function medico()
    {
        return $this->belongsTo(Medico::class, 'id_medico', 'id_medico'); 
    }

    // --- SCOPES (Filtros Inteligentes) ---

    public function scopeFiltrarPorPaciente($query, $busqueda)
    {
        if (empty($busqueda)) return $query;

        return $query->whereHas(Rol::PACIENTE, function ($q) use ($busqueda) {
            $q->withTrashed()
              ->where(function ($w) use ($busqueda) {
                  $w->where('dni', 'like', "%{$busqueda}%")
                    ->orWhere('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('apellido', 'like', "%{$busqueda}%");
              });
        });
    }

    public function scopeFiltrarPorMedico($query, $busqueda)
    {
        if (empty($busqueda)) return $query;

        return $query->whereHas('medico.usuario', function ($q) use ($busqueda) {
            $q->where(function ($w) use ($busqueda) {
                $w->where('dni', 'like', "%{$busqueda}%")
                  ->orWhere('nombre', 'like', "%{$busqueda}%")
                  ->orWhere('apellido', 'like', "%{$busqueda}%");
            });
        });
    }

    public function scopeFiltrarPorEspecialidad($query, $idEspecialidad)
    {
        if (empty($idEspecialidad)) return $query;

        return $query->whereHas('medico.especialidades', function ($q) use ($idEspecialidad) {
            $q->where('especialidades.id_especialidad', $idEspecialidad);
        });
    }

    public function scopeFiltrarPorFecha($query, $fecha, $inicio, $fin)
    {
        if (!empty($fecha)) {
            return $query->whereDate('fecha', $fecha);
        }

        if (!empty($inicio) && !empty($fin)) {
            return $query->whereBetween('fecha', [$inicio, $fin]);
        } elseif (!empty($inicio)) {
            return $query->whereDate('fecha', '>=', $inicio);
        } elseif (!empty($fin)) {
            return $query->whereDate('fecha', '<=', $fin);
        }

        return $query;
    }

    public function scopeFiltrarPorEstado($query, $estado)
    {
        if ($estado === 'todos') {
            return $query->whereIn('estado', [Turno::REALIZADO, 'atendido', Turno::PENDIENTE, Turno::CANCELADO, 'ausente']);
        }
        if ($estado === Turno::REALIZADO) {
            return $query->whereIn('estado', [Turno::REALIZADO, 'atendido']);
        }
        
        // Si no es un filtro especial, filtra directo por la columna (ej: 'pendiente')
        return $query->where('estado', $estado);
    }
}