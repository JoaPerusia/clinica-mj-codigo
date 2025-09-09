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
                        <label class="form-label">Especialidad(es):</label>
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
                        
                        <button type="button" id="add-specialty-btn" class="inline-flex items-center px-3 py-1 text-sm font-semibold rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 mt-2">
                            + Agregar Especialidad
                        </button>
                    </div>

                    {{-- Horarios de Trabajo --}}
                    <div class="form-group">
                        <h2 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Horarios de Trabajo:</h2>
                        @foreach($diasSemana as $key => $dia)
                            <div class="day-schedule-container border-t pt-4 mt-4 first:border-t-0 first:pt-0 first:mt-0">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-300">{{ ucfirst($dia) }}</h4>
                                    <button 
                                        type="button" 
                                        class="inline-flex items-center px-3 py-1 text-sm font-semibold rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 mt-2 add-schedule-btn" 
                                        data-day-number="{{ $key }}"
                                        data-day-name="{{ $dia }}"
                                    >
                                        + Agregar Horario
                                    </button>
                                </div>
                                <div class="schedule-inputs-container">
                                    {{-- Aquí se agregarán los campos dinámicamente --}}
                                    {{-- Lógica para repoblar si hay un error de validación --}}
                                    @if(old('horarios'))
                                        @foreach(old('horarios') as $horarioKey => $horario)
                                            @if($horario['dia_semana'] == $key)
                                                <div class="flex items-center space-x-2 mt-2 schedule-input-group">
                                                    <input type="time" name="horarios[{{ $key }}][{{ $horarioKey }}][hora_inicio]" value="{{ $horario['hora_inicio'] }}" class="form-input">
                                                    <span class="text-gray-500">-</span>
                                                    <input type="time" name="horarios[{{ $key }}][{{ $horarioKey }}][hora_fin]" value="{{ $horario['hora_fin'] }}" class="form-input">
                                                    <input type="hidden" name="horarios[{{ $key }}][{{ $horarioKey }}][dia_semana]" value="{{ $horario['dia_semana'] }}">
                                                    <button type="button" class="btn-danger remove-schedule-btn w-6 h-6 p-1 flex items-center justify-center rounded-md">X</button>
                                                </div>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <button type="submit" class="btn-primary mt-4">Guardar Médico</button>
                    <a href="{{ route('admin.medicos.index') }}" class="btn-secondary ml-2">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const usuarioInput = document.getElementById('usuario_input');
            const usuariosList = document.getElementById('usuarios_list');
            const idUsuarioHidden = document.getElementById('id_usuario_hidden');

            usuarioInput.addEventListener('input', function() {
                // Encuentra la opción seleccionada en la datalist
                const selectedOption = usuariosList.querySelector(`option[value="${this.value}"]`);
                
                // Si se encuentra una opción, actualiza el valor del campo oculto
                if (selectedOption) {
                    idUsuarioHidden.value = selectedOption.dataset.id;
                } else {
                    // Si el texto no coincide con una opción, vacía el campo oculto
                    idUsuarioHidden.value = '';
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const especialidadesContainer = document.getElementById('especialidades-container');
            const addSpecialtyBtn = document.getElementById('add-specialty-btn');

            // Función para crear un nuevo campo de especialidad
            function createNewSpecialtySelect() {
                const newGroup = document.createElement('div');
                newGroup.classList.add('flex', 'items-center', 'space-x-2', 'mt-2', 'specialty-select-group');
                newGroup.innerHTML = `
                    <select name="especialidades[]" class="form-input w-full" required>
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
            }

            // Agregar un nuevo campo al hacer clic en el botón
            addSpecialtyBtn.addEventListener('click', function() {
                createNewSpecialtySelect();
            });

            // Delegación de eventos para manejar la eliminación de campos dinámicos
            especialidadesContainer.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-specialty-btn')) {
                    e.target.closest('.specialty-select-group').remove();
                }
            });
        });
    </script>

    {{-- Scripts para la lógica dinámica de horarios --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lógica para agregar campos de horario
            document.querySelectorAll('.add-schedule-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const dayNumber = this.dataset.dayNumber;
                    const dayName = this.dataset.dayName; // Nuevo: Obtenemos el nombre del día
                    const container = this.closest('.day-schedule-container').querySelector('.schedule-inputs-container');
                    const index = container.querySelectorAll('.schedule-input-group').length;

                    const newGroup = document.createElement('div');
                    newGroup.classList.add('flex', 'items-center', 'space-x-2', 'mt-2', 'schedule-input-group');
                    newGroup.innerHTML = `
                        <input type="time" name="horarios[${dayNumber}][${index}][hora_inicio]" class="form-input">
                        <span class="text-gray-500">-</span>
                        <input type="time" name="horarios[${dayNumber}][${index}][hora_fin]" class="form-input">
                        <input type="hidden" name="horarios[${dayNumber}][${index}][dia_semana]" value="${dayNumber}">
                        <button type="button" class="btn-danger remove-schedule-btn w-6 h-6 p-1 flex items-center justify-center rounded-md">X</button>
                    `;
                    container.appendChild(newGroup);
                });
            });

            // Lógica para eliminar campos de horario
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-schedule-btn')) {
                    const groupToRemove = e.target.closest('.schedule-input-group');
                    groupToRemove.remove();
                }
            });
        });
    </script>
@endsection