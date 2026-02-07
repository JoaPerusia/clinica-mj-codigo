@inject('Rol', 'App\Models\Rol')
@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">Lista de Pacientes</h1>

                {{-- Botonera Superior --}}
                <div class="action-buttons-container flex justify-between items-center mb-6">
                    @php
                        $dashboardRoute = null;
                        // Uso de Constantes Inyectadas
                        if (auth()->user()->hasRolActivo($Rol::ADMINISTRADOR)) $dashboardRoute = 'admin.dashboard';
                        elseif (auth()->user()->hasRolActivo($Rol::MEDICO))    $dashboardRoute = 'medico.dashboard';
                        elseif (auth()->user()->hasRolActivo($Rol::PACIENTE))  $dashboardRoute = 'paciente.dashboard';
                    @endphp

                    @if($dashboardRoute)
                        <a href="{{ route($dashboardRoute) }}" class="btn-secondary">← Inicio</a>
                    @else
                        <div></div>
                    @endif

                    @if(auth()->user()->hasRolActivo($Rol::ADMINISTRADOR))
                        <a href="{{ route('admin.pacientes.create') }}" class="btn-primary">Agregar Paciente</a>
                    @elseif(auth()->user()->hasRolActivo($Rol::PACIENTE))
                        <a href="{{ route('paciente.pacientes.create') }}" class="btn-primary">Agregar Paciente</a>
                    @endif
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

                @if ($pacientes->isEmpty())
                    <p class="text-center text-gray-500 py-8">No hay pacientes registrados.</p>
                @else
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="table-header">Nombre</th>
                                    <th class="table-header">DNI</th>
                                    <th class="table-header">Fecha Nacimiento</th>
                                    <th class="table-header">Teléfono</th>
                                    <th class="table-header">Obra Social</th>
                                    <th class="table-header">Usuario Asociado</th>
                                    <th class="table-header"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($pacientes as $paciente)
                                <tr>
                                    <td class="table-data">{{ $paciente->apellido }}, {{ $paciente->nombre }}</td>
                                    <td class="table-data">{{ $paciente->dni }}</td>
                                    <td class="table-data">{{ \Carbon\Carbon::parse($paciente->fecha_nacimiento)->format('d/m/Y') }}</td> 
                                    <td class="table-data">{{ $paciente->telefono }}</td> 
                                    <td class="table-data">{{ $paciente->obraSocial ? $paciente->obraSocial->nombre : 'Sin Obra Social' }}</td>
                                    <td class="table-data">{{ $paciente->usuario ? $paciente->usuario->nombre . ' ' . $paciente->usuario->apellido . ' (' . $paciente->usuario->id_usuario . ')' : 'N/A' }}</td>
                                    <td class="table-actions"> 
                                        @php
                                            $canEdit = auth()->user()->hasRolActivo($Rol::ADMINISTRADOR) || (auth()->user()->hasRolActivo($Rol::PACIENTE) && $paciente->id_usuario == auth()->user()->id_usuario);
                                            
                                            $editRoute = auth()->user()->hasRolActivo($Rol::ADMINISTRADOR) 
                                                ? route('admin.pacientes.edit', $paciente->id_paciente) 
                                                : route('paciente.pacientes.edit', $paciente->id_paciente);
                                            
                                            $destroyRoute = auth()->user()->hasRolActivo($Rol::ADMINISTRADOR) 
                                                ? route('admin.pacientes.destroy', $paciente->id_paciente) 
                                                : route('paciente.pacientes.destroy', $paciente->id_paciente);
                                        @endphp

                                        @if($canEdit)
                                            <div class="flex justify-center items-center space-x-3">
                                                {{-- Botón Editar --}}
                                                <a href="{{ $editRoute }}" title="Editar Paciente">
                                                    <x-action-icon accion="editar" />
                                                </a>

                                                {{-- Botón Eliminar --}}
                                                <form action="{{ $destroyRoute }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este paciente?');">
                                                    @csrf 
                                                    @method('DELETE')
                                                    <button type="submit" class="pt-1" title="Eliminar Paciente">
                                                        <x-action-icon accion="eliminar" />
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                <div class="mt-4">
                    {{ $pacientes->links() }}
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
@endpush