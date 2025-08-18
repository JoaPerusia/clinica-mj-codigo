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
                                La Clínica Comunal Santa Juana es un centro de atención médica e internación que pertenece a la Comuna de María Juana, una localidad del departamento Castellanos en la provincia de Santa Fe, Argentina. Desde su fundación en 2005, la clínica ha sido un pilar fundamental en el sistema de salud local, brindando atención médica de calidad a los habitantes de la comunidad y zonas aledañas.
                            </p>
                            <p class="text-lg text-gray-700 leading-relaxed mb-4">
                                A comienzos de los años 2000, la localidad carecía de servicios de internación y cirugía.
                                Muchos médicos habían cerrado sus consultorios privados y la comunidad debía trasladarse a otras ciudades (Rafaela, San Jorge, San Francisco) incluso para prestaciones mínimas.
                                Esta situación generó un reclamo fuerte de la población y llevó a la Comuna a priorizar una solución.
                            </p>
                            <p class="text-lg text-gray-700 leading-relaxed">
                                Clínica Comunal “Santa Juana” fue inaugurada oficialmente el 24 de Junio de 2005 y abrió sus puertas el 1° de Julio del mismo año.
                                Desde entonces, se consolidó como un centro de salud de alta importancia local, abarcando consultorios externos, guardias, cirugías, partos e internación.
                                El objetivo nunca fue económico, sino social y sanitario con un autofinanciamiento.
                                Actualmente debido a las leyes de maternidad segura, no se realizaron más partos y cesáreas.
                                La clínica permitió reducir el desarraigo de los pacientes y sus familias, brindando atención de calidad en su entorno local. 
                                Esta Institución es una de las obras más significativas de la historia de María Juana. No fue un proyecto fácil, pero es un ejemplo de lo que se puede lograr con compromiso, gestión y el acompañamiento de una comunidad entera.
                            </p>
                        </div>
                        
                        <div class="lg:col-span-1 flex justify-center items-start pt-16">
                            <img src="{{ asset('images/ambulancia.jpg') }}" alt="Médico revisando un portapapeles" class="rounded-lg shadow-md-custom w-full h-auto">
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <footer class="bg-gray-300 text-gray-800 py-8">
            <div class="container mx-auto max-w-7xl px-4 md:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-sm border-t border-gray-300 pt-8">
                    <!-- Horarios -->
                    <div>
                        <h4 class="font-bold text-base mb-2">Horarios administrativos</h4>
                        <p class="mb-1">Lunes a viernes: 8:00 a 20:00</p>
                        <p>Sábado: 8:00 a 12:00</p>
                    </div>
                    <!-- Logo y dirección -->
                    <div class="flex flex-col items-center justify-center">
                        <img src="{{ asset('images/logoSinFondo.png') }}" alt="Logo de Clínica Comunal Santa Juana" class="h-12 w-12 mb-2">
                        <p class="text-center">9 de julio 278, María Juana, Santa Fe</p>
                    </div>
                    <!-- Contacto y Redes Sociales -->
                    <div class="md:text-right flex flex-col justify-between">
                        <div>
                            <h4 class="font-bold text-base mb-2">Contacto</h4>
                            <p class="flex items-center justify-start md:justify-end mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m-2 11H5a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2z" /></svg>
                                clinicasantajuana@coopmj.com.ar
                            </p>
                            <p class="flex items-center justify-start md:justify-end">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                (3406) 472554 || (3406) 472555
                            </p>
                        </div>
                        <!-- Redes Sociales -->
                        <div class="mt-4 md:mt-0 flex flex-col items-start md:items-end">
                            <h4 class="font-bold text-base mb-2">Síguenos</h4>
                            <div class="flex space-x-4">
                                <a href="https://www.facebook.com/p/Cl%C3%ADnica-Comunal-Santa-Juana-100063528853153/" class="text-gray-600 hover:text-blue-500 transition duration-300" target="_blank">
                                    <img src="{{ asset('images/logoFacebook.webp') }}" alt="Facebook" class="h-6 w-6">
                                </a>
                                <a href="https://www.instagram.com/clinicacomunalsantajuana/" class="text-gray-600 hover:text-blue-500 transition duration-300" target="_blank">
                                    <img src="{{ asset('images/logoIg.png') }}" alt="Instagram" class="h-6 w-8">
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-8 text-center text-xs text-black">
                    &copy; 2025 Clínica Comunal Santa Juana. Todos los derechos reservados.
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
