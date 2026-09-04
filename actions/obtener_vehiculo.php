<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

header('Content-Type: application/json; charset=utf-8');

try {

    // ==========================================================
    // VALIDAR ID
    // ==========================================================

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

        echo json_encode([
            'success' => false,
            'mensaje' => 'ID de vehículo no válido.'
        ]);

        exit;
    }


    $id_vehiculo = (int) $_GET['id'];


    // ==========================================================
    // CONSULTAR VEHÍCULO
    // ==========================================================

    $sql = "
        SELECT
            v.id_vehiculo,
            v.placa,
            v.tipo,
            v.marca,
            v.modelo,
            v.color,
            v.id_residente,
            v.id_unidad,
            v.estado,
            v.fecha_desde,
            v.fecha_hasta,
            v.observaciones
        FROM vehiculos v
        WHERE v.id_vehiculo = :id_vehiculo
        LIMIT 1
    ";


    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ':id_vehiculo' => $id_vehiculo
    ]);


    $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);


    // ==========================================================
    // VEHÍCULO NO ENCONTRADO
    // ==========================================================

    if (!$vehiculo) {

        echo json_encode([
            'success' => false,
            'mensaje' => 'No se encontró el vehículo.'
        ]);

        exit;
    }


    // ==========================================================
    // RESPUESTA
    // ==========================================================

    echo json_encode([
        'success' => true,
        'data' => $vehiculo
    ]);

} catch (PDOException $e) {

    error_log($e->getMessage());

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al consultar el vehículo.'
    ]);
}