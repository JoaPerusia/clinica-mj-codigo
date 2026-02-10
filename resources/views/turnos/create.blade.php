@inject('Rol', 'App\Models\Rol')
@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">Reservar un turno</h1>

                {{-- Botón de Inicio (dinámico por rol) --}}
                @if(auth()->check())
                    <div class="action-buttons-container"> 
                        @php
                            $dashboardRoute = '';
                            if (auth()->user()->hasRole($Rol::ADMINISTRADOR)) {
                                $dashboardRoute = route('admin.turnos.index');
                            } elseif (auth()->user()->hasRole($Rol::PACIENTE)) {
                                $dashboardRoute = route('paciente.turnos.index');
                            }
                        @endphp

                        @if($dashboardRoute)
                            <a href="{{ $dashboardRoute }}" class="btn-secondary">
                                ← Turnos
                            </a>
                        @endif
                    </div>
                @endif

                {{-- Formulario Principal --}}
                <form method="POST" action="
                    @if(auth()->check() && auth()->user()->hasRole($Rol::ADMINISTRADOR))
                        {{ route('admin.turnos.store') }}
                    @elseif(auth()->check() && auth()->user()->hasRole($Rol::MEDICO))
                        {{ route('medico.turnos.store') }}
                    @else
                        {{ route('paciente.turnos.store') }}
                    @endif
                ">
                    @csrf

                    @if ($errors->any())
                        <div class="alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- 1. Seleccionar Paciente --}}
                    <div class="form-group">
                        <label for="paciente_input" class="form-label">Paciente:</label>
                        <input type="text"
                            id="paciente_input"
                            list="pacientes_list"
                            class="form-input"
                            placeholder="Buscar por nombre, apellido o DNI..."
                            autocomplete="off"
                            required>

                        <datalist id="pacientes_list">
                            @foreach($pacientes as $paciente)
                                <option value="{{ $paciente->nombre }} {{ $paciente->apellido }} (DNI: {{ $paciente->dni }})"
                                        data-id="{{ $paciente->id_paciente }}">
                            @endforeach
                        </datalist>

                        <input type="hidden" name="id_paciente" id="id_paciente_hidden" value="{{ old('id_paciente') }}">
                    </div>

                    {{-- 2. Seleccionar Especialidad --}}
                    <div class="form-group">
                        <label for="id_especialidad" class="form-label">Especialidad</label>
                        <select name="id_especialidad" id="id_especialidad" class="form-input">
                            <option value="">Selecciona una especialidad</option>
                            @foreach($especialidades as $especialidad)
                                <option value="{{ $especialidad->id_especialidad }}">{{ $especialidad->nombre_especialidad }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 3. Seleccionar Médico --}}
                    <div class="form-group">
                        <label for="id_medico" class="form-label">Médico:</label>
                        <select name="id_medico" id="id_medico" required class="form-input" disabled>
                            <option value="">Selecciona una especialidad primero</option>
                        </select> 
                    </div>

                    {{-- Info de Costos e Instrucciones segun medico y obra social --}}
                    <div id="info-turno-container" class="mb-4 p-4 bg-blue-50 dark:bg-gray-700 border-l-4 border-blue-500 rounded hidden">
                        <h4 class="text-md font-bold text-blue-700 dark:text-blue-300 mb-2">Información del Turno:</h4>
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            <strong>Obra Social:</strong> <span id="info-obra-social">Cargando...</span>
                        </p>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                            <strong>Costo (Honorarios):</strong> <span id="info-costo" class="font-bold text-green-600">$ -</span>
                        </p>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                            <strong>Instrucciones/Requisitos:</strong> <br>
                            <span id="info-instrucciones" class="italic">Seleccione médico y paciente para ver detalles.</span>
                        </p>
                    </div>

                    {{-- 4. Fecha --}}
                    <div class="form-group">
                        <label for="fecha" class="form-label">Fecha:</label>
                        
                        <input type="text" id="fecha" name="fecha" 
                               class="form-input cursor-pointer"
                               placeholder="Selecciona una fecha..." 
                               disabled required>
                        
                        <div id="referencia-colores" class="mt-2 flex items-center text-xs text-gray-600 hidden">
                            <div class="flex items-center mr-4">
                                <span class="w-3 h-3 rounded-full bg-green-200 border border-green-400 mr-1"></span>
                                <span class="ml-1 text-white">Disponible</span>
                            </div>
                            <div class="flex items-center">
                                <span class="w-3 h-3 rounded-full bg-red-200 border border-red-400 mr-1"></span>
                                <span class="ml-1 text-white">Ocupado / No atiende</span>
                            </div>
                        </div>
                    </div>

                    {{-- 5. Hora --}}
                    <div class="form-group">
                        <label for="hora" class="form-label">Hora:</label>
                        <select name="hora" id="hora" required disabled class="form-input">
                            <option value="">Selecciona primero médico y fecha</option>
                        </select>
                    </div>

                    {{-- Botones de Acción --}}
                    <button type="submit" class="btn-primary mt-4">Confirmar turno</button>
                    
                    @php
                        $cancelRoute = '';
                        if (auth()->check() && auth()->user()->hasRole($Rol::ADMINISTRADOR)) {
                            $cancelRoute = route('admin.turnos.index');
                        } elseif (auth()->check() && auth()->user()->hasRole($Rol::PACIENTE)) {
                            $cancelRoute = route('paciente.turnos.index');
                        }
                    @endphp
                    
                    @if($cancelRoute)
                        <a href="{{ $cancelRoute }}" class="btn-secondary ml-2">Cancelar</a>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- Estilos de Flatpickr --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    {{-- ESTILOS PERSONALIZADOS PARA LOS COLORES --}}
    <style>
        /* Día Disponible (Verde) */
        .flatpickr-day.dia-disponible {
            background: #dcfce7 !important; /* Verde claro */
            border-color: #86efac !important;
            color: #166534 !important; /* Texto verde oscuro */
            font-weight: bold;
        }

        /* Día Bloqueado (Rojo) */
        .flatpickr-day.dia-bloqueado {
            background: #fee2e2 !important; /* Rojo claro */
            border-color: #fca5a5 !important;
            color: #991b1b !important; /* Texto rojo oscuro */
            cursor: not-allowed;
            text-decoration: line-through;
        }
        
        /* Ajuste para que el hover no tape el color */
        .flatpickr-day.dia-disponible:hover {
            background: #bbf7d0 !important;
        }
    </style>
