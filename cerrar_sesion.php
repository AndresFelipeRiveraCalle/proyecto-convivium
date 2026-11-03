<?php

require_once __DIR__ . "/config/sesion.php";

$_SESSION = [];

$parametrosCookie = session_get_cookie_params();

setcookie(session_name(), '', [
    'expires' => time() - 42000,
    'path' => $parametrosCookie['path'] ?: '/',
    'domain' => $parametrosCookie['domain'] ?? '',
    'secure' => $parametrosCookie['secure'],
    'httponly' => $parametrosCookie['httponly'],
    'samesite' => $parametrosCookie['samesite'] ?? 'Lax',
]);

session_destroy();

header("Location: login.php");
exit;