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
    <title>Recuperar Contraseña - Convivium</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>

<body class="auth-body">

    <div class="auth-card">
        <img src="../assets/img/Logo_2.png" alt="Convivium" class="auth-logo">
        <p class="auth-subtitle">¿Olvidaste tu contraseña?</p>

        <p class="auth-text">Ingresa tu correo electrónico registrado y te enviaremos un enlace seguro para restablecerla de inmediato.</p>

        <form action="procesar_recuperacion.php" method="POST">
            <div class="form-group">
                <label class="form-label" for="correo">Correo Electrónico</label>
                <input type="email" class="form-control" id="correo" name="correo" required placeholder="ejemplo@correo.com">
            </div>

            <button type="submit" class="btn-primary">Enviar enlace de recuperación</button>

            <div class="auth-footer">
                <a href="../login.php">Volver al Inicio de Sesión</a>
            </div>
        </form>
    </div>

</body>

</html>