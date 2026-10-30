<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (
    empty($_SESSION['id_usuario_autenticado'])
    || (int) $_SESSION['id_usuario_autenticado'] <= 0
) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => false,
        'message' => 'La sesión expiró. Inicia sesión nuevamente.'
    ], JSON_UNESCAPED_UNICODE);

    exit();
}
