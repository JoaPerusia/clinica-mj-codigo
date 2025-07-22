<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel de Médico') }}
        </h2>
    </x-slot>

    @section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper"> 
                <h1 class="page-title">Bienvenido, Dr./Dra. {{ Auth::user()->medico->nombre }}!</h1> 

                <div class="action-buttons-container"> 
                    <a href="{{ route('medico.turnos.index') }}" class="btn-primary">
                        Ver Mis Turnos
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endsection
</x-app-layout>
