<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" href="{{ asset('images/logoSinFondo.png') }}" type="image/png">
        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">

            @include('layouts.navigation')

            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main>
                @yield('content')
            </main>

        </div>

        {{-- 1. CDN de SweetAlert2 --}}
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        {{-- 2. Configuración Global Estilizada (Dark & Compact) --}}
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                
                // COLORES DEL TEMA
                const themeColor = '#1f2937';
                const themeText  = '#f3f4f6'; 
                const themeBorder = '#374151'; 

                // A. MIXIN PARA TOASTS 
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    background: themeColor,
                    color: themeText,
                    customClass: {
                        popup: 'colored-toast shadow-lg border border-gray-700 rounded-lg'
                    },
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });

                // B. INTERCEPTAR MENSAJES LARAVEL
                @if(session('success')) Toast.fire({ icon: 'success', title: "{{ session('success') }}" }); @endif
                @if(session('error')) Toast.fire({ icon: 'error', title: "{{ session('error') }}" }); @endif
                @if(session('warning')) Toast.fire({ icon: 'warning', title: "{{ session('warning') }}" }); @endif
                @if(session('info')) Toast.fire({ icon: 'info', title: "{{ session('info') }}" }); @endif

                // C. CONFIRMACIÓN DE FORMULARIOS
                document.body.addEventListener('submit', function(e) {
                    if (e.target.classList.contains('confirm-delete')) {
                        e.preventDefault();
                        const form = e.target;
                        
                        Swal.fire({
                            title: '¿Estás seguro?',
                            text: "Esta acción es irreversible.",
                            icon: 'warning',
                            iconColor: '#f59e0b', 
                            
                            width: '24em',       
                            background: themeColor,
                            color: themeText,
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444', 
                            cancelButtonColor: '#4b5563',  
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar',
                            reverseButtons: true, 
                            
                            customClass: {
                                popup: 'rounded-xl shadow-2xl border border-gray-700',
                                title: 'text-lg font-bold', 
                                htmlContainer: 'text-sm'    
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    }
                });
            });
        </script>
        
        @stack('scripts')
    </body>
</html>