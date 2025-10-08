@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">Gestión de Bloqueos de Agenda</h1>

                <div class="action-buttons-container">
                    <a href="{{ route('admin.dashboard') }}" class="btn-secondary">
                        ← Inicio
                    </a>
                </div>
                
                <div class="action-buttons-container mb-6">
                    <a href="{{ route('admin.bloqueos.create') }}" class="btn-primary">
                        Agregar Bloqueo
                    </a>
                </div>

                <div class="mb-4 flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                    <div class="flex items-center space-x-2">
                        <label for="dni_filtro" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Buscar médico:
                        </label>
                        <input type="text"
                            name="dni_filtro"
                            id="dni_filtro"
                            placeholder="DNI, nombre o apellido"
                            value="{{ request('dni_filtro') }}"
                            autocomplete="off"
                            class="mt-1 inline-block w-auto pl-3 pr-10 py-2 text-base border-gray-300
                                    focus:outline-none focus:ring-indigo-500 focus:border-indigo-500
                                    sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
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

                @if($bloqueos->isEmpty())
                    <p class="text-center text-white">No hay bloqueos de agenda registrados.</p>
                @else
                    <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="py-3 px-6">Médico</th>
                                    <th scope="col" class="py-3 px-6">Fecha Inicio</th>
                                    <th scope="col" class="py-3 px-6">Fecha Fin</th>
                                    <th scope="col" class="py-3 px-6">Hora Inicio</th>
                                    <th scope="col" class="py-3 px-6">Hora Fin</th>
                                    <th scope="col" class="py-3 px-6">Motivo</th>
                                    <th scope="col" class="py-3 px-6">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bloqueos as $bloqueo)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="py-4 px-6 table-data">
                                            {{ $bloqueo->medico->usuario->nombre }} {{ $bloqueo->medico->usuario->apellido }} ({{ $bloqueo->medico->usuario->dni }})
                                        </td>
                                        <td class="py-4 px-6 table-data">{{ $bloqueo->fecha_inicio->format('d/m/Y') }}</td>
                                        <td class="py-4 px-6 table-data">{{ $bloqueo->fecha_fin->format('d/m/Y') }}</td>
                                        <td class="py-4 px-6 table-data">{{ $bloqueo->hora_inicio ? $bloqueo->hora_inicio->format('H:i') : 'Día Completo' }}</td>
                                        <td class="py-4 px-6 table-data">{{ $bloqueo->hora_fin ? $bloqueo->hora_fin->format('H:i') : '-' }}</td>
                                        <td class="py-4 px-6 table-data">{{ $bloqueo->motivo ?? 'Sin motivo' }}</td>
                                        <td class="py-4 px-6 table-data">
                                            @php
                                                $fechaFin = \Carbon\Carbon::parse($bloqueo->fecha_fin);
                                                $horaFin = $bloqueo->hora_fin ? \Carbon\Carbon::parse($bloqueo->hora_fin) : \Carbon\Carbon::now()->endOfDay();
                                                $fechaHoraFin = $fechaFin->setTimeFrom($horaFin);
                                            @endphp
                                            {{-- Se muestra el botón de cancelar solo si la fecha y hora de fin del bloqueo no ha pasado --}}
                                            @if ($fechaHoraFin->isFuture())
                                                <form action="{{ route('admin.bloqueos.destroy', $bloqueo->id_bloqueo) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de que deseas cancelar este bloqueo?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger text-sm px-4 py-2 mt-1">Cancelar</button>
                                                </form>
                                            @else
                                                <span class="text-gray-500 dark:text-gray-400">Finalizado</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $bloqueos->links() }}
                    </div>
                @endif
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
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.delete('dni_filtro');
                currentUrl.searchParams.set('page', 1);

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