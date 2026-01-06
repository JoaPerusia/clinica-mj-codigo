@inject('Rol', 'App\Models\Rol')
@inject('Turno', 'App\Models\Turno')
@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">Turnos</h1>

                @php
                    $rolActivo = session('rol_activo');
                    $dashboardRoute = 'dashboard';
                    
                    if ($rolActivo === $Rol::ADMINISTRADOR) $dashboardRoute = 'admin.dashboard';
                    elseif ($rolActivo === $Rol::MEDICO)    $dashboardRoute = 'medico.dashboard';
                    elseif ($rolActivo === $Rol::PACIENTE)  $dashboardRoute = 'paciente.dashboard';
                @endphp

                <div class="action-buttons-container mb-6 flex justify-between items-center">
                    {{-- Botón Volver --}}
                    <a href="{{ route($dashboardRoute) }}" class="btn-secondary">
                        ← Inicio
                    </a>

                    {{-- Botón Nuevo Turno (Solo Admin y Paciente) --}}
                    @if(auth()->user()->hasRolActivo($Rol::ADMINISTRADOR))
                        <a href="{{ route('admin.turnos.create') }}" class="btn-primary">Reservar Turno</a>
                    @elseif(auth()->user()->hasRolActivo($Rol::PACIENTE))
                        <a href="{{ route('paciente.turnos.create') }}" class="btn-primary">Reservar Turno</a>
                    @endif
                </div>

                {{-- Mensajes --}}
                @if (session('success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Sección de Filtros --}}
                <div class="mb-4">
                    <button id="toggle_filtros_btn" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm hover:bg-indigo-700 transition shadow">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                        </svg>
                        Filtros
                    </button>

                    <div id="filtros_container" class="mt-4 hidden border border-gray-300 p-4 rounded-md bg-gray-50 dark:bg-gray-800 shadow-inner">
                        <div class="flex flex-col sm:flex-row flex-wrap gap-4">
                                                        
                            {{-- Estado --}}
                            <div class="filter-group">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Estado</label>
                                <select id="estado_filtro" class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="pendiente" {{ request('estado_filtro', $Turno::PENDIENTE) == $Turno::PENDIENTE ? 'selected' : '' }}>Pendiente</option>
                                    <option value="realizado" {{ request('estado_filtro') == $Turno::REALIZADO ? 'selected' : '' }}>Realizado</option>
                                    <option value="cancelado" {{ request('estado_filtro') == $Turno::CANCELADO ? 'selected' : '' }}>Cancelado</option>
                                    <option value="todos" {{ request('estado_filtro') == 'todos' ? 'selected' : '' }}>Todos</option>
                                </select>
                            </div>

                            {{-- Paciente --}}
                            <div class="filter-group">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Paciente</label>
                                <input type="text" id="dni_filtro_paciente" value="{{ request('dni_filtro_paciente') }}" placeholder="DNI o Nombre" class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>

                            {{-- Médico --}}
                            @if($rolActivo !== $Rol::MEDICO)
                                <div class="filter-group">
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Médico</label>
                                    <input type="text" id="dni_filtro_medico" value="{{ request('dni_filtro_medico') }}" placeholder="DNI o Nombre" class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                            @endif

                            {{-- Fecha --}}
                            <div class="filter-group">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Fecha</label>
                                <input type="date" id="fecha_filtro" value="{{ request('fecha_filtro') }}" class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>

                            {{-- BOTONES DE ACCIÓN --}}
                            <div class="flex items-end space-x-2 pb-0.5">
                                {{-- Botón BUSCAR --}}
                                <button id="buscar_filtros_btn" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition shadow flex items-center" title="Buscar">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                    </svg>
                                </button>

                                {{-- Botón LIMPIAR filtros --}}
                                <button id="limpiar_filtros_btn" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition shadow flex items-center" title="Limpiar filtros">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TABLAS DE TURNOS (Usando el nuevo componente) --}}
                <div class="table-responsive">
                    {{-- Si los filtros están "limpios" (solo estado pendiente por defecto) --}}
                    @if ($estado_filtro == $Turno::PENDIENTE && !$fecha_filtro && !$fecha_inicio && !$fecha_fin && !$dni_filtro_paciente && !$dni_filtro_medico)
                        
                        @if($turnosHoy->isEmpty() && $turnosManana->isEmpty() && $turnosProximos->isEmpty())
                            <p class="text-white text-center mt-8 text-lg">📅 No tienes turnos pendientes próximos.</p>
                        @else
                            <x-tabla-turnos :turnos="$turnosHoy" titulo="Hoy" />
                            <x-tabla-turnos :turnos="$turnosManana" titulo="Mañana" />
                            <x-tabla-turnos :turnos="$turnosProximos" titulo="Próximos Días" />
                        @endif

                    @else
                        {{-- Resultados de búsqueda / Otros estados --}}
                        @if ($turnosPaginados->isEmpty())
                            <p class="text-white text-center mt-8 text-lg">🔍 No se encontraron turnos con estos filtros.</p>
                        @else
                            <x-tabla-turnos :turnos="$turnosPaginados" />
                        @endif
                    @endif
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Elementos
        const inputs = {
            estado: document.getElementById('estado_filtro'),
            paciente: document.getElementById('dni_filtro_paciente'),
            medico: document.getElementById('dni_filtro_medico'),
            fecha: document.getElementById('fecha_filtro'),
            inicio: document.getElementById('fecha_inicio'),
            fin: document.getElementById('fecha_fin'),
            esp: document.getElementById('especialidad_filtro')
        };
        
        const btns = {
            buscar: document.getElementById('buscar_filtros_btn'),
            limpiar: document.getElementById('limpiar_filtros_btn'),
            toggle: document.getElementById('toggle_filtros_btn'),
            container: document.getElementById('filtros_container')
        };

        // Toggle
        if (btns.toggle && btns.container) {
            btns.toggle.addEventListener('click', () => btns.container.classList.toggle('hidden'));
        }

        // Función Redirigir
        function aplicarFiltros() {
            const url = new URL(window.location.href);
            
            // Mapeo manual para que coincida con los nombres de request del controlador
            if (inputs.estado) url.searchParams.set('estado_filtro', inputs.estado.value);
            if (inputs.paciente && inputs.paciente.value) url.searchParams.set('dni_filtro_paciente', inputs.paciente.value);
            else url.searchParams.delete('dni_filtro_paciente');

            if (inputs.medico && inputs.medico.value) url.searchParams.set('dni_filtro_medico', inputs.medico.value);
            else url.searchParams.delete('dni_filtro_medico');

            if (inputs.fecha && inputs.fecha.value) url.searchParams.set('fecha_filtro', inputs.fecha.value);
            else url.searchParams.delete('fecha_filtro');

            // Reset página
            url.searchParams.set('page', 1);
            window.location.href = url.toString();
        }

        // Eventos
        if (btns.buscar) btns.buscar.addEventListener('click', aplicarFiltros);
        
        if (btns.limpiar) {
            btns.limpiar.addEventListener('click', () => {
                const url = new URL(window.location.href);
                ['estado_filtro', 'dni_filtro_paciente', 'dni_filtro_medico', 'fecha_filtro', 'fecha_inicio', 'fecha_fin'].forEach(p => url.searchParams.delete(p));
                url.searchParams.set('page', 1);
                window.location.href = url.toString();
            });
        }

        // Enter en inputs
        Object.values(inputs).forEach(input => {
            if(input && input.tagName === 'INPUT') { // Solo inputs de texto/fecha
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        aplicarFiltros();
                    }
                });
            }
        });
    });
</script>
@endpush