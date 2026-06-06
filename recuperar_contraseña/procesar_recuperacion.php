<?php

/**
 * CONVIVIUM - Módulo de Autenticación
 * Archivo: procesar_recuperacion.php
 * Objetivo: Validar el correo, generar el token de seguridad y registrar la expiración.
 */

// 1. Importamos la conexión centralizada a la base de datos
require '../config/conexion.php';

// Seguridad: Verificar que los datos lleguen estrictamente por el método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Limpiamos espacios en blanco innecesarios en el correo ingresado
    $correo = trim($_POST['correo']);

    if (empty($correo)) {
        die("Por favor, ingresa un correo válido.");
    }

    // 2. CONSULTA: Validamos si el correo existe en la tabla 'usuario'
    $stmt = $conexion->prepare("SELECT id FROM usuario WHERE correo = ?");
    $stmt->execute([$correo]);
    $user = $stmt->fetch(); // Retorna un array asociativo si encuentra al usuario, o false si no

    if ($user) {
        // 3. GENERACIÓN DEL TOKEN: Creamos una cadena alfanumérica aleatoria de 64 caracteres
        // bin2hex(random_bytes(32)) genera un código criptográficamente seguro e indescifrable
        $token = bin2hex(random_bytes(32));

        // 4. TIEMPO DE EXPIRACIÓN: Sincronizamos la hora local de Colombia
        date_default_timezone_set('America/Bogota');
        // Calculamos la hora actual + 15 minutos en formato estándar de MySQL (Año-Mes-Día Hora:Min:Seg)
        $expiracion = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // 5. ACTUALIZACIÓN: Guardamos el token y la expiración en el registro del usuario correspondiente
        $update = $conexion->prepare("UPDATE usuario SET reset_token = ?, reset_expires_at = ? WHERE correo = ?");
        $update->execute([$token, $expiracion, $correo]);

        // 6. ENLACE DINÁMICO: Construimos la URL inyectándole el token como parámetro GET
        $enlace = "http://localhost/proyecto-convivium/recuperar_contraseña/restablecer_password.php?token=" . $token;

        // --- ENTORNO DE DESARROLLO (SIMULACIÓN) ---
        // Explicación para el equipo: Como estamos en localhost, simulamos el envío imprimiendo el enlace.
        // En producción, aquí se integraría PHPMailer para disparar el correo de forma oculta al usuario.
        echo "<h2>Convivium - Servidor de Correo (Simulación)</h2>";
        echo "<p>Se ha generado una solicitud de cambio de clave para: <b>" . htmlspecialchars($correo) . "</b></p>";
        echo "<p>Para probarlo ahora mismo, haz clic aquí:</p>";
        echo "<a href='$enlace'>Restablecer mi contraseña</a>";
    } else {
        // Buenas prácticas de ciberseguridad: Si el correo no existe, mostramos el mismo mensaje.
        // Esto evita que atacantes maliciosos adivinen qué correos están registrados en el sistema.
        echo "<h3>Proceso iniciado</h3>";
        echo "<p>Si el correo electrónico coincide con una cuenta registrada, se enviará un enlace de recuperación.</p>";
        echo "<br><a href='../login.php'>Volver al login</a>";
    }
} else {
    // Si alguien intenta entrar a este archivo escribiendo la URL a mano, lo mandamos al formulario
    header("Location: solicitar_recuperacion.php");
    exit;
}
