
<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (
    empty($_SESSION['id_usuario_autenticado'])
    || (int) $_SESSION['id_usuario_autenticado'] <= 0
) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'La sesión expiró. Inicia sesión nuevamente.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if ((int) ($_SESSION['rol_usuario_autenticado'] ?? 0) !== 1) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'No tienes permisos para realizar esta acción.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}
?>