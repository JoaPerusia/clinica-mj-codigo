@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">Agregar Nuevo Médico</h1>

                {{-- Botón de Inicio --}}
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
                
                <form action="{{ route('admin.medicos.store') }}" method="POST">
                    @csrf

                    {{-- Campo de selección de Usuario --}}
                    <div class="form-group">
                        <label for="usuario_input" class="form-label">Usuario (Paciente):</label>
                        <input type="text" id="usuario_input" list="usuarios_list" class="form-input" placeholder="Buscar por nombre o DNI..." required>
                        
                        <datalist id="usuarios_list">
                            @foreach($usuarios as $usuario)
                                <option value="{{ $usuario->nombre }} {{ $usuario->apellido }} (DNI: {{ $usuario->dni }})" data-id="{{ $usuario->id_usuario }}">
                            @endforeach
                        </datalist>

                        {{-- Campo oculto para enviar el ID del usuario en el formulario --}}
                        <input type="hidden" name="id_usuario" id="id_usuario_hidden">
                    </div>

                    {{-- Campo oculto para nombre y apellido --}}
                    {{-- Usaremos los valores del usuario seleccionado para poblar el controlador --}}
                    {{-- Esto simplifica el formulario para el administrador --}}
                    <input type="hidden" name="nombre" id="nombre">
                    <input type="hidden" name="apellido" id="apellido">


                    {{-- Selección de Especialidades --}}
                    <div class="form-group mb-9">
                        <label class="form-label">Especialidad:</label>
                        <div id="especialidades-container" class="space-y-4">
                            {{-- Campo de selección inicial --}}
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
                        </div>
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
                                        {{-- Lógica para repoblar si hay error de validación (OLD DATA) --}}
                                        @if(old('horarios'))
                                            @foreach(old('horarios') as $index => $horario)
                                                @if(isset($horario['dia_semana']) && $horario['dia_semana'] == $dia)
                                                    <div class="flex items-end justify-between gap-4 horario-row bg-gray-50 dark:bg-gray-700/50 p-3 rounded mb-2">
                                                        {{-- Contenedor Inputs --}}
                                                        <div class="flex items-center gap-4 flex-1">
                                                            {{-- Input Inicio --}}
                                                            <div class="flex flex-col flex-1">
                                                                <span class="text-[10px] uppercase text-gray-300 font-bold mb-1">Desde</span>
                                                                <input type="time" name="horarios[{{ $dia }}][{{ $index }}][hora_inicio]" 
                                                                    value="{{ $horario['hora_inicio'] }}" 
                                                                    class="form-input w-full py-2 px-3 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                                            </div>
                                                            
                                                            {{-- Input Fin --}}
                                                            <div class="flex flex-col flex-1">
                                                                <span class="text-[10px] uppercase text-gray-300 font-bold mb-1">Hasta</span>
                                                                <input type="time" name="horarios[{{ $dia }}][{{ $index }}][hora_fin]" 
                                                                    value="{{ $horario['hora_fin'] }}" 
                                                                    class="form-input w-full py-2 px-3 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                                            </div>

                                                            {{-- INPUT HIDDEN --}}
                                                            <input type="hidden" name="horarios[{{ $dia }}][{{ $index }}][dia_semana]" value="{{ $dia }}">
                                                        </div>

                                                        {{-- Botón Eliminar --}}
                                                        <button type="button" class="p-1 rounded-full transition mb-0.5" onclick="eliminarHorario(this)">
                                                            <x-action-icon accion="eliminar" />
                                                        </button>
                                                    </div>
                                                @endif
                                            @endforeach
                                        @endif
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
                        <div class="form-group mb-8">
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
                                    <div class="md:col-span-5">
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Seleccionar Fechas (Múltiples)</label>
                                        <input type="text" id="selector_fechas_lote" 
                                            class="form-input w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm placeholder-gray-500 dark:placeholder-gray-400" 
                                            placeholder="Clic para elegir varios días...">
                                    </div>

                                    {{-- Hora Inicio --}}
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Hora Inicio</label>
                                        <input type="time" id="hora_inicio_lote" 
                                            class="form-input w-full bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                                    </div>

                                    {{-- Hora Fin --}}
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Hora Fin</label>
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
                                    * Selecciona varios días en el calendario, define el horario y pulsa "Agregar" para crear el grupo.
                                </p>
                            </div>

                            {{-- 2. LISTADO DINÁMICO (Aquí caen las fechas generadas) --}}
                            <div id="contenedor-fechas-especiales" class="space-y-3 max-h-60 overflow-y-auto pr-2 scrollbar-fino">
                                {{-- Si falla la validación, volvemos a pintar los que ya estaban --}}
                                @if(old('fechas_especiales'))
                                    @foreach(old('fechas_especiales') as $key => $fechaData)
                                        <div class="flex items-center justify-between gap-4 fecha-row bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-700 shadow-sm">
                                            <div class="flex items-center gap-4 flex-1">
                                                <div class="w-32">
                                                    <span class="text-xs uppercase text-blue-600 dark:text-blue-400 font-bold block">Fecha</span>
                                                    <span class="text-sm font-medium dark:text-white">{{ $fechaData['fecha'] }}</span>
                                                    <input type="hidden" name="fechas_especiales[{{ $key }}][fecha]" value="{{ $fechaData['fecha'] }}">
                                                </div>
                                                <div class="flex flex-col flex-1">
                                                    <span class="text-[10px] uppercase text-gray-300 font-bold mb-1">Desde</span>
                                                    <input type="time" name="fechas_especiales[{{ $key }}][hora_inicio]" value="{{ $fechaData['hora_inicio'] }}" required
                                                        class="form-input w-full py-1 px-2 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                                </div>
                                                <div class="flex flex-col flex-1">
                                                    <span class="text-[10px] uppercase text-gray-300 font-bold mb-1">Hasta</span>
                                                    <input type="time" name="fechas_especiales[{{ $key }}][hora_fin]" value="{{ $fechaData['hora_fin'] }}" required
                                                        class="form-input w-full py-1 px-2 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                                                </div>
                                            </div>
                                            <button type="button" class="text-red-500 hover:text-red-700 transition p-1" onclick="this.closest('.fecha-row').remove()">
                                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                <hr class="my-8 border-gray-300 dark:border-gray-600">

                    {{-- Campo de Duración del Turno --}}
                    <div class="form-group">
                        <label for="tiempo_turno" class="form-label">Duración del Turno (minutos):</label>
                        <input type="number" 
                               id="tiempo_turno" 
                               name="tiempo_turno" 
                               class="form-input" 
                               value="{{ old('tiempo_turno', 30) }}" 
                               required 
                               min="5" 
                               max="120" 
                               step="5"
                               placeholder="Ej: 30">
                        <p class="text-xs text-gray-400 mt-1 ml-1">Tiempo estándar para cada consulta (Ej: 15, 20, 30, 60).</p>
                        @error('tiempo_turno')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    
                    <button type="submit" class="btn-primary mt-4">Crear Médico</button>
                    <a href="{{ route('admin.medicos.index') }}" class="btn-secondary ml-2">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
    
