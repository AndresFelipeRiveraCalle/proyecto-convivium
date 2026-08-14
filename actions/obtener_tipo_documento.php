<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

header("Content-Type: application/json; charset=utf-8");

try {

    $id = (int) ($_GET["id"] ?? 0);

    if ($id <= 0) {
        throw new Exception(
            "ID de tipo de documento no válido."
        );
    }


    $sql = "
        SELECT
            id_tipo_documento,
            codigo,
            nombre,
            estado
        FROM tipos_documento
        WHERE id_tipo_documento = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $id
    ]);


    $tipoDocumento = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$tipoDocumento) {

        throw new Exception(
            "Tipo de documento no encontrado."
        );

    }


    echo json_encode([
        "success" => true,
        "data" => $tipoDocumento
    ]);


} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "mensaje" => $e->getMessage()
    ]);

}