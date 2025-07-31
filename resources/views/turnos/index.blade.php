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
                    <div class="action-buttons-container mb-6"> 
                        @if(auth()->user()->id_rol == 1)
                            <a href="{{ route('admin.turnos.create') }}" class="btn-primary">
                                Reservar Turno
                            </a>
                        @elseif(auth()->user()->id_rol == 3)
                            <a href="{{ route('paciente.turnos.create') }}" class="btn-primary">
                                Reservar Turno
                            </a>
                        @endif
                    </div>
                @endif
                
                {{-- Mensajes de estado --}}
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @if($turnos->isEmpty())
                    <p>No tienes turnos registrados.</p>
                @else
                    <div class="overflow-x-auto bg-white dark:bg-gray-800 shadow-md sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Médico
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Especialidad
                                    </th>
                                    @if(auth()->user()->id_rol != 3)
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Paciente
                                        </th>
                                    @endif
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Fecha
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Horario
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                @foreach($turnos as $turno)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900 transition duration-150 ease-in-out">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-200">
                                        {{ $turno->medico->nombre }} {{ $turno->medico->apellido }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $turno->medico->especialidades->pluck('nombre_especialidad')->implode(', ') }}
                                    </td>
                                    @if(auth()->user()->id_rol != 3)
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ $turno->paciente->nombre }} {{ $turno->paciente->apellido }}
                                        </td>
                                    @endif
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($turno->fecha)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($turno->hora)->format('H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ ucfirst($turno->estado) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        @if(auth()->check())
                                            @if(auth()->user()->id_rol == 1)
                                                <a href="{{ route('admin.turnos.edit', $turno->id_turno) }}" class="btn-info mr-2">Editar</a>
                                                <form action="{{ route('admin.turnos.destroy', $turno->id_turno) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de eliminar este turno?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger">Eliminar</button>
                                                </form>
                                            @elseif(auth()->user()->id_rol == 3 && $turno->estado == 'pendiente')
                                                <form action="{{ route('paciente.turnos.destroy', $turno->id_turno) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de cancelar este turno?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger">Cancelar</button>
                                                </form>
                                            @elseif(auth()->user()->id_rol == 2 && $turno->medico && $turno->medico->id_usuario == auth()->user()->id_usuario)
                                                <a href="{{ route('medico.turnos.edit', $turno->id_turno) }}" class="btn-info">Editar</a>
                                            @else
                                                <span class="text-gray-500 dark:text-gray-400">No disponible</span>
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