@endsection

@push('scripts')
    {{-- Scripts de Flatpickr --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>

    {{-- Variables para JS --}}
    <script>
        const apiUrlBase = @json(Auth::check() ? (Auth::user()->hasRole($Rol::ADMINISTRADOR) ? '/admin/turnos' : (Auth::user()->hasRole($Rol::MEDICO) ? '/medico/turnos' : '/paciente/turnos')) : '/paciente/turnos');
        const apiUrlMedicosBase = '{{ route('api.medicos.by-especialidad') }}';
        const apiUrlHorariosDisponibles = '{{ route('api.turnos.disponibles') }}';
        const apiUrlAgenda = '{{ route('api.agenda.mes') }}'; // Variable necesaria para los colores
        
        const currentTurnoId = null;
        const currentTurnoHora = '';
    </script>

    {{-- Tu Script Principal --}}
    @vite('resources/js/turnos.js')

    {{-- Script de Pacientes (Datalist) --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const pacienteInput = document.getElementById('paciente_input');
        const pacientesList = document.getElementById('pacientes_list');
        const idHidden      = document.getElementById('id_paciente_hidden');

        if(pacienteInput) {
            pacienteInput.addEventListener('input', function () {
                const texto = this.value;
                let encontrado = false;

                for (let opt of pacientesList.options) {
                    if (opt.value === texto) {
                        idHidden.value = opt.dataset.id;
                        encontrado = true;
                        break;
                    }
                }
                if (!encontrado) idHidden.value = '';
            });
        }
    });
    </script>

    <script>
    const apiUrlInfoCosto = '{{ route('api.turno.info-costo') }}';

    document.addEventListener('DOMContentLoaded', function() {
        const medicoSelect = document.getElementById('id_medico');
        const pacienteInput = document.getElementById('id_paciente_hidden'); 
        const infoContainer = document.getElementById('info-turno-container');
        const spanObraSocial = document.getElementById('info-obra-social');
        const spanCosto = document.getElementById('info-costo');
        const spanInstrucciones = document.getElementById('info-instrucciones');

        // Función para consultar la info
        function actualizarInfoTurno() {
            const idMedico = medicoSelect.value;
            const idPaciente = pacienteInput ? pacienteInput.value : null; // Si es paciente logueado, esto puede ser null y el controller lo resuelve
            
            if (idMedico) {
                let url = `${apiUrlInfoCosto}?id_medico=${idMedico}`;
                if (idPaciente) {
                    url += `&id_paciente=${idPaciente}`;
                }

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        infoContainer.classList.remove('hidden'); 
                        
                        if (data.status === 'ok') {
                            spanObraSocial.textContent = data.obra_social;
                            spanCosto.textContent = `$ ${data.costo}`;
                            spanInstrucciones.textContent = data.instrucciones;
                            infoContainer.classList.remove('border-red-500', 'bg-red-50');
                            infoContainer.classList.add('border-blue-500', 'bg-blue-50');
                        } else if (data.status === 'warning') {
                            spanObraSocial.textContent = data.obra_social;
                            spanCosto.textContent = 'Consultar (Particular: $' + data.costo + ')';                           
                            spanInstrucciones.textContent = data.mensaje;
                            infoContainer.classList.remove('border-blue-500', 'bg-blue-50');
                            infoContainer.classList.add('border-yellow-500', 'bg-yellow-50');
                        } else {
                            infoContainer.classList.add('hidden');
                        }
                    })
                    .catch(error => console.error('Error al obtener info del turno:', error));
            } else {
                infoContainer.classList.add('hidden');
            }
        }

        if (medicoSelect) {
            medicoSelect.addEventListener('change', actualizarInfoTurno);
        }
        
        const inputVisiblePaciente = document.getElementById('paciente_input');
        if (inputVisiblePaciente) {
            inputVisiblePaciente.addEventListener('input', function() {
                 setTimeout(actualizarInfoTurno, 100); 
            });
        }
    });
    </script>
@endpush