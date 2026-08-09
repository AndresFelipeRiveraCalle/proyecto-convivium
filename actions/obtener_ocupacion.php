<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

header("Content-Type: application/json; charset=utf-8");

try {
    $id = (int) ($_GET["id"] ?? 0);
    if ($id <= 0) {
        throw new Exception("ID de ocupación no válido.");
    }

    $sql = " SELECT id_ocupacion, nombre, estado
        FROM ocupaciones
        WHERE id_ocupacion = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([$id]);

    $ocupacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ocupacion) {
        throw new Exception("Ocupación no encontrada.");
    }

    echo json_encode([
        "success" => true,
        "data" => $ocupacion
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "mensaje" => $e->getMessage()
    ]);
}