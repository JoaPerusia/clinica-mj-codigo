@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">Turnos</h1>

                @php
                    $user = Auth::user();
                    $rolActivo = session('rol_activo');
                    $dashboardRoute = 'dashboard'; // Ruta por defecto

                    // URLs de los iconos desde un CDN
                    $realizadoIcon = 'https://img.icons8.com/color/48/checked--v1.png';
                    $ausenteIcon = 'https://img.icons8.com/emoji/48/minus-emoji.png';
                    $canceladoIcon = 'https://img.icons8.com/color/48/cancel--v1.png';
                                    
                    if ($rolActivo === 'Administrador') {
                        $dashboardRoute = 'admin.dashboard';
                    } elseif ($rolActivo === 'Medico') {
                        $dashboardRoute = 'medico.dashboard';
                    } elseif ($rolActivo === 'Paciente') {
                        $dashboardRoute = 'paciente.dashboard';
                    }
                @endphp
                {{-- Botón de Inicio (dinámico por rol) --}}
                @if($dashboardRoute)
                    <div class="action-buttons-container">
                        <a href="{{ route($dashboardRoute) }}" class="btn-secondary">
                            ← Inicio
                        </a>
                    </div>
                @endif

                {{-- Ajuste de la ruta para crear turno según el rol --}}
                @if(auth()->check() && (auth()->user()->hasRolActivo('Administrador') || auth()->user()->hasRolActivo('Paciente')))
                    <div class="action-buttons-container mb-6">
                        @if(auth()->user()->hasRolActivo('Administrador'))
                            <a href="{{ route('admin.turnos.create') }}" class="btn-primary">
                                Reservar Turno
                            </a>
                        @elseif(auth()->user()->hasRolActivo('Paciente'))
                            <a href="{{ route('paciente.turnos.create') }}" class="btn-primary">
                                Reservar Turno
                            </a>
                        @endif
                    </div>
                @endif
                
                {{-- Mensajes de estado --}}
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

                <div class="mb-4">
                    {{-- Botón para desplegar filtros --}}
                    <button id="toggle_filtros_btn"
                        class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm">
                        Mostrar/Ocultar Filtros
                    </button>

                    {{-- Contenedor de filtros (oculto por defecto) --}}
                    <div id="filtros_container" class="mt-4 hidden border border-gray-400 p-4 rounded-md bg-gray-50 dark:bg-gray-800">
                        <div class="flex flex-col sm:flex-row flex-wrap gap-4">

                            {{-- Filtro por Estado --}}
                            <div class="flex items-center space-x-2">
                                <label for="estado_filtro" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado:</label>
                                <select id="estado_filtro" name="estado_filtro" class="form-select">
                                    <option value="pendiente" {{ request('estado_filtro', 'pendiente') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="realizado" {{ request('estado_filtro') == 'realizado' ? 'selected' : '' }}>Realizado</option>
                                    <option value="cancelado" {{ request('estado_filtro') == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                                    <option value="ausente" {{ request('estado_filtro') == 'ausente' ? 'selected' : '' }}>Ausente</option>
                                    <option value="todos" {{ request('estado_filtro') == 'todos' ? 'selected' : '' }}>Todos</option>
                                </select>
                            </div>

                            {{-- DNI Paciente --}}
                            <div class="flex items-center space-x-2">
                                <label for="dni_filtro_paciente" class="block text-sm font-medium text-gray-700 dark:text-gray-300">DNI Paciente:</label>
                                <input type="text" id="dni_filtro_paciente" placeholder="DNI del paciente" value="{{ request('dni_filtro_paciente') }}" class="form-input">
                            </div>

                            {{-- DNI Médico --}}
                            <div class="flex items-center space-x-2">
                                <label for="dni_filtro_medico" class="block text-sm font-medium text-gray-700 dark:text-gray-300">DNI Médico:</label>
                                <input type="text" id="dni_filtro_medico" placeholder="DNI del médico" value="{{ request('dni_filtro_medico') }}" class="form-input">
                            </div>

                            {{-- Fecha única --}}
                            <div class="flex items-center space-x-2">
                                <label for="fecha_filtro" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha:</label>
                                <input type="date" id="fecha_filtro" name="fecha_filtro" value="{{ request('fecha_filtro') }}" class="form-input">
                            </div>

                            {{-- Rango de fechas --}}
                            <div class="flex items-center space-x-2">
                                <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Desde:</label>
                                <input type="date" id="fecha_inicio" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="form-input">
                                <label for="fecha_fin" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hasta:</label>
                                <input type="date" id="fecha_fin" name="fecha_fin" value="{{ request('fecha_fin') }}" class="form-input">
                            </div>

                            {{-- Especialidad --}}
                            <div class="flex items-center space-x-2">
                                <label for="especialidad_filtro" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Especialidad:</label>
                                <select id="especialidad_filtro" name="especialidad_filtro" class="form-select">
                                    <option value="">Todas</option>
                                    @foreach($especialidades as $esp)
                                        <option value="{{ $esp->id_especialidad }}" {{ request('especialidad_filtro') == $esp->id_especialidad ? 'selected' : '' }}>
                                            {{ $esp->nombre_especialidad }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Nombre Paciente/Médico --}}
                            <div class="flex items-center space-x-2">
                                <label for="nombre_filtro" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre:</label>
                                <input type="text" id="nombre_filtro" placeholder="Nombre" value="{{ request('nombre_filtro') }}" class="form-input">
                            </div>

                            {{-- Botones --}}
                            <div class="flex items-center space-x-2">
                                {{-- Botón Buscar (lupa) --}}
                                <button id="buscar_filtros_btn" class="btn-primary text-sm px-4 py-2 mt-1" title="Buscar">
                                    <svg xmlns="http://www.w3.org/2000/svg" 
                                        fill="none" viewBox="0 0 24 24" stroke-width="1.5" 
                                        stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" 
                                            d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 
                                                0 5.196 5.196a7.5 7.5 0 0 0 
                                                10.607 10.607Z" />
                                    </svg>
                                </button>

                                {{-- Botón Limpiar (ícono de reinicio/borrar) --}}
                                <button id="limpiar_filtros_btn" class="btn-secondary text-sm px-4 py-2 mt-1" 
                                        title="Restablecer filtros">
                                    <svg xmlns="http://www.w3.org/2000/svg" 
                                        fill="none" viewBox="0 0 24 24" stroke-width="1.5" 
                                        stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" 
                                            d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 
                                                9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    {{-- Condicional para mostrar la vista de turnos pendientes (Hoy, Mañana, Próximos) --}}
                    @if ($estado_filtro == 'pendiente' && !$fecha_filtro)
                        @if($turnosHoy->isEmpty() && $turnosManana->isEmpty() && $turnosProximos->isEmpty())
                            <p class="text-white">No tienes turnos pendientes para el filtro seleccionado.</p>
                        @else
                            {{-- Sección de "Hoy" --}}
                            @if ($turnosHoy->isNotEmpty())
                                <h2 class="sub-title text-2xl text-white">Hoy</h2>
                                <table class="custom-table">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th scope="col" class="table-header py-4">Médico</th>
                                            <th scope="col" class="table-header py-4">Especialidad</th>
                                            <th scope="col" class="table-header py-4">Paciente</th>
                                            <th scope="col" class="table-header py-4">Fecha</th>
                                            <th scope="col" class="table-header py-4">Horario</th>
                                            <th scope="col" class="table-header py-4">Estado</th>
                                            <th scope="col" class="table-header py-4">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($turnosHoy as $turno)
                                            @include('turnos.partials.turno_row', ['turno' => $turno])
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif

                            @if ($turnosHoy->isNotEmpty() && ($turnosManana->isNotEmpty() || $turnosProximos->isNotEmpty()))
                                <hr class="my-6 border-gray-300 dark:border-gray-600">
                            @endif

                            {{-- Sección de "Mañana" --}}
                            @if ($turnosManana->isNotEmpty())
                                <h2 class="sub-title text-2xl text-white">Mañana</h2>
                                <table class="custom-table">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th scope="col" class="table-header py-4">Médico</th>
                                            <th scope="col" class="table-header py-4">Especialidad</th>
                                            <th scope="col" class="table-header py-4">Paciente</th>
                                            <th scope="col" class="table-header py-4">Fecha</th>
                                            <th scope="col" class="table-header py-4">Horario</th>
                                            <th scope="col" class="table-header py-4">Estado</th>
                                            <th scope="col" class="table-header py-4">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($turnosManana as $turno)
                                            @include('turnos.partials.turno_row', ['turno' => $turno])
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif

                            @if ($turnosManana->isNotEmpty() && $turnosProximos->isNotEmpty())
                                <hr class="my-6 border-gray-300 dark:border-gray-600">
                            @endif

                            {{-- Sección de "Próximos" --}}
                            @if ($turnosProximos->isNotEmpty())
                                <h2 class="sub-title text-2xl text-white">Próximos</h2>
                                <table class="custom-table">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th scope="col" class="table-header py-4">Médico</th>
                                            <th scope="col" class="table-header py-4">Especialidad</th>
                                            <th scope="col" class="table-header py-4">Paciente</th>
                                            <th scope="col" class="table-header py-4">Fecha</th>
                                            <th scope="col" class="table-header py-4">Horario</th>
                                            <th scope="col" class="table-header py-4">Estado</th>
                                            <th scope="col" class="table-header py-4">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($turnosProximos as $turno)
                                            @include('turnos.partials.turno_row', ['turno' => $turno])
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        @endif
                    @else
                        {{-- Condicional para mostrar la vista de turnos paginados (realizados, ausentes, etc.) --}}
                        @if ($turnosPaginados->isEmpty())
                            <p class="text-white">No tienes turnos con este estado para el filtro seleccionado.</p>
                        @else
                            <table class="custom-table">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th scope="col" class="table-header py-4">Médico</th>
                                        <th scope="col" class="table-header py-4">Especialidad</th>
                                        <th scope="col" class="table-header py-4">Paciente</th>
                                        <th scope="col" class="table-header py-4">Fecha</th>
                                        <th scope="col" class="table-header py-4">Horario</th>
                                        <th scope="col" class="table-header py-4">Estado</th>
                                        <th scope="col" class="table-header py-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($turnosPaginados as $turno)
                                        @include('turnos.partials.turno_row', ['turno' => $turno])
                                    @endforeach
                                </tbody>
                            </table>
                            
                            <div class="mt-8">
                                {{ $turnosPaginados->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const estadoFiltroSelect     = document.getElementById('estado_filtro');
        const dniFiltroPacienteInput = document.getElementById('dni_filtro_paciente');
        const dniFiltroMedicoInput   = document.getElementById('dni_filtro_medico');
        const fechaFiltroInput       = document.getElementById('fecha_filtro');
        const fechaInicioInput       = document.getElementById('fecha_inicio');
        const fechaFinInput          = document.getElementById('fecha_fin');
        const especialidadSelect     = document.getElementById('especialidad_filtro');
        const nombreFiltroInput      = document.getElementById('nombre_filtro');
        const buscarFiltrosBtn       = document.getElementById('buscar_filtros_btn');
        const limpiarFiltrosBtn      = document.getElementById('limpiar_filtros_btn');
        const toggleFiltrosBtn       = document.getElementById('toggle_filtros_btn');
        const filtrosContainer       = document.getElementById('filtros_container');

        // Toggle del contenedor de filtros
        if (toggleFiltrosBtn && filtrosContainer) {
            toggleFiltrosBtn.addEventListener('click', function () {
                filtrosContainer.classList.toggle('hidden');
            });
        }

        function updateUrlAndRedirect() {
            const currentUrl = new URL(window.location.href);

            // Estado
            if (estadoFiltroSelect) {
                currentUrl.searchParams.set('estado_filtro', estadoFiltroSelect.value);
            }

            // DNI paciente
            if (dniFiltroPacienteInput) {
                const v = dniFiltroPacienteInput.value.trim();
                v ? currentUrl.searchParams.set('dni_filtro_paciente', v)
                : currentUrl.searchParams.delete('dni_filtro_paciente');
            }

            // DNI médico
            if (dniFiltroMedicoInput) {
                const v = dniFiltroMedicoInput.value.trim();
                v ? currentUrl.searchParams.set('dni_filtro_medico', v)
                : currentUrl.searchParams.delete('dni_filtro_medico');
            }

            // Fecha única
            if (fechaFiltroInput) {
                fechaFiltroInput.value
                    ? currentUrl.searchParams.set('fecha_filtro', fechaFiltroInput.value)
                    : currentUrl.searchParams.delete('fecha_filtro');
            }

            // Rango de fechas
            if (fechaInicioInput) {
                fechaInicioInput.value
                    ? currentUrl.searchParams.set('fecha_inicio', fechaInicioInput.value)
                    : currentUrl.searchParams.delete('fecha_inicio');
            }
            if (fechaFinInput) {
                fechaFinInput.value
                    ? currentUrl.searchParams.set('fecha_fin', fechaFinInput.value)
                    : currentUrl.searchParams.delete('fecha_fin');
            }

            // Especialidad
            if (especialidadSelect) {
                especialidadSelect.value
                    ? currentUrl.searchParams.set('especialidad_filtro', especialidadSelect.value)
                    : currentUrl.searchParams.delete('especialidad_filtro');
            }

            // Nombre
            if (nombreFiltroInput) {
                const v = nombreFiltroInput.value.trim();
                v ? currentUrl.searchParams.set('nombre_filtro', v)
                : currentUrl.searchParams.delete('nombre_filtro');
            }

            currentUrl.searchParams.set('page', 1);
            window.location.href = currentUrl.toString();
        }

        function clearFiltersAndRedirect() {
            const currentUrl = new URL(window.location.href);
            [
                'estado_filtro',
                'dni_filtro_paciente',
                'dni_filtro_medico',
                'fecha_filtro',
                'fecha_inicio',
                'fecha_fin',
                'especialidad_filtro',
                'nombre_filtro'
            ].forEach(param => currentUrl.searchParams.delete(param));
            currentUrl.searchParams.set('page', 1);
            window.location.href = currentUrl.toString();
        }

        // Listeners de cambio
        if (estadoFiltroSelect)  estadoFiltroSelect.addEventListener('change', updateUrlAndRedirect);
        if (fechaFiltroInput)    fechaFiltroInput.addEventListener('change', updateUrlAndRedirect);
        if (fechaInicioInput)    fechaInicioInput.addEventListener('change', updateUrlAndRedirect);
        if (fechaFinInput)       fechaFinInput.addEventListener('change', updateUrlAndRedirect);
        if (especialidadSelect)  especialidadSelect.addEventListener('change', updateUrlAndRedirect);

        // Enter en inputs de texto
        if (dniFiltroPacienteInput) {
            dniFiltroPacienteInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    updateUrlAndRedirect();
                }
            });
        }
        if (dniFiltroMedicoInput) {
            dniFiltroMedicoInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    updateUrlAndRedirect();
                }
            });
        }
        if (nombreFiltroInput) {
            nombreFiltroInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    updateUrlAndRedirect();
                }
            });
        }

        // Botones
        if (buscarFiltrosBtn) {
            buscarFiltrosBtn.addEventListener('click', function (e) {
                e.preventDefault();
                updateUrlAndRedirect();
            });
        }
        if (limpiarFiltrosBtn) {
            limpiarFiltrosBtn.addEventListener('click', function (e) {
                e.preventDefault();
                clearFiltersAndRedirect();
            });
        }
    });
    </script>

    <style>
        .acciones-fijas-columna {
            width: 160px; 
            min-width: 160px;
            
            white-space: nowrap;
        }
    </style>
@endsection