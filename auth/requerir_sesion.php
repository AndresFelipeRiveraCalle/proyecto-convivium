<?php

require_once dirname(__DIR__) . "/config/sesion.php";

if (
    empty($_SESSION['id_usuario_autenticado'])
    || (int) $_SESSION['id_usuario_autenticado'] <= 0
) {
    $urlLogin = defined('BASE_URL')
        ? BASE_URL . 'login.php'
        : '/proyecto-convivium/login.php';

    header('Location: ' . $urlLogin);
    exit();
}

if ((int) ($_SESSION['rol_usuario_autenticado'] ?? 0) !== 1) {
    http_response_code(403);
    exit('No tienes permisos para acceder a esta sección.');
}