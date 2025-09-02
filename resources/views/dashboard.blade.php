@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Pantalla General') }}
    </h2>
@endsection

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    {{ __("¡Estás en la pantalla general!") }}

                    <p class="mt-4">
                        {{ __('Haz clic en el botón de abajo para ir a tu pantalla específica.') }}
                    </p>

                    @php
                        $user_rol = Auth::user()->roles->first()->rol ?? null;
                        $redirect_route = 'dashboard'; // Ruta por defecto

                        if ($user_rol === 'Administrador') {
                            $redirect_route = 'admin.dashboard';
                        } elseif ($user_rol === 'Medico') {
                            $redirect_route = 'medico.dashboard';
                        } elseif ($user_rol === 'Paciente') {
                            $redirect_route = 'paciente.dashboard';
                        }
                    @endphp

                    <a href="{{ route($redirect_route) }}" class="inline-flex items-center mt-4 px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        {{ __('Ir a mi Inicio') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
