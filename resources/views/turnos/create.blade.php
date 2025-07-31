@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">Reservar un turno</h1>

                {{-- Botón de Inicio (dinámico por rol) --}}
                @if(auth()->check())
                    <div class="action-buttons-container">
                        @php
                            $dashboardRoute = '';
                            if (auth()->user()->id_rol == 1) {
                                $dashboardRoute = route('admin.dashboard');
                            } elseif (auth()->user()->id_rol == 3) {
                                $dashboardRoute = route('paciente.dashboard');
                            }
                        @endphp

                        @if($dashboardRoute)
                            <a href="{{ $dashboardRoute }}" class="btn-secondary">
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
                        <div class="alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="form-group">
                        <label for="id_paciente" class="form-label">Paciente:</label>
                        <select name="id_paciente" id="id_paciente" required class="form-input">
                            <option value="">Selecciona un paciente</option>
                            @foreach($pacientes as $paciente)
                                <option value="{{ $paciente->id_paciente }}">
                                    {{ $paciente->nombre }} {{ $paciente->apellido }} (DNI: {{ $paciente->dni }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_medico" class="form-label">Médico:</label>
                        <select name="id_medico" id="id_medico" required class="form-input">
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
                    </div>

                    <div class="form-group">
                        <label for="fecha" class="form-label">Fecha:</label>
                        <input type="date" name="fecha" id="fecha" required min="{{ \Carbon\Carbon::today()->toDateString() }}" class="form-input">
                    </div>

                    <div class="form-group">
                        <label for="hora" class="form-label">Hora:</label>
                        <select name="hora" id="hora" required disabled class="form-input">
                            <option value="">Selecciona primero médico y fecha</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-primary mt-4">Confirmar turno</button>
                    @php
                        $cancelRoute = '';
                        if (auth()->check() && auth()->user()->id_rol == 1) {
                            $cancelRoute = route('admin.turnos.index');
                        } elseif (auth()->check() && auth()->user()->id_rol == 3) {
                            $cancelRoute = route('paciente.turnos.index');
                        }
                    @endphp
                    @if($cancelRoute)
                        <a href="{{ $cancelRoute }}" class="btn-secondary ml-2">Cancelar</a>
                    @endif
                </form>

                {{-- Incluye el script de JavaScript para cargar horarios --}}
                <script>
                    // Definimos estas variables para que sean accesibles desde el script externo
                    const apiUrlBase = @json(Auth::check() ? (Auth::user()->id_rol == 1 ? '/admin/turnos' : (Auth::user()->id_rol == 2 ? '/medico/turnos' : '/paciente/turnos')) : '/paciente/turnos');
                    const currentTurnoId = null;
                    const currentTurnoHora = '';
                </script>
                @vite('resources/js/turnos.js')
            </div>
        </div>
    </div>
@endsection
