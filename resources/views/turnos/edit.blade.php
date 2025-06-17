<h1>Editar turno</h1>

<form method="POST" action="{{ route('turnos.update', $turno->id) }}">
    @csrf
    @method('PUT')

    <label>Paciente:</label>
    <select name="id_paciente" required>
        @foreach($pacientes as $paciente)
            <option value="{{ $paciente->id }}" {{ $paciente->id == $turno->id_paciente ? 'selected' : '' }}>
                {{ $paciente->nombre }} {{ $paciente->apellido }}
            </option>
        @endforeach
    </select>
    <br>

    <label>Médico:</label>
    <select name="id_medico" required>
        @foreach($medicos as $medico)
            <option value="{{ $medico->id }}" {{ $medico->id == $turno->id_medico ? 'selected' : '' }}>
                {{ $medico->nombre }} {{ $medico->apellido }}
            </option>
        @endforeach
    </select>
    <br>

    <label>Fecha:</label>
    <input type="date" name="fecha" value="{{ $turno->fecha }}" required>
    <br>

    <label>Hora:</label>
    <input type="time" name="hora" value="{{ $turno->hora }}" required>
    <br>

    <label>Estado:</label>
    <select name="estado" required>
        <option value="pendiente" {{ $turno->estado == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
        <option value="realizado" {{ $turno->estado == 'realizado' ? 'selected' : '' }}>Realizado</option>
        <option value="cancelado" {{ $turno->estado == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
    </select>
    <br>

    <button type="submit">Guardar cambios</button>
</form>
