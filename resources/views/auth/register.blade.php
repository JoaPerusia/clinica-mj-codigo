<x-guest-layout>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
            <x-validation-errors class="mb-4" />
            <form method="POST" action="{{ route('register') }}">
                @csrf

                {{-- Nombre --}}
                <div>
                    <label for="nombre" class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Nombre') }}</label>
                    <input id="nombre" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" type="text" name="nombre" value="{{ old('nombre') }}" required autofocus autocomplete="nombre" />
                </div>

                {{-- Apellido --}}
                <div class="mt-4">
                    <label for="apellido" class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Apellido') }}</label>
                    <input id="apellido" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" type="text" name="apellido" value="{{ old('apellido') }}" required autocomplete="apellido" />
                </div>

                {{-- DNI --}}
                <div class="mt-4">
                    <label for="dni" class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('DNI') }}</label>
                    <input id="dni" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" type="text" name="dni" value="{{ old('dni') }}" required autocomplete="dni" />
                </div>

                {{-- Fecha de Nacimiento --}}
                <div class="mt-4">
                    <label for="fecha_nacimiento" class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Fecha de Nacimiento') }}</label>
                    <input id="fecha_nacimiento" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}" required autocomplete="fecha_nacimiento" />
                </div>

                {{-- Obra Social --}}
                <div class="mt-4">
                    <label for="id_obra_social" class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Obra Social') }}</label>
                    <select id="id_obra_social" name="id_obra_social" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>
                        <option value="" disabled selected>Seleccione su obra social...</option>
                        @foreach($obras_sociales as $obra)
                            <option value="{{ $obra->id_obra_social }}" {{ old('id_obra_social') == $obra->id_obra_social ? 'selected' : '' }}>
                                {{ $obra->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Teléfono --}}
                <div class="mt-4">
                    <label for="telefono" class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Teléfono') }}</label>
                    <input id="telefono" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" type="text" name="telefono" value="{{ old('telefono') }}" autocomplete="telefono" />
                </div>

                {{-- Email Address --}}
                <div class="mt-4">
                    <label for="email" class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Email') }}</label>
                    <input id="email" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" />
                </div>

                {{-- Password --}}
                <div class="mt-4">
                    <label for="password" class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Contraseña') }}</label>
                    <input id="password" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" type="password" name="password" required autocomplete="new-password" />
                </div>

                {{-- Confirm Password --}}
                <div class="mt-4">
                    <label for="password_confirmation" class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Confirmar Contraseña') }}</label>
                    <input id="password_confirmation" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" type="password" name="password_confirmation" required autocomplete="new-password" />
                </div>

                {{-- Campo oculto para id_rol, por defecto 3 (Paciente) --}}
                

                
                <div class="flex items-center justify-end mt-4">
                    <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('login') }}">
                        {{ __('¿Ya estás registrado?') }}
                    </a>

                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 ms-4">
                        {{ __('Registrar') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>