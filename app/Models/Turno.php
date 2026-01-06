<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; 
use App\Models\Rol;
use Carbon\Carbon;

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

    protected $appends = ['estado_actual'];

    // --- CONSTANTES DE ESTADO ---
    public const PENDIENTE = 'pendiente';
    public const REALIZADO = 'realizado';
    public const CANCELADO = 'cancelado';

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente')->withTrashed(); 
    }

    public function medico()
    {
        return $this->belongsTo(Medico::class, 'id_medico', 'id_medico')->withTrashed(); 
    }

    // --- ATRIBUTO VIRTUAL (ACCESSOR) ---
    public function getEstadoActualAttribute()
    {
        if ($this->estado === self::CANCELADO) {
            return self::CANCELADO;
        }

        $fechaHoraTurno = Carbon::parse($this->fecha . ' ' . $this->hora);

        if ($fechaHoraTurno->isPast()) {
            return self::REALIZADO;
        }

        return self::PENDIENTE;
    }

    // --- SCOPES (Filtros Inteligentes) ---

    public function scopeFiltrarPorPaciente($query, $busqueda)
    {
        if (empty($busqueda)) return $query;

        return $query->whereHas('paciente', function ($q) use ($busqueda) { 
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

        return $query->whereHas('medico', function ($q) use ($busqueda) {
            $q->withTrashed()
              ->whereHas('usuario', function ($u) use ($busqueda) {
                  $u->where(function ($w) use ($busqueda) {
                      $w->where('dni', 'like', "%{$busqueda}%")
                        ->orWhere('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('apellido', 'like', "%{$busqueda}%");
                  });
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
            return $query->whereIn('estado', [Turno::REALIZADO, Turno::PENDIENTE, Turno::CANCELADO]);
        }
        return $query->where('estado', $estado);
    }
}