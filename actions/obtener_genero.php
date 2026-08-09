<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

header("Content-Type: application/json; charset=utf-8");

try {

    $id = (int) ($_GET["id"] ?? 0);

    if ($id <= 0) {
        throw new Exception("ID de género no válido.");
    }

    $sql = "
        SELECT
            id_genero,
            nombre,
            estado
        FROM generos
        WHERE id_genero = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([$id]);

    $genero = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$genero) {
        throw new Exception("Género no encontrado.");
    }

    echo json_encode([
        "success" => true,
        "data" => $genero
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "mensaje" => $e->getMessage()
    ]);
}