<tr>
    <td class="table-data py-4">
        {{ $turno->medico->usuario->nombre }} {{ $turno->medico->usuario->apellido }} ({{ $turno->medico->usuario->dni ?? 'N/A' }})
    </td>
    <td class="table-data py-4">
        {{ $turno->medico->especialidades->pluck('nombre_especialidad')->implode(', ') }}
    </td>
    <td class="table-data py-4">
        {{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }} ({{ $turno->paciente->dni ?? 'N/A' }})
    </td>
    <td class="table-data py-4">
        {{ \Carbon\Carbon::parse($turno->fecha)->format('d/m/Y') }}
    </td>
    <td class="table-data py-4">
        {{ \Carbon\Carbon::parse($turno->hora)->format('H:i') }}
    </td>
    <td class="table-data py-4">
        {{ ucfirst($turno->estado) }}
    </td>
    <td class="table-data py-4 acciones-fijas-columna">
        @if(auth()->check() && $turno->estado == 'pendiente')
            <div class="flex justify-center space-x-2">
                {{-- Botones para Administrador --}}
                @if(auth()->user()->hasRolActivo('Administrador'))
                    <form action="{{ route('admin.turnos.cambiar-estado', $turno->id_turno) }}" method="POST" class="inline-block" title="Marcar como realizado">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="estado" value="realizado">
                        <button type="submit" class="p-1">
                            <img src="{{ $realizadoIcon }}" alt="Realizado" class="w-7 h-7">
                        </button>
                    </form>
                    <form action="{{ route('admin.turnos.cambiar-estado', $turno->id_turno) }}" method="POST" class="inline-block" title="Marcar como ausente">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="estado" value="ausente">
                        <button type="submit" class="p-1">
                            <img src="{{ $ausenteIcon }}" alt="Ausente" class="w-7 h-7">
                        </button>
                    </form>
                    <form action="{{ route('admin.turnos.cambiar-estado', $turno->id_turno) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de cancelar este turno?');" title="Cancelar turno">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="estado" value="cancelado">
                        <button type="submit" class="p-1">
                            <img src="{{ $canceladoIcon }}" alt="Cancelar" class="w-7 h-7">
                        </button>
                    </form>
                {{-- Botones para Médico --}}
                @elseif(auth()->user()->hasRolActivo('Medico') && $turno->medico && $turno->medico->id_usuario == auth()->user()->id_usuario)
                    <form action="{{ route(strtolower($rolActivo) . '.turnos.cambiar-estado', $turno->id_turno) }}" method="POST" class="inline-block" title="Marcar como realizado">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="estado" value="realizado">
                        <button type="submit" class="p-1">
                            <img src="{{ $realizadoIcon }}" alt="Realizado" class="w-7 h-7">
                        </button>
                    </form>
                    <form action="{{ route(strtolower($rolActivo) . '.turnos.cambiar-estado', $turno->id_turno) }}" method="POST" class="inline-block" title="Marcar como ausente">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="estado" value="ausente">
                        <button type="submit" class="p-1">
                            <img src="{{ $ausenteIcon }}" alt="Ausente" class="w-7 h-7">
                        </button>
                    </form>
                {{-- Botón de "Cancelar" para Paciente --}}
                @elseif(auth()->user()->hasRolActivo('Paciente'))
                    <form action="{{ route(strtolower($rolActivo) . '.turnos.cambiar-estado', $turno->id_turno) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de cancelar este turno?');" title="Cancelar turno">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="estado" value="cancelado">
                        <button type="submit" class="p-1">
                            <img src="{{ $canceladoIcon }}" alt="Cancelar" class="w-7 h-7">
                        </button>
                    </form>
                @endif
            </div>
        @else
            <span class="text-gray-500 dark:text-gray-400">-</span>
        @endif
    </td>
</tr>