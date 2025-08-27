@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">Especialidades</h1>

                {{-- Botón de Inicio para Admin --}}
                @if(auth()->check() && auth()->user()->hasRole('Administrador'))
                    <div class="action-buttons-container">
                        <a href="{{ route('admin.dashboard') }}" class="btn-secondary">
                            ← Inicio
                        </a>
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

                <div class="action-buttons-container mb-6"> {{-- Usando la nueva clase, y mb-6 para más separación de la tabla --}}
                    <a href="{{ route('admin.especialidades.create') }}" class="btn-primary">Crear Nueva Especialidad</a>
                </div>

                @if ($especialidades->isEmpty())
                    <p class="text-gray-700 dark:text-gray-300">No hay especialidades registradas.</p>
                @else
                    <div class="table-responsive"> 
                        <table class="custom-table"> 
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="table-header">ID</th> 
                                    <th scope="col" class="table-header">Nombre</th>
                                    <th scope="col" class="table-header">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($especialidades as $especialidad)
                                    <tr>
                                        <td class="table-data">{{ $especialidad->id_especialidad }}</td> 
                                        <td class="table-data">{{ $especialidad->nombre_especialidad }}</td>
                                        <td class="table-actions"> 
                                            <a href="{{ route('admin.especialidades.edit', $especialidad->id_especialidad) }}" class="btn-info table-action-button">Editar</a>
                                            <form action="{{ route('admin.especialidades.destroy', $especialidad->id_especialidad) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta especialidad?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-danger">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                <div class="mt-4">
                        {{ $especialidades->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
