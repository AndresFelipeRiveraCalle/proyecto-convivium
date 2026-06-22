<?php

/**
 * CONVIVIUM - Módulo de Autenticación
 * Archivo: restablecer_password.php
 * Ubicación: recuperar_contrasena/restablecer_password.php
 * * Explicación para el equipo: Valida el token de seguridad temporal por URL (GET).
 * Si es válido, abre la tarjeta con el formulario estilizado para la nueva contraseña.
 */

require '../config/conexion.php';

// Capturamos el token que viene por la URL
$token = $_GET['token'] ?? '';
$token_valido = false;
$error_mensaje = "";

if (empty($token)) {
    $error_mensaje = "El enlace es completamente inválido o ha sido alterado.";
} else {
    // Verificamos si el token existe y aún no ha expirado
    date_default_timezone_set('America/Bogota');
    $ahora = date('Y-m-d H:i:s');

    $stmt = $conexion->prepare("SELECT id FROM usuario WHERE reset_token = ? AND reset_expires_at > ?");
    $stmt->execute([$token, $ahora]);
    $user = $stmt->fetch();

    if ($user) {
        $token_valido = true; // ¡Todo super bien! El token es legal y vigente
    } else {
        $error_mensaje = "El enlace ha expirado, ya fue usado o es inválido.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Convivium</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>

<body class="auth-body">
    <div class="auth-card">
        <div class="auth-header">
            <img src="../assets/img/logo_2.png" alt="Convivium" class="auth-logo">
        </div>

        <?php if ($token_valido): ?>
            <p class="auth-subtitle">Establecer nueva contraseña</p>

            <form action="guardar_nuevo_password.php" method="post">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="form-group">
                    <label class="form-label" for="password">Nueva Contraseña</label>
                    <input type="password" class="form-control" name="nueva_contrasena" id="password" placeholder="Mínimo 6 caracteres" required>
                </div>

                <button type="submit" class="btn-primary">Guardar Contraseña</button>
            </form>

        <?php else: ?>
            <p class="auth-subtitle">Enlace Inválido</p>

            <div class="mensaje-error">
                <?= htmlspecialchars($error_mensaje) ?>
            </div>

            <p class="auth-text">
                Por motivos de seguridad, los enlaces de recuperación expiran a los 15 minutos de ser solicitados o quedan inutilizados tras su primer uso.
            </p>

            <div class="auth-footer">
                <a href="solicitar_recuperacion.php">Solicitar un nuevo enlace</a>
            </div>
        <?php endif; ?>

    </div>
</body>

</html>