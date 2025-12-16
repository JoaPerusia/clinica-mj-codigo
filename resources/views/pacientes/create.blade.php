@inject('Rol', 'App\Models\Rol')
@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper"> 
                <h1 class="page-title">Crear Nuevo Paciente</h1> 

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

                {{-- Determinar la ruta de almacenamiento dinámicamente según el rol --}}
                <form method="POST" action="
                    @if(auth()->check() && auth()->user()->hasRole($Rol::ADMINISTRADOR))
                        {{ route('admin.pacientes.store') }}
                    @elseif(auth()->check() && auth()->user()->hasRole($Rol::PACIENTE))
                        {{ route('paciente.pacientes.store') }}
                    @else
                        {{-- Fallback o manejo de error si el rol no está cubierto --}}
                        {{ route('dashboard') }}
                    @endif
                ">
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
                        <label for="dni" class="form-label">DNI:</label>
                        <input type="text" name="dni" id="dni" value="{{ old('dni') }}" required class="form-input">
                    </div>

                    <div class="form-group">
                        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento:</label>
                        <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}" required class="form-input">
                    </div>

                    <div class="form-group">
                        <label for="telefono" class="form-label">Teléfono:</label>
                        <input type="text" name="telefono" id="telefono" value="{{ old('telefono') }}" class="form-input">
                    </div>

                    <div class="form-group">
                        <label for="obra_social" class="form-label">Obra Social:</label>
                        <input type="text" name="obra_social" id="obra_social" value="{{ old('obra_social') }}" class="form-input">
                    </div>

                    @if(auth()->user()->hasRole($Rol::ADMINISTRADOR))
                        <div class="form-group">
                            <label for="usuario_input" class="form-label">Usuario (Paciente):</label>
                            <input
                                type="text"
                                id="usuario_input"
                                class="form-input"
                                placeholder="Buscar por nombre o DNI..."
                                list="usuarios_list"
                                value="{{ old('usuario_input') }}"
                                required
                            >

                            <datalist id="usuarios_list">
                                @foreach($usuarios as $u)
                                    <option
                                        value="{{ $u->nombre }} {{ $u->apellido }} (DNI: {{ $u->dni }})"
                                        data-id="{{ $u->id_usuario }}"
                                    ></option>
                                @endforeach
                            </datalist>

                            {{-- Este hidden envía el id al store --}}
                            <input
                                type="hidden"
                                name="id_usuario"
                                id="id_usuario_hidden"
                                value="{{ old('id_usuario') }}"
                            >
                        </div>
                    @endif

                    <button type="submit" class="btn-primary mt-4">Guardar Paciente</button>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const usuarioInput = document.getElementById('usuario_input');
            const usuariosList = document.getElementById('usuarios_list');
            const idHidden     = document.getElementById('id_usuario_hidden');

            usuarioInput.addEventListener('input', function () {
                const texto = this.value;
                let encontrado = false;

                for (let opt of usuariosList.options) {
                    if (opt.value === texto) {
                        idHidden.value = opt.dataset.id;
                        encontrado = true;
                        break;
                    }
                }

                if (!encontrado) {
                    idHidden.value = '';
                }
            });
        });
    </script>
@endsection
