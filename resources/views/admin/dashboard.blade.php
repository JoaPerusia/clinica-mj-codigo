<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel de Administración') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    Bienvenido, Administrador!

                    <div class="mt-4">
                        <a href="{{ route('admin.turnos.index') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-semibold rounded-md shadow-lg text-white bg-blue-700 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition ease-in-out duration-150 mb-4 mr-4">
                            Gestionar Turnos
                        </a>
                        <a href="{{ route('admin.especialidades.index') }}" class="inline-flex items-center px-6 py-3 bg-blue-600 border border-transparent rounded-md font-semibold text-base text-white tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150 mb-4 mr-4">
                            Gestionar Especialidades
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>