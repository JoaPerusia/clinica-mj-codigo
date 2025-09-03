@extends('layouts.app')

@section('header')
    @php
        $roles = Auth::user()->roles;
    @endphp
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        @if($roles->count() > 1)
            {{ __('Selección de Rol') }}
        @else
            {{ __('Dashboard') }}
        @endif
    </h2>
@endsection

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @php
                        $roles = Auth::user()->roles;
                        $roleColors = [
                            'Administrador' => 'bg-blue-500 hover:bg-blue-600',
                            'Medico' => 'bg-green-500 hover:bg-green-600',
                            'Paciente' => 'bg-indigo-500 hover:bg-indigo-600',
                        ];
                    @endphp

                    @if ($roles->count() > 1)
                        {{-- Muestra la pantalla de selección con los estilos deseados --}}
                        <h2 class="text-xl font-semibold mb-4">{{ __("¡Bienvenido al sistema de gestión de turnos de la Clínica Comunal Santa Juana!") }}</h2>
                        <p class="mb-6">{{ __("Selecciona tu perfil para continuar.") }}</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach ($roles as $rol)
                                @php
                                    $redirect_route = 'dashboard';
                                    $description = '';
                                    $colorClass = $roleColors[$rol->rol] ?? 'bg-gray-500 hover:bg-gray-600';

                                    if ($rol->rol === 'Administrador') {
                                        $redirect_route = 'admin.dashboard';
                                        $description = 'Gestiona usuarios, médicos, turnos y especialidades.';
                                    } elseif ($rol->rol === 'Medico') {
                                        $redirect_route = 'medico.dashboard';
                                        $description = 'Accede a tu agenda de turnos.';
                                    } elseif ($rol->rol === 'Paciente') {
                                        $redirect_route = 'paciente.dashboard';
                                        $description = 'Solicita turnos y consulta tus citas.';
                                    }
                                @endphp
                                <a href="{{ route($redirect_route) }}" class="block p-6 {{ $colorClass }} text-white rounded-lg shadow-lg transition duration-200 ease-in-out transform hover:-translate-y-1">
                                    <h3 class="text-xl font-bold">{{ $rol->rol }}</h3>
                                    <p class="mt-2 text-sm opacity-90">{{ __($description) }}</p>
                                </a>
                            @endforeach
                        </div>
                    @else
                        {{-- Redirecciona automáticamente si tiene un solo rol --}}
                        @php
                            $user_rol = $roles->first()->rol ?? null;
                            $redirect_route = 'dashboard';
                            if ($user_rol === 'Administrador') {
                                $redirect_route = 'admin.dashboard';
                            } elseif ($user_rol === 'Medico') {
                                $redirect_route = 'medico.dashboard';
                            } elseif ($user_rol === 'Paciente') {
                                $redirect_route = 'paciente.dashboard';
                            }
                        @endphp
                        <script>
                            window.location.href = "{{ route($redirect_route) }}";
                        </script>
                        <p>{{ __('Redireccionando...') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection