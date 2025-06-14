<h1>Editar Paciente</h1>

<form action="{{ route('pacientes.update', $paciente->id) }}" method="POST">
    @csrf
    @method('PUT')

    <label>Nombre:</label>
    <input type="text" name="nombre" value="{{ $paciente->nombre }}" required><br>

    <label>Apellido:</label>
    <input type="text" name="apellido" value="{{ $paciente->apellido }}" required><br>

    <label>DNI:</label>
    <input type="text" name="dni" value="{{ $paciente->dni }}" required><br>

    <label>Fecha de Nacimiento:</label>
    <input type="date" name="fecha_nacimiento" value="{{ $paciente->fecha_nacimiento }}" required><br>

    <label>Obra Social:</label>
    <input type="text" name="obra_social" value="{{ $paciente->obra_social }}" required><br>

    <button type="submit">Guardar cambios</button>
</form>
