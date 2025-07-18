<h1>Editar turno</h1>

{{-- Determinar la ruta de actualización dinámicamente según el rol --}}
@php
    $routeName = '';
    if (auth()->user()->id_rol == 1) {
        $routeName = 'admin.turnos.update';
    } elseif (auth()->user()->id_rol == 2) {
        $routeName = 'medico.turnos.update';
    } elseif (auth()->user()->id_rol == 3) {
        $routeName = 'paciente.turnos.update';
    }

    // Determinar si el usuario actual es un médico
    $isMedico = (auth()->user()->id_rol == 2);
@endphp

{{-- CAMBIO CLAVE AQUÍ: Usar $turno->id_turno para la ruta --}}
<form method="POST" action="{{ route($routeName, $turno->id_turno) }}">
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
    <select name="id_paciente" id="id_paciente" required {{ $isMedico ? 'disabled' : '' }}>
        @foreach($pacientes as $paciente)
            <option value="{{ $paciente->id_paciente }}" {{ $paciente->id_paciente == $turno->id_paciente ? 'selected' : '' }}>
                {{ $paciente->nombre }} {{ $paciente->apellido }}
            </option>
        @endforeach
    </select>
    {{-- Si está deshabilitado, necesitamos un campo oculto para enviar el valor --}}
    @if($isMedico)
        <input type="hidden" name="id_paciente" value="{{ $turno->id_paciente }}">
    @endif
    <br>

    <label for="id_medico">Médico:</label>
    <select name="id_medico" id="id_medico" required {{ $isMedico ? 'disabled' : '' }}>
        @foreach($medicos as $medico)
            <option value="{{ $medico->id_medico }}" {{ $medico->id_medico == $turno->id_medico ? 'selected' : '' }}>
                {{ $medico->nombre }} {{ $medico->apellido }}
                @if($medico->especialidades->isNotEmpty())
                    ({{ $medico->especialidades->pluck('nombre')->join(', ') }})
                @endif
            </option>
        @endforeach
    </select>
    {{-- Si está deshabilitado, necesitamos un campo oculto para enviar el valor --}}
    @if($isMedico)
        <input type="hidden" name="id_medico" value="{{ $turno->id_medico }}">
    @endif
    <br>

    <label for="fecha">Fecha:</label>
    <input type="date" name="fecha" id="fecha" value="{{ $turno->fecha }}" required min="{{ \Carbon\Carbon::today()->toDateString() }}" {{ $isMedico ? 'readonly' : '' }}>
    <br>

    <label for="hora">Hora:</label>
    <select name="hora" id="hora" required {{ $isMedico ? 'disabled' : '' }}>
        <option value="">Cargando horarios...</option>
    </select>
    {{-- Si está deshabilitado, necesitamos un campo oculto para enviar el valor --}}
    @if($isMedico)
        <input type="hidden" name="hora" value="{{ $turno->hora }}">
    @endif
    <br>

    <label for="estado">Estado:</label>
    <select name="estado" id="estado" required>
        <option value="pendiente" {{ $turno->estado == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
        <option value="realizado" {{ $turno->estado == 'realizado' ? 'selected' : '' }}>Realizado</option>
        <option value="cancelado" {{ $turno->estado == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
        <option value="ausente" {{ $turno->estado == 'ausente' ? 'selected' : '' }}>Ausente</option>
    </select>
    <br>

    <button type="submit">Guardar cambios</button>
</form>

{{-- Incluye el archivo JavaScript, pasando el ID del turno actual y su hora --}}
<script>
    // Asegúrate de que $turno->id_turno esté disponible en la vista
    const currentTurnoId = {{ $turno->id_turno ?? 'null' }}; // CAMBIO AQUÍ: Usar id_turno
    const currentTurnoHora = "{{ $turno->hora ?? '' }}";
    const apiUrlBase = @json(Auth::check() ? (Auth::user()->id_rol == 1 ? '/admin/turnos' : (Auth::user()->id_rol == 2 ? '/medico/turnos' : '/paciente/turnos')) : '/paciente/turnos');
</script>
<script src="{{ asset('build/turnos.js') }}"></script>
