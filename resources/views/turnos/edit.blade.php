{{-- Asumiendo que este es el contenido de tu archivo turnos/edit.blade.php --}}

@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">Editar Turno</h1>

                {{-- Mensajes de éxito o error --}}
                @if (session('success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-lg shadow" role="alert">
                        <p>{{ session('success') }}</p>
                    </div>
                @endif
                @if (session('error'))
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg shadow" role="alert">
                        <p>{{ session('error') }}</p>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg shadow" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route(Auth::user()->id_rol == 1 ? 'admin.turnos.update' : (Auth::user()->id_rol == 2 ? 'medico.turnos.update' : 'paciente.turnos.update'), $turno->id_turno) }}">
                    @csrf
                    @method('PUT')

                    {{-- Campo Paciente (deshabilitado) --}}
                    <div class="form-group">
                        <label for="id_paciente" class="form-label">Paciente:</label>
                        <input type="text" id="id_paciente" class="form-input" value="{{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }} (DNI: {{ $turno->paciente->dni }})" disabled>
                        <input type="hidden" name="id_paciente" value="{{ $turno->id_paciente }}"> {{-- Mantener el valor oculto para el controlador --}}
                    </div>

                    {{-- Campo Médico (deshabilitado) --}}
                    <div class="form-group">
                        <label for="id_medico" class="form-label">Médico:</label>
                        <input type="text" id="id_medico" class="form-input" value="{{ $turno->medico->nombre }} {{ $turno->medico->apellido }}" disabled>
                        <input type="hidden" name="id_medico" value="{{ $turno->id_medico }}"> {{-- Mantener el valor oculto para el controlador --}}
                    </div>
                    
                    {{-- Campo Fecha (deshabilitado) --}}
                    <div class="form-group">
                        <label for="fecha" class="form-label">Fecha:</label>
                        <input type="date" name="fecha" id="fecha" class="form-input" value="{{ $turno->fecha }}" disabled>
                    </div>

                    {{-- Campo Hora (deshabilitado) --}}
                    <div class="form-group">
                        <label for="hora" class="form-label">Hora:</label>
                        <input type="time" name="hora" id="hora" class="form-input" value="{{ \Carbon\Carbon::parse($turno->hora)->format('H:i') }}" disabled>
                    </div>

                    {{-- Campo Estado (condicionalmente habilitado/deshabilitado) --}}
                    @php
                        $estadoActual = $turno->estado;
                        $isDisabled = in_array($estadoActual, ['realizado', 'cancelado', 'ausente']);
                        // Solo el médico y el admin pueden cambiar el estado
                        $isMedico = Auth::user()->id_rol == 2;
                        $isAdmin = Auth::user()->id_rol == 1;
                    @endphp

                    @if ($isMedico || $isAdmin)
                        <div class="form-group">
                            <label for="estado" class="form-label">Estado</label>
                            <select name="estado" id="estado" required class="form-input" {{ $isDisabled ? 'disabled' : '' }}>
                                @if ($isDisabled)
                                    {{-- Si el estado es final, solo muestra la opción actual --}}
                                    <option value="{{ $estadoActual }}" selected>{{ ucfirst($estadoActual) }}</option>
                                @else
                                    {{-- Si el estado es pendiente, permite cambiar a cualquier otro --}}
                                    <option value="pendiente" {{ $estadoActual == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="realizado" {{ $estadoActual == 'realizado' ? 'selected' : '' }}>Realizado</option>
                                    <option value="ausente" {{ $estadoActual == 'ausente' ? 'selected' : '' }}>Ausente</option>
                                @endif
                            </select>
                            @error('estado')
                                <p class="text-danger mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                        {{-- Si no es médico ni admin, solo muestra el estado actual como texto --}}
                        <div class="form-group">
                            <label for="estado" class="form-label">Estado:</label>
                            <input type="text" id="estado" class="form-input" value="{{ ucfirst($estadoActual) }}" disabled>
                        </div>
                    @endif

                    <button type="submit" class="btn-primary mt-4" {{ $isDisabled ? 'disabled' : '' }}>Actualizar Turno</button>
                    
                    @php
                        $cancelRoute = '';
                        if (auth()->check() && auth()->user()->id_rol == 1) {
                            $cancelRoute = route('admin.turnos.index');
                        } elseif (auth()->check() && auth()->user()->id_rol == 3) {
                            $cancelRoute = route('paciente.turnos.index');
                        } elseif (auth()->check() && auth()->user()->id_rol == 2) {
                            $cancelRoute = route('medico.turnos.index');
                        }
                    @endphp
                    @if($cancelRoute)
                        <a href="{{ $cancelRoute }}" class="btn-secondary ml-2">Cancelar</a>
                    @endif
                </form>
            </div>
        </div>
    </div>
@endsection
