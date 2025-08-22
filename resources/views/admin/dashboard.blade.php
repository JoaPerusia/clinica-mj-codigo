<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel de Administración') }}
        </h2>
    </x-slot>

    @section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper"> 
                <h1 class="page-title">Bienvenido, Administrador!</h1> 

                <div class="action-buttons-container"> 
                    <a href="{{ route('admin.turnos.index') }}" class="btn-primary">
                        Gestionar Turnos
                    </a>
                    <a href="{{ route('admin.especialidades.index') }}" class="btn-primary">
                        Gestionar Especialidades
                    </a>
                    <a href="{{ route('admin.medicos.index') }}" class="btn-primary">
                        Gestionar Médicos
                    </a>
                    <a href="{{ route('admin.pacientes.index') }}" class="btn-primary">
                        Gestionar Pacientes
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endsection
</x-app-layout>