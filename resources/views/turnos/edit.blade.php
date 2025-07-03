<h1>Editar turno</h1>

<form method="POST" action="{{ route('paciente.turnos.update', $turno->id) }}"> {{-- Asegúrate de que la ruta sea la correcta --}}
    @csrf
    @method('PUT')

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
        @foreach($pacientes as $paciente)
            <option value="{{ $paciente->id_paciente }}" {{ $paciente->id_paciente == $turno->id_paciente ? 'selected' : '' }}> {{-- CAMBIO: Usar id_paciente --}}
                {{ $paciente->nombre }} {{ $paciente->apellido }}
            </option>
        @endforeach
    </select>
    <br>

    <label for="id_medico">Médico:</label>
    <select name="id_medico" id="id_medico" required>
        @foreach($medicos as $medico)
            <option value="{{ $medico->id_medico }}" {{ $medico->id_medico == $turno->id_medico ? 'selected' : '' }}> {{-- CAMBIO: Usar id_medico --}}
                {{ $medico->nombre }} {{ $medico->apellido }}
                @if($medico->especialidades->isNotEmpty())
                    ({{ $medico->especialidades->pluck('nombre')->join(', ') }})
                @endif
            </option>
        @endforeach
    </select>
    <br>

    <label for="fecha">Fecha:</label>
    <input type="date" name="fecha" id="fecha" value="{{ $turno->fecha }}" required min="{{ \Carbon\Carbon::today()->toDateString() }}">
    <br>

    <label for="hora">Hora:</label>
    <select name="hora" id="hora" required disabled> {{-- Empieza deshabilitado --}}
        <option value="">Cargando horarios...</option>
    </select>
    <br>

    <label for="estado">Estado:</label>
    <select name="estado" id="estado" required>
        <option value="pendiente" {{ $turno->estado == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
        <option value="realizado" {{ $turno->estado == 'realizado' ? 'selected' : '' }}>Realizado</option>
        <option value="cancelado" {{ $turno->estado == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
    </select>
    <br>

    <button type="submit">Guardar cambios</button>
</form>

{{-- Incluye el archivo JavaScript, pasando el ID del turno actual y su hora --}}
<script>
    const currentTurnoId = {{ $turno->id ?? 'null' }};
    const currentTurnoHora = "{{ $turno->hora ?? '' }}";
</script>
<script src="{{ asset('js/turnos.js') }}"></script>