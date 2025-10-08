@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">Gestión de Médicos</h1>

                <div class="action-buttons-container">
                    <a href="{{ route('admin.dashboard') }}" class="btn-secondary">
                        ← Inicio
                    </a>
                </div>
                
                <div class="action-buttons-container mb-6">
                    <a href="{{ route('admin.medicos.create') }}" class="btn-primary">
                        Agregar Médico
                    </a>
                </div>
                <div class="mb-4 flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                    <div class="flex items-center space-x-2">
                        <label for="dni_filtro" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Buscar médico:
                        </label>
                        <input type="text" id="dni_filtro" name="dni_filtro"
                            placeholder="DNI, nombre o apellido"
                            value="{{ request('dni_filtro') }}"
                            autocomplete="off"
                            class="form-input inline-block w-auto">
                        <button id="buscar_dni_btn" class="btn-primary text-sm px-4 py-2 mt-1" title="Buscar">
                            <svg xmlns="http://www.w3.org/2000/svg" 
                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" 
                                stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" 
                                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 
                                        0 5.196 5.196a7.5 7.5 0 0 0 
                                        10.607 10.607Z" />
                            </svg>
                        </button>
                        <button id="limpiar_filtros_btn" class="btn-secondary text-sm px-4 py-2 mt-1" style="text-transform: none;" title="Restablecer filtros">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                @if (session('success'))
                    <div class="alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                @if($medicos->isEmpty())
                    <p class="text-center">No hay médicos registrados.</p>
                @else
                    <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="py-3 px-6">DNI</th>
                                    <th scope="col" class="py-3 px-6">Nombre</th>
                                    <th scope="col" class="py-3 px-6">Especialidades</th>
                                    <th scope="col" class="py-3 px-6">Día</th>
                                    <th scope="col" class="py-3 px-6">Horarios</th>
                                    <th scope="col" class="py-3 px-6">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    // Mapeo de números de día a nombres, ya que los datos se guardan así.
                                    $diasSemana = [
                                        0 => 'Domingo',
                                        1 => 'Lunes',
                                        2 => 'Martes',
                                        3 => 'Miércoles',
                                        4 => 'Jueves',
                                        5 => 'Viernes',
                                        6 => 'Sábado',
                                    ];
                                @endphp
                                @foreach ($medicos as $medico)
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <td class="py-4 px-6 table-data">{{ $medico->usuario->dni }}</td>
                                    <td class="py-4 px-6 table-data">{{ $medico->usuario->nombre }} {{ $medico->usuario->apellido }}</td>
                                    <td class="py-4 px-6 table-data">
                                        @foreach($medico->especialidades as $especialidad)
                                            <span class="badge badge-info">{{ $especialidad->nombre_especialidad }}</span>
                                        @endforeach
                                    </td>
                                    <td class="py-4 px-6 table-data">
                                        @forelse($medico->horariosTrabajo as $horario)
                                            <p>{{ $diasSemana[$horario->dia_semana] ?? 'Día no válido' }}</p>
                                        @empty
                                            <p>No tiene</p>
                                        @endforelse
                                    </td>
                                    <td class="py-4 px-6 table-data">
                                        @forelse($medico->horariosTrabajo as $horario)
                                            <p>{{ \Carbon\Carbon::parse($horario->hora_inicio)->format('H:i') }} - {{ \Carbon\Carbon::parse($horario->hora_fin)->format('H:i') }}</p>
                                        @empty
                                            <p>horarios de trabajo</p>
                                        @endforelse
                                    </td>
                                    <td class="py-4 px-6 table-data">
                                        <a href="{{ route('admin.medicos.edit', $medico->id_medico) }}" class="btn-info table-action-button text-sm px-4 py-2 mt-1">Editar</a>
                                        <form action="{{ route('admin.medicos.destroy', $medico->id_medico) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de eliminar este médico?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger text-sm px-4 py-2 mt-1">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $medicos->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dniFiltroInput = document.getElementById('dni_filtro');
            const buscarDniBtn = document.getElementById('buscar_dni_btn'); // Asegúrate de que tu botón tiene este ID
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