<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel de Paciente') }}
        </h2>
    </x-slot>

    @section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper"> 
                <h1 class="page-title">
                    Bienvenido/a,
                    @if(Auth::user()->pacientes->isNotEmpty())
                        {{ Auth::user()->pacientes->first()->nombre }}!
                    @else
                        Paciente! {{-- Mensaje alternativo si no se encuentra un paciente asociado --}}
                    @endif
                </h1>

                <div class="action-buttons-container"> 
                    <a href="{{ route('paciente.turnos.index') }}" class="btn-primary">
                        Ver Mis Turnos
                    </a>
                    <a href="{{ route('paciente.turnos.create') }}" class="btn-primary">
                        Solicitar Nuevo Turno
                    </a>
                    <a href="{{ route('paciente.pacientes.index') }}" class="btn-primary">
                        Mis Pacientes
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endsection
</x-app-layout>
