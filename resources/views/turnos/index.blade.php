@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper"> 
                <h1 class="page-title">Turnos</h1> 

                {{-- Botón de Inicio (dinámico por rol) --}}
                @if(auth()->check())
                    <div class="action-buttons-container"> 
                        @php
                            $dashboardRoute = '';
                            if (auth()->user()->hasRole('Administrador')) {
                                $dashboardRoute = route('admin.dashboard');
                            } elseif (auth()->user()->hasRole('Medico')) {
                                $dashboardRoute = route('medico.dashboard');
                            } elseif (auth()->user()->hasRole('Paciente')) {
                                $dashboardRoute = route('paciente.dashboard');
                            }
                        @endphp

                        @if($dashboardRoute)
                            <a href="{{ $dashboardRoute }}" class="btn-secondary">
                                ← Inicio
                            </a>
                        @endif
                    </div>
                @endif

                {{-- Ajuste de la ruta para crear turno según el rol --}}
                @if(auth()->check() && (auth()->user()->hasRolActivo('Administrador') || auth()->user()->hasRolActivo('Paciente')))
                    <div class="action-buttons-container mb-6"> 
                        <a href="{{ route(strtolower(session('rol_activo')) . '.turnos.create') }}" class="btn-primary">
                            Reservar Turno
                        </a>
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

                <div class="mb-4 flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                    {{-- Filtro por Estado --}}
                    <div class="flex items-center space-x-2">
                        <label for="estado_filtro" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filtrar por estado:</label>
                        <select id="estado_filtro" name="estado_filtro" class="mt-1 block w-auto pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="pendiente" {{ request('estado_filtro', 'pendiente') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                            <option value="realizado_atendido" {{ request('estado_filtro') == 'realizado_atendido' ? 'selected' : '' }}>Realizado/Atendido</option>
                            <option value="cancelado" {{ request('estado_filtro') == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                            <option value="ausente" {{ request('estado_filtro') == 'ausente' ? 'selected' : '' }}>Ausente</option>
                            <option value="todos" {{ request('estado_filtro') == 'todos' ? 'selected' : '' }}>Todos</option>
                        </select>
                    </div>

                    {{-- Buscador por DNI de Paciente --}}
                    <div class="flex items-center space-x-2">
                        <label for="dni_filtro_paciente" class="block text-sm font-medium text-gray-700 dark:text-gray-300">DNI Paciente:</label>
                        <input type="text" id="dni_filtro_paciente" placeholder="DNI del paciente" value="{{ request('dni_filtro_paciente') }}" class="mt-1 block w-auto pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    {{-- Buscador por DNI de Médico con el único botón de búsqueda --}}
                    <div class="flex items-center space-x-2">
                        <label for="dni_filtro_medico" class="block text-sm font-medium text-gray-700 dark:text-gray-300">DNI Médico:</label>
                        <input type="text" id="dni_filtro_medico" placeholder="DNI del médico" value="{{ request('dni_filtro_medico') }}" class="mt-1 block w-auto pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <button id="buscar_filtros_btn" class="btn-primary text-sm px-4 py-2 mt-1">Buscar</button>
                        <button id="limpiar_filtros_btn" class="btn-secondary text-sm px-4 py-2 mt-1" style="text-transform: none;" title="Restablecer filtros">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </button>
                    </div>
                </div>

                @if($turnos->isEmpty())
                    <p class="text-white">No tienes turnos registrados para el filtro seleccionado.</p>
                @else
                    <div class="overflow-x-auto bg-white dark:bg-gray-800 shadow-md sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Médico
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Especialidad
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Paciente
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Fecha
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Horario
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                @foreach($turnos as $turno)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900 transition duration-150 ease-in-out">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $turno->medico->usuario->nombre }} {{ $turno->medico->usuario->apellido }} - ({{ $turno->medico->usuario->dni ?? 'N/A' }})
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $turno->medico->especialidades->pluck('nombre_especialidad')->implode(', ') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }} - ({{ $turno->paciente->dni ?? 'N/A' }})
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($turno->fecha)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($turno->hora)->format('H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ ucfirst($turno->estado) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        @if(auth()->check())
                                            {{-- Lógica de botones de acción con clases ajustadas para ser más pequeños --}}
                                            @if(auth()->user()->hasRolActivo('Administrador'))
                                                <a href="{{ route(strtolower(session('rol_activo')) . '.turnos.edit', $turno->id_turno) }}" class="btn-info text-sm px-3 py-2 mt-1">Editar</a>
                                                {{-- El botón de eliminar ahora solo cambia el estado a 'cancelado' --}}
                                                <form action="{{ route(strtolower(session('rol_activo')) . '.turnos.destroy', $turno->id_turno) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de cancelar este turno?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger text-sm px-3 py-2 mt-1">Cancelar</button>
                                                </form>
                                            @elseif(auth()->user()->hasRolActivo('Paciente') && $turno->estado == 'pendiente')
                                                <form action="{{ route(strtolower(session('rol_activo')) . '.turnos.destroy', $turno->id_turno) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de cancelar este turno?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger text-sm px-3 py-2 mt-1">Cancelar</button>
                                                </form>
                                            @elseif(auth()->user()->hasRolActivo('Medico') && $turno->medico && $turno->medico->id_usuario == auth()->user()->id_usuario)
                                                <a href="{{ route(strtolower(session('rol_activo')) . '.turnos.edit', $turno->id_turno) }}" class="btn-info text-sm px-3 py-2 mt-1">Editar</a>
                                            @else
                                                <span class="text-gray-500 dark:text-gray-400">No disponible</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Enlaces de paginación --}}
                    <div class="mt-4">
                        {{ $turnos->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const estadoFiltroSelect = document.getElementById('estado_filtro');
            const dniFiltroPacienteInput = document.getElementById('dni_filtro_paciente');
            const dniFiltroMedicoInput = document.getElementById('dni_filtro_medico');
            const buscarFiltrosBtn = document.getElementById('buscar_filtros_btn');
            const limpiarFiltrosBtn = document.getElementById('limpiar_filtros_btn'); // Nuevo botón de limpiar

            function updateUrlAndRedirect() {
                const selectedEstado = estadoFiltroSelect.value;
                const dniPacienteValue = dniFiltroPacienteInput.value.trim();
                const dniMedicoValue = dniFiltroMedicoInput.value.trim();
                const currentUrl = new URL(window.location.href);

                // Actualizar el parámetro 'estado_filtro'
                currentUrl.searchParams.set('estado_filtro', selectedEstado);

                // Actualizar el parámetro 'dni_filtro_paciente'
                if (dniPacienteValue) {
                    currentUrl.searchParams.set('dni_filtro_paciente', dniPacienteValue);
                } else {
                    currentUrl.searchParams.delete('dni_filtro_paciente');
                }

                // Actualizar el parámetro 'dni_filtro_medico'
                if (dniMedicoValue) {
                    currentUrl.searchParams.set('dni_filtro_medico', dniMedicoValue);
                } else {
                    currentUrl.searchParams.delete('dni_filtro_medico');
                }

                // Asegurarse de que el parámetro de página se resetee a 1
                currentUrl.searchParams.set('page', 1);

                // Redirigir a la nueva URL
                window.location.href = currentUrl.toString();
            }

            // Nueva función para limpiar los filtros
            function clearFiltersAndRedirect() {
                // Reestablecer los campos a sus valores predeterminados
                const currentUrl = new URL(window.location.href);

                // Borrar todos los parámetros de búsqueda de la URL
                currentUrl.searchParams.delete('estado_filtro');
                currentUrl.searchParams.delete('dni_filtro_paciente');
                currentUrl.searchParams.delete('dni_filtro_medico');
                currentUrl.searchParams.set('page', 1); // Resetear la página a 1

                // Redirigir a la URL sin los parámetros de filtro
                window.location.href = currentUrl.toString();
            }

            // Eventos para los filtros
            estadoFiltroSelect.addEventListener('change', updateUrlAndRedirect);

            buscarFiltrosBtn.addEventListener('click', function (event) {
                event.preventDefault(); 
                updateUrlAndRedirect();
            });

            dniFiltroPacienteInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    updateUrlAndRedirect();
                }
            });

            dniFiltroMedicoInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    updateUrlAndRedirect();
                }
            });

            // Nuevo evento para el botón "Limpiar"
            limpiarFiltrosBtn.addEventListener('click', function (event) {
                event.preventDefault();
                clearFiltersAndRedirect();
            });
        });
    </script>
@endsection