<?php
    define("ROOT_PATH", realpath(__DIR__ . "/.."));
    define("BASE_URL", "/proyecto-convivium/");

    // ==========================================================
    // CIFRADO DE CREDENCIALES DE CORREO
    // Clave privada utilizada para SMTP e IMAP.
    // ==========================================================

    define(
        'CORREO_ENCRYPTION_KEY',
        '185d77dadc8d96a22b8cbb6d9975b69b0c52bb0f8504681560955bd776f59d2d'
    );
?>