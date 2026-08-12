<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

header("Content-Type: application/json; charset=utf-8");

try {

    $id = intval($_GET["id"] ?? 0);

    if ($id <= 0) {
        echo json_encode([
            "error" => "ID de agrupación no válido."
        ]);
        exit;
    }

    $sql = "
        SELECT
            id_agrupacion,
            id_tipo_agrupacion,
            nombre,
            descripcion
        FROM agrupaciones
        WHERE id_agrupacion = :id
          AND activo = 1
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bindValue(
        ":id",
        $id,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $agrupacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agrupacion) {
        echo json_encode([
            "error" => "La agrupación no existe."
        ]);
        exit;
    }

    echo json_encode($agrupacion);

} catch (PDOException $e) {

    echo json_encode([
        "error" => "Error al consultar la agrupación."
    ]);
}