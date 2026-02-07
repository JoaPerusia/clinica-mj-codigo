@inject('Rol', 'App\Models\Rol')
@inject('Turno', 'App\Models\Turno')

<tr>
    {{-- Columna Médico --}}
    <td class="table-data py-4">
        {{ $turno->medico->usuario->nombre }} {{ $turno->medico->usuario->apellido }} ({{ $turno->medico->usuario->dni ?? 'N/A' }})
        @if($turno->medico->deleted_at)
            <span class="text-red-500 ml-1">(eliminado)</span>
        @endif
    </td>

    {{-- Columna Especialidad --}}
    <td class="table-data py-4">
        {{ $turno->medico->especialidades->pluck('nombre_especialidad')->implode(', ') }}
    </td>

    {{-- Columna Paciente --}}
    <td class="table-data py-4">
        {{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }} ({{ $turno->paciente->dni ?? 'N/A' }})
        @if($turno->paciente->deleted_at)
            <span class="text-red-500 ml-1">(eliminado)</span>
        @endif
    </td>

    {{-- Columna Fecha --}}
    <td class="table-data py-4">
        {{ \Carbon\Carbon::parse($turno->fecha)->format('d/m/Y') }}
    </td>

    {{-- Columna Hora --}}
    <td class="table-data py-4">
        {{ \Carbon\Carbon::parse($turno->hora)->format('H:i') }}
    </td>
    
    {{-- Columna Estado --}}
    <td class="table-data py-4">
        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
            {{ $turno->estado_actual === $Turno::PENDIENTE ? 'bg-yellow-100 text-yellow-800' : '' }}
            {{ $turno->estado_actual === $Turno::REALIZADO ? 'bg-green-100 text-green-800' : '' }}
            {{ $turno->estado_actual === $Turno::CANCELADO ? 'bg-red-100 text-red-800' : '' }}">
            {{ ucfirst($turno->estado_actual) }}
        </span>
    </td>

    {{-- Columna Acciones --}}
    <td class="table-data py-4 text-center"> {{-- FALTABA ABRIR ESTE TD --}}
        
        @if(!auth()->user()->hasRolActivo($Rol::MEDICO))
            <div class="flex justify-center items-center space-x-3">
    
                {{-- 1. BOTÓN DE INFORMACIÓN (Azul) --}}
                @if($turno->estado != 'cancelado') 
                    <button type="button" 
                            onclick="abrirModalInfo({{ $turno->id_medico }}, {{ $turno->id_paciente }})"
                            class="transition transform hover:scale-110" 
                            title="Ver Costos e Instrucciones">
                        <x-action-icon accion="info" />
                    </button>
                @endif
                
                {{-- 2. BOTÓN DE CANCELAR (Rojo) --}}
                @if(auth()->user()->hasRolActivo($Rol::ADMINISTRADOR) || auth()->user()->hasRolActivo($Rol::PACIENTE))
                    
                    @php
                        $rutaCancelar = auth()->user()->hasRolActivo($Rol::ADMINISTRADOR) 
                            ? route('admin.turnos.cambiar-estado', $turno->id_turno)
                            : route('paciente.turnos.cambiar-estado', $turno->id_turno);
                    @endphp

                    <form action="{{ $rutaCancelar }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de cancelar este turno?');">
                        @csrf 
                        @method('PATCH')
                        <input type="hidden" name="estado" value="{{ $Turno::CANCELADO }}">
                        
                        <button type="submit" class="pt-1 transition transform hover:scale-110" title="Cancelar turno">
                            <x-action-icon accion="eliminar" />
                        </button>
                    </form>
                @endif
            </div>

        @else
            {{-- Si es Médico, no ve acciones --}}
            <span class="text-gray-300">-</span>
        @endif
    </td>
</tr>