@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper">
                <h1 class="page-title">
                    Configurar Honorarios: {{ $medico->usuario->nombre }} {{ $medico->usuario->apellido }}
                </h1>

                <form action="{{ route('admin.medicos.updatePrecios', $medico->id_medico) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- SECCIÓN 1: PRECIO PARA PARTICULARES --}}
                    <div class="mb-8 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                        <h3 class="text-lg font-medium text-yellow-800 dark:text-yellow-200 mb-2">Atención Particular / Sin Obra Social</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Defina el costo de la consulta para pacientes que asisten de forma particular.
                        </p>
                        <div class="flex items-center">
                            <label for="precio_particular" class="mr-3 font-bold text-gray-700 dark:text-gray-300">Costo Consulta ($):</label>
                            <input type="number" 
                                step="1"
                                name="precio_particular" 
                                id="precio_particular"
                                value="{{ number_format(old('precio_particular', $medico->precio_particular), 0, '', '') }}"
                                class="form-input w-48 text-lg font-bold">
                        </div>
                    </div>

                    {{-- SECCIÓN 2: OBRAS SOCIALES (SIN PRECIOS) --}}
                    <div class="table-responsive">
                        <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-4 px-1">Cobertura de Obras Sociales</h3>
                        <table class="custom-table">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="table-header text-center w-24">¿Atiende?</th>
                                    <th class="table-header">Obra Social</th>
                                    {{-- Columna de precio eliminada --}}
                                    <th class="table-header">Instrucciones / Requisitos (Bono, Orden, etc.)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($obrasSociales as $obra)
                                    @php
                                        $relacion = $medico->obrasSociales->find($obra->id_obra_social);
                                    @endphp
                                    <tr>
                                        <td class="table-data text-center">
                                            <input type="checkbox" 
                                                name="obras[{{ $obra->id_obra_social }}][activo]" 
                                                value="1"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 h-5 w-5"
                                                {{ $relacion ? 'checked' : '' }}>
                                        </td>
                                        <td class="table-data font-medium text-lg">
                                            {{ $obra->nombre }}
                                        </td>
                                        <td class="table-data">
                                            <input type="text" 
                                                name="obras[{{ $obra->id_obra_social }}][instrucciones]"
                                                value="{{ $relacion ? $relacion->pivot->instrucciones : '' }}"
                                                placeholder="Ej: Traer bono autorizado..."
                                                class="form-input w-full py-2">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <a href="{{ route('admin.medicos.index') }}" class="btn-secondary">Cancelar</a>
                        <button type="submit" class="btn-primary">Guardar Configuración</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection