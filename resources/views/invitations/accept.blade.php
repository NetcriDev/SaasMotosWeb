<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invitación al taller</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 32rem; margin: 4rem auto; padding: 0 1rem; color: #1f2937; }
        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; }
        p { line-height: 1.6; color: #4b5563; }
        .actions { display: flex; gap: 0.75rem; margin-top: 1.5rem; flex-wrap: wrap; }
        a { display: inline-block; padding: 0.625rem 1rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600; }
        .primary { background: #d97706; color: #fff; }
        .secondary { background: #f3f4f6; color: #111827; }
    </style>
</head>
<body>
    <h1>Invitación al taller</h1>
    <p>Para unirte al equipo, inicia sesión o crea una cuenta con el mismo correo al que se envió la invitación.</p>
    <div class="actions">
        <a class="primary" href="{{ $loginUrl }}">Iniciar sesión</a>
        <a class="secondary" href="{{ $registerUrl }}">Registrarse</a>
    </div>
</body>
</html>
