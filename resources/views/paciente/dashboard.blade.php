<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel de Paciente') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    Bienvenido/a, {{ Auth::user()->paciente->nombre }}!

                    <div class="mt-4">
                        <a href="{{ route('paciente.turnos.index') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 active:bg-purple-800 focus:outline-none focus:border-purple-900 focus:ring ring-purple-300 disabled:opacity-25 transition ease-in-out duration-150">
                            Ver Mis Turnos
                        </a>
                        <a href="{{ route('paciente.turnos.create') }}" class="ml-4 inline-flex items-center px-4 py-2 bg-purple-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-600 active:bg-purple-700 focus:outline-none focus:border-purple-800 focus:ring ring-purple-300 disabled:opacity-25 transition ease-in-out duration-150">
                            Solicitar Nuevo Turno
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>