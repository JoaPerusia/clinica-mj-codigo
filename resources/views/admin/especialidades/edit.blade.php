<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Especialidad</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1 class="mb-4">Editar Especialidad</h1>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.especialidades.update', $especialidade->id_especialidad) }}" method="POST">
            @csrf
            @method('PUT') {{-- Importante para las actualizaciones --}}
            <div class="mb-3">
                <label for="nombre_especialidad" class="form-label">Nombre de la Especialidad</label>
                <input type="text" class="form-control" id="nombre_especialidad" name="nombre_especialidad" value="{{ old('nombre_especialidad', $especialidade->nombre_especialidad) }}" required>
            </div>
            <button type="submit" class="btn btn-primary">Actualizar Especialidad</button>
            <a href="{{ route('admin.especialidades.index') }}" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>