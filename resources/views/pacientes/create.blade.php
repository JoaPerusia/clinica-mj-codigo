<h1>Nuevo Paciente</h1>

<form action="{{ route('pacientes.store') }}" method="POST">
    @csrf

    <label>Nombre:</label>
    <input type="text" name="nombre" required><br>

    <label>Apellido:</label>
    <input type="text" name="apellido" required><br>

    <label>DNI:</label>
    <input type="text" name="dni" required><br>

    <label>Fecha de Nacimiento:</label>
    <input type="date" name="fecha_nacimiento" required><br>

    <label>Obra Social:</label>
    <input type="text" name="obra_social" required><br>

    <button type="submit">Guardar</button>
</form>

<a href="{{ route('pacientes.index') }}">Volver a la lista</a>
