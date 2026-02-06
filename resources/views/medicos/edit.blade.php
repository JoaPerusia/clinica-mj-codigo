@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">Editar Médico</h1>

                {{-- Botón de Regresar --}}
                <div class="action-buttons-container">
                    <a href="{{ route('admin.medicos.index') }}" class="btn-secondary">
                        ← Médicos
                    </a>
                </div>

                @if ($errors->any())
                    <div class="alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <form action="{{ route('admin.medicos.update', $medico->id_medico) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="fechas_eliminar" id="fechas_eliminar" value="">

                    {{-- Nombre de Usuario - Campo estático --}}
                    <div class="form-group">
                        <label for="nombre" class="form-label">Usuario:</label>
                        <p class="form-input">{{ $medico->usuario->nombre }} {{ $medico->usuario->apellido }} (DNI: {{ $medico->usuario->dni }})</p>
                    </div>

                    {{-- Campo de selección de Especialidad --}}
                    <div class="form-group mb-8">
                        <label class="form-label">Especialidad:</label>
                        <div id="especialidades-container" class="space-y-4">
                            @php
                                // Obtener las especialidades actuales del médico
                                $medicoEspecialidadesIds = $medico->especialidades->pluck('id_especialidad')->toArray();
                            @endphp
                            @forelse(old('especialidades', $medicoEspecialidadesIds) as $especialidadId)
                                <div class="flex items-center space-x-2 specialty-select-group">
                                    <select name="especialidades[]" class="form-input w-full">
                                        <option value="">-- Seleccionar especialidad --</option>
                                        @foreach($especialidades as $especialidad)
                                            <option value="{{ $especialidad->id_especialidad }}"
                                                {{ ($especialidad->id_especialidad == $especialidadId) ? 'selected' : '' }}>
                                                {{ $especialidad->nombre_especialidad }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($loop->index > 0)
                                        <button type="button" class="btn-danger remove-specialty-btn w-6 h-6 p-1 flex items-center justify-center rounded-md">X</button>
                                    @endif
                                </div>
                            @empty
                                <div class="flex items-center space-x-2 specialty-select-group">
                                    <select name="especialidades[]" class="form-input w-full">
                                        <option value="">-- Seleccionar especialidad --</option>
                                        @foreach($especialidades as $especialidad)
                                            <option value="{{ $especialidad->id_especialidad }}">
                                                {{ $especialidad->nombre_especialidad }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforelse
                        </div>
                        <!--
                        <button type="button" id="add-specialty-btn" class="inline-flex items-center px-3 py-1 text-sm font-semibold rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 mt-2">
                            + Agregar Especialidad
                        </button>
                        -->
                    </div>

                    {{-- SECCIÓN: HORARIOS SEMANALES (GRILLA) --}}
                    <div class="mb-8">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-4">Horarios Semanales</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            @foreach($diasSemana as $dia => $nombreDia)
                                
                                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow border border-gray-200 dark:border-gray-700">
                                    <h4 class="font-bold text-gray-800 dark:text-gray-200 border-b pb-2 mb-3 uppercase text-sm">
                                        {{ $nombreDia }}
                                    </h4>

                                    <div id="horarios-container-{{ $dia }}" class="space-y-3">
                                        @php
                                            $horariosDia = $medico->horariosTrabajo->where('dia_semana', $dia);
                                        @endphp

                                        @foreach($horariosDia as $index => $horario)
                                            <div class="flex items-end justify-between gap-4 horario-row bg-gray-50 dark:bg-gray-700/50 p-3 rounded mb-2">
                                                {{-- Contenedor Inputs (Ocupa todo el espacio menos el botón) --}}
                                                <div class="flex items-center gap-4 flex-1">
                                                    {{-- Input Inicio (Crece con flex-1) --}}
                                                    <div class="flex flex-col flex-1">
                                                        <span class="text-[10px] uppercase text-gray-500 font-bold mb-1">Desde</span>
                                                        <input type="time" name="horarios[{{ $dia }}][{{ $index }}][hora_inicio]" 
                                                            value="{{ \Carbon\Carbon::parse($horario->hora_inicio)->format('H:i') }}" 
                                                            class="form-input w-full py-2 px-3 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                                    </div>
                                                    
                                                    {{-- Input Fin (Crece con flex-1) --}}
                                                    <div class="flex flex-col flex-1">
                                                        <span class="text-[10px] uppercase text-gray-500 font-bold mb-1">Hasta</span>
                                                        <input type="time" name="horarios[{{ $dia }}][{{ $index }}][hora_fin]" 
                                                            value="{{ \Carbon\Carbon::parse($horario->hora_fin)->format('H:i') }}" 
                                                            class="form-input w-full py-2 px-3 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                                    </div>
                                                </div>

                                                {{-- Botón Eliminar --}}
                                                <button type="button" class="p-1 rounded-full transition mb-0.5" onclick="eliminarHorario(this)">
                                                    <x-action-icon accion="eliminar" />
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>

                                    <button type="button" 
                                            onclick="agregarHorario({{ $dia }})"
                                            class="mt-3 text-xs flex items-center text-blue-600 dark:text-blue-400 hover:underline font-semibold uppercase tracking-wide">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Agregar Horario
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <hr class="my-8 border-gray-300 dark:border-gray-600">

                        {{-- SECCIÓN: FECHAS PUNTUALES / IRREGULARES --}}
                        <div class="form-group">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="font-semibold text-lg text-gray-800 dark:text-gray-200">
                                    📅 Fechas Puntuales / Irregulares
                                </h2>
                            </div>

                            {{-- 1. FORMULARIO DE CARGA RÁPIDA --}}
                            <div class="bg-blue-50 dark:bg-gray-700 p-4 rounded-lg border border-blue-100 dark:border-gray-600 mb-6">
                                <h3 class="text-sm font-bold text-blue-800 dark:text-blue-200 mb-3 uppercase">Agregar Nuevas Fechas</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                                    {{-- Calendario Multiple --}}
                                    <div class="md:col-span-6">
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Seleccionar Fechas (Múltiples)</label>
                                        <input type="text" id="calendario_multiple" name="fechas_nuevas" 
                                        class="form-input w-full bg-white text-gray-900 placeholder-gray-500" 
                                        placeholder="Haga clic para elegir días...">
                                    </div>

                                    {{-- Hora Inicio --}}
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Hora Inicio</label>
                                        <input type="time" name="hora_inicio_fecha" class="form-input w-full">
                                    </div>

                                    {{-- Hora Fin --}}
                                    <div class="md:col-span-3">
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Hora Fin</label>
                                        <input type="time" name="hora_fin_fecha" class="form-input w-full">
                                    </div>
                                </div>
                            </div>

                            {{-- 2. LISTADO DE FECHAS YA CARGADAS --}}
                            @if($medico->horariosFechas->count() > 0)
                                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg mt-4">
                                    <div class="max-h-60 overflow-y-auto bg-white dark:bg-gray-800"> 
                                        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600 relative">
                                            <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0 z-10 shadow-sm">
                                                <tr>
                                                    <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">Fecha</th>
                                                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">Horario</th>
                                                    <th class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-center text-sm font-semibold text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                                @foreach($medico->horariosFechas->sortBy('fecha') as $fechaPuntual)
                                                    <tr id="fila-fecha-{{ $fechaPuntual->id }}">
                                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-white">
                                                            {{ \Carbon\Carbon::parse($fechaPuntual->fecha)->format('d/m/Y') }} 
                                                            <span class="text-gray-400 text-xs ml-1">({{ \Carbon\Carbon::parse($fechaPuntual->fecha)->locale('es')->dayName }})</span>
                                                        </td>
                                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">
                                                            {{ \Carbon\Carbon::parse($fechaPuntual->hora_inicio)->format('H:i') }} - 
                                                            {{ \Carbon\Carbon::parse($fechaPuntual->hora_fin)->format('H:i') }}
                                                        </td>
                                                        <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-center text-sm font-medium sm:pr-6">
                                                            <button type="button" 
                                                                    onclick="marcarFechaParaEliminar({{ $fechaPuntual->id }})" 
                                                                    class="transition hover:opacity-75"
                                                                    title="Eliminar fecha">
                                                                <x-action-icon accion="eliminar" class="w-6 h-6" />
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <p class="text-sm text-gray-500 italic mt-2">No hay fechas puntuales cargadas.</p>
                            @endif
                        </div>

                        {{-- Campo de Duración del Turno --}}
                        <div class="form-group">
                            <label for="tiempo_turno" class="form-label">Duración del Turno (minutos):</label>
                            <input type="number" 
                                id="tiempo_turno" 
                                name="tiempo_turno" 
                                class="form-input" 
                                value="{{ old('tiempo_turno', $medico->tiempo_turno ?? 30) }}" 
                                required 
                                min="5" 
                                max="120" 
                                step="5">
                            <p class="text-xs text-gray-500 mt-1 ml-1">Tiempo estándar para cada consulta.</p>
                            @error('tiempo_turno')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Botones de acción --}}
                        <div class="form-actions-container mt-9">
                            <button type="submit" class="btn-primary">
                                Actualizar Médico
                            </button>
                            <a href="{{ route('admin.medicos.index') }}" class="btn-secondary ml-2">Cancelar</a>
                        </div>
                    </form>
                
                <hr class="my-8">

                {{-- Sección para Bloqueos Existentes --}}
                <div class="flex items-center justify-between mb-4">
                    <h2 class="page-title">Bloqueos de Agenda</h2>
                    {{-- Botón para redirigir a la gestión centralizada de bloqueos --}}
                    <a href="{{ route('admin.bloqueos.index') }}" class="btn-secondary">
                        Gestionar Bloqueos
                    </a>
                </div>

                @if ($bloqueos->isEmpty())
                    <div class="alert-info text-white">
                        <p>No hay bloqueos de agenda registrados para este médico.</p>
                    </div>
                @else
                    <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="py-3 px-6">Fecha de Inicio</th>
                                    <th scope="col" class="py-3 px-6">Fecha de Fin</th>
                                    <th scope="col" class="py-3 px-6">Hora de Inicio</th>
                                    <th scope="col" class="py-3 px-6">Hora de Fin</th>
                                    <th scope="col" class="py-3 px-6">Motivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bloqueos as $bloqueo)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="py-4 px-6">{{ $bloqueo->fecha_inicio->format('d/m/Y') }}</td>
                                        <td class="py-4 px-6">{{ $bloqueo->fecha_fin->format('d/m/Y') }}</td>
                                        <td class="py-4 px-6">{{ $bloqueo->hora_inicio ? $bloqueo->hora_inicio->format('H:i') : 'Día Completo' }}</td>
                                        <td class="py-4 px-6">{{ $bloqueo->hora_fin ? $bloqueo->hora_fin->format('H:i') : '-' }}</td>
                                        <td class="py-4 px-6">{{ $bloqueo->motivo ?? 'Sin motivo' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>

    <script>
        // 1. LÓGICA DE HORARIOS    
        function agregarHorario(dia) {
        const container = document.getElementById(`horarios-container-${dia}`);
        const index = Date.now() + Math.floor(Math.random() * 1000); 

        const nuevoHtml = `
            <div class="flex items-end justify-between gap-4 horario-row bg-gray-50 dark:bg-gray-700/50 p-3 rounded animate-fade-in-down mt-2">
                <div class="flex items-center gap-4 flex-1">
                    {{-- Input Inicio --}}
                    <div class="flex flex-col flex-1">
                        <span class="text-[10px] uppercase text-gray-500 font-bold mb-1">Desde</span>
                        <input type="time" name="horarios[${dia}][${index}][hora_inicio]" required
                            class="form-input w-full py-2 px-3 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                    </div>
                    
                    {{-- Input Fin --}}
                    <div class="flex flex-col flex-1">
                        <span class="text-[10px] uppercase text-gray-500 font-bold mb-1">Hasta</span>
                        <input type="time" name="horarios[${dia}][${index}][hora_fin]" required
                            class="form-input w-full py-2 px-3 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                    </div>

                    <input type="hidden" name="horarios[${dia}][${index}][dia_semana]" value="${dia}">
                </div>

                {{-- BOTÓN ELIMINAR ACTUALIZADO (SVG ROJO) --}}
                <button type="button" class="p-1 rounded-full transition mb-0.5" onclick="eliminarHorario(this)">
                    <svg class="w-7 h-7 text-red-500 hover:text-red-700 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>
            </div>
        `;

            container.insertAdjacentHTML('beforeend', nuevoHtml);
        }

        function eliminarHorario(btn) {
            const row = btn.closest('.horario-row');
            if (row) {
                row.remove();
            }
        }

        // 2. LÓGICA DE ELIMINACIÓN DE FECHAS
        let idsParaEliminar = [];

        function marcarFechaParaEliminar(id) {
            if(confirm('¿Quitar esta fecha de la lista? (Se confirmará al guardar cambios)')) {
                idsParaEliminar.push(id);
                document.getElementById('fechas_eliminar').value = idsParaEliminar.join(',');
                const fila = document.getElementById('fila-fecha-' + id);
                if (fila) {
                    fila.remove();
                }
            }
        }

        // 3. EVENTOS AL CARGAR (Especialidades y Flatpickr)
        document.addEventListener('DOMContentLoaded', function () {
            
            // --- FLATPICKR ---
            flatpickr("#calendario_multiple", {
                mode: "multiple", 
                dateFormat: "Y-m-d", 
                locale: "es", 
                conjunction: ", ", 
                minDate: "today", 
                disableMobile: "true" 
            });

            // --- ESPECIALIDADES ---
            const especialidadesContainer = document.getElementById('especialidades-container');
            const addSpecialtyBtn = document.getElementById('add-specialty-btn');

            if (addSpecialtyBtn) {
                addSpecialtyBtn.addEventListener('click', function() {
                    const newGroup = document.createElement('div');
                    newGroup.classList.add('flex', 'items-center', 'space-x-2', 'mt-2', 'specialty-select-group');
                    newGroup.innerHTML = `
                        <select name="especialidades[]" class="form-input w-full">
                            <option value="">-- Seleccionar especialidad --</option>
                            @foreach($especialidades as $especialidad)
                                <option value="{{ $especialidad->id_especialidad }}">
                                    {{ $especialidad->nombre_especialidad }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn-danger remove-specialty-btn w-6 h-6 p-1 flex items-center justify-center rounded-md">X</button>
                    `;
                    especialidadesContainer.appendChild(newGroup);
                });
            }

            if (especialidadesContainer) {
                especialidadesContainer.addEventListener('click', function(e) {
                    if (e.target.classList.contains('remove-specialty-btn') || e.target.closest('.remove-specialty-btn')) {
                        const btn = e.target.classList.contains('remove-specialty-btn') ? e.target : e.target.closest('.remove-specialty-btn');
                        btn.closest('.specialty-select-group').remove();
                    }
                });
            }
        });
    </script>

    <style>
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-down {
            animation: fadeInDown 0.3s ease-out forwards;
        }
    </style>
@endpush