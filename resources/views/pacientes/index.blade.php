@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper"> {{-- Usando la nueva clase --}}
                <h1 class="page-title">Lista de Pacientes</h1> {{-- Usando la nueva clase --}}

                {{-- Botón de Inicio (dinámico por rol) --}}
                @if(auth()->check())
                    <div class="action-buttons-container"> {{-- Usando la nueva clase --}}
                        @php
                            $dashboardRoute = '';
                            if (auth()->user()->id_rol == 1) {
                                $dashboardRoute = route('admin.dashboard');
                            } elseif (auth()->user()->id_rol == 3) {
                                $dashboardRoute = route('paciente.dashboard');
                            }
                        @endphp

                        @if($dashboardRoute)
                            <a href="{{ $dashboardRoute }}" class="btn-secondary">
                                ← Inicio
                            </a>
                        @endif
                    </div>
                @endif

                {{-- Botón para Crear Nuevo Paciente (dinámico por rol) --}}
                @if(auth()->check() && (auth()->user()->id_rol == 1 || auth()->user()->id_rol == 3))
                    <div class="action-buttons-container mb-6"> {{-- Usando la nueva clase, y mb-6 para más separación de la tabla --}}
                        @php
                            $createPacienteRoute = '';
                            if (auth()->user()->id_rol == 1) {
                                $createPacienteRoute = route('admin.pacientes.create');
                            } elseif (auth()->user()->id_rol == 3) {
                                $createPacienteRoute = route('paciente.pacientes.create');
                            }
                        @endphp

                        @if($createPacienteRoute)
                            <a href="{{ $createPacienteRoute }}" class="btn-primary">
                                Crear Nuevo Paciente
                            </a>
                        @endif
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert-success"> {{-- Usando la nueva clase --}}
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert-danger"> {{-- Usando la nueva clase --}}
                        {{ session('error') }}
                    </div>
                @endif

                @if ($pacientes->isEmpty())
                    <p class="text-gray-700 dark:text-gray-300">No hay pacientes registrados.</p>
                @else
                    <div class="table-responsive"> {{-- Usando la nueva clase --}}
                        <table class="custom-table"> {{-- Usando la nueva clase --}}
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="table-header">ID Paciente</th> {{-- Usando la nueva clase --}}
                                    <th scope="col" class="table-header">Nombre</th>
                                    <th scope="col" class="table-header">Apellido</th>
                                    <th scope="col" class="table-header">DNI</th>
                                    <th scope="col" class="table-header">Fecha Nacimiento</th>
                                    <th scope="col" class="table-header">Teléfono</th> {{-- AÑADIDO: Encabezado para Teléfono --}}
                                    <th scope="col" class="table-header">Usuario Asociado</th>
                                    <th scope="col" class="table-header">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($pacientes as $paciente)
                                <tr>
                                    <td class="table-data">{{ $paciente->id_paciente }}</td> 
                                    <td class="table-data">{{ $paciente->nombre }}</td>
                                    <td class="table-data">{{ $paciente->apellido }}</td>
                                    <td class="table-data">{{ $paciente->dni }}</td>
                                    <td class="table-data">{{ \Carbon\Carbon::parse($paciente->fecha_nacimiento)->format('d/m/Y') }}</td> 
                                    <td class="table-data">{{ $paciente->telefono }}</td> 
                                    <td class="table-data">{{ $paciente->usuario ? $paciente->usuario->nombre . ' (' . $paciente->usuario->id_usuario . ')' : 'N/A' }}</td>
                                    <td class="table-actions"> 
                                        @if(auth()->check())
                                            @if(auth()->user()->id_rol == 1)
                                                <a href="{{ route('admin.pacientes.edit', $paciente->id_paciente) }}" class="btn-info table-action-button">Editar</a>
                                                <form action="{{ route('admin.pacientes.destroy', $paciente->id_paciente) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de eliminar este paciente?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger">Eliminar</button>
                                                </form>
                                            @elseif(auth()->user()->id_rol == 3 && $paciente->id_usuario == auth()->user()->id_usuario)
                                                <a href="{{ route('paciente.pacientes.edit', $paciente->id_paciente) }}" class="btn-info table-action-button">Editar</a>
                                                <form action="{{ route('paciente.pacientes.destroy', $paciente->id_paciente) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de eliminar este paciente?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger">Eliminar</button>
                                                </form>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="table-data text-center">No hay pacientes registrados.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
                <div class="mt-4">
                    {{ $pacientes->links() }} {{-- Paginación --}}
                </div>
            </div>
        </div>
    </div>
@endsection
