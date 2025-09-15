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

                <div class="mb-4 flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                    {{-- Filtro por Estado --}}
                    <div class="flex items-center space-x-2">
                        <label for="estado_filtro" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filtrar por estado:</label>
                        <select id="estado_filtro" name="estado_filtro" class="mt-1 block w-auto pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="pendiente" {{ request('estado_filtro', 'pendiente') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                            <option value="realizado" {{ request('estado_filtro') == 'realizado' ? 'selected' : '' }}>Realizado</option>
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
                    <div class="table-responsive">
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
                                @foreach($turnos as $turno)
                                <tr>
                                    <td class="table-data py-4">
                                        {{ $turno->medico->usuario->nombre }} {{ $turno->medico->usuario->apellido }} - ({{ $turno->medico->usuario->dni ?? 'N/A' }})
                                    </td>
                                    <td class="table-data py-4">
                                        {{ $turno->medico->especialidades->pluck('nombre_especialidad')->implode(', ') }}
                                    </td>
                                    <td class="table-data py-4">
                                        {{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }} - ({{ $turno->paciente->dni ?? 'N/A' }})
                                    </td>
                                    <td class="table-data py-4">
                                        {{ \Carbon\Carbon::parse($turno->fecha)->format('d/m/Y') }}
                                    </td>
                                    <td class="table-data py-4">
                                        {{ \Carbon\Carbon::parse($turno->hora)->format('H:i') }}
                                    </td>
                                    <td class="table-data py-4">
                                        {{ ucfirst($turno->estado) }}
                                    </td>
                                    <td class="table-data py-4">
                                        @if(auth()->check() && $turno->estado == 'pendiente')
                                            <div class="flex justify-center space-x-2">
                                                {{-- Botones para Administrador --}}
                                                @if($user->hasRolActivo('Administrador'))
                                                    <form action="{{ route('admin.turnos.cambiar-estado', $turno->id_turno) }}" method="POST" class="inline-block" title="Marcar como realizado">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="estado" value="realizado">
                                                        <button type="submit" class="p-1">
                                                            <img src="{{ $realizadoIcon }}" alt="Realizado" class="w-7 h-7">
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.turnos.cambiar-estado', $turno->id_turno) }}" method="POST" class="inline-block" title="Marcar como ausente">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="estado" value="ausente">
                                                        <button type="submit" class="p-1">
                                                            <img src="{{ $ausenteIcon }}" alt="Ausente" class="w-7 h-7">
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.turnos.cambiar-estado', $turno->id_turno) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de cancelar este turno?');" title="Cancelar turno">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="estado" value="cancelado">
                                                        <button type="submit" class="p-1">
                                                            <img src="{{ $canceladoIcon }}" alt="Cancelar" class="w-7 h-7">
                                                        </button>
                                                    </form>
                                                
                                                {{-- Botones para Médico --}}
                                                @elseif($user->hasRolActivo('Medico') && $turno->medico && $turno->medico->id_usuario == $user->id_usuario)
                                                    <form action="{{ route(strtolower($rolActivo) . '.turnos.cambiar-estado', $turno->id_turno) }}" method="POST" class="inline-block" title="Marcar como realizado">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="estado" value="realizado">
                                                        <button type="submit" class="p-1">
                                                            <img src="{{ $realizadoIcon }}" alt="Realizado" class="w-7 h-7">
                                                        </button>
                                                    </form>
                                                    <form action="{{ route(strtolower($rolActivo) . '.turnos.cambiar-estado', $turno->id_turno) }}" method="POST" class="inline-block" title="Marcar como ausente">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="estado" value="ausente">
                                                        <button type="submit" class="p-1">
                                                            <img src="{{ $ausenteIcon }}" alt="Ausente" class="w-7 h-7">
                                                        </button>
                                                    </form>
                                                
                                                {{-- Botón de "Cancelar" para Paciente --}}
                                                @elseif($user->hasRolActivo('Paciente'))
                                                    <form action="{{ route(strtolower($rolActivo) . '.turnos.cambiar-estado', $turno->id_turno) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de cancelar este turno?');" title="Cancelar turno">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="estado" value="cancelado">
                                                        <button type="submit" class="p-1">
                                                            <img src="{{ $canceladoIcon }}" alt="Cancelar" class="w-7 h-7">
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400">-</span>
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
            const limpiarFiltrosBtn = document.getElementById('limpiar_filtros_btn');

            function updateUrlAndRedirect() {
                const selectedEstado = estadoFiltroSelect.value;
                const dniPacienteValue = dniFiltroPacienteInput.value.trim();
                const dniMedicoValue = dniFiltroMedicoInput.value.trim();
                const currentUrl = new URL(window.location.href);

                currentUrl.searchParams.set('estado_filtro', selectedEstado);
                if (dniPacienteValue) {
                    currentUrl.searchParams.set('dni_filtro_paciente', dniPacienteValue);
                } else {
                    currentUrl.searchParams.delete('dni_filtro_paciente');
                }
                if (dniMedicoValue) {
                    currentUrl.searchParams.set('dni_filtro_medico', dniMedicoValue);
                } else {
                    currentUrl.searchParams.delete('dni_filtro_medico');
                }
                currentUrl.searchParams.set('page', 1);

                window.location.href = currentUrl.toString();
            }

            function clearFiltersAndRedirect() {
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.delete('estado_filtro');
                currentUrl.searchParams.delete('dni_filtro_paciente');
                currentUrl.searchParams.delete('dni_filtro_medico');
                currentUrl.searchParams.set('page', 1);
                window.location.href = currentUrl.toString();
            }

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
            limpiarFiltrosBtn.addEventListener('click', function (event) {
                event.preventDefault();
                clearFiltersAndRedirect();
            });
        });
    </script>
@endsection