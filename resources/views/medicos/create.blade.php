@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">Agregar Nuevo Médico</h1>

                <div class="action-buttons-container">
                    <a href="{{ route('admin.dashboard') }}" class="btn-secondary">
                        ← Inicio
                    </a>
                </div>
                
                @if ($errors->any())
                    <div class="alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.medicos.store') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="nombre" class="form-label">Nombre:</label>
                        <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}" required class="form-input">
                    </div>

                    <div class="form-group">
                        <label for="apellido" class="form-label">Apellido:</label>
                        <input type="text" name="apellido" id="apellido" value="{{ old('apellido') }}" required class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label for="id_usuario" class="form-label">Usuario Asociado (con rol de médico):</label>
                        <select name="id_usuario" id="id_usuario" class="form-select">
                            <option value="">Seleccione un usuario</option>
                            @foreach($usuarios as $usuario)
                                <option value="{{ $usuario->id_usuario }}" {{ old('id_usuario') == $usuario->id_usuario ? 'selected' : '' }}>
                                    {{ $usuario->name }} {{ $usuario->apellido }} ({{ $usuario->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Selección de Especialidades --}}
                    <div class="form-group">
                        <label for="especialidades" class="form-label">Especialidades:</label>
                        <div class="grid grid-cols-2 gap-4">
                            @foreach($especialidades as $especialidad)
                                <div class="flex items-center">
                                    <input type="checkbox" name="especialidades[]" value="{{ $especialidad->id_especialidad }}" 
                                        id="especialidad_{{ $especialidad->id_especialidad }}" 
                                        {{ in_array($especialidad->id_especialidad, old('especialidades', [])) ? 'checked' : '' }} 
                                        class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                                    <label for="especialidad_{{ $especialidad->id_especialidad }}" class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $especialidad->nombre_especialidad }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Horarios de Trabajo --}}
                    <div class="form-group">
                        <label class="form-label">Horarios de Trabajo:</label>
                        @foreach($diasSemana as $dia)
                            <div class="flex items-center space-x-2 mt-2">
                                <span class="w-24 text-sm">{{ ucfirst($dia) }}:</span>
                                <input type="time" name="horarios[{{ $loop->index }}][hora_inicio]" value="{{ old("horarios.{$loop->index}.hora_inicio") }}" class="form-input">
                                <span class="text-gray-500">-</span>
                                <input type="time" name="horarios[{{ $loop->index }}][hora_fin]" value="{{ old("horarios.{$loop->index}.hora_fin") }}" class="form-input">
                                <input type="hidden" name="horarios[{{ $loop->index }}][dia_semana]" value="{{ $dia }}">
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn-primary">Guardar Médico</button>
                        <a href="{{ route('admin.medicos.index') }}" class="btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection