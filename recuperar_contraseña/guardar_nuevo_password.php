<?php

/**
 * CONVIVIUM - Módulo de Autenticación
 * Archivo: guardar_nuevo_password.php
 * Objetivo: Encriptar la nueva clave, actualizar la base de datos y destruir el token usado.
 */

require '../config/conexion.php';

// Verificamos que los datos del formulario lleguen por el método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibimos el token oculto y la clave que el usuario digitó
    $token = $_POST['token'] ?? '';
    $nueva_contrasena = $_POST['nueva_contrasena'] ?? '';

    if (empty($token) || empty($nueva_contrasena)) {
        die("Datos incompletos para procesar la solicitud.");
    }

    // 1. RE-VERIFICACIÓN DE SEGURIDAD: Volvemos a comprobar el token y el tiempo en el servidor.
    // Esto evita ataques donde alguien intente saltarse el paso anterior enviando datos directos a este archivo.
    date_default_timezone_set('America/Bogota');
    $ahora = date('Y-m-d H:i:s');

    $stmt = $conexion->prepare("SELECT id FROM usuario WHERE reset_token = ? AND reset_expires_at > ?");
    $stmt->execute([$token, $ahora]);
    $user = $stmt->fetch();

    if ($user) {
        // 2. ENCRIPTACIÓN: Ciframos la contraseña usando el algoritmo estándar de la industria (Bcrypt).
        // password_hash se encarga de que la clave nunca se guarde en texto plano, protegiendo la privacidad del usuario.
        $hash_contrasena = password_hash($nueva_contrasena, PASSWORD_BCRYPT);

        // 3. ACTUALIZACIÓN Y LIMPIEZA TOTAL:
        // - Modificamos la columna 'contrasena' con el nuevo hash cifrado.
        // - Ponemos 'reset_token' y 'reset_expires_at' en NULL.
        // Al ponerlos en NULL, invalidamos el enlace para siempre, haciendo que sea de UN SOLO USO.
        $update = $conexion->prepare("UPDATE usuario SET contrasena = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
        $update->execute([$hash_contrasena, $user['id']]);

        echo "<h2>¡Contraseña modificada con éxito!</h2>";
        echo "<p>Los cambios se guardaron de forma segura en Convivium.</p>";
        echo "<a href='../login.php'>Ir al Login a Probar</a>";
    } else {
        echo "<h2>Error</h2><p>No se pudo procesar la solicitud. El token caducó en el último minuto o fue manipulado.</p>";
    }
} else {
    header("Location: ../login.php");
    exit;
}
