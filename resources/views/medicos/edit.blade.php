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

                                        {{-- 1. CARGA DE HORARIOS EXISTENTES --}}
                                        @foreach($horariosDia as $index => $horario)
                                            <div class="flex items-end justify-between gap-4 horario-row bg-gray-50 dark:bg-gray-700/50 p-3 rounded mb-2">
                                                <div class="flex items-center gap-4 flex-1">
                                                    {{-- Input Inicio --}}
                                                    <div class="flex flex-col flex-1">
                                                        <span class="text-[10px] uppercase text-gray-300 font-bold mb-1">Desde</span>
                                                        <input type="time" name="horarios[{{ $dia }}][{{ $index }}][hora_inicio]" 
                                                            value="{{ \Carbon\Carbon::parse($horario->hora_inicio)->format('H:i') }}" 
                                                            class="form-input w-full py-2 px-3 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                                    </div>
                                                    
                                                    {{-- Input Fin --}}
                                                    <div class="flex flex-col flex-1">
                                                        <span class="text-[10px] uppercase text-gray-300 font-bold mb-1">Hasta</span>
                                                        <input type="time" name="horarios[{{ $dia }}][{{ $index }}][hora_fin]" 
                                                            value="{{ \Carbon\Carbon::parse($horario->hora_fin)->format('H:i') }}" 
                                                            class="form-input w-full py-2 px-3 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                                    </div>

                                                    <input type="hidden" name="horarios[{{ $dia }}][{{ $index }}][dia_semana]" value="{{ $dia }}">
                                                </div>

                                                {{-- Botón Eliminar --}}
                                                <button type="button" class="text-red-500 hover:text-red-700 transition p-1" onclick="this.closest('.horario-row').remove()">
                                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Botón Agregar --}}
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
                    <div class="form-group mb-8">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="font-semibold text-lg text-gray-800 dark:text-gray-200">
                                📅 Fechas Puntuales / Irregulares
                            </h2>
                        </div>

                        {{-- 1. FORMULARIO DE CARGA RÁPIDA (CAJA AZUL) --}}
                        <div class="bg-blue-50 dark:bg-gray-700 p-4 rounded-lg border border-blue-100 dark:border-gray-600 mb-6">
                            <h3 class="text-sm font-bold text-blue-800 dark:text-blue-200 mb-3 uppercase">Agregar Nuevas Fechas</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                                {{-- Calendario Multiple --}}
                                <div class="md:col-span-5">
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Seleccionar Fechas</label>
                                    <input type="text" id="selector_fechas_lote" 
                                        class="form-input w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm placeholder-gray-500 dark:placeholder-gray-400" 
                                        placeholder="Clic para elegir varios días...">
                                </div>

                                {{-- Hora Inicio --}}
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Inicio</label>
                                    <input type="time" id="hora_inicio_lote" 
                                        class="form-input w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                                </div>

                                {{-- Hora Fin --}}
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Fin</label>
                                    <input type="time" id="hora_fin_lote" 
                                        class="form-input w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                                </div>

                                {{-- Botón Agregar --}}
                                <div class="md:col-span-3">
                                    <button type="button" onclick="procesarLoteFechas()" 
                                            class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none shadow-sm transition">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Agregar
                                    </button>
                                </div>
                            </div>
                            <p class="text-xs text-blue-600/70 dark:text-blue-300 mt-2">
                                * Selecciona varios días en el calendario, define el horario y pulsa "Agregar". Las fechas aparecerán en la lista inferior.
                            </p>
                        </div>

                        {{-- 2. LISTADO UNIFICADO (EXISTENTES + NUEVAS) --}}
                        <div id="contenedor-fechas-especiales" class="space-y-3 max-h-60 overflow-y-auto pr-2 scrollbar-fino">
                            
                            {{-- A. FECHAS YA EXISTENTES --}}
                            @foreach($medico->horariosFechas->sortBy('fecha') as $fechaPuntual)
                                <div class="flex items-center justify-between gap-4 fecha-row bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-700 shadow-sm" id="fila-fecha-bd-{{ $fechaPuntual->id }}">
                                    <div class="flex items-center gap-4 flex-1">
                                        {{-- Fecha --}}
                                        <div class="w-32">
                                            <span class="text-xs uppercase text-gray-500 dark:text-gray-400 font-bold block">REGISTRADA</span>
                                            <span class="text-sm font-medium dark:text-white">
                                                {{ \Carbon\Carbon::parse($fechaPuntual->fecha)->format('d/m/Y') }}
                                            </span>
                                        </div>

                                        {{-- Hora Inicio --}}
                                        <div class="flex flex-col flex-1">
                                            <span class="text-[10px] uppercase text-gray-300 font-bold mb-1">Desde</span>
                                            <input type="time" value="{{ \Carbon\Carbon::parse($fechaPuntual->hora_inicio)->format('H:i') }}" readonly
                                                class="form-input w-full py-1 px-2 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 bg-gray-50 rounded-md shadow-sm cursor-not-allowed">
                                        </div>

                                        {{-- Hora Fin --}}
                                        <div class="flex flex-col flex-1">
                                            <span class="text-[10px] uppercase text-gray-300 font-bold mb-1">Hasta</span>
                                            <input type="time" value="{{ \Carbon\Carbon::parse($fechaPuntual->hora_fin)->format('H:i') }}" readonly
                                                class="form-input w-full py-1 px-2 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 bg-gray-50 rounded-md shadow-sm cursor-not-allowed">
                                        </div>
                                    </div>

                                    {{-- Botón Eliminar --}}
                                    <button type="button" onclick="marcarFechaParaEliminar({{ $fechaPuntual->id }})" 
                                            class="text-red-500 hover:text-red-700 transition p-1" title="Eliminar fecha registrada">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                </div>
                            @endforeach

                            {{-- B. AQUÍ CAERÁN LAS NUEVAS FECHAS AGREGADAS POR JS --}}

                        </div>
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
                        <p class="text-xs text-gray-400 mt-1 ml-1">Tiempo estándar para cada consulta.</p>
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
        let idsParaEliminar = [];

        // 1. ELIMINAR FECHAS (LÓGICA INSTANTÁNEA)
        window.marcarFechaParaEliminar = function(id) {
            // 1. Agregamos el ID al array de eliminación
            idsParaEliminar.push(id);
            
            // 2. Actualizamos el input hidden 
            const inputEliminar = document.getElementById('fechas_eliminar');
            if (inputEliminar) {
                inputEliminar.value = idsParaEliminar.join(',');
            } else {
                console.error("Error: No se encontró el input 'fechas_eliminar'");
            }
            
            // 3. Eliminamos la fila visualmente 
            const fila = document.getElementById('fila-fecha-bd-' + id);
            if (fila) {
                fila.classList.add('opacity-0', 'transform', 'scale-95'); 
                setTimeout(() => fila.remove(), 200); 
            }
        };

        // 2. AGREGAR NUEVAS FECHAS (LOTE)
        window.procesarLoteFechas = function() {
            const inputFechas = document.getElementById('selector_fechas_lote');
            const inputInicio = document.getElementById('hora_inicio_lote');
            const inputFin = document.getElementById('hora_fin_lote');

            if (!inputFechas || !inputInicio || !inputFin) return;

            const fechasVal = inputFechas.value;
            const horaInicio = inputInicio.value;
            const horaFin = inputFin.value;

            if (!fechasVal) {
                alert('⚠️ Selecciona al menos una fecha.');
                return;
            }
            if (!horaInicio || !horaFin) {
                alert('⚠️ Define hora de inicio y fin.');
                return;
            }

            const fechasArray = fechasVal.split(', ');
            fechasArray.forEach(fecha => {
                agregarFilaFechaVisual(fecha, horaInicio, horaFin);
            });

            if(inputFechas._flatpickr) inputFechas._flatpickr.clear();
            inputInicio.value = '';
            inputFin.value = '';
        };

        function agregarFilaFechaVisual(fecha, inicio, fin) {
            const container = document.getElementById('contenedor-fechas-especiales');
            const index = Date.now() + Math.floor(Math.random() * 1000); 
            const parts = fecha.split('-');
            const fechaLegible = `${parts[2]}/${parts[1]}/${parts[0]}`;

            const html = `
                <div class="flex items-center justify-between gap-4 fecha-row bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-700 shadow-sm animate-fade-in-down mt-2">
                    <div class="flex items-center gap-4 flex-1">
                        <div class="w-32">
                            <span class="text-xs uppercase text-green-600 dark:text-green-400 font-bold block">NUEVA</span>
                            <span class="text-sm font-medium dark:text-white">${fechaLegible}</span>
                            <input type="hidden" name="fechas_nuevas[${index}][fecha]" value="${fecha}">
                        </div>
                        <div class="flex flex-col flex-1">
                            <span class="text-[10px] uppercase text-gray-300 font-bold mb-1">Desde</span>
                            <input type="time" name="fechas_nuevas[${index}][hora_inicio]" value="${inicio}" required
                                class="form-input w-full py-1 px-2 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                        </div>
                        <div class="flex flex-col flex-1">
                            <span class="text-[10px] uppercase text-gray-300 font-bold mb-1">Hasta</span>
                            <input type="time" name="fechas_nuevas[${index}][hora_fin]" value="${fin}" required
                                class="form-input w-full py-1 px-2 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                        </div>
                    </div>
                    <button type="button" class="text-red-500 hover:text-red-700 transition p-1" onclick="this.closest('.fecha-row').remove()">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        // 3. HORARIOS SEMANALES
        window.agregarHorario = function(dia) {
            const container = document.getElementById(`horarios-container-${dia}`);
            const index = Date.now() + Math.floor(Math.random() * 1000); 

            const nuevoHtml = `
                <div class="flex items-end justify-between gap-4 horario-row bg-gray-50 dark:bg-gray-700/50 p-3 rounded animate-fade-in-down mt-2">
                    <div class="flex items-center gap-4 flex-1">
                        <div class="flex flex-col flex-1">
                            <span class="text-[10px] uppercase text-gray-300 font-bold mb-1">Desde</span>
                            <input type="time" name="horarios[${dia}][${index}][hora_inicio]" required
                                class="form-input w-full py-2 px-3 text-sm rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div class="flex flex-col flex-1">
                            <span class="text-[10px] uppercase text-gray-300 font-bold mb-1">Hasta</span>
                            <input type="time" name="horarios[${dia}][${index}][hora_fin]" required
                                class="form-input w-full py-2 px-3 text-sm rounded-md shadow-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <input type="hidden" name="horarios[${dia}][${index}][dia_semana]" value="${dia}">
                    </div>
                    <button type="button" class="p-1 rounded-full transition mb-0.5" onclick="this.closest('.horario-row').remove()">
                        <svg class="w-7 h-7 text-red-500 hover:text-red-700 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                </div>`;
            container.insertAdjacentHTML('beforeend', nuevoHtml);
        }

        // 4. INICIALIZACIÓN
        document.addEventListener('DOMContentLoaded', function () {
            if(document.getElementById("selector_fechas_lote")) {
                flatpickr("#selector_fechas_lote", {
                    mode: "multiple", 
                    dateFormat: "Y-m-d", 
                    locale: "es", 
                    conjunction: ", ", 
                    minDate: "today", 
                    disableMobile: "true" 
                });
            }

            // Lógica de especialidades 
            const especialidadesContainer = document.getElementById('especialidades-container');
            const addSpecialtyBtn = document.getElementById('add-specialty-btn');

            if (especialidadesContainer) {
                especialidadesContainer.addEventListener('click', function(e) {
                    if (e.target.classList.contains('remove-specialty-btn') || e.target.closest('.remove-specialty-btn')) {
                        const btn = e.target.classList.contains('remove-specialty-btn') ? e.target : e.target.closest('.remove-specialty-btn');
                        btn.closest('.specialty-select-group').remove();
                    }
                });

                if(addSpecialtyBtn) {
                     addSpecialtyBtn.addEventListener('click', function() {
                        const firstGroup = especialidadesContainer.querySelector('.specialty-select-group');
                        if(firstGroup) {
                            const clone = firstGroup.cloneNode(true);
                            clone.querySelector('select').value = "";
                            let removeBtn = clone.querySelector('.remove-specialty-btn');
                            if(!removeBtn) {
                                const actionBtn = clone.querySelector('button');
                                if(actionBtn) {
                                    actionBtn.className = "btn-danger remove-specialty-btn w-6 h-6 p-1 flex items-center justify-center rounded-md ml-2";
                                    actionBtn.textContent = "X";
                                    actionBtn.removeAttribute('id'); 
                                }
                            }
                            especialidadesContainer.appendChild(clone);
                        }
                     });
                }
            }
        });
    </script>
    
    <style>
        .animate-fade-in-down { animation: fadeInDown 0.3s ease-out forwards; }
        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
@endpush