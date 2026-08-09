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
            "ID de ciudad no válido."
        );

    }


    // ======================================================
    // CONSULTAR CIUDAD
    // ======================================================

    $sql = "
        SELECT
            c.id_ciudad,
            c.id_departamento,
            c.nombre,
            c.codigo_dane,
            c.Activo,
            d.nombre AS nombre_departamento,
            p.nombre AS nombre_pais

        FROM ciudades c

        INNER JOIN departamentos d
            ON d.id_departamento = c.id_departamento

        INNER JOIN paises p
            ON p.id_pais = d.id_pais

        WHERE c.id_ciudad = ?

        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $id
    ]);


    $ciudad = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$ciudad) {

        throw new Exception(
            "Ciudad no encontrada."
        );

    }


    echo json_encode([
        "success" => true,
        "data" => $ciudad
    ]);


} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "mensaje" => $e->getMessage()
    ]);

}