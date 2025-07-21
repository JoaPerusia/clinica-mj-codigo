<h1>Lista de Pacientes</h1>

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
        @endphp

        @if($dashboardRoute)
            <a href="{{ $dashboardRoute }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                ← Inicio
            </a>
        @endif
    </div>
@endif

{{-- Botón para Crear Nuevo Paciente (dinámico por rol) --}}
@if(auth()->check() && (auth()->user()->id_rol == 1 || auth()->user()->id_rol == 3))
    <div class="mb-4">
        @php
            $createPacienteRoute = '';
            if (auth()->user()->id_rol == 1) {
                $createPacienteRoute = route('admin.pacientes.create');
            } elseif (auth()->user()->id_rol == 3) {
                $createPacienteRoute = route('paciente.pacientes.create');
            }
        @endphp

        @if($createPacienteRoute)
            <a href="{{ $createPacienteRoute }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-semibold rounded-md shadow-lg text-white bg-green-700 hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition ease-in-out duration-150">
                Crear Nuevo Paciente
            </a>
        @endif
    </div>
@endif

<table border="1">
    <thead>
        <tr>
            <th>ID Paciente</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>DNI</th>
            <th>Fecha Nacimiento</th>
            <th>Teléfono</th>
            <th>Usuario Asociado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($pacientes as $paciente)
        <tr>
            <td>{{ $paciente->id_paciente }}</td>
            <td>{{ $paciente->nombre }}</td>
            <td>{{ $paciente->apellido }}</td>
            <td>{{ $paciente->dni }}</td>
            <td>{{ $paciente->fecha_nacimiento }}</td>
            <td>{{ $paciente->telefono }}</td>
            <td>{{ $paciente->usuario ? $paciente->usuario->nombre . ' (' . $paciente->usuario->id_usuario . ')' : 'N/A' }}</td>
            <td>
                {{-- Acciones para editar/eliminar si las tienes --}}
                @if(auth()->check())
                    @if(auth()->user()->id_rol == 1)
                        <a href="{{ route('admin.pacientes.edit', $paciente->id_paciente) }}">Editar</a>
                        <form action="{{ route('admin.pacientes.destroy', $paciente->id_paciente) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este paciente?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Eliminar</button>
                        </form>
                    @elseif(auth()->user()->id_rol == 3 && $paciente->id_usuario == auth()->user()->id_usuario)
                        {{-- Un paciente solo puede editar/eliminar sus propios perfiles de paciente --}}
                        <a href="{{ route('paciente.pacientes.edit', $paciente->id_paciente) }}">Editar</a>
                        <form action="{{ route('paciente.pacientes.destroy', $paciente->id_paciente) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este paciente?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Eliminar</button>
                        </form>
                    @endif
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8">No hay pacientes registrados.</td>
        </tr>
        @endforelse
    </tbody>
</table>
