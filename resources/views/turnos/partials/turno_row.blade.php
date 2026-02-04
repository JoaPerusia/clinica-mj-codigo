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
            
            {{-- Verificamos si el turno está pendiente y el usuario logueado --}}
            @if(auth()->check() && $turno->estado_actual == $Turno::PENDIENTE)
                
                <div class="flex justify-center items-center space-x-2">
                    
                    {{-- 1. BOTÓN DE INFORMACIÓN (Azul) --}}
                    @if($turno->estado != 'cancelado') 
                        <button type="button" 
                                onclick="abrirModalInfo({{ $turno->id_medico }}, {{ $turno->id_paciente }})"
                                class="text-blue-500 hover:text-blue-700 transition" 
                                title="Ver Costos e Instrucciones">
                            <svg xmlns="https://img.icons8.com/color/48/info--v1.png" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                        </button>
                    @endif
                    
                    {{-- 2. BOTÓN DE CANCELAR (Rojo) --}}
                    @if(auth()->user()->hasRolActivo($Rol::ADMINISTRADOR) || auth()->user()->hasRolActivo($Rol::PACIENTE))
                        
                        {{-- Definimos la ruta según el rol --}}
                        @php
                            $rutaCancelar = auth()->user()->hasRolActivo($Rol::ADMINISTRADOR) 
                                ? route('admin.turnos.cambiar-estado', $turno->id_turno)
                                : route('paciente.turnos.cambiar-estado', $turno->id_turno);
                        @endphp

                        <form action="{{ $rutaCancelar }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de cancelar este turno?');" title="Cancelar turno">
                            @csrf 
                            @method('PATCH')
                            <input type="hidden" name="estado" value="{{ $Turno::CANCELADO }}">
                            
                            <button type="submit" class="text-red-500 hover:text-red-700 transition pt-1">
                                {{-- Icono X SVG (Sin imagen externa) --}}
                                <svg xmlns="https://img.icons8.com/color/48/cancel--v1.png" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
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