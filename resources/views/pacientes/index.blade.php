@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper"> {{-- Usando la nueva clase --}}
                <h1 class="page-title">Lista de Pacientes</h1> {{-- Usando la nueva clase --}}

                {{-- Botón de Inicio (dinámico por rol) --}}
                @if(auth()->check())
                    <div class="action-buttons-container"> {{-- Usando la nueva clase --}}
                        @php
                            $user = Auth::user();
                            $rolActivo = session('rol_activo');
                            $dashboardRoute = 'dashboard'; // Ruta por defecto

                            if ($rolActivo === 'Administrador') {
                                $dashboardRoute = 'admin.dashboard';
                            } elseif ($rolActivo === 'Medico') {
                                $dashboardRoute = 'medico.dashboard';
                            } elseif ($rolActivo === 'Paciente') {
                                $dashboardRoute = 'paciente.dashboard';
                            }
                        @endphp

                        @if($dashboardRoute)
                            <a href="{{ route($dashboardRoute) }}" class="btn-secondary">
                                ← Inicio
                            </a>
                        @endif
                    </div>
                @endif

                {{-- Botón para Crear Nuevo Paciente (dinámico por rol) --}}
                @if(auth()->check() && (auth()->user()->hasRole('Administrador') || auth()->user()->hasRole('Paciente')))
                    <div class="action-buttons-container mb-6"> {{-- Usando la nueva clase, y mb-6 para más separación de la tabla --}}
                        @php
                            $createPacienteRoute = '';
                            if (auth()->user()->hasRole('Administrador')) {
                                $createPacienteRoute = route('admin.pacientes.create');
                            } elseif (auth()->user()->hasRole('Paciente')) {
                                $createPacienteRoute = route('paciente.pacientes.create');
                            }
                        @endphp

                        @if($createPacienteRoute)
                            <a href="{{ $createPacienteRoute }}" class="btn-primary">
                                Agregar Paciente
                            </a>
                        @endif
                    </div>
                @endif
                {{-- Nuevo: Formulario de búsqueda por DNI --}}
                <div class="mb-4 flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                    <div class="flex items-center space-x-2">
                        <label for="dni_filtro" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Buscar por DNI:</label>
                        <input type="text" id="dni_filtro" placeholder="DNI del paciente" value="{{ request('dni_filtro') }}" class="mt-1 block w-auto pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <button id="buscar_dni_btn" class="btn-primary text-sm px-4 py-2 mt-1">Buscar</button>
                        <button id="limpiar_filtros_btn" class="btn-secondary text-sm px-4 py-2 mt-1" style="text-transform: none;" title="Restablecer filtros">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </button>
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert-success"> {{-- Usando la nueva clase --}}
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert-danger"> {{-- Usando la nueva clase --}}
                        {{ session('error') }}
                    </div>
                @endif

                @if ($pacientes->isEmpty())
                    <p class="text-gray-700 dark:text-gray-300">No hay pacientes registrados.</p>
                @else
                    <div class="table-responsive"> {{-- Usando la nueva clase --}}
                        <table class="custom-table"> {{-- Usando la nueva clase --}}
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="table-header">Nombre</th>
                                    <th scope="col" class="table-header">DNI</th>
                                    <th scope="col" class="table-header">Fecha Nacimiento</th>
                                    <th scope="col" class="table-header">Teléfono</th>
                                    <th scope="col" class="table-header">Obra Social</th>
                                    <th scope="col" class="table-header">Usuario Asociado</th>
                                    <th scope="col" class="table-header">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($pacientes as $paciente)
                                <tr>
                                    <td class="table-data">{{ $paciente->apellido }}, {{ $paciente->nombre }}</td>
                                    <td class="table-data">{{ $paciente->dni }}</td>
                                    <td class="table-data">{{ \Carbon\Carbon::parse($paciente->fecha_nacimiento)->format('d/m/Y') }}</td> 
                                    <td class="table-data">{{ $paciente->telefono }}</td> 
                                    <td class="table-data">{{ $paciente->obra_social }}</td>
                                    <td class="table-data">{{ $paciente->usuario ? $paciente->usuario->nombre . ' (' . $paciente->usuario->id_usuario . ')' : 'N/A' }}</td>
                                    <td class="table-actions"> 
                                        @if(auth()->check())
                                            @if(auth()->user()->hasRole('Administrador'))
                                                <a href="{{ route('admin.pacientes.edit', $paciente->id_paciente) }}" class="btn-info table-action-button text-sm px-4 py-2 mt-1">Editar</a>
                                                <form action="{{ route('admin.pacientes.destroy', $paciente->id_paciente) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de eliminar este paciente?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger text-sm px-4 py-2 mt-1">Eliminar</button>
                                                </form>
                                            @elseif(auth()->user()->hasRole('Paciente') && $paciente->id_usuario == auth()->user()->id_usuario)
                                                <a href="{{ route('paciente.pacientes.edit', $paciente->id_paciente) }}" class="btn-info table-action-button text-sm px-4 py-2 mt-1">Editar</a>
                                                <form action="{{ route('paciente.pacientes.destroy', $paciente->id_paciente) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de eliminar este paciente?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger text-sm px-4 py-2 mt-1">Eliminar</button>
                                                </form>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="table-data text-center">No hay pacientes registrados.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
                <div class="mt-4">
                    {{ $pacientes->links() }} {{-- Paginación --}}
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dniFiltroInput = document.getElementById('dni_filtro');
            const buscarDniBtn = document.getElementById('buscar_dni_btn');
            const limpiarFiltrosBtn = document.getElementById('limpiar_filtros_btn');

            function updateUrlAndRedirect() {
                const dniValue = dniFiltroInput.value.trim();
                const currentUrl = new URL(window.location.href);

                if (dniValue) {
                    currentUrl.searchParams.set('dni_filtro', dniValue);
                } else {
                    currentUrl.searchParams.delete('dni_filtro');
                }

                currentUrl.searchParams.set('page', 1);

                window.location.href = currentUrl.toString();
            }

            function clearFiltersAndRedirect() {
                // Reestablecer los campos a sus valores predeterminados
                const currentUrl = new URL(window.location.href);

                // Borrar todos los parámetros de búsqueda de la URL
                currentUrl.searchParams.delete('dni_filtro');
                currentUrl.searchParams.set('page', 1); // Resetear la página a 1

                // Redirigir a la URL sin los parámetros de filtro
                window.location.href = currentUrl.toString();
            }

            buscarDniBtn.addEventListener('click', function (event) {
                event.preventDefault(); 
                updateUrlAndRedirect();
            });

            dniFiltroInput.addEventListener('keydown', function (event) {
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