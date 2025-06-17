<h1>Reservar un turno</h1>

<form method="POST" action="{{ route('turnos.store') }}">
    @csrf

    <label>Paciente:</label>
    <select name="id_paciente" required>
        @foreach($pacientes as $paciente)
            <option value="{{ $paciente->id }}">
                {{ $paciente->nombre }} {{ $paciente->apellido }} (DNI: {{ $paciente->dni }})
            </option>
        @endforeach
    </select>
    <br>

    <label>Médico:</label>
    <select name="id_medico" required>
        @foreach($medicos as $medico)
            <option value="{{ $medico->id }}">
                {{ $medico->nombre }} {{ $medico->apellido }}
            </option>
        @endforeach
    </select>
    <br>

    <label>Fecha:</label>
    <input type="date" name="fecha" required>
    <br>

    <label>Hora:</label>
    <input type="time" name="hora" required>
    <br>

    <button type="submit">Confirmar turno</button>
</form>
