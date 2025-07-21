<h1>Crear Nuevo Paciente</h1>

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

{{-- Determinar la ruta de almacenamiento dinámicamente según el rol --}}
<form method="POST" action="
    @if(auth()->check() && auth()->user()->id_rol == 1)
        {{ route('admin.pacientes.store') }}
    @elseif(auth()->check() && auth()->user()->id_rol == 3)
        {{ route('paciente.pacientes.store') }}
    @else
        {{-- Fallback o manejo de error si el rol no está cubierto --}}
        {{ route('dashboard') }}
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

    <label for="nombre">Nombre:</label>
    <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}" required>
    <br>

    <label for="apellido">Apellido:</label>
    <input type="text" name="apellido" id="apellido" value="{{ old('apellido') }}" required>
    <br>

    <label for="dni">DNI:</label>
    <input type="text" name="dni" id="dni" value="{{ old('dni') }}" required>
    <br>

    <label for="fecha_nacimiento">Fecha de Nacimiento:</label>
    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}" required>
    <br>

    <label for="telefono">Teléfono:</label>
    <input type="text" name="telefono" id="telefono" value="{{ old('telefono') }}">
    <br>

    {{-- NUEVO CAMPO: Obra Social --}}
    <label for="obra_social">Obra Social:</label>
    <input type="text" name="obra_social" id="obra_social" value="{{ old('obra_social') }}" required>
    <br>

    {{-- Campo para id_usuario (solo visible para admin, oculto para paciente) --}}
    @if(auth()->check() && auth()->user()->id_rol == 1)
        <label for="id_usuario">Usuario Asociado (ID):</label>
        <input type="number" name="id_usuario" id="id_usuario" value="{{ old('id_usuario') }}" required>
        <br>
    @else
        {{-- Para pacientes, el id_usuario se asigna automáticamente en el controlador --}}
        {{-- No es necesario un campo oculto aquí si el controlador lo maneja --}}
    @endif

    <button type="submit">Guardar Paciente</button>
</form>
