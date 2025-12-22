@inject('Rol', 'App\Models\Rol')
@inject('Turno', 'App\Models\Turno')

@php
    $canceladoIcon = 'https://img.icons8.com/color/48/cancel--v1.png';
@endphp

<tr>
    <td class="table-data py-4">
        {{ $turno->medico->usuario->nombre }} {{ $turno->medico->usuario->apellido }} ({{ $turno->medico->usuario->dni ?? 'N/A' }})
        @if($turno->medico->deleted_at)
            <span class="text-red-500 ml-1">(eliminado)</span>
        @endif
    </td>
    <td class="table-data py-4">
        {{ $turno->medico->especialidades->pluck('nombre_especialidad')->implode(', ') }}
    </td>
    <td class="table-data py-4">
        {{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }} ({{ $turno->paciente->dni ?? 'N/A' }})
        @if($turno->paciente->deleted_at)
            <span class="text-red-500 ml-1">(eliminado)</span>
        @endif
    </td>

    <td class="table-data py-4">
        {{ \Carbon\Carbon::parse($turno->fecha)->format('d/m/Y') }}
    </td>
    <td class="table-data py-4">
        {{ \Carbon\Carbon::parse($turno->hora)->format('H:i') }}
    </td>
    
    <td class="table-data py-4">
        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
            {{ $turno->estado_actual === $Turno::PENDIENTE ? 'bg-yellow-100 text-yellow-800' : '' }}
            {{ $turno->estado_actual === $Turno::REALIZADO ? 'bg-green-100 text-green-800' : '' }}
            {{ $turno->estado_actual === $Turno::CANCELADO ? 'bg-red-100 text-red-800' : '' }}">
            {{ ucfirst($turno->estado_actual) }}
        </span>
    </td>

    {{-- Solo mostramos la columna de acciones si NO ES MÉDICO --}}
    @if(!auth()->user()->hasRolActivo($Rol::MEDICO))
        <td class="table-data py-4 actions-cell text-center">
            @if(auth()->check() && $turno->estado_actual == $Turno::PENDIENTE)
                <div class="flex justify-center space-x-2">
                    
                    {{-- Admin --}}
                    @if(auth()->user()->hasRolActivo($Rol::ADMINISTRADOR))
                        <form action="{{ route('admin.turnos.cambiar-estado', $turno->id_turno) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de cancelar este turno?');" title="Cancelar turno">
                            @csrf @method('PATCH')
                            <input type="hidden" name="estado" value="{{ $Turno::CANCELADO }}">
                            <button type="submit" class="p-1 hover:bg-red-100 rounded-full transition">
                                <img src="{{ $canceladoIcon }}" alt="Cancelar" class="w-7 h-7">
                            </button>
                        </form>

                    {{-- Paciente --}}
                    @elseif(auth()->user()->hasRolActivo($Rol::PACIENTE))
                        <form action="{{ route('paciente.turnos.cambiar-estado', $turno->id_turno) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de cancelar este turno?');" title="Cancelar turno">
                            @csrf @method('PATCH')
                            <input type="hidden" name="estado" value="{{ $Turno::CANCELADO }}">
                            <button type="submit" class="p-1 hover:bg-red-100 rounded-full transition">
                                <img src="{{ $canceladoIcon }}" alt="Cancelar" class="w-7 h-7">
                            </button>
                        </form>
                    @endif

                </div>
            @else
                <span class="text-gray-300">-</span>
            @endif
        </td>
    @endif
</tr>