<?php
// 1. Iniciamos sesión para comprobar si el usuario ya está autenticado
session_start();

// 2. Si el usuario ya tiene una sesión activa, lo enviamos al inicio.
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
    <title>Iniciar Sesión - Convivium</title>

    <!-- Cargamos el archivo de estilos separado -->
    <link rel="stylesheet" href="assets/css/login.css">

</head>
<body class="login-body">

    <div class="login-container">
        <h2>Convivium</h2>
        <p class="subtitle">Sistema de Administración Residencial</p>
	<?php if (isset($_GET['exito']) && $_GET['exito'] === 'cambio_clave'): ?>
    <div
        role="status"
        style="background:#dcfce7;color:#166534;border:1px solid #86efac;padding:12px;border-radius:8px;margin-bottom:16px;text-align:center;"
    >
        <strong>Contraseña actualizada.</strong>
        Ya puedes iniciar sesión con tu nueva contraseña.
    </div>
<?php endif; ?>
        <!-- Recepción de errores desde el backend -->
        <?php if (isset($_GET['error']) && $_GET['error'] === 'credenciales'): ?>
            <div class="alerta-error">
                <strong>Error:</strong> Correo o contraseña incorrectos.
            </div>
        <?php elseif (isset($_GET['error']) && $_GET['error'] === 'sistema'): ?>
            <div class="alerta-error">
                <strong>Error:</strong> No fue posible iniciar sesión. Intenta de nuevo más tarde.
            </div>
        <?php endif; ?>

        <!-- Formulario que envía los datos a la carpeta auth -->
        <form action="auth/procesar_login.php" method="POST">

            <div class="form-group">
                <label for="correo">Correo Electrónico</label>
                <input type="email" id="correo" name="correo_electronico" placeholder="usuario@correo.com" required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="clave">Contraseña</label>
                <input type="password" id="clave" name="contrasena_secreta" placeholder="Ingresa tu contraseña" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn-submit">Ingresar al Sistema</button>

            <!-- Enlace de recuperación de contraseña -->
            <div style="text-align: center; margin-top: 15px;">
                <a href="recuperar_contrasena.php" style="color: #2563eb; font-size: 13px; text-decoration: none; font-weight: 600;">¿Olvidaste tu contraseña?</a>
            </div>

        </form>
    </div>

</body>
</html>