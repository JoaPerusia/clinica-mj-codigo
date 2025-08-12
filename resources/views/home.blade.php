<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="{{ asset('images/logoSinFondo.png') }}">
    <title>Clínica Comunal Santa Juana</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/carousel.js'])

</head>
<body class="antialiased bg-gray-200 text-gray-900 font-sans">
    <div class="min-h-screen flex flex-col">

        <header class="w-full bg-gray-200 shadow-md-custom">
            <nav class="w-full max-w-7xl mx-auto flex items-center justify-between p-4 md:p-6">
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('images/logoSinFondo.png') }}" alt="Logo de Clínica Comunal Santa Juana" class="h-10 w-10">
                    <span class="text-xl font-bold text-gray-800">Clínica Comunal Santa Juana</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ url('/about') }}" class="text-gray-800 hover:text-blue-600 font-medium">Sobre Nosotros</a>
                    <a href="{{ route('login') }}" class="btn-primary" style="background-color: #0000FE; border-color: #0000FE;">Turnos Online</a>
                </div>
            </nav>
        </header>

        <main class="flex-grow">
            <div class="relative w-full h-[60vh] md:h-[87vh]">
                <div id="carousel-container" class="absolute inset-0 overflow-hidden">
                    <img id="carousel-image" class="w-full h-full object-cover transition-opacity duration-1000 ease-in-out" src="{{ asset('images/carousel-1.jpg') }}" alt="Imagen de la clínica">
                </div>
                <div class="absolute inset-0 bg-black opacity-50"></div>
                <div class="relative z-10 w-full h-full flex items-center justify-center text-center text-white px-4">
                    <div>
                        <h1 class="text-3xl md:text-5xl font-extrabold mb-2">Bienvenido a Clínica Comunal Santa Juana</h1>
                        <p class="text-lg md:text-2xl font-medium">Cuidando tu salud, un turno a la vez.</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-100 py-12">
                <div class="container mx-auto max-w-7xl px-4 md:px-6 lg:px-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                        <div class="flex flex-col items-center justify-center p-6 bg-gray-200 rounded-lg shadow-md-custom">
                            <h3 class="text-xl md:text-2xl font-bold mb-2 text-gray-700">SOLICITÁ TU</h3>
                            <h3 class="text-2xl md:text-3xl font-extrabold" style="color: #0000FE;">TURNO AHORA</h3>
                        </div>
                        <div class="flex flex-col items-center justify-center p-6 bg-gray-200 rounded-lg shadow-md-custom">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <p class="text-lg font-semibold text-gray-700 mb-4">
                                Turnos online haciendo click en
                            </p>
                            <a href="{{ route('login') }}" class="btn-primary" style="background-color: #018000; border-color: #018000;">TURNOS ONLINE</a>
                        </div>
                        <div class="flex flex-col items-center justify-center p-6 bg-gray-200 rounded-lg shadow-md-custom">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <p class="text-lg font-semibold text-gray-700">
                                Llamá a la clínica y agendá tu cita
                            </p>
                            <p class="text-2xl md:text-3xl font-bold" style="color: #0000FE;">(3406) 472555</p>
                        </div>
                    </div>
                </div>
            </div>

        </main>

        <footer class="bg-gray-300 text-gray-800 py-8">
            <div class="container mx-auto max-w-7xl px-4 md:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-sm border-t border-gray-300 pt-8">
                    <div>
                        <h4 class="font-bold text-base mb-2">Horarios administrativos</h4>
                        <p class="mb-1">Lunes a viernes: 8:00 a 20:00</p>
                        <p>Sábado: 8:00 a 12:00</p>
                    </div>
                    <div class="flex flex-col items-center justify-center">
                        <img src="{{ asset('images/logoSinFondo.png') }}" alt="Logo de Clínica Comunal Santa Juana" class="h-12 w-12 mb-2">
                        <p class="text-center">9 de julio 283, María Juana, Santa Fe</p>
                    </div>
                    <div class="md:text-right">
                        <h4 class="font-bold text-base mb-2">Contacto</h4>
                        <p class="flex items-center justify-start md:justify-end mb-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m-2 11H5a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2z" /></svg>
                            clinica_mj@gmail.com
                        </p>
                        <p class="flex items-center justify-start md:justify-end">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                            (3406) 471001
                        </p>
                    </div>
                </div>
                <div class="mt-8 text-center text-xs text-black">
                    &copy; 2024 Clínica Comunal Santa Juana. Todos los derechos reservados.
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
