<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="{{ asset('images/logoSinFondo.png') }}">
    <title>Sobre Nosotros | Clínica Comunal Santa Juana</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-gray-200 text-gray-900 font-sans">
    <div class="min-h-screen flex flex-col">

        <header class="w-full bg-gray-200 shadow-md">
            <nav class="w-full max-w-7xl mx-auto flex items-center justify-between p-4 md:p-6">
                <a href="{{ url('/') }}" class="flex items-center space-x-3 hover:opacity-80 transition duration-300">
                    <img src="{{ asset('images/logoSinFondo.png') }}" alt="Logo de Clínica Comunal Santa Juana" class="h-10 w-10">
                    <span class="text-xl font-bold text-gray-900">Clínica Comunal Santa Juana</span>
                </a>
                <div class="flex items-center space-x-4">
                    <a href="{{ url('/about') }}" class="text-blue-600 font-bold border-b-2 border-blue-600">Sobre Nosotros</a>
                    <a href="{{ route('login') }}" class="btn-primary" style="background-color: #0000FE; border-color: #0000FE;">Turnos Online</a>
                </div>
            </nav>
        </header>

        <main class="flex-grow py-12 bg-gray-100">
            <div class="container mx-auto max-w-7xl px-4 md:px-6 lg:px-8">
                <div class="bg-white p-8 rounded-lg shadow-lg">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2">
                            <h1 class="text-4xl font-extrabold mb-4" style="color: #018000;">SOBRE NOSOTROS</h1>
                            <p class="text-lg text-gray-700 leading-relaxed mb-4">
                                En Clínica Comunal Santa Juana, nos dedicamos a ofrecer la mejor atención médica en un ambiente cálido y profesional. Nuestro equipo de médicos especialistas está comprometido con tu bienestar y el de tu familia. Te invitamos a conocer más sobre nuestra historia y los valores que nos guían.
                            </p>
                            <p class="text-lg text-gray-700 leading-relaxed mb-4">
                                Fundada con el objetivo de servir a nuestra comunidad, la clínica ha crecido para convertirse en un referente de salud en la región. Contamos con tecnología de vanguardia y un personal altamente calificado para asegurar que cada paciente reciba el cuidado que merece.
                            </p>
                            <p class="text-lg text-gray-700 leading-relaxed">
                                Nuestra misión es proveer servicios de salud accesibles y de alta calidad, promoviendo un estilo de vida saludable y previniendo enfermedades. Creemos firmemente que una atención cercana y personalizada es clave para una recuperación exitosa.
                            </p>
                        </div>
                        
                        <div class="lg:col-span-1 flex justify-center items-start pt-16">
                            <img src="{{ asset('images/2.jpg') }}" alt="Médico revisando un portapapeles" class="rounded-lg shadow-md-custom w-full h-auto">
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
