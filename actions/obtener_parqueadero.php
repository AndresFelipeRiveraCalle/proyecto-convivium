<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

header("Content-Type: application/json; charset=utf-8");

try {

    $id = filter_input(
        INPUT_GET,
        'id',
        FILTER_VALIDATE_INT
    );

    if (!$id) {
        echo json_encode([
            "success" => false,
            "mensaje" => "ID de parqueadero no válido."
        ]);
        exit;
    }

    $sql = "
        SELECT
            id_parqueadero,
            codigo,
            id_unidad,
            tipo,
            ubicacion,
            estado,
            observaciones,
            activo
        FROM parqueaderos
        WHERE id_parqueadero = :id
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":id" => $id
    ]);

    $parqueadero = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$parqueadero) {

        echo json_encode([
            "success" => false,
            "mensaje" => "El parqueadero no existe."
        ]);

        exit;
    }

    echo json_encode([
        "success" => true,
        "data" => $parqueadero
    ]);

} catch (PDOException $e) {

    error_log($e->getMessage());

    echo json_encode([
        "success" => false,
        "mensaje" => "Error al consultar el parqueadero."
    ]);
}