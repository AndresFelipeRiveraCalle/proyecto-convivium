<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

header("Content-Type: application/json; charset=utf-8");

try {

    $id = (int) ($_GET["id"] ?? 0);

    if ($id <= 0) {
        throw new Exception("ID no válido.");
    }

    $sql = "
        SELECT
            id_estado_civil,
            nombre,
            estado
        FROM estados_civiles
        WHERE id_estado_civil = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([$id]);

    $estadoCivil = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$estadoCivil) {
        throw new Exception("Estado civil no encontrado.");
    }

    echo json_encode([
        "success" => true,
        "data" => $estadoCivil
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "mensaje" => $e->getMessage()
    ]);
}