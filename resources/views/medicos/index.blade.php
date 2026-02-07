@inject('Rol', 'App\Models\Rol')
@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">Gestión de Médicos</h1>

                {{-- Botonera Superior --}}
                <div class="action-buttons-container flex justify-between items-center mb-6">
                    <a href="{{ route('admin.dashboard') }}" class="btn-secondary">
                        ← Inicio
                    </a>
                    
                    <a href="{{ route('admin.medicos.create') }}" class="btn-primary">
                        Agregar Médico
                    </a>
                </div>

                {{-- Filtros --}}
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center space-x-2">
                        <label for="dni_filtro" class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase">
                            Buscar:
                        </label>
                        <input type="text" id="dni_filtro" name="dni_filtro"
                            placeholder="DNI, nombre o apellido"
                            value="{{ request('dni_filtro') }}"
                            autocomplete="off"
                            class="form-input inline-block w-64">
                        
                        <button id="buscar_dni_btn" class="bg-blue-600 text-white px-3 py-2 rounded-md hover:bg-blue-700 transition shadow flex items-center" title="Buscar">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                        </button>
                        <button id="limpiar_filtros_btn" class="bg-gray-500 text-white px-3 py-2 rounded-md hover:bg-gray-600 transition shadow flex items-center" title="Restablecer filtros">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                @if (session('success'))
                    <div class="alert-success mb-4">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert-danger mb-4">{{ session('error') }}</div>
                @endif

                @if($medicos->isEmpty())
                    <p class="text-center text-gray-500 py-8">No hay médicos registrados.</p>
                @else
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="table-header">DNI</th>
                                    <th class="table-header">Nombre</th>
                                    <th class="table-header">Especialidades</th>
                                    <th class="table-header">Día</th>
                                    <th class="table-header">Horarios</th>
                                    <th class="table-header">Disponibilidad Extra</th>
                                    <th class="table-header"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($medicos as $medico)
                                <tr>
                                    <td class="table-data">{{ $medico->usuario->dni }}</td>
                                    <td class="table-data">{{ $medico->usuario->nombre }} {{ $medico->usuario->apellido }}</td>
                                    <td class="table-data">
                                        @foreach($medico->especialidades as $especialidad)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-s font-medium bg-blue-100 text-blue-800 mr-1">
                                                {{ $especialidad->nombre_especialidad }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="table-data">
                                        @forelse($medico->horariosTrabajo as $horario)
                                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                                {{ $horario->nombre_dia }}
                                            </div>
                                        @empty
                                            <span class="text-gray-400 italic">Sin asignar</span>
                                        @endforelse
                                    </td>
                                    <td class="table-data">
                                        @forelse($medico->horariosTrabajo as $horario)
                                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                                {{ \Carbon\Carbon::parse($horario->hora_inicio)->format('H:i') }} - {{ \Carbon\Carbon::parse($horario->hora_fin)->format('H:i') }}
                                            </div>
                                        @empty
                                            <span class="text-gray-400 italic">-</span>
                                        @endforelse
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-center">
                                        @if($medico->horariosFechas->count() > 0)
                                            <button onclick="abrirModalFechas('{{ $medico->usuario->apellido }} {{ $medico->usuario->nombre }}', {{ $medico->horariosFechas }})"
                                                    class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-white dark:bg-transparent border border-gray-300 dark:border-gray-500 
                                                    text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition shadow-sm group">
                                                
                                                <x-action-icon accion="calendario" class="w-4 h-4 mr-2 text-gray-500 group-hover:text-blue-600" />
                                                
                                                Ver Fechas ({{ $medico->horariosFechas->count() }})
                                            </button>
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600 text-lg">-</span>
                                        @endif
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                        <div class="flex justify-center items-center space-x-3">
                                            
                                            <a href="{{ route('admin.medicos.precios', $medico->id_medico) }}" title="Configurar Honorarios">
                                                <x-action-icon accion="dinero" />
                                            </a>

                                            <a href="{{ route('admin.medicos.edit', $medico->id_medico) }}" title="Configurar Médico">
                                                <x-action-icon accion="editar" />
                                            </a>

                                            <form action="{{ route('admin.medicos.destroy', $medico->id_medico) }}" method="POST" 
                                                onsubmit="return confirm('¿Seguro que deseas eliminar este médico?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="pt-1" title="Eliminar Médico">
                                                    <x-action-icon accion="eliminar" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $medicos->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
    {{-- VENTANA MODAL DE FECHAS --}}
    <div id="modal-fechas" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        {{-- Fondo oscuro --}}
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="cerrarModalFechas()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {{-- Contenido del Modal --}}
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-titulo-medico">
                                Fechas Especiales
                            </h3>
                            <div class="mt-4">
                                {{-- Aquí se inyectará la lista de fechas con JS --}}
                                <ul id="lista-fechas-modal" class="divide-y divide-gray-200 dark:divide-gray-700 max-h-60 overflow-y-auto pr-4">
                                    </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="cerrarModalFechas()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dniInput = document.getElementById('dni_filtro');
            const btnBuscar = document.getElementById('buscar_dni_btn');
            const btnLimpiar = document.getElementById('limpiar_filtros_btn');

            function redirigir(params) {
                const url = new URL(window.location.href);
                if (!params) {
                    url.searchParams.delete('dni_filtro');
                } else {
                    url.searchParams.set('dni_filtro', params);
                }
                url.searchParams.set('page', 1);
                window.location.href = url.toString();
            }

            if(btnBuscar) btnBuscar.addEventListener('click', (e) => { e.preventDefault(); redirigir(dniInput.value.trim()); });
            if(dniInput) dniInput.addEventListener('keydown', (e) => { if(e.key === 'Enter') { e.preventDefault(); redirigir(dniInput.value.trim()); } });
            if(btnLimpiar) btnLimpiar.addEventListener('click', (e) => { e.preventDefault(); redirigir(null); });
        });
    </script>


    <script>
        function abrirModalFechas(nombreMedico, fechas) {
            // 1. Poner el nombre en el título
            document.getElementById('modal-titulo-medico').innerText = 'Fechas Adicionales: ' + nombreMedico;

            // 2. Limpiar la lista anterior
            const lista = document.getElementById('lista-fechas-modal');
            lista.innerHTML = '';

            // 3. Generar la lista por día
            fechas.sort((a, b) => new Date(a.fecha) - new Date(b.fecha));

            fechas.forEach(f => {
                const fechaParts = f.fecha.split('-'); 
                const fechaFormateada = `${fechaParts[2]}/${fechaParts[1]}/${fechaParts[0]}`;
                
                const inicio = f.hora_inicio.substring(0, 5);
                const fin = f.hora_fin.substring(0, 5);

                const item = `
                    <li class="py-3 flex justify-between">
                        <span class="text-sm font-medium text-gray-900 dark:text-white flex items-center"> 
                            <x-action-icon accion="calendario" class="w-4 h-4 mr-2 text-gray-500" /> 
                            ${fechaFormateada}
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">${inicio} - ${fin} hs</span>
                    </li>
                `;
                lista.insertAdjacentHTML('beforeend', item);
            });
            document.getElementById('modal-fechas').classList.remove('hidden');
        }

        function cerrarModalFechas() {
            document.getElementById('modal-fechas').classList.add('hidden');
        }

        document.addEventListener('keydown', function(event) {
            if(event.key === "Escape"){
                cerrarModalFechas();
            }
        });
    </script>
@endpush