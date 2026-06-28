<?php

/**
 * CONVIVIUM - Módulo de Autenticación
 * Archivo: guardar_nueva_password.php
 * Ubicación: recuperar_contrasena/guardar_nueva_password.php
 * * Objetivo:
 * 1. Recibir por POST la contraseña y el token oculto.
 * 2. Re-verificar la vigencia del token por seguridad.
 * 3. Encriptar la contraseña con Bcrypt.
 * 4. Actualizar la BD, limpiar el token (NULL) para que sea de un solo uso.
 * 5. Mostrar una interfaz de éxito o error integrada 100% con auth.css.
 */

require '../config/conexion.php';

// Verificamos que los datos del formulario lleguen estrictamente por el método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['token'] ?? '';
    $nueva_contrasena = $_POST['nueva_contrasena'] ?? '';

    if (empty($token) || empty($nueva_contrasena)) {
        die("Datos incompletos para procesar la solicitud.");
    }

    // 1. RE-VERIFICACIÓN DE SEGURIDAD: Comprobamos el token y el tiempo una última vez en el servidor
    date_default_timezone_set('America/Bogota');
    $ahora = date('Y-m-d H:i:s');

    $stmt = $conexion->prepare("SELECT id FROM usuario WHERE reset_token = ? AND reset_expires_at > ?");
    $stmt->execute([$token, $ahora]);
    $user = $stmt->fetch();

    // -------------------------------------------------------------------------
    // EMPIEZA LA RENDERIZACIÓN DE LA VISTA ESTILIZADA UNIFICADA
    // -------------------------------------------------------------------------
?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Guardar Contraseña - Convivium</title>
        <link rel="stylesheet" href="../assets/css/auth.css">
    </head>

    <body class="auth-body">
        <div class="auth-card">
            <img src="../assets/img/logo_2.png" alt="Convivium" class="auth-logo">
            <?php

            if ($user) {
                // 2. ENCRIPTACIÓN: Ciframos la contraseña usando Bcrypt
                $hash_contrasena = password_hash($nueva_contrasena, PASSWORD_BCRYPT);

                // 3. ACTUALIZACIÓN Y LIMPIEZA TOTAL:
                // Seteamos el token y su fecha en NULL para que el enlace quede INUTILIZABLE de inmediato (un solo uso)
                $update = $conexion->prepare("UPDATE usuario SET contrasena = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
                $update->execute([$hash_contrasena, $user['id']]);

                // --- INTERFAZ DE ÉXITO ---
            ?>
                <p class="auth-subtitle">Actualización Completa</p>

                <div class="mensaje-exito" style="margin-bottom: 24px;">
                    ¡Contraseña modificada con éxito!
                </div>

                <p class="auth-text">
                    Los cambios se han guardado de forma cifrada y segura en el sistema. El enlace temporal ha sido destruido.
                </p>

                <a href="../login.php" class="btn-primary" style="display: block; text-align: center; text-decoration: none; margin-bottom: 8px;">
                    Ir al Login
                </a>
            <?php
            } else {
                // --- INTERFAZ DE ERROR ---
                // Se ejecuta si el usuario tardó más de 15 minutos en el formulario o alteró el token hidden
            ?>
                <p class="auth-subtitle">Error de Procesamiento</p>

                <div class="mensaje-error">
                    No se pudo procesar la solicitud.
                </div>

                <p class="auth-text">
                    El token de seguridad caducó en el último minuto o fue manipulado desde las herramientas de desarrollador.
                </p>

                <div class="auth-footer">
                    <a href="solicitar_recuperacion.php" class="btn-primary">Solicitar un nuevo enlace</a>
                </div>
            <?php
            }

            // Pie de página común que cierra las etiquetas contenedoras
            ?>
        </div>
    </body>

    </html>
<?php

} else {
    // Si intentan acceder escribiendo la URL del archivo de forma directa, los saca al login
    header("Location: ../login.php");
    exit;
}
?>