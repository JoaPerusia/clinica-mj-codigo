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
                            if (auth()->user()->id_rol == 1) {
                                $dashboardRoute = route('admin.dashboard');
                            } elseif (auth()->user()->id_rol == 2) {
                                $dashboardRoute = route('medico.dashboard');
                            } elseif (auth()->user()->id_rol == 3) {
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
                @if(auth()->check() && (auth()->user()->id_rol == 1 || auth()->user()->id_rol == 3))
                    <div class="action-buttons-container mb-6"> 
                        @if(auth()->user()->id_rol == 1)
                            <a href="{{ route('admin.turnos.create') }}" class="btn-primary">
                                Reservar Turno
                            </a>
                        @elseif(auth()->user()->id_rol == 3)
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

                {{-- Filtro de estado de turnos --}}
                <div class="mb-4 flex items-center space-x-2">
                    <label for="estado_filtro" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filtrar por estado:</label>
                    <select id="estado_filtro" class="mt-1 block w-auto pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="pendiente" {{ $estado_filtro == 'pendiente' ? 'selected' : '' }}>Pendientes</option>
                        <option value="todos" {{ $estado_filtro == 'todos' ? 'selected' : '' }}>Todos</option>
                        <option value="cancelado" {{ $estado_filtro == 'cancelado' ? 'selected' : '' }}>Cancelados</option>
                        <option value="realizado_atendido" {{ $estado_filtro == 'realizado_atendido' ? 'selected' : '' }}>Realizados/Atendidos</option>
                        <option value="ausente" {{ $estado_filtro == 'ausente' ? 'selected' : '' }}>Ausentes</option>
                    </select>
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
                                    @if(auth()->user()->id_rol != 3)
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Paciente
                                        </th>
                                    @endif
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-200">
                                        {{ $turno->medico->nombre }} {{ $turno->medico->apellido }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $turno->medico->especialidades->pluck('nombre_especialidad')->implode(', ') }}
                                    </td>
                                    @if(auth()->user()->id_rol != 3)
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }}
                                        </td>
                                    @endif
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
                                            {{-- Lógica de botones de acción --}}
                                            @if(auth()->user()->id_rol == 1)
                                                <a href="{{ route('admin.turnos.edit', $turno->id_turno) }}" class="btn-info mr-2">Editar</a>
                                                {{-- El botón de eliminar ahora solo cambia el estado a 'cancelado' --}}
                                                <form action="{{ route('admin.turnos.destroy', $turno->id_turno) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de cancelar este turno?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger">Cancelar</button>
                                                </form>
                                            @elseif(auth()->user()->id_rol == 3 && $turno->estado == 'pendiente')
                                                <form action="{{ route('paciente.turnos.destroy', $turno->id_turno) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de cancelar este turno?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger">Cancelar</button>
                                                </form>
                                            @elseif(auth()->user()->id_rol == 2 && $turno->medico && $turno->medico->id_usuario == auth()->user()->id_usuario)
                                                <a href="{{ route('medico.turnos.edit', $turno->id_turno) }}" class="btn-info">Editar</a>
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

            estadoFiltroSelect.addEventListener('change', function () {
                const selectedEstado = this.value;
                const currentUrl = new URL(window.location.href);
                
                // Actualizar el parámetro 'estado_filtro' en la URL
                if (selectedEstado === 'todos') {
                    currentUrl.searchParams.set('estado_filtro', selectedEstado); // Eliminar el parámetro si es 'todos'
                } else {
                    currentUrl.searchParams.set('estado_filtro', selectedEstado);
                }
                
                // Asegurarse de que el parámetro de página se resetee a 1 cuando se cambia el filtro de estado
                currentUrl.searchParams.set('page', 1);

                // Redirigir a la nueva URL
                window.location.href = currentUrl.toString();
            });
        });
    </script>
@endsection
