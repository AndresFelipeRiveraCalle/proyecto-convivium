<?php

/**
 * CONVIVIUM - Módulo de Autenticación
 * Archivo: procesar_recuperacion.php
 * Ubicación: recuperar_contrasena/procesar_recuperacion.php
 * * Explicación para el equipo: Este archivo recibe el correo, valida si existe,
 * guarda el token temporal en la base de datos y simula el envío del correo
 * pintando el botón de acceso con el estilo unificado de la app.
 */

// Conexión central saliendo de la carpeta actual
require '../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $correo = trim($_POST['correo'] ?? '');

    if (empty($correo)) {
        die("Por favor, ingresa un correo válido.");
    }

    // CONSULTA: Validamos si el correo existe en la tabla 'usuario'
    $stmt = $conexion->prepare("SELECT id FROM usuario WHERE correo = ?");
    $stmt->execute([$correo]);
    $user = $stmt->fetch();

    // Abrimos la estructura HTML para pintar la respuesta de forma estética
?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Procesando Recuperación - Convivium</title>
        <link rel="stylesheet" href="../assets/css/auth.css">
    </head>

    <body class="auth-body">
        <div class="auth-card">
            <img src="../assets/img/logo_2.png" alt="Convivium" class="auth-logo">
            <?php

            if ($user) {
                // GENERACIÓN DEL TOKEN: Código seguro de 64 caracteres
                $token = bin2hex(random_bytes(32));

                // ZONA HORARIA: Sincronizamos la hora local de Colombia
                date_default_timezone_set('America/Bogota');
                $expiracion = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                // ACTUALIZACIÓN: Guardamos token y expiración en el registro del usuario
                $update = $conexion->prepare("UPDATE usuario SET reset_token = ?, reset_expires_at = ? WHERE correo = ?");
                $update->execute([$token, $expiracion, $correo]);

                // ENLACE DINÁMICO: URL apuntando a la subcarpeta correcta
                $enlace = "http://localhost/proyecto-convivium/recuperar_contrasena/restablecer_password.php?token=" . $token;

                // --- ENTORNO DE DESARROLLO (VISTA DE SIMULACIÓN) ---
                // Pintamos el buzón de simulación usando las clases unificadas de auth.css
            ?>
                <p class="auth-subtitle">Servidor de Correo (Simulación)</p>

                <div class="mensaje-exito">
                    Solicitud procesada correctamente para el residente.
                </div>

                <p class="auth-text">
                    Se ha generado un enlace único de un solo uso para: <br>
                    <b><?= htmlspecialchars($correo) ?></b>.
                </p>

                <a href="<?= $enlace ?>" class="btn-primary">
                    Restablecer mi contraseña
                </a>
            <?php

            } else {
                // CIBERSEGURIDAD: Si el correo no existe, mostramos un mensaje genérico para que un 
                // atacante no descubra qué correos están registrados en el conjunto residencial.
            ?>
                <p class="auth-subtitle">Proceso Iniciado</p>

                <div class="mensaje-exito">
                    Si el correo electrónico coincide con una cuenta registrada, se enviará un enlace de recuperación.
                </div>

                <p class="auth-text">
                    Por favor, revisa la bandeja de entrada o la carpeta de spam en los próximos minutos.
                </p>
            <?php
            }

            // Cierre del contenedor de la tarjeta y el pie de página común para ambos casos
            ?>
            <div class="auth-footer">
                <a href="../login.php">Volver al Inicio de Sesión</a>
            </div>
        </div>
    </body>

    </html>
<?php

} else {
    header("Location: solicitar_recuperacion.php");
    exit;
}
?>