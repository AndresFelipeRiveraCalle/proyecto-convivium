<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

header('Content-Type: application/json; charset=utf-8');

try {

    $id = isset($_GET['id'])
        ? (int) $_GET['id']
        : 0;

    if ($id <= 0) {

        echo json_encode([
            'success' => false,
            'mensaje' => 'ID de concepto no válido.'
        ]);

        exit;
    }


    // ==========================================================
    // CONSULTAR CONCEPTO
    // ==========================================================

    $sql = "
        SELECT
            id_concepto,
            nombre,
            descripcion,
            tipo_calculo,
            id_cuenta_contable,
            obligatorio,
            estado

        FROM conceptos_facturacion

        WHERE id_concepto = :id

        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ':id' => $id
    ]);

    $concepto = $stmt->fetch(PDO::FETCH_ASSOC);


    // ==========================================================
    // VALIDAR
    // ==========================================================

    if (!$concepto) {

        echo json_encode([
            'success' => false,
            'mensaje' => 'El concepto no existe.'
        ]);

        exit;
    }


    // ==========================================================
    // RESPUESTA
    // ==========================================================

    echo json_encode([
        'success' => true,
        'concepto' => $concepto
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al consultar el concepto.',
        'error' => $e->getMessage()
    ]);
}