<?php
// Archivo: recuperar_contrasena.php
session_start();
if (isset($_SESSION['id_usuario_autenticado'])) {
    header("Location: inicio.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Convivium</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="login-body">

    <div class="login-container">
        <h2>Recuperación</h2>
        <p class="subtitle">Ingresa tu correo para buscar tu cuenta</p>

        <!-- Mensajes de Error y Éxito -->
        <?php if (isset($_GET['error']) && $_GET['error'] == 'no_existe'): ?>
            <div class="alerta-error">
                <strong>Error:</strong> No encontramos ninguna cuenta asociada a ese correo.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'sistema'): ?>
            <div class="alerta-error">
                <strong>Error del Sistema:</strong> Ocurrió un problema en la base de datos.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] == 'no_envio'): ?>
            <div class="alerta-error">
                <strong>Error:</strong> No se pudo enviar el correo. Revisa la configuración de SMTP.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['exito']) && $_GET['exito'] == 'correo_enviado'): ?>
            <div style="background-color: #dcfce7; color: #166534; padding: 15px; border-radius: 4px; margin-bottom: 25px; font-size: 14px; text-align: center; border: 1px solid #86efac;">
                <strong>¡Correo Enviado!</strong><br><br>
                Hemos enviado un enlace de recuperación a tu correo electrónico. Por favor, revisa tu bandeja de entrada o la carpeta de spam.
            </div>
        <?php else: ?>

            <!-- Formulario -->
            <form action="auth/procesar_recuperacion.php" method="POST">
                <div class="form-group">
                    <label for="correo_recuperar">Correo Electrónico Registrado</label>
                    <input type="email" id="correo_recuperar" name="correo_recuperacion" placeholder="usuario@correo.com" required>
                </div>

                <button type="submit" class="btn-submit" style="background-color: #f59e0b;">Buscar mi cuenta</button>

                <div style="text-align: center; margin-top: 15px;">
                    <a href="login.php" style="color: #64748b; font-size: 13px; text-decoration: none; font-weight: 600;">Volver al Login</a>
                </div>
            </form>

        <?php endif; ?>
    </div>
</body>
</html>