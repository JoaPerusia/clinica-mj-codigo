@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">Editar Médico</h1>

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
                
                <form action="{{ route('admin.medicos.update', $medico->id_medico) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="nombre" class="form-label">Nombre:</label>
                        <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $medico->usuario->nombre) }}" required class="form-input">
                    </div>

                    <div class="form-group">
                        <label for="apellido" class="form-label">Apellido:</label>
                        <input type="text" name="apellido" id="apellido" value="{{ old('apellido', $medico->usuario->apellido) }}" required class="form-input">
                    </div>

                    {{-- Selección de Especialidades --}}
                    <div class="form-group">
                        <label for="especialidades" class="form-label">Especialidades:</label>
                        <div class="grid grid-cols-2 gap-4">
                            @foreach($especialidades as $especialidad)
                                @php
                                    $medicoEspecialidadesIds = $medico->especialidades->pluck('id_especialidad')->toArray();
                                    $checked = in_array($especialidad->id_especialidad, old('especialidades', $medicoEspecialidadesIds));
                                @endphp
                                <div class="flex items-center">
                                    <input type="checkbox" name="especialidades[]" value="{{ $especialidad->id_especialidad }}" 
                                        id="especialidad_{{ $especialidad->id_especialidad }}" 
                                        {{ $checked ? 'checked' : '' }} 
                                        class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                                    <label for="especialidad_{{ $especialidad->id_especialidad }}" class="ms-2 text-md text-gray-600 dark:text-gray-400">
                                        {{ $especialidad->nombre_especialidad }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Horarios de Trabajo --}}
                    <div class="form-group">
                        <label class="form-label">Horarios de Trabajo:</label>
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
                            $horariosPorDia = $medico->horariosTrabajo->groupBy('dia_semana');
                        @endphp

                        @foreach($diasSemana as $dia_numero => $dia_nombre)
                            <div class="mt-10 mb-10 day-schedule-container" data-day-number="{{ $dia_numero }}">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-md font-semibold dark:text-gray-200">{{ $dia_nombre }}</h4>
                                    <button type="button" class="btn-small add-schedule-btn text-white" data-day-number="{{ $dia_numero }}">
                                        + Agregar Horario
                                    </button>
                                </div>
                                <div class="schedule-inputs-container mt-2">
                                    @php
                                        $horariosDelDia = $horariosPorDia->get($dia_numero, collect());
                                    @endphp
                                    
                                    @if($horariosDelDia->isEmpty())
                                        <div class="flex items-center space-x-2 schedule-input-group">
                                            <input type="time" name="horarios[{{ $dia_numero }}][0][hora_inicio]" class="form-input">
                                            <span class="text-gray-500">-</span>
                                            <input type="time" name="horarios[{{ $dia_numero }}][0][hora_fin]" class="form-input">
                                            <input type="hidden" name="horarios[{{ $dia_numero }}][0][dia_semana]" value="{{ $dia_numero }}">
                                        </div>
                                    @else
                                        @foreach($horariosDelDia as $key => $horario)
                                            <div class="flex items-center space-x-2 mt-2 schedule-input-group">
                                                <input type="time" name="horarios[{{ $dia_numero }}][{{ $key }}][hora_inicio]" value="{{ \Carbon\Carbon::parse($horario->hora_inicio)->format('H:i') }}" class="form-input">
                                                <span class="text-gray-500">-</span>
                                                <input type="time" name="horarios[{{ $dia_numero }}][{{ $key }}][hora_fin]" value="{{ \Carbon\Carbon::parse($horario->hora_fin)->format('H:i') }}" class="form-input">
                                                <input type="hidden" name="horarios[{{ $dia_numero }}][{{ $key }}][dia_semana]" value="{{ $dia_numero }}">
                                                <button type="button" class="btn-danger remove-schedule-btn w-6 h-6 p-1 flex items-center justify-center rounded-md">X</button>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn-primary">Guardar cambios</button>
                        <a href="{{ route('admin.medicos.index') }}" class="btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Script para la funcionalidad de agregar/eliminar campos de horario --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                        <input type="hidden" name="horarios[${dayNumber}][${index}][dia_semana]" value="${dayNumber}">
                        <button type="button" class="btn-danger remove-schedule-btn w-6 h-6 p-1 flex items-center justify-center rounded-md">X</button>
                    `;
                    container.appendChild(newGroup);
                });
            });

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-schedule-btn')) {
                    const groupToRemove = e.target.closest('.schedule-input-group');
                    groupToRemove.remove();
                }
            });
        });
    </script>
@endsection