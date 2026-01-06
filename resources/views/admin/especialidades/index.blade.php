@inject('Rol', 'App\Models\Rol')
@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">Especialidades</h1>

                {{-- Botonera Superior --}}
                <div class="action-buttons-container flex justify-between items-center mb-6">
                    {{-- Verificación con constante --}}
                    @if(auth()->check() && auth()->user()->hasRolActivo($Rol::ADMINISTRADOR))
                        <a href="{{ route('admin.dashboard') }}" class="btn-secondary">
                            ← Inicio
                        </a>
                    @else
                        <div></div>
                    @endif

                    <a href="{{ route('admin.especialidades.create') }}" class="btn-primary">Agregar Especialidad</a>
                </div>

                @if (session('success'))
                    <div class="alert-success mb-4">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="alert-danger mb-4">{{ session('error') }}</div>
                @endif

                @if ($especialidades->isEmpty())
                    <p class="text-center text-gray-500 py-8">No hay especialidades registradas.</p>
                @else
                    <div class="table-responsive"> 
                        <table class="custom-table"> 
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="table-header">ID</th> 
                                    <th class="table-header">Nombre</th>
                                    <th class="table-header">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($especialidades as $especialidad)
                                    <tr>
                                        <td class="table-data">{{ $especialidad->id_especialidad }}</td> 
                                        <td class="table-data">{{ $especialidad->nombre_especialidad }}</td>
                                        <td class="table-actions"> 
                                            <a href="{{ route('admin.especialidades.edit', $especialidad->id_especialidad) }}" class="btn-info table-action-button text-sm px-3 py-1 mr-1">Editar</a>
                                            <form action="{{ route('admin.especialidades.destroy', $especialidad->id_especialidad) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta especialidad?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn-danger text-sm px-3 py-1">Eliminar</button>
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