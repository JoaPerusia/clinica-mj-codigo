<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Clínica MJ</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <div class="min-h-screen flex flex-col justify-center items-center p-6">
        <!-- Header -->
        <header class="w-full max-w-7xl mx-auto flex justify-between items-center py-4 px-6 md:px-8">
            <div class="flex items-center space-x-2">
                <!-- Aquí puedes poner el logo de tu clínica -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <h1 class="text-2xl font-bold">Clínica MJ</h1>
            </div>
            <!-- Botones de Login y Register -->
            <nav class="flex space-x-4">
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="btn-primary">Iniciar Sesión</a>
                @endif
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn-secondary">Registrarse</a>
                @endif
            </nav>
        </header>

        <!-- Main Content Section -->
        <main class="flex-grow w-full max-w-7xl mx-auto flex flex-col items-center justify-center text-center p-6 md:p-8">
            <h2 class="text-4xl md:text-5xl font-extrabold text-blue-600 dark:text-blue-400 mb-4">Bienvenido a Clínica MJ</h2>
            <p class="text-lg md:text-xl text-gray-700 dark:text-gray-300 mb-8 max-w-3xl">
                La mejor atención para tu salud y bienestar. Reserva tus turnos de manera sencilla y rápida.
            </p>
            <div class="space-x-4">
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="btn-primary">
                        Reserva tu turno ahora
                    </a>
                @endif
            </div>
        </main>

        <!-- Footer -->
        <footer class="w-full max-w-7xl mx-auto py-4 text-center text-sm text-gray-500 dark:text-gray-400">
            &copy; 2025 Clínica MJ. Todos los derechos reservados.
        </footer>
    </div>
</body>
</html>
