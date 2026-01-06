@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">Agregar Bloqueo de Agenda</h1>

                @if ($errors->any())
                    <div class="alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <div class="action-buttons-container">
                    <a href="{{ route('admin.bloqueos.index') }}" class="btn-secondary">
                        ← Bloqueos
                    </a>
                </div>

                <form action="{{ route('admin.bloqueos.store') }}" method="POST">
                    @csrf
                    
                    {{-- Seleccionar Médico --}}
                    <div class="form-group mb-4">
                        <label for="medico_input" class="form-label block font-medium text-gray-700">Médico:</label>
                        
                        {{-- Input visible para buscar --}}
                        <input type="text"
                               id="medico_input"
                               list="medicos_list"
                               class="form-input block w-full rounded-md border-gray-300 shadow-sm"
                               placeholder="Escribe para buscar médico..."
                               autocomplete="off"
                               required>

                        {{-- Lista oculta de opciones --}}
                        <datalist id="medicos_list">
                            @foreach($medicos as $medico)
                                <option value="{{ $medico->usuario->apellido }} {{ $medico->usuario->nombre }}" 
                                        data-id="{{ $medico->id_medico }}">
                            @endforeach
                        </datalist>

                        {{-- Input oculto que envía el ID real al backend --}}
                        <input type="hidden" name="id_medico" id="id_medico_hidden" value="{{ old('id_medico') }}">
                    </div>

                    {{-- Formulario de Bloqueo --}}
                    <div class="form-group grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                            <input type="date" name="fecha_inicio" id="fecha_inicio" value="{{ old('fecha_inicio') }}" class="form-input" required>
                        </div>
                        <div>
                            <label for="fecha_fin" class="form-label">Fecha de Fin</label>
                            <input type="date" name="fecha_fin" id="fecha_fin" value="{{ old('fecha_fin') }}" class="form-input" required>
                        </div>
                        <div>
                            <label for="hora_inicio" class="form-label">Hora de Inicio (Opcional)</label>
                            <input type="time" name="hora_inicio" id="hora_inicio" value="{{ old('hora_inicio') }}" class="form-input">
                        </div>
                        <div>
                            <label for="hora_fin" class="form-label">Hora de Fin (Opcional)</label>
                            <input type="time" name="hora_fin" id="hora_fin" value="{{ old('hora_fin') }}" class="form-input">
                        </div>
                    </div>
                    <div class="form-group mt-4">
                        <label for="motivo" class="form-label">Motivo (Opcional)</label>
                        <input type="text" name="motivo" id="motivo" value="{{ old('motivo') }}" class="form-input" autocomplete="off" placeholder="Ej: Vacaciones, Licencia, Congreso">
                    </div>

                    <div class="form-actions-container mt-8">
                        <button type="submit" class="btn-primary">
                            Crear Bloqueo
                        </button>
                        <a href="{{ route('admin.bloqueos.index') }}" class="btn-secondary ml-2">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const medicoInput = document.getElementById('medico_input');
        const medicosList = document.getElementById('medicos_list');
        const idHidden    = document.getElementById('id_medico_hidden');

        medicoInput.addEventListener('input', function () {
            const val = this.value;
            let found = false;

            // Buscamos si lo escrito coincide con alguna opción de la lista
            for (let option of medicosList.options) {
                if (option.value === val) {
                    // Si coincide, guardamos el ID oculto
                    idHidden.value = option.dataset.id;
                    found = true;
                    break;
                }
            }

            // Si el usuario borra o escribe algo inválido, limpiamos el ID
            if (!found) {
                idHidden.value = '';
            }
        });
    });
</script>
@endpush