<!-- resources/views/pacientes/index.blade.php -->
<h1>Lista de Pacientes</h1>

{{-- Mensajes de confirmacion o advertencia --}}
@if(session('success'))
    <p style="color: green">{{ session('success') }}</p>
@endif

@if(session('warning'))
    <p style="color: orange">{{ session('warning') }}</p>
@endif

<a href="{{ route('pacientes.create') }}">Agregar nuevo paciente</a>

<table border="1">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>DNI</th>
            <th>Obra Social</th>
            <th>Acciones</th> <!-- Nueva columna -->
        </tr>
    </thead>
    <tbody>
        @foreach ($pacientes as $paciente)
        <tr>
            <td>{{ $paciente->nombre }}</td>
            <td>{{ $paciente->apellido }}</td>
            <td>{{ $paciente->dni }}</td>
            <td>{{ $paciente->obra_social }}</td>
            <td>
                <!-- Boton Editar -->
                <a href="{{ route('pacientes.edit', $paciente->id) }}">Editar</a>

                <!-- Boton Eliminar -->
                <form action="{{ route('pacientes.destroy', $paciente->id) }}" method="POST" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" onclick="return confirm('¿Estás seguro que querés eliminar este paciente?')">Eliminar</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>



