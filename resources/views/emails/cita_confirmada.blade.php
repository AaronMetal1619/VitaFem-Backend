<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .container { background-color: #ffffff; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 5px solid #0d6efd; }
        .header { text-align: center; padding-bottom: 20px; border-bottom: 1px solid #eeeeee; margin-bottom: 20px; }
        .header h2 { color: #0d6efd; margin: 0; }
        .details { background-color: #f8f9fa; padding: 20px; border-left: 4px solid #0d6efd; border-radius: 5px; margin-bottom: 20px; }
        .details p { margin: 10px 0; font-size: 16px; color: #333333; }
        .footer { text-align: center; color: #888888; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eeeeee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>¡Tu cita está confirmada! 🏥</h2>
        </div>
        <p style="font-size: 16px; color: #555555;">Hola,</p>
        <p style="font-size: 16px; color: #555555;">Tu cita en <strong>Clínica VitaFem</strong> ha sido agendada exitosamente. Aquí tienes los detalles de tu visita:</p>

        <div class="details">
            <p><strong>👨‍⚕️ Especialista:</strong> {{ $citaData['medico_nombre'] }}</p>
            <p><strong>📅 Fecha:</strong> {{ $citaData['fecha'] }}</p>
            <p><strong>⏰ Hora:</strong> {{ $citaData['hora'] }} hrs</p>
            @if(isset($citaData['motivo']) && $citaData['motivo'] != '')
                <p><strong>📝 Motivo:</strong> {{ $citaData['motivo'] }}</p>
            @endif
        </div>

        <p style="font-size: 16px; color: #555555;">Por favor, procura llegar 10 minutos antes de tu cita. Si necesitas cancelar o reprogramar, contáctanos con anticipación.</p>
        <p style="font-size: 16px; color: #555555;">¡Te esperamos!</p>

        <div class="footer">
            <p><strong>Clínica VitaFem</strong> - Cuidando de tu salud</p>
            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
        </div>
    </div>
</body>
</html>
