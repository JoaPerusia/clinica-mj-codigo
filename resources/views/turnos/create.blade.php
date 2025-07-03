<h1>Reservar un turno</h1>

<form method="POST" action="{{ route('paciente.turnos.store') }}"> {{-- Asegúrate de que la ruta sea la correcta para el rol --}}
    @csrf

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <label for="id_paciente">Paciente:</label>
    <select name="id_paciente" id="id_paciente" required>
        <option value="">Selecciona un paciente</option>
        @foreach($pacientes as $paciente)
            <option value="{{ $paciente->id_paciente }}"> {{-- CAMBIO: Usar id_paciente --}}
                {{ $paciente->nombre }} {{ $paciente->apellido }} (DNI: {{ $paciente->dni }})
            </option>
        @endforeach
    </select>
    <br>

    <label for="id_medico">Médico:</label>
    <select name="id_medico" id="id_medico" required>
        <option value="">Selecciona un médico</option>
        @foreach($medicos as $medico)
            <option value="{{ $medico->id_medico }}"> {{-- CAMBIO: Usar id_medico --}}
                {{ $medico->nombre }} {{ $medico->apellido }}
                @if($medico->especialidades->isNotEmpty())
                    ({{ $medico->especialidades->pluck('nombre')->join(', ') }})
                @endif
            </option>
        @endforeach
    </select>
    <br>

    <label for="fecha">Fecha:</label>
    <input type="date" name="fecha" id="fecha" required min="{{ \Carbon\Carbon::today()->toDateString() }}"> {{-- Fecha mínima hoy --}}
    <br>

    <label for="hora">Hora:</label>
    <select name="hora" id="hora" required disabled> {{-- Empieza deshabilitado --}}
        <option value="">Selecciona primero médico y fecha</option>
    </select>
    <br>

    <button type="Guardar">Confirmar turno</button>
</form>

{{-- Incluye el archivo JavaScript --}}
<script src="{{ asset('js/turnos.js') }}"></script>