<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

header(
    "Content-Type: application/json; charset=utf-8"
);

try {

    $id = (int) (
        $_GET["id"] ?? 0
    );

    if ($id <= 0) {

        throw new Exception(
            "ID de departamento no válido."
        );

    }


    $sql = "
        SELECT
            d.id_departamento,
            d.id_pais,
            p.nombre AS nombre_pais,
            d.nombre,
            d.codigo,
            d.Activo
        FROM departamentos d
        INNER JOIN paises p
            ON p.id_pais = d.id_pais
        WHERE d.id_departamento = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $id
    ]);

    $departamento = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$departamento) {

        throw new Exception(
            "Departamento no encontrado."
        );

    }


    echo json_encode([
        "success" => true,
        "data" => $departamento
    ]);


} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "mensaje" => $e->getMessage()
    ]);

}