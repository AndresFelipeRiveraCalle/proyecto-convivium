<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

header('Content-Type: application/json; charset=utf-8');

try {

    $id = isset($_GET['id'])
        ? (int) $_GET['id']
        : 0;

    if ($id <= 0) {

        echo json_encode([
            'success' => false,
            'mensaje' => 'ID de usuario no válido.'
        ]);

        exit;
    }


    // ==========================================================
    // CONSULTAR USUARIO
    // ==========================================================

    $sql = "
        SELECT
            u.id,
            u.nombres,
            u.apellidos,
            u.id_tipo_documento,
            u.numero_documento,
            u.correo,
            u.telefono,
            u.celular,
            u.fecha_nacimiento,
            u.id_genero,
            u.id_estado_civil,
            u.id_ocupacion,
            u.direccion,
            u.foto,
            u.estado

        FROM usuario u

        WHERE u.id = :id

        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ':id' => $id
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);


    // ==========================================================
    // VALIDAR
    // ==========================================================

    if (!$usuario) {

        echo json_encode([
            'success' => false,
            'mensaje' => 'El usuario no existe.'
        ]);

        exit;
    }


    // ==========================================================
    // RESPUESTA
    // ==========================================================

    echo json_encode([
        'success' => true,
        'usuario' => $usuario
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al consultar el usuario.',
        'error' => $e->getMessage()
    ]);
}