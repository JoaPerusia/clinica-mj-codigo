<h1>Reservar un turno</h1>

{{-- Botón de Inicio (dinámico por rol) --}}
@if(auth()->check())
    <div class="mb-4">
        @php
            $dashboardRoute = '';
            if (auth()->user()->id_rol == 1) {
                $dashboardRoute = route('admin.dashboard');
            } elseif (auth()->user()->id_rol == 3) {
                $dashboardRoute = route('paciente.dashboard');
            }
            // Los médicos (rol 2) no crean turnos desde aquí, así que no necesitan un botón de "Inicio" en esta vista
        @endphp

        @if($dashboardRoute)
            <a href="{{ $dashboardRoute }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                ← Inicio
            </a>
        @endif
    </div>
@endif

{{-- Determinar la ruta de almacenamiento dinámicamente según el rol --}}
<form method="POST" action="
    @if(auth()->check() && auth()->user()->id_rol == 1)
        {{ route('admin.turnos.store') }}
    @elseif(auth()->check() && auth()->user()->id_rol == 2)
        {{-- Los médicos no deberían crear turnos desde aquí, pero por si acaso --}}
        {{ route('medico.turnos.store') }}
    @else {{-- Asumiendo que es paciente (id_rol 3) o cualquier otro rol --}}
        {{ route('paciente.turnos.store') }}
    @endif
">
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
            <option value="{{ $paciente->id_paciente }}">
                {{ $paciente->nombre }} {{ $paciente->apellido }} (DNI: {{ $paciente->dni }})
            </option>
        @endforeach
    </select>
    <br>

    <label for="id_medico">Médico:</label>
    <select name="id_medico" id="id_medico" required>
        <option value="">Selecciona un médico</option>
        @foreach($medicos as $medico)
            <option value="{{ $medico->id_medico }}">
                {{ $medico->nombre }} {{ $medico->apellido }}
                @if($medico->especialidades->isNotEmpty())
                    ({{ $medico->especialidades->pluck('nombre')->join(', ') }})
                @endif
            </option>
        @endforeach
    </select>
    <br>

    <label for="fecha">Fecha:</label>
    <input type="date" name="fecha" id="fecha" required min="{{ \Carbon\Carbon::today()->toDateString() }}">
    <br>

    <label for="hora">Hora:</label>
    <select name="hora" id="hora" required disabled>
        <option value="">Selecciona primero médico y fecha</option>
    </select>
    <br>

    <button type="submit">Confirmar turno</button>
</form>

{{-- Incluye el script de JavaScript para cargar horarios --}}
<script>
    // Determinar la base de la URL API según el rol del usuario autenticado
    const apiUrlBase = @json(Auth::check() ? (Auth::user()->id_rol == 1 ? '/admin/turnos' : (Auth::user()->id_rol == 2 ? '/medico/turnos' : '/paciente/turnos')) : '/paciente/turnos');
    // Para la vista de creación, estas variables no son relevantes pero las definimos como null/vacío
    const currentTurnoId = null;
    const currentTurnoHora = '';
</script>
<script src="{{ asset('build/turnos.js') }}"></script>
