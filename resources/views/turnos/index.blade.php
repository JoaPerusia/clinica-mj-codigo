@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper"> 
                <h1 class="page-title">Turnos</h1> 

                {{-- Botón de Inicio (dinámico por rol) --}}
                @if(auth()->check())
                    <div class="action-buttons-container"> 
                        @php
                            $dashboardRoute = '';
                            if (auth()->user()->id_rol == 1) {
                                $dashboardRoute = route('admin.dashboard');
                            } elseif (auth()->user()->id_rol == 2) {
                                $dashboardRoute = route('medico.dashboard');
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

                {{-- Ajuste de la ruta para crear turno según el rol --}}
                @if(auth()->check() && (auth()->user()->id_rol == 1 || auth()->user()->id_rol == 3))
                    <div class="action-buttons-container mb-6"> {{-- Usando la nueva clase, y mb-6 para más separación de la tabla --}}
                        {{-- Solo admin y paciente pueden "crear" turnos desde aquí --}}
                        <a href="{{ auth()->user()->id_rol == 1 ? route('admin.turnos.create') : route('paciente.turnos.create') }}" class="btn-primary">Crear Nuevo Turno</a>
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert-success"> 
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert-danger"> 
                        {{ session('error') }}
                    </div>
                @endif

                @if ($turnos->isEmpty())
                    <p class="text-gray-700 dark:text-gray-300">No hay turnos registrados.</p>
                @else
                    <div class="table-responsive"> 
                        <table class="custom-table"> 
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="table-header">Paciente</th> 
                                    <th scope="col" class="table-header">Médico</th>
                                    <th scope="col" class="table-header">Fecha</th>
                                    <th scope="col" class="table-header">Hora</th>
                                    <th scope="col" class="table-header">Estado</th>
                                    <th scope="col" class="table-header">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($turnos as $turno)
                                <tr>
                                    <td class="table-data">{{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }}</td> 
                                    <td class="table-data">{{ $turno->medico->nombre }} {{ $turno->medico->apellido }}</td>
                                    <td class="table-data">{{ $turno->fecha }}</td>
                                    <td class="table-data">{{ $turno->hora }}</td>
                                    <td class="table-data">{{ $turno->estado }}</td>
                                    <td class="table-actions"> 
                                        @if(auth()->check())
                                            @if(auth()->user()->id_rol == 1)
                                                <a href="{{ route('admin.turnos.edit', $turno->id_turno) }}" class="btn-info table-action-button">Editar</a>
                                                <form action="{{ route('admin.turnos.destroy', $turno->id_turno) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de cancelar este turno?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger">Cancelar</button>
                                                </form>
                                            @elseif(auth()->user()->id_rol == 3 && $turno->paciente && $turno->paciente->id_usuario == auth()->user()->id_usuario)
                                                @if($turno->estado == 'pendiente')
                                                    <form action="{{ route('paciente.turnos.destroy', $turno->id_turno) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de cancelar este turno?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn-danger">Cancelar</button>
                                                    </form>
                                                @else
                                                    <span class="text-gray-500 dark:text-gray-400">No disponible</span>
                                                @endif
                                            @elseif(auth()->user()->id_rol == 2 && $turno->medico && $turno->medico->id_usuario == auth()->user()->id_usuario)
                                                <a href="{{ route('medico.turnos.edit', $turno->id_turno) }}" class="btn-info">Editar</a>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
