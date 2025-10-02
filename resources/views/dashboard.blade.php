@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        @if($roles->count() > 1)
            {{ __('Selección de Rol') }}
        @else
            {{ __('Dashboard') }}
        @endif
    </h2>
@endsection

@section('content')
    @if ($roles->count() > 1)
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-xl font-semibold mb-4 text-white">
                        {{ __("¡Bienvenido! Tenés múltiples roles asignados.") }}
                    </h2>
                    <p class="mb-6 text-white">
                        {{ __('Por favor, seleccioná el rol con el que ingresás:') }}
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        @php
                            $colors = [
                                'Administrador' => 'bg-blue-500 hover:bg-blue-600',
                                'Medico'        => 'bg-green-500 hover:bg-green-600',
                                'Paciente'      => 'bg-indigo-500 hover:bg-indigo-600',
                            ];
                        @endphp

                        @foreach ($roles as $r)
                            <form action="{{ route('rol.setActivo') }}" method="POST" class="w-full">
                                @csrf
                                <input type="hidden" name="rol" value="{{ $r->rol }}">
                                <button
                                    type="submit"
                                    class="w-full p-6 {{ $colors[$r->rol] }} text-white rounded-lg shadow-lg transition"
                                >
                                    <h3 class="text-xl font-bold">{{ $r->rol }}</h3>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @else
        @php
            $solo = $roles->first()->rol;
            session(['rol_activo' => $solo]);
            $ruta = match($solo) {
                'Administrador' => 'admin.dashboard',
                'Medico'        => 'medico.dashboard',
                'Paciente'      => 'paciente.dashboard',
            };
        @endphp
        <script>window.location.href = "{{ route($ruta) }}";</script>
    @endif
@endsection