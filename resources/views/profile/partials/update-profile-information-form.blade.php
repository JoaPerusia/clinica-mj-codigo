<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Información del Perfil') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Actualiza la información de tu cuenta y dirección de correo electrónico.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        {{-- Campo Nombre (Editable) --}}
        <div>
            <x-input-label for="nombre" :value="__('Nombre')" />
            <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre', $user->nombre)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('nombre')" />
        </div>

        {{-- Campo Apellido (Editable) --}}
        <div class="mt-4">
            <x-input-label for="apellido" :value="__('Apellido')" />
            <x-text-input id="apellido" name="apellido" type="text" class="mt-1 block w-full" :value="old('apellido', $user->apellido)" required autocomplete="family-name" />
            <x-input-error class="mt-2" :messages="$errors->get('apellido')" />
        </div>

        {{-- Campo Email (Editable) --}}
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Tu dirección de correo electrónico no está verificada.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Haz clic aquí para reenviar el correo de verificación.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('Se ha enviado un nuevo enlace de verificación a tu dirección de correo electrónico.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Campo DNI (Solo Lectura) --}}
        <div class="mt-4">
            <x-input-label for="dni" :value="__('DNI')" />
            <x-text-input id="dni" name="dni" type="text" class="mt-1 block w-full" :value="old('dni', $user->dni)" disabled />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Para modificar el DNI, contacta a un administrador.') }}</p>
        </div>

        {{-- Campo Fecha de Nacimiento (Solo Lectura) --}}
        <div class="mt-4">
            <x-input-label for="fecha_nacimiento" :value="__('Fecha de Nacimiento')" />
            <x-text-input id="fecha_nacimiento" name="fecha_nacimiento" type="date" class="mt-1 block w-full" :value="old('fecha_nacimiento', $user->fecha_nacimiento)" disabled />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Para modificar la fecha de nacimiento, contacta a un administrador.') }}</p>
        </div>

        {{-- Campo Obra Social (Solo Lectura) --}}
        <div class="mt-4">
            <x-input-label for="obra_social" :value="__('Obra Social')" />
            <x-text-input id="obra_social" name="obra_social" type="text" class="mt-1 block w-full" :value="old('obra_social', $user->obra_social)" disabled />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('Para modificar la obra social, contacta a un administrador.') }}</p>
        </div>

        {{-- Campo Teléfono (Editable) --}}
        <div class="mt-4">
            <x-input-label for="telefono" :value="__('Teléfono')" />
            <x-text-input id="telefono" name="telefono" type="text" class="mt-1 block w-full" :value="old('telefono', $user->telefono)" autocomplete="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('telefono')" />
        </div>

        {{-- Campo Rol (Solo Lectura) --}}
        <div class="mt-4">
            <x-input-label for="rol" :value="__('Rol')" />
            <x-text-input id="rol" name="rol" type="text" class="mt-1 block w-full" :value="$user->rol->rol ?? 'No definido'" disabled />
        </div>

        <div class="flex items-center gap-4 mt-4">
            <x-primary-button>{{ __('Guardar Cambios') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Guardado.') }}</p>
            @endif

            {{-- Botón de Cancelar --}}
            <a href="{{ route('profile.edit') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-800 disabled:opacity-25 transition ease-in-out duration-150">
                {{ __('Cancelar') }}
            </a>
        </div>
    </form>
</section>
