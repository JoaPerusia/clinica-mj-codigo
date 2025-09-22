<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Notificación de Cancelación de Turno</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .header { text-align: center; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .header h1 { margin: 0; color: #0056b3; }
        .content { padding: 20px 0; }
        .content p { margin: 0 0 10px; }
        .footer { text-align: center; font-size: 0.8em; color: #888; border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px; }
        .button { display: inline-block; padding: 10px 20px; margin: 20px 0; font-size: 16px; color: #fff; background-color: #007bff; border-radius: 5px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Turno Cancelado</h1>
        </div>
        <div class="content">
            <p>Hola {{ $turno->paciente->usuario->nombre ?? 'Paciente' }},</p>
            <p>Te escribimos para informarte que tu turno ha sido cancelado debido a un bloqueo de agenda del médico.</p>
            <p><strong>Detalles del Turno Cancelado:</strong></p>
            <ul>
                <li><strong>Médico:</strong> {{ $turno->medico->usuario->nombre }} {{ $turno->medico->usuario->apellido }}</li>
                <li><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($turno->fecha)->format('d/m/Y') }}</li>
                <li><strong>Hora:</strong> {{ \Carbon\Carbon::parse($turno->hora)->format('H:i') }}</li>
            </ul>
            <p><strong>Motivo de la Cancelación:</strong> {{ $motivoBloqueo }}</p>
            <p>Te pedimos disculpas por los inconvenientes que esto pueda ocasionar. Puedes reagendar un nuevo turno en nuestro sistema.</p>
            <a href="{{ url('/') }}" class="button">Agendar Nuevo Turno</a>
        </div>
        <div class="footer">
            <p>Gracias por tu comprensión.</p>
            <p>Atentamente, la Administración de Clínica Comunal Santa Juana.</p>
        </div>
    </div>
</body>
</html>