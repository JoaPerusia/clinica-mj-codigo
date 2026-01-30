@inject('Rol', 'App\Models\Rol')
@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper"> 
                <h1 class="page-title">Editar Paciente</h1> 

                {{-- Botón de Inicio (dinámico por rol) --}}
                @if(auth()->check())
                    <div class="action-buttons-container"> 
                        @php
                            $dashboardRoute = '';
                            if (auth()->user()->hasRole($Rol::ADMINISTRADOR)) {
                                $dashboardRoute = route('admin.pacientes.index');
                            } elseif (auth()->user()->hasRole($Rol::PACIENTE)) {
                                $dashboardRoute = route('paciente.pacientes.index');
                            }
                        @endphp

                        @if($dashboardRoute)
                            <a href="{{ $dashboardRoute }}" class="btn-secondary">
                                ← Pacientes
                            </a>
                        @endif
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert-danger"> 
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Determinar la ruta de actualización dinámicamente según el rol --}}
                <form method="POST" action="
                    @if(auth()->check() && auth()->user()->hasRole($Rol::ADMINISTRADOR))
                        {{ route('admin.pacientes.update', $paciente->id_paciente) }}
                    @elseif(auth()->check() && auth()->user()->hasRole($Rol::PACIENTE))
                        {{ route('paciente.pacientes.update', $paciente->id_paciente) }}
                    @else
                        {{-- Fallback o manejo de error si el rol no está cubierto --}}
                        {{ route('dashboard') }}
                    @endif
                ">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="nombre" class="form-label">Nombre:</label>
                        <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $paciente->nombre) }}" required class="form-input" @if(!auth()->user()->hasRole($Rol::ADMINISTRADOR)) disabled @endif>
                        @if(!auth()->user()->hasRole($Rol::ADMINISTRADOR))
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Para modificar este campo, contacta a un administrador.</p>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="apellido" class="form-label">Apellido:</label>
                        <input type="text" name="apellido" id="apellido" value="{{ old('apellido', $paciente->apellido) }}" required class="form-input" @if(!auth()->user()->hasRole($Rol::ADMINISTRADOR)) disabled @endif>
                        @if(!auth()->user()->hasRole($Rol::ADMINISTRADOR))
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Para modificar este campo, contacta a un administrador.</p>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="dni" class="form-label">DNI:</label>
                        <input type="text" name="dni" id="dni" value="{{ old('dni', $paciente->dni) }}" required class="form-input"
                            @if(!auth()->user()->hasRole($Rol::ADMINISTRADOR)) disabled @endif>
                        @if(!auth()->user()->hasRole($Rol::ADMINISTRADOR))
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Para modificar el DNI, contacta a un administrador.</p>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento:</label>
                        <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" value="{{ old('fecha_nacimiento', $paciente->fecha_nacimiento) }}" required class="form-input"
                            @if(!auth()->user()->hasRole($Rol::ADMINISTRADOR)) disabled @endif>
                        @if(!auth()->user()->hasRole($Rol::ADMINISTRADOR))
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Para modificar la fecha de nacimiento, contacta a un administrador.</p>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="telefono" class="form-label">Teléfono:</label>
                        <input type="text" name="telefono" id="telefono" value="{{ old('telefono', $paciente->telefono) }}" class="form-input">
                    </div>

                    <div class="form-group">
                        <label for="id_obra_social" class="form-label">Obra Social:</label>
                        <select name="id_obra_social" id="id_obra_social" class="form-input" required 
                            @if(!auth()->user()->hasRole($Rol::ADMINISTRADOR)) disabled @endif>
                            
                            @foreach($obras_sociales as $obra)
                                <option value="{{ $obra->id_obra_social }}" 
                                    {{ old('id_obra_social', $paciente->id_obra_social) == $obra->id_obra_social ? 'selected' : '' }}>
                                    {{ $obra->nombre }}
                                </option>
                            @endforeach
                        </select>
                        
                        @if(!auth()->user()->hasRole($Rol::ADMINISTRADOR))
                            {{-- Si está deshabilitado, necesitamos enviar el valor oculto para que no falle la validación si se envía --}}
                            <input type="hidden" name="id_obra_social" value="{{ $paciente->id_obra_social }}">
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Para modificar tu obra social, contacta a un administrador.</p>
                        @endif
                    </div>

                    {{-- Campo para id_usuario (solo visible para admin, oculto para paciente) --}}
                    @if(auth()->check() && auth()->user()->hasRole($Rol::ADMINISTRADOR))
                        <div class="form-group">
                            <label for="id_usuario" class="form-label">Usuario Asociado (ID):</label>
                            <input type="number" name="id_usuario" id="id_usuario" value="{{ old('id_usuario', $paciente->id_usuario) }}" required class="form-input">
                        </div>
                    @endif

                    <button type="submit" class="btn-primary mt-4">Guardar cambios</button>
                    @php
                        $cancelRoute = '';
                        if (auth()->check() && auth()->user()->hasRole($Rol::ADMINISTRADOR)) {
                            $cancelRoute = route('admin.pacientes.index');
                        } elseif (auth()->check() && auth()->user()->hasRole($Rol::PACIENTE)) {
                            $cancelRoute = route('paciente.pacientes.index');
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
