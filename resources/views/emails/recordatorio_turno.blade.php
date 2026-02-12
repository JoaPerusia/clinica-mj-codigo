<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recordatorio de Turno Médico</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        
        .header { text-align: center; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .header h1 { margin: 0; color: #0056b3; }
        
        .content { padding: 20px 0; }
        .content p { margin: 0 0 10px; }
        
        .aviso-cancelacion {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ffeeba;
            font-size: 0.9em;
            margin-top: 15px;
            text-align: center;
        }

        .footer { text-align: center; font-size: 0.8em; color: #888; border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px; }
        
        .button { 
            display: inline-block; 
            padding: 10px 20px; 
            margin: 20px 0; 
            font-size: 16px; 
            color: #fff !important; 
            background-color: #28a745;
            border-radius: 5px; 
            text-decoration: none; 
        }
        .center { text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Recordatorio de Turno</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{{ $turno->paciente->usuario->nombre ?? 'Paciente' }}</strong>,</p>
            
            <p>Te recordamos que tienes un turno médico programado para <strong>mañana</strong>.</p>
            
            <p><strong>Detalles de la cita:</strong></p>
            <ul>
                <li><strong>Profesional:</strong> {{ $turno->medico->usuario->nombre }} {{ $turno->medico->usuario->apellido }}</li>
                <li><strong>Especialidad:</strong> {{ $turno->medico->especialidades->first()->nombre_especialidad ?? 'Consulta General' }}</li>
                <li><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($turno->fecha)->format('d/m/Y') }}</li>
                <li><strong>Hora:</strong> {{ \Carbon\Carbon::parse($turno->hora)->format('H:i') }} hs</li>
            </ul>

            <div class="center">
                <a href="{{ url('/') }}" class="button">Ver mis Turnos</a>
            </div>

            <div class="aviso-cancelacion">
                <strong>¿No podrás asistir?</strong><br>
                Por favor, ingresa al sistema y cancela el turno con anticipación para ceder el lugar a otro paciente que lo necesite.
            </div>
        </div>
        <div class="footer">
            <p>Te esperamos.</p>
            <p>Atentamente, la Administración de Clínica Comunal Santa Juana.</p>
        </div>
    </div>
</body>
</html>