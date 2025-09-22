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
                    
                    {{-- Selector de Médico --}}
                    <div class="form-group mb-4">
                        <label for="id_medico" class="form-label">Médico</label>
                        <select name="id_medico" id="id_medico" class="form-select" required>
                            <option value="">Seleccione un médico</option>
                            @foreach($medicos as $medico)
                                <option value="{{ $medico->id_medico }}" {{ old('id_medico') == $medico->id_medico ? 'selected' : '' }}>
                                    {{ $medico->usuario->nombre }} {{ $medico->usuario->apellido }} (DNI: {{ $medico->usuario->dni }})
                                </option>
                            @endforeach
                        </select>
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