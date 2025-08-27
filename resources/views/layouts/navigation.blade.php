<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    {{-- Lógica para el botón de Inicio/Dashboard dinámico --}}
                    @php
                        $dashboardRoute = '';
                        if (Auth::check()) {
                            if (Auth::user()->hasRole('Administrador')) {
                                $dashboardRoute = route('admin.dashboard');
                            } elseif (Auth::user()->hasRole('Medico')) {
                                $dashboardRoute = route('medico.dashboard');
                            } elseif (Auth::user()->hasRole('Paciente')) {
                                $dashboardRoute = route('paciente.dashboard');
                            } else {
                                $dashboardRoute = route('dashboard'); // Ruta por defecto si el rol no es reconocido
                            }
                        } else {
                            $dashboardRoute = route('dashboard'); // Para usuarios no autenticados, si aplica
                        }
                    @endphp
                    <a href="{{ $dashboardRoute }}">
                        <img src="{{ asset('images/logoSinFondo.png') }}" alt="Logo Clínica MJ" class="block h-9 w-auto">
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @if (Auth::user()->hasRole('Administrador'))
                        {{-- Opciones del Administrador --}}
                        <x-nav-link :href="route('admin.turnos.index')" :active="request()->routeIs('admin.turnos.*')">
                            {{ __('Turnos') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.especialidades.index')" :active="request()->routeIs('admin.especialidades.*')">
                            {{ __('Especialidades') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.medicos.index')" :active="request()->routeIs('admin.medicos.*')">
                            {{ __('Médicos') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admin.pacientes.index')" :active="request()->routeIs('admin.pacientes.*')">
                            {{ __('Pacientes') }}
                        </x-nav-link>
                    @endif
                    @if (Auth::user()->hasRole('Medico'))
                        {{-- Opciones del Médico --}}
                        <x-nav-link :href="route('medico.turnos.index')" :active="request()->routeIs('medico.turnos.*')">
                            {{ __('Mis turnos (médico)') }}
                        </x-nav-link>
                        <x-nav-link :href="route('medico.pacientes.index')" :active="request()->routeIs('medico.pacientes.*')">
                            {{ __('Mis pacientes') }}
                        </x-nav-link>
                    @endif
                    @if (Auth::user()->hasRole('Paciente'))
                        {{-- Opciones del Paciente --}}
                        <x-nav-link :href="route('paciente.turnos.index')" :active="request()->routeIs('paciente.turnos.*')">
                            {{ __('Mis turnos (paciente)') }}
                        </x-nav-link>
                        <x-nav-link :href="route('paciente.pacientes.index')" :active="request()->routeIs('paciente.pacientes.*')">
                            {{ __('Mis pacientes') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>
            
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            {{-- Mostrar Nombre, Apellido y Rol del usuario --}}
                            @if (Auth::check())
                                <div>{{ Auth::user()->nombre }} {{ Auth::user()->apellido }}</div>
                            @endif

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Perfil') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Cerrar sesión') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                {{-- Mostrar Nombre, Apellido y Rol del usuario --}}
                @if (Auth::check())
                    <div class="font-medium text-base text-gray-800 dark:text-gray-200">
                        {{ Auth::user()->nombre }} {{ Auth::user()->apellido }}
                    </div>
                    <div class="font-medium text-sm text-gray-500">
                        {{ Auth::user()->email }}
                    </div>
                    <div class="font-medium text-sm text-gray-500">
                        @php
                            $roles = Auth::user()->roles->pluck('rol')->implode(', ');
                        @endphp
                        Rol: {{ $roles ?? 'No definido' }}
                    </div>
                @endif
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Perfil') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Cerrar sesión') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>