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

    {{-- ================= MODAL DE INFORMACIÓN DE COSTOS ================= --}}
<div id="modalInfoTurno" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="cerrarModalInfo()"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                            Detalles de Atención
                        </h3>
                        
                        <div id="modal-loading" class="mt-4 text-sm text-gray-500">
                            Cargando información...
                        </div>

                        <div id="modal-content" class="mt-4 hidden space-y-3">
                            <div>
                                <p class="text-sm font-bold text-gray-500 dark:text-gray-400">Obra Social detectada:</p>
                                <p class="text-md text-gray-900 dark:text-white" id="modal-os">-</p>
                            </div>
                            
                            <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-md border border-green-200 dark:border-green-800">
                                <p class="text-sm font-bold text-green-700 dark:text-green-400">Costo / Honorarios:</p>
                                <p class="text-xl font-bold text-green-800 dark:text-green-300" id="modal-costo">$ -</p>
                            </div>

                            <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-md border border-blue-200 dark:border-blue-800">
                                <p class="text-sm font-bold text-blue-700 dark:text-blue-400">Instrucciones / Requisitos:</p>
                                <p class="text-sm text-blue-900 dark:text-blue-200 italic" id="modal-instrucciones">-</p>
                            </div>
                        </div>

                        <div id="modal-error" class="mt-4 hidden bg-red-50 p-3 rounded text-red-600 text-sm">
                            No se pudo obtener la información.
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="cerrarModalInfo()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cerrar
                </button>
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

    <script>
        function abrirModalInfo(idMedico, idPaciente) {
            // 1. Mostrar modal y estado de carga
            const modal = document.getElementById('modalInfoTurno');
            const loading = document.getElementById('modal-loading');
            const content = document.getElementById('modal-content');
            const errorDiv = document.getElementById('modal-error');

            modal.classList.remove('hidden');
            loading.classList.remove('hidden');
            content.classList.add('hidden');
            errorDiv.classList.add('hidden');

            // 2. Consultar API
            // Usamos la ruta que ya creaste en web.php
            const url = `{{ route('api.turno.info-costo') }}?id_medico=${idMedico}&id_paciente=${idPaciente}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    loading.classList.add('hidden');
                    
                    if (data.status === 'ok' || data.status === 'warning') {
                    document.getElementById('modal-os').textContent = data.obra_social;
                    if (data.status === 'warning') {
                        document.getElementById('modal-costo').textContent = 'Consultar (Particular: $' + data.costo + ')';
                    } else {
                        let costoTexto = (!isNaN(data.costo)) ? '$ ' + data.costo : data.costo;
                        document.getElementById('modal-costo').textContent = costoTexto;
                    }

                    document.getElementById('modal-instrucciones').textContent = data.instrucciones || 'Ninguna';
                    content.classList.remove('hidden');
                }})
                .catch(error => {
                    console.error('Error:', error);
                    loading.classList.add('hidden');
                    errorDiv.textContent = 'Error de conexión al consultar los datos.';
                    errorDiv.classList.remove('hidden');
                });
        }

        function cerrarModalInfo() {
            document.getElementById('modalInfoTurno').classList.add('hidden');
        }
    </script>
@endpush