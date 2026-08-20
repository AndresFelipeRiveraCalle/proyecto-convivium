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
            'mensaje' => 'ID de tarifa no válido.'
        ]);

        exit;
    }


    // ==========================================================
    // CONSULTAR TARIFA
    // ==========================================================

    $sql = "
        SELECT
            tf.id_tarifa,
            tf.id_concepto,
            tf.id_tipo_config,
            tf.nombre,
            tf.valor,
            tf.fecha_inicio,
            tf.fecha_fin,
            tf.estado,
            tf.observaciones

        FROM tarifas_facturacion tf

        WHERE tf.id_tarifa = :id

        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ':id' => $id
    ]);

    $tarifa = $stmt->fetch(PDO::FETCH_ASSOC);


    // ==========================================================
    // VALIDAR
    // ==========================================================

    if (!$tarifa) {

        echo json_encode([
            'success' => false,
            'mensaje' => 'La tarifa no existe.'
        ]);

        exit;
    }


    // ==========================================================
    // RESPUESTA
    // ==========================================================

    echo json_encode([
        'success' => true,
        'tarifa' => $tarifa
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al consultar la tarifa.',
        'error' => $e->getMessage()
    ]);
}