@extends('layouts.app')

@section('content')
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

                    {{-- Nombre de Usuario - Campo estático --}}
                    <div class="form-group">
                        <label for="nombre" class="form-label">Usuario:</label>
                        <p class="form-input">{{ $medico->usuario->nombre }} {{ $medico->usuario->apellido }} (DNI: {{ $medico->usuario->dni }})</p>
                    </div>

                    {{-- Campo de selección de Especialidad --}}
                    <div class="form-group mb-8">
                        <label class="form-label">Especialidad(es):</label>
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
                        <button type="button" id="add-specialty-btn" class="inline-flex items-center px-3 py-1 text-sm font-semibold rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 mt-2">
                            + Agregar Especialidad
                        </button>
                    </div>

                    {{-- Horarios de Trabajo --}}
                    <div class="form-group">
                        <h2 class="font-semibold text-lg text-gray-800 dark:text-gray-200">Horarios de Trabajo:</h2>
                        @php
                            $diasSemana = [
                                0 => 'Domingo',
                                1 => 'Lunes',
                                2 => 'Martes',
                                3 => 'Miércoles',
                                4 => 'Jueves',
                                5 => 'Viernes',
                                6 => 'Sábado',
                            ];
                            // Agrupa los horarios por día de la semana para una fácil manipulación
                            $horariosPorDia = $medico->horariosTrabajo->groupBy('dia_semana');
                        @endphp
                        
                        @foreach($diasSemana as $dia_numero => $dia_nombre)
                            <div class="day-schedule-container border-t pt-4 mt-4 first:border-t-0 first:pt-0 first:mt-0" data-day-number="{{ $dia_numero }}">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-300">{{ ucfirst($dia_nombre) }}</h4>
                                    <button 
                                        type="button" 
                                        class="inline-flex items-center px-3 py-1 text-sm font-semibold rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 mt-2 add-schedule-btn" 
                                        data-day-number="{{ $dia_numero }}"
                                        data-day-name="{{ $dia_nombre }}"
                                    >
                                        + Agregar Horario
                                    </button>
                                </div>
                                <div class="schedule-inputs-container">
                                    @php
                                        // Usa los datos antiguos en caso de errores de validación, si no, carga los datos del médico
                                        $horariosDelDia = old('horarios.' . $dia_numero, $horariosPorDia->get($dia_numero, collect()));
                                    @endphp
                                    @forelse($horariosDelDia as $key => $horario)
                                        <div class="flex items-center space-x-2 mt-2 schedule-input-group">
                                            @php
                                                // Verifica si $horario es un array (del formulario) o un objeto (de la DB)
                                                $horaInicio = is_array($horario) ? $horario['hora_inicio'] : \Carbon\Carbon::parse($horario->hora_inicio)->format('H:i');
                                                $horaFin = is_array($horario) ? $horario['hora_fin'] : \Carbon\Carbon::parse($horario->hora_fin)->format('H:i');
                                            @endphp
                                            <input type="time" name="horarios[{{ $dia_numero }}][{{ $key }}][hora_inicio]" value="{{ $horaInicio }}" class="form-input">
                                            <span class="text-gray-500">-</span>
                                            <input type="time" name="horarios[{{ $dia_numero }}][{{ $key }}][hora_fin]" value="{{ $horaFin }}" class="form-input">
                                            <input type="hidden" name="horarios[{{ $dia_numero }}][{{ $key }}][dia_semana]" value="{{ $dia_numero }}">
                                            <button type="button" class="btn-danger remove-schedule-btn w-6 h-6 p-1 flex items-center justify-center rounded-md">X</button>
                                        </div>
                                    @empty
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
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

    {{-- Scripts para la lógica dinámica de horarios y especialidades --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Lógica para agregar campos de especialidad
            const especialidadesContainer = document.getElementById('especialidades-container');
            const addSpecialtyBtn = document.getElementById('add-specialty-btn');

            function createNewSpecialtySelect() {
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
            }

            addSpecialtyBtn.addEventListener('click', function() {
                createNewSpecialtySelect();
            });

            especialidadesContainer.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-specialty-btn')) {
                    e.target.closest('.specialty-select-group').remove();
                }
            });

            // Lógica para agregar campos de horario
            document.querySelectorAll('.add-schedule-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const dayNumber = this.dataset.dayNumber;
                    const dayName = this.dataset.dayName;
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