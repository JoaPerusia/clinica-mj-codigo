<h1>Turnos</h1>

<a href="{{ route('turnos.create') }}">Reservar nuevo turno</a>

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
                @if(auth()->user()->id_rol == 1 || 
                    (auth()->user()->id_rol == 3 && $turno->paciente->id_usuario == auth()->user()->id))
                    <form action="{{ route('turnos.destroy', $turno->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit">Cancelar</button>
                    </form>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