@endsection

@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>

    <script>
        // 1. LÓGICA DE FECHAS (AGREGAR NUEVAS)
        window.procesarLoteFechas = function() {
            const inputFechas = document.getElementById('selector_fechas_lote');
            const inputInicio = document.getElementById('hora_inicio_lote');
            const inputFin = document.getElementById('hora_fin_lote');

            if (!inputFechas || !inputInicio || !inputFin) {
                console.error("Error: No se encuentran los inputs del formulario de fechas.");
                return;
            }

            const fechasVal = inputFechas.value;
            const horaInicio = inputInicio.value;
            const horaFin = inputFin.value;

            // Validaciones
            if (!fechasVal) {
                Swal.fire({icon: 'warning', title: 'Atención', text: 'Debes seleccionar al menos un día en el calendario.', confirmButtonColor: '#3b82f6'
                });                
            return;
            }
            
            if (!horaInicio || !horaFin) {
                Swal.fire({icon: 'warning', title: 'Atención', text: 'Por favor, ingresa la hora de inicio y fin.', confirmButtonColor: '#3b82f6'
                });                
            return;
            }

            // Procesar
            const fechasArray = fechasVal.split(', ');
            fechasArray.forEach(fecha => {
                agregarFilaFechaVisual(fecha, horaInicio, horaFin);
            });

            // Limpiar campos
            if (inputFechas._flatpickr) {
                inputFechas._flatpickr.clear();
            }
            inputInicio.value = '';
            inputFin.value = '';
        };

        // Función auxiliar para dibujar la fila
        function agregarFilaFechaVisual(fecha, inicio, fin) {
            const container = document.getElementById('contenedor-fechas-especiales');
            if (!container) return;

            const index = Date.now() + Math.floor(Math.random() * 1000); 
            const parts = fecha.split('-');
            const fechaLegible = `${parts[2]}/${parts[1]}/${parts[0]}`; // DD/MM/YYYY

            const html = `
                <div class="flex items-center justify-between gap-4 fecha-row bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-700 shadow-sm animate-fade-in-down mt-2">
                    <div class="flex items-center gap-4 flex-1">
                        {{-- Fecha --}}
                        <div class="w-32">
                            <span class="text-xs uppercase text-blue-600 dark:text-blue-400 font-bold block">Fecha</span>
                            <span class="text-sm font-medium dark:text-white">${fechaLegible}</span>
                            <input type="hidden" name="fechas_nuevas[${index}][fecha]" value="${fecha}">
                        </div>

                        {{-- Inicio --}}
                        <div class="flex flex-col flex-1">
                            <span class="text-[10px] uppercase text-gray-300 font-bold mb-1">Desde</span>
                            <input type="time" name="fechas_nuevas[${index}][hora_inicio]" value="${inicio}" required
                                   class="form-input w-full py-1 px-2 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                        </div>

                        {{-- Fin --}}
                        <div class="flex flex-col flex-1">
                            <span class="text-[10px] uppercase text-gray-300 font-bold mb-1">Hasta</span>
                            <input type="time" name="fechas_nuevas[${index}][hora_fin]" value="${fin}" required
                                   class="form-input w-full py-1 px-2 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm">
                        </div>
                    </div>

                    {{-- Eliminar --}}
                    <button type="button" class="text-red-500 hover:text-red-700 transition p-1" onclick="this.closest('.fecha-row').remove()">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        // 2. INICIALIZACIONES AL CARGAR EL DOM
        document.addEventListener('DOMContentLoaded', function () {
            
            // A) Inicializar Flatpickr (Calendario)
            if (typeof flatpickr !== 'undefined') {
                flatpickr("#selector_fechas_lote", {
                    mode: "multiple", 
                    dateFormat: "Y-m-d", 
                    conjunction: ", ", 
                    locale: "es", 
                    minDate: "today", 
                    disableMobile: "true" 
                });
            } else {
                console.error("Flatpickr no se cargó correctamente.");
            }

            // B) Lógica de Usuarios (Buscador)
            const usuarioInput = document.getElementById('usuario_input');
            const usuariosList = document.getElementById('usuarios_list');
            const idUsuarioHidden = document.getElementById('id_usuario_hidden');

            if (usuarioInput && usuariosList && idUsuarioHidden) {
                function validarUsuario() {
                    const val = usuarioInput.value;
                    const opts = usuariosList.options;
                    let match = false;
                    for (let i = 0; i < opts.length; i++) {
                        if (opts[i].value === val) {
                            idUsuarioHidden.value = opts[i].getAttribute('data-id');
                            match = true;
                            break;
                        }
                    }
                    if (match) {
                        usuarioInput.classList.add('border-green-500', 'ring-green-500');
                        usuarioInput.classList.remove('border-red-500', 'ring-red-500');
                    } else {
                        idUsuarioHidden.value = '';
                        if(val.length > 0) {
                            usuarioInput.classList.add('border-red-500', 'ring-red-500');
                            usuarioInput.classList.remove('border-green-500', 'ring-green-500');
                        }
                    }
                }
                usuarioInput.addEventListener('input', validarUsuario);
                usuarioInput.addEventListener('change', validarUsuario);
            }
        });

        // 3. LÓGICA DE HORARIOS SEMANALES (Global)
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
                    <button type="button" class="text-red-500 hover:text-red-700 transition p-1" onclick="this.closest('.horario-row').remove()">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                </div>`;
            container.insertAdjacentHTML('beforeend', nuevoHtml);
        }
    </script>
    
    <style>
        .animate-fade-in-down { animation: fadeInDown 0.3s ease-out forwards; }
        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
@endpush