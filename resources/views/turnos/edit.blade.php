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
                        <label for="id_paciente" class="form-label">Paciente</label>
                        <select name="id_paciente" id="id_paciente" class="form-input" required @if(!$isMedico) disabled @endif>
                            <option value="{{ $turno->paciente->id_paciente }}" selected>
                                {{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_medico" class="form-label">Médico</label>
                        <select name="id_medico" id="id_medico" class="form-input" required @if(!$isMedico) disabled @endif>
                            <option value="{{ $turno->medico->id_medico }}" selected>
                                {{ $turno->medico->nombre }} {{ $turno->medico->apellido }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fecha" class="form-label">Fecha</label>
                        <input type="date" name="fecha" id="fecha" value="{{ $turno->fecha }}" required class="form-input" @if(!$isMedico) disabled @endif>
                    </div>

                    <div class="form-group">
                        <label for="hora" class="form-label">Hora</label>
                        <select name="hora" id="hora" required class="form-input" @if(!$isMedico) disabled @endif>
                            <option value="{{ $turno->hora }}">{{ \Carbon\Carbon::parse($turno->hora)->format('H:i') }}</option>
                        </select>
                    </div>

                    {{-- Este campo es solo para que el médico pueda cambiar el estado del turno --}}
                    @if ($isMedico)
                        <div class="form-group">
                            <label for="estado" class="form-label">Estado</label>
                            <select name="estado" id="estado" required class="form-input"> 
                                <option value="pendiente" {{ $turno->estado == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                <option value="realizado" {{ $turno->estado == 'realizado' ? 'selected' : '' }}>Realizado</option>
                                <option value="cancelado" {{ $turno->estado == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                                <option value="ausente" {{ $turno->estado == 'ausente' ? 'selected' : '' }}>Ausente</option>
                            </select>
                        </div>
                    @endif

                    
                        <button type="submit" class="btn-primary mt-4">Guardar cambios</button>
                    {{-- Botón de Cancelar --}}
                    @php
                        $cancelRoute = '';
                            if (auth()->check() && auth()->user()->id_rol == 1) {
                                $cancelRoute = route('admin.turnos.index');
                            } elseif (auth()->check() && auth()->user()->id_rol == 2) {
                                $cancelRoute = route('medico.turnos.index');
                            } elseif (auth()->check() && auth()->user()->id_rol == 3) {
                                $cancelRoute = route('paciente.turnos.index');
                            }
                    @endphp
                    @if($cancelRoute)
                            <a href="{{ $cancelRoute }}" class="btn-secondary ml-2">Cancelar</a>
                    @endif
                </form>

                {{-- Incluye el archivo JavaScript, pasando el ID del turno actual y su hora --}}
                <script>
                    const currentTurnoId = {{ $turno->id_turno ?? 'null' }};
                    const currentTurnoHora = "{{ $turno->hora ?? '' }}";
                    const apiUrlBase = @json(Auth::check() ? (Auth::user()->id_rol == 1 ? '/admin/turnos' : (Auth::user()->id_rol == 2 ? '/medico/turnos' : '/paciente/turnos')) : '/paciente/turnos');
                </script>
                @vite('resources/js/turnos.js')
            </div>
        </div>
    </div>
@endsection
