<?php
    define("ROOT_PATH", realpath(__DIR__ . "/.."));
    define("BASE_URL", "/proyecto-convivium/");

    // ==========================================================
    // CIFRADO DE CREDENCIALES DE CORREO
    // Clave privada utilizada para SMTP e IMAP.
    // ==========================================================

    define(
        'CORREO_ENCRYPTION_KEY',
        '***REMOVED***'
    );
?>