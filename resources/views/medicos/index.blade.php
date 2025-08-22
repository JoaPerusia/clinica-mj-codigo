@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">Gestión de Médicos</h1>
                
                <div class="action-buttons-container">
                    <a href="{{ route('admin.dashboard') }}" class="btn-secondary">
                        ← Inicio
                    </a>
                </div>

                <div class="action-buttons-container mb-6">
                    <a href="{{ route('admin.medicos.create') }}" class="btn-primary">
                        Agregar Médico
                    </a>
                </div>

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

                @if($medicos->isEmpty())
                    <p class="text-center">No hay médicos registrados.</p>
                @else
                    <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="py-3 px-6">Nombre</th>
                                    <th scope="col" class="py-3 px-6">Especialidades</th>
                                    <th scope="col" class="py-3 px-6">Horarios de Trabajo</th>
                                    <th scope="col" class="py-3 px-6">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($medicos as $medico)
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <td class="py-4 px-6">{{ $medico->nombre }} {{ $medico->apellido }}</td>
                                    <td class="py-4 px-6">
                                        @foreach($medico->especialidades as $especialidad)
                                            <span class="badge badge-info">{{ $especialidad->nombre_especialidad }}</span>
                                        @endforeach
                                    </td>
                                    <td class="py-4 px-6">
                                        @forelse($medico->horariosTrabajo as $horario)
                                            <p>{{ ucfirst($horario->dia_semana) }}: {{ \Carbon\Carbon::parse($horario->hora_inicio)->format('H:i') }} - {{ \Carbon\Carbon::parse($horario->hora_fin)->format('H:i') }}</p>
                                        @empty
                                            <p>Sin horarios</p>
                                        @endforelse
                                    </td>
                                    <td class="py-4 px-6">
                                        <a href="{{ route('admin.medicos.edit', $medico->id_medico) }}" class="btn-info table-action-button text-sm px-4 py-2 mt-1">Editar</a>
                                        <form action="{{ route('admin.medicos.destroy', $medico->id_medico) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de eliminar este médico?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger text-sm px-4 py-2 mt-1">Eliminar</button>
                                        </form>
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
@endsection