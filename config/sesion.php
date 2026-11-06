<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    $entorno = strtolower(
        trim((string)(getenv('APP_ENV') ?: 'development'))
    );

    $conexionSegura =
        $entorno === 'production'
        || (
            !empty($_SERVER['HTTPS'])
            && strtolower((string)$_SERVER['HTTPS']) !== 'off'
        );

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $conexionSegura,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}