<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

header('Content-Type: application/json; charset=utf-8');


// ==========================================================
// VALIDAR MÉTODO
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido.'
    ]);

    exit;
}


// ==========================================================
// VALIDAR ID
// ==========================================================

$idTarifa = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$idTarifa || $idTarifa <= 0) {

    echo json_encode([
        'success' => false,
        'message' => 'La tarifa no es válida.'
    ]);

    exit;
}


// ==========================================================
// CONSULTAR TARIFA
// ==========================================================

try {

    $sql = "
        SELECT
            t.id_tarifa,
            t.id_concepto,
            t.id_tipo_config,
            t.nombre,
            t.valor,
            t.fecha_inicio,
            t.fecha_fin,
            t.estado,
            t.observaciones,

            cf.nombre AS concepto_nombre,
            cf.tipo_calculo,

            dtu.nombre_grupo AS tipo_unidad

        FROM tarifas_facturacion t

        INNER JOIN conceptos_facturacion cf
            ON cf.id_concepto = t.id_concepto

        INNER JOIN detalle_tipos_unidad dtu
            ON dtu.id_tipo_config = t.id_tipo_config

        WHERE t.id_tarifa = :id_tarifa

        LIMIT 1
    ";


    $stmt = $conexion->prepare(
        $sql
    );


    $stmt->execute([

        ':id_tarifa'
            => $idTarifa

    ]);


    $tarifa = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    // ======================================================
    // VERIFICAR EXISTENCIA
    // ======================================================

    if (!$tarifa) {

        echo json_encode([
            'success' => false,
            'message' => 'La tarifa no existe.'
        ]);

        exit;
    }


    // ======================================================
    // NORMALIZAR DATOS
    // ======================================================

    $tarifa['id_tarifa'] =
        (int)$tarifa['id_tarifa'];

    $tarifa['id_concepto'] =
        (int)$tarifa['id_concepto'];

    $tarifa['id_tipo_config'] =
        (int)$tarifa['id_tipo_config'];

    $tarifa['valor'] =
        (float)$tarifa['valor'];

    $tarifa['estado'] =
        (int)$tarifa['estado'];


    // ======================================================
    // RESPUESTA
    // ======================================================

    echo json_encode(
        [
            'success' => true,
            'tarifa' => $tarifa
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;


} catch (PDOException $e) {

    error_log(
        'Error obteniendo tarifa de facturación: ' .
        $e->getMessage()
    );


    echo json_encode([
        'success' => false,
        'message' => 'No fue posible consultar la tarifa.'
    ]);

    exit;
}