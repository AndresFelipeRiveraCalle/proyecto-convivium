<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

header('Content-Type: application/json; charset=utf-8');


// ==========================================================
// VALIDAR ID
// ==========================================================

$idConcepto = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$idConcepto || $idConcepto <= 0) {

    echo json_encode([
        'success' => false,
        'message' => 'El concepto de facturación no es válido.'
    ]);

    exit;
}


// ==========================================================
// CONSULTAR CONCEPTO
// ==========================================================

try {

    $sql = "
        SELECT
            id_concepto,
            nombre,
            descripcion,
            tipo_calculo,
            id_tipo_obligacion,
            id_cuenta_contable,
            obligatorio,
            estado

        FROM conceptos_facturacion

        WHERE id_concepto = :id_concepto

        LIMIT 1
    ";


    $stmt = $conexion->prepare(
        $sql
    );


    $stmt->execute([

        ':id_concepto'
            => $idConcepto

    ]);


    $concepto = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    // ======================================================
    // VERIFICAR EXISTENCIA
    // ======================================================

    if (!$concepto) {

        echo json_encode([
            'success' => false,
            'message' => 'El concepto de facturación no existe.'
        ]);

        exit;
    }


    // ======================================================
    // NORMALIZAR DATOS
    // ======================================================

    $concepto['id_concepto'] =
        (int)$concepto['id_concepto'];

    $concepto['id_tipo_obligacion'] =
        $concepto['id_tipo_obligacion'] !== null
            ? (int)$concepto['id_tipo_obligacion']
            : null;

    $concepto['id_cuenta_contable'] =
        $concepto['id_cuenta_contable'] !== null
            ? (int)$concepto['id_cuenta_contable']
            : null;

    $concepto['obligatorio'] =
        (int)$concepto['obligatorio'];

    $concepto['estado'] =
        (int)$concepto['estado'];


    // ======================================================
    // RESPUESTA
    // ======================================================

    echo json_encode([
        'success' => true,
        'concepto' => $concepto
    ]);

    exit;


} catch (PDOException $e) {

    error_log(
        'Error obteniendo concepto de facturación: ' .
        $e->getMessage()
    );


    echo json_encode([
        'success' => false,
        'message' => 'No fue posible consultar el concepto de facturación.'
    ]);

    exit;
}