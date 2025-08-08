<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    {{-- Lógica para el botón de Inicio/Dashboard dinámico --}}
                    @php
                        $dashboardRoute = '';
                        if (Auth::check()) {
                            if (Auth::user()->id_rol == 1) {
                                $dashboardRoute = route('admin.dashboard');
                            } elseif (Auth::user()->id_rol == 2) {
                                $dashboardRoute = route('medico.dashboard');
                            } elseif (Auth::user()->id_rol == 3) {
                                $dashboardRoute = route('paciente.dashboard');
                            } else {
                                $dashboardRoute = route('dashboard'); // Ruta por defecto si el rol no es reconocido
                            }
                        } else {
                            $dashboardRoute = route('dashboard'); // Para usuarios no autenticados, si aplica
                        }
                    @endphp
                    <a href="{{ $dashboardRoute }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                    </a>
                </div>

                <!-- Navigation Links (Desktop) -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    {{-- Enlace dinámico a Dashboard/Inicio --}}
                    <x-nav-link :href="$dashboardRoute" :active="request()->routeIs(explode('.', $dashboardRoute)[0] . '.dashboard')">
                        {{ __('Inicio') }}
                    </x-nav-link>

                    {{-- Enlaces específicos por rol --}}
                    @if (Auth::check())
                        @if (Auth::user()->id_rol == 1) {{-- Administrador --}}
                            <x-nav-link :href="route('admin.turnos.index')" :active="request()->routeIs('admin.turnos.*')">
                                {{ __('Gestión de Turnos') }}
                            </x-nav-link>
                            {{-- Agrega más enlaces para el administrador aquí, ej: Gestión de Médicos, Pacientes, etc. --}}
                        @elseif (Auth::user()->id_rol == 2) {{-- Médico --}}
                            <x-nav-link :href="route('medico.turnos.index')" :active="request()->routeIs('medico.turnos.*')">
                                {{ __('Mis Turnos') }}
                            </x-nav-link>
                            {{-- Agrega más enlaces para el médico aquí --}}
                        @elseif (Auth::user()->id_rol == 3) {{-- Paciente --}}
                            <x-nav-link :href="route('paciente.turnos.create')" :active="request()->routeIs('paciente.turnos.create')">
                                {{ __('Reservar Turno') }}
                            </x-nav-link>
                            <x-nav-link :href="route('paciente.turnos.index')" :active="request()->routeIs('paciente.turnos.index')">
                                {{ __('Mis Turnos') }}
                            </x-nav-link>
                            {{-- Agrega más enlaces para el paciente aquí --}}
                        @endif
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown (Desktop) -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            <div>
                                {{-- Mostrar Nombre y Apellido del usuario --}}
                                @if (Auth::check())
                                    {{ Auth::user()->nombre }} {{ Auth::user()->apellido }}
                                @else
                                    Invitado
                                @endif
                            </div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        {{-- Enlace a Perfil --}}
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Perfil') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
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

            <!-- Hamburger (Mobile) -->
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

    <!-- Responsive Navigation Menu (Mobile) -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            {{-- Enlace dinámico a Dashboard/Inicio (Mobile) --}}
            <x-responsive-nav-link :href="$dashboardRoute" :active="request()->routeIs(explode('.', $dashboardRoute)[0] . '.dashboard')">
                {{ __('Inicio') }}
            </x-responsive-nav-link>

            {{-- Enlaces específicos por rol (Mobile) --}}
            @if (Auth::check())
                @if (Auth::user()->id_rol == 1) {{-- Administrador --}}
                    <x-responsive-nav-link :href="route('admin.turnos.index')" :active="request()->routeIs('admin.turnos.*')">
                        {{ __('Gestión de Turnos') }}
                    </x-responsive-nav-link>
                    {{-- Agrega más enlaces para el administrador aquí --}}
                @elseif (Auth::user()->id_rol == 2) {{-- Médico --}}
                    <x-responsive-nav-link :href="route('medico.turnos.index')" :active="request()->routeIs('medico.turnos.*')">
                        {{ __('Mis Turnos') }}
                    </x-responsive-nav-link>
                    {{-- Agrega más enlaces para el médico aquí --}}
                @elseif (Auth::user()->id_rol == 3) {{-- Paciente --}}
                    <x-responsive-nav-link :href="route('paciente.turnos.create')" :active="request()->routeIs('paciente.turnos.create')">
                        {{ __('Reservar Turno') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('paciente.turnos.index')" :active="request()->routeIs('paciente.turnos.index')">
                        {{ __('Mis Turnos') }}
                    </x-responsive-nav-link>
                    {{-- Agrega más enlaces para el paciente aquí --}}
                @endif
            @endif
        </div>

        <!-- Responsive Settings Options (Mobile) -->
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
                        Rol: {{ Auth::user()->rol->rol ?? 'No definido' }}
                    </div>
                @endif
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Perfil') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
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
