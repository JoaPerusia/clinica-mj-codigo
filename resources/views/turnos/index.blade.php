<h1>Turnos</h1>

{{-- Ajuste de la ruta para crear turno según el rol --}}
@if(auth()->check() && (auth()->user()->id_rol == 1 || auth()->user()->id_rol == 3))
    {{-- Solo admin y paciente pueden "crear" turnos desde aquí --}}
    <a href="{{ auth()->user()->id_rol == 1 ? route('admin.turnos.create') : route('paciente.turnos.create') }}">Crear Nuevo Turno</a>
@endif

<table border="1">
    <thead>
        <tr>
            <th>Paciente</th>
            <th>Médico</th>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach($turnos as $turno)
        <tr>
            <td>{{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }}</td>
            <td>{{ $turno->medico->nombre }} {{ $turno->medico->apellido }}</td>
            <td>{{ $turno->fecha }}</td>
            <td>{{ $turno->hora }}</td>
            <td>{{ $turno->estado }}</td>
            <td>
                @if(auth()->check())
                    {{-- Acciones para Administrador --}}
                    @if(auth()->user()->id_rol == 1)
                        <a href="{{ route('admin.turnos.edit', $turno->id) }}">Editar</a>
                        <form action="{{ route('admin.turnos.destroy', $turno->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de cancelar este turno?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Cancelar</button>
                        </form>
                    {{-- Acciones para Paciente --}}
                    @elseif(auth()->user()->id_rol == 3 && $turno->paciente && $turno->paciente->id_usuario == auth()->user()->id_usuario)
                        {{-- Un paciente solo puede cancelar sus propios turnos pendientes --}}
                        @if($turno->estado == 'pendiente')
                            <form action="{{ route('paciente.turnos.destroy', $turno->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de cancelar este turno?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Cancelar</button>
                            </form>
                        @else
                            No disponible
                        @endif
                    {{-- Acciones para Médico --}}
                    @elseif(auth()->user()->id_rol == 2 && $turno->medico && $turno->medico->id_usuario == auth()->user()->id_usuario)
                        {{-- Un médico solo puede editar (cambiar estado a realizado/ausente) sus turnos --}}
                        <a href="{{ route('medico.turnos.edit', $turno->id) }}">Editar</a>
                        {{-- Se elimina la opción de cancelar para el médico --}}
                    @endif
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>