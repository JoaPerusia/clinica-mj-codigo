@props(['turnos', 'titulo' => null])
@inject('Rol', 'App\Models\Rol')

@if($turnos && $turnos->count())
    @if($titulo)
        <h2 class="sub-title text-2xl text-white mt-8 mb-4">{{ $titulo }}</h2>
    @endif
    
    <div class="overflow-x-auto rounded-lg shadow-lg">
        <table class="custom-table w-full">
            <thead class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-200 uppercase text-sm leading-normal">
                <tr>
                    <th class="table-header py-3 px-4 text-left">Médico</th>
                    <th class="table-header py-3 px-4 text-left">Especialidad</th>
                    <th class="table-header py-3 px-4 text-left">Paciente</th>
                    <th class="table-header py-3 px-4 text-left">Fecha</th>
                    <th class="table-header py-3 px-4 text-left">Horario</th>
                    <th class="table-header py-3 px-4 text-left">Estado</th>
                    
                    {{-- Ocultamos columna acciones a médicos --}}
                    @if(!auth()->user()->hasRolActivo($Rol::MEDICO))
                        <th class="table-header py-3 px-4 text-center w-24"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($turnos as $turno)
                    @include('turnos.partials.turno_row', ['turno' => $turno])
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $turnos->links() }}
    </div>
@endif