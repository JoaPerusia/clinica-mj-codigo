@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">Agregar Nuevo Médico</h1>

                {{-- Botón de Inicio --}}
                <div class="action-buttons-container">
                    <a href="{{ route('admin.dashboard') }}" class="btn-secondary">
                        ← Inicio
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
                        <label for="id_usuario" class="form-label">Usuario (Paciente):</label>
                        <select name="id_usuario" id="id_usuario" class="form-input" required>
                            <option value="">-- Seleccionar un Usuario --</option>
                            @foreach($usuarios as $usuario)
                                <option value="{{ $usuario->id_usuario }}" {{ old('id_usuario') == $usuario->id_usuario ? 'selected' : '' }}>
                                    {{ $usuario->nombre }} {{ $usuario->apellido }} ({{ $usuario->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Campo oculto para nombre y apellido --}}
                    {{-- Usaremos los valores del usuario seleccionado para poblar el controlador --}}
                    {{-- Esto simplifica el formulario para el administrador --}}
                    <input type="hidden" name="nombre" id="nombre">
                    <input type="hidden" name="apellido" id="apellido">


                    {{-- Selección de Especialidades --}}
                    <div class="form-group">
                        <label class="form-label">Especialidad(es):</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            @foreach($especialidades as $especialidad)
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" name="especialidades[]" value="{{ $especialidad->id_especialidad }}"
                                           class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                                           {{ in_array($especialidad->id_especialidad, old('especialidades', [])) ? 'checked' : '' }}>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $especialidad->nombre_especialidad }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Horarios de Trabajo --}}
                    <div class="form-group">
                        <label class="form-label">Horarios de Trabajo:</label>
                        @foreach($diasSemana as $key => $dia)
                            <div class="day-schedule-container border-t pt-4 mt-4 first:border-t-0 first:pt-0 first:mt-0">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-300">{{ ucfirst($dia) }}</h4>
                                    <button type="button" class="btn-primary-small add-schedule-btn" data-day-number="{{ $key }}">
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
                                                    <input type="hidden" name="horarios[{{ $key }}][{{ $horarioKey }}][dia_semana]" value="{{ $key }}">
                                                    <button type="button" class="btn-danger remove-schedule-btn w-6 h-6 p-1 flex items-center justify-center rounded-md">X</button>
                                                </div>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="mt-4 flex space-x-2">
                        <button type="submit" class="btn-primary">Guardar Médico</button>
                        <a href="{{ route('admin.medicos.index') }}" class="btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    {{-- Scripts para la lógica dinámica de horarios --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lógica para agregar campos de horario
            document.querySelectorAll('.add-schedule-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const dayNumber = this.dataset.dayNumber;
                    const container = this.closest('.day-schedule-container').querySelector('.schedule-inputs-container');
                    const index = container.querySelectorAll('.schedule-input-group').length;

                    const newGroup = document.createElement('div');
                    newGroup.classList.add('flex', 'items-center', 'space-x-2', 'mt-2', 'schedule-input-group');
                    newGroup.innerHTML = `
                        <input type="time" name="horarios[${dayNumber}][${index}][hora_inicio]" class="form-input">
                        <span class="text-gray-500">-</span>
                        <input type="time" name="horarios[${dayNumber}][${index}][hora_fin]" class="form-input">
                        <input type="hidden" name="horarios[${dayNumber}][${index}][dia_semana]" value="{{ $diasSemana[0] }}".substr(0, 0) + dayNumber>
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
