<?php

/**
 * CONVIVIUM - Módulo de Autenticación
 * Archivo: restablecer_password.php
 * Objetivo: Validar el token de la URL y mostrar el formulario para la nueva contraseña.
 */

require '../config/conexion.php';

// 1. CAPTURA DEL TOKEN: Recibimos el token viajando de forma visible en la URL (?token=...)
$token = $_GET['token'] ?? '';

// Si la URL no contiene ningún token, bloqueamos el acceso inmediatamente
if (empty($token)) {
    die("Acceso denegado: Token de recuperación no proporcionado.");
}

// 2. VALIDACIÓN DE TIEMPO: Capturamos la hora actual de Colombia
date_default_timezone_set('America/Bogota');
$ahora = date('Y-m-d H:i:s');

// 3. CONSULTA DE SEGURIDAD: Buscamos un usuario que tenga ese token exacto Y que la hora de expiración
// guardada en la BD sea MAYOR que la hora actual (es decir, que no hayan pasado los 15 minutos).
$stmt = $conexion->prepare("SELECT id FROM usuario WHERE reset_token = ? AND reset_expires_at > ?");
$stmt->execute([$token, $ahora]);
$user = $stmt->fetch();

// Si no encuentra ningún usuario bajo esas reglas, el enlace ya caducó o es falso
if (!$user) {
    die("<h2>Error de Validación</h2><p>El enlace ha expirado o es inválido. Por favor, solicita uno nuevo en la sección de login.</p><a href='solicitar_recuperacion.php'>Volver a intentarlo</a>");
}
// Si la consulta fue exitosa, el script continúa hacia abajo y pinta el HTML
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convivium - Nueva Contraseña</title>
</head>

<body>
    <h2>Crea tu nueva contraseña de acceso</h2>
    <p>Ingresa una combinación segura que no olvides.</p>

    <form action="guardar_nuevo_password.php" method="POST">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

        <label for="nueva_contrasena">Nueva Contraseña:</label>
        <input type="password" id="nueva_contrasena" name="nueva_contrasena" required minlength="6" placeholder="Mínimo 6 caracteres">
        <br><br>
        <button type="submit">Actualizar y Guardar Contraseña</button>
    </form>
</body>

</html>