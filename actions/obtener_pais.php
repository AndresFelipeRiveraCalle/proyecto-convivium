<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

header("Content-Type: application/json; charset=utf-8");

try {

    $id = (int) ($_GET["id"] ?? 0);

    if ($id <= 0) {
        throw new Exception("ID de país no válido.");
    }

    $sql = "
        SELECT
            id_pais,
            nombre,
            estado
        FROM paises
        WHERE id_pais = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $id
    ]);

    $pais = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pais) {
        throw new Exception("País no encontrado.");
    }

    echo json_encode([
        "success" => true,
        "data" => $pais
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "mensaje" => $e->getMessage()
    ]);

}