<?php

/**
 * CONVIVIUM - Módulo de Autenticación
 * Archivo: solicitar_recuperacion.php
 * Objetivo: Formulario visual para que el usuario ingrese su correo electrónico.
 */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convivium - Recuperar Contraseña</title>
</head>

<body>
    <h2>¿Olvidaste tu contraseña?</h2>
    <p>Ingresa tu correo electrónico registrado y te enviaremos un enlace para restablecerla.</p>

    <form action="procesar_recuperacion.php" method="POST">
        <label for="correo">Correo Electrónico:</label>
        <input type="email" id="correo" name="correo" required placeholder="ejemplo@correo.com">
        <br><br>
        <button type="submit">Enviar enlace de recuperación</button>
    </form>

    <br>
    <a href="../login.php">Volver al Inicio de Sesión</a>
</body>

</html>