@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper"> 
                <h1 class="page-title">Editar turno</h1> 

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

                <form method="POST" action="{{ route($routeName, $turno->id_turno) }}">
                    @csrf
                    @method('PUT')

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
                        <select name="id_paciente" id="id_paciente" required {{ $isMedico ? 'disabled' : '' }} class="form-input"> 
                            @foreach($pacientes as $paciente)
                                <option value="{{ $paciente->id_paciente }}" {{ $paciente->id_paciente == $turno->id_paciente ? 'selected' : '' }}>
                                    {{ $paciente->nombre }} {{ $paciente->apellido }}
                                </option>
                            @endforeach
                        </select>
                        @if($isMedico)
                            <input type="hidden" name="id_paciente" value="{{ $turno->id_paciente }}">
                        @endif
                    </div>

                    <div class="form-group"> 
                        <label for="id_medico" class="form-label">Médico:</label> 
                        <select name="id_medico" id="id_medico" required {{ $isMedico ? 'disabled' : '' }} class="form-input"> 
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
                    </div>

                    <div class="form-group"> 
                        <label for="fecha" class="form-label">Fecha:</label> 
                        <input type="date" name="fecha" id="fecha" value="{{ $turno->fecha }}" required min="{{ \Carbon\Carbon::today()->toDateString() }}" {{ $isMedico ? 'readonly' : '' }} class="form-input"> 
                    </div>

                    <div class="form-group"> 
                        <label for="hora" class="form-label">Hora:</label> 
                        <select name="hora" id="hora" required {{ $isMedico ? 'disabled' : '' }} class="form-input"> 
                            <option value="">Cargando horarios...</option>
                        </select>
                        {{-- Si está deshabilitado, necesitamos un campo oculto para enviar el valor --}}
                        @if($isMedico)
                            <input type="hidden" name="hora" value="{{ $turno->hora }}">
                        @endif
                    </div>

                    <div class="form-group"> 
                        <label for="estado" class="form-label">Estado:</label> 
                        <select name="estado" id="estado" required class="form-input"> 
                            <option value="pendiente" {{ $turno->estado == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                            <option value="realizado" {{ $turno->estado == 'realizado' ? 'selected' : '' }}>Realizado</option>
                            <option value="cancelado" {{ $turno->estado == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                            <option value="ausente" {{ $turno->estado == 'ausente' ? 'selected' : '' }}>Ausente</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-primary mt-4">Guardar cambios</button>
                </form>

                {{-- Incluye el archivo JavaScript, pasando el ID del turno actual y su hora --}}
                <script>
                    // Asegúrate de que $turno->id_turno esté disponible en la vista
                    const currentTurnoId = {{ $turno->id_turno ?? 'null' }}; // CAMBIO AQUÍ: Usar id_turno
                    const currentTurnoHora = "{{ $turno->hora ?? '' }}";
                    const apiUrlBase = @json(Auth::check() ? (Auth::user()->id_rol == 1 ? '/admin/turnos' : (Auth::user()->id_rol == 2 ? '/medico/turnos' : '/paciente/turnos')) : '/paciente/turnos');
                </script>
                <script src="{{ asset('build/turnos.js') }}"></script>
            </div>
        </div>
    </div>
@endsection
