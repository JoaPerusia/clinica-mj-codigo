@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">Gestión de Bloqueos de Agenda</h1>

                {{-- Botonera Superior --}}
                <div class="action-buttons-container flex justify-between items-center mb-6">
                    {{-- Botón Inicio --}}
                    <a href="{{ route('admin.dashboard') }}" class="btn-secondary">← Inicio</a>

                    {{-- Botón Agregar --}}
                    <a href="{{ route('admin.bloqueos.create') }}" class="btn-primary">Agregar Bloqueo</a>
                </div>

                {{-- Filtros --}}
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center space-x-2">
                        <label for="dni_filtro" class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase">
                            BUSCAR MÉDICO:
                        </label>
                        
                        <input type="text" id="dni_filtro" name="dni_filtro"
                            placeholder="DNI, nombre o apellido"
                            value="{{ request('dni_filtro') }}"
                            autocomplete="off"
                            class="form-input inline-block w-64">
                        
                        {{-- Botón Buscar --}}
                        <button id="buscar_dni_btn" class="bg-blue-600 text-white px-3 py-2 rounded-md hover:bg-blue-700 transition shadow flex items-center" title="Buscar">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </button>

                        {{-- Botón Limpiar --}}
                        <button id="limpiar_filtros_btn" class="bg-gray-500 text-white px-3 py-2 rounded-md hover:bg-gray-600 transition shadow flex items-center" title="Restablecer filtros">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                {{-- Alertas --}}
                @if (session('success'))
                    <div class="alert-success mb-4">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert-danger mb-4">{{ session('error') }}</div>
                @endif

                @if($bloqueos->isEmpty())
                    <p class="text-center text-white">No hay bloqueos de agenda registrados.</p>
                @else
                    <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="table-header">Médico</th>
                                    <th class="table-header">Fecha Inicio</th>
                                    <th class="table-header">Fecha Fin</th>
                                    <th class="table-header">Hora Inicio</th>
                                    <th class="table-header">Hora Fin</th>
                                    <th class="table-header">Motivo</th>
                                    <th class="table-header"></th>
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
                                        <td class="py-4 px-6 table-data text-center"> {{-- Agregado text-center --}}
                                            @php
                                                $fechaFin = \Carbon\Carbon::parse($bloqueo->fecha_fin);
                                                $horaFin = $bloqueo->hora_fin ? \Carbon\Carbon::parse($bloqueo->hora_fin) : \Carbon\Carbon::now()->endOfDay();
                                                $fechaHoraFin = $fechaFin->setTimeFrom($horaFin);
                                            @endphp

                                            <div class="flex justify-center items-center">
                                                @if ($fechaHoraFin->isFuture())
                                                    <form action="{{ route('admin.bloqueos.destroy', $bloqueo->id_bloqueo) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de que deseas cancelar este bloqueo?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        
                                                        <button type="submit" class="pt-1" title="Cancelar Bloqueo">
                                                            <x-action-icon accion="eliminar" />
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-gray-400 dark:text-gray-500 text-sm italic">Finalizado</span>
                                                @endif
                                            </div>
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