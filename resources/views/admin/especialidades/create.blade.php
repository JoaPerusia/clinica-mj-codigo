@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="content-wrapper"> 
                <h1 class="page-title">Crear Nueva Especialidad</h1> 

                {{-- Botón de Inicio para Admin --}}
                @if(auth()->check() && auth()->user()->id_rol == 1)
                    <div class="action-buttons-container"> 
                        <a href="{{ route('admin.dashboard') }}" class="btn-secondary">
                            ← Inicio
                        </a>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert-danger"> 
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.especialidades.store') }}" method="POST">
                    @csrf
                    <div class="form-group"> 
                        <label for="nombre_especialidad" class="form-label">Nombre de la Especialidad</label> 
                        <input type="text" class="form-input" id="nombre_especialidad" name="nombre_especialidad" value="{{ old('nombre_especialidad') }}" required> 
                    </div>
                    <button type="submit" class="btn-primary">Guardar Especialidad</button>
                    <a href="{{ route('admin.especialidades.index') }}" class="btn-secondary ml-2">Cancelar</a> {{-- Añadido ml-2 para separar el botón --}}
                </form>
            </div>
        </div>
    </div>
@endsection
