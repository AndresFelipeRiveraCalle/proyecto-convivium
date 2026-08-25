<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

header("Content-Type: application/json; charset=utf-8");

try {

    // ==========================================================
    // ID DEL PAGO
    // ==========================================================

    $id_pago = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

    if ($id_pago <= 0) {
        throw new Exception("ID de pago inválido. Recibido: " . ($_GET["id"] ?? "NO EXISTE"));
    }


    // ==========================================================
    // CONSULTAR
    // ==========================================================

    $sql = "
        SELECT
            id_pago,
            id_unidad,
            fecha_pago,
            valor,
            medio_pago,
            origen_pago,
            referencia,
            observaciones,
            estado,
            estado_conciliacion
        FROM pagos
        WHERE id_pago = :id_pago
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":id_pago" => $id_pago
    ]);

    $pago = $stmt->fetch(PDO::FETCH_ASSOC);


    // ==========================================================
    // NO EXISTE
    // ==========================================================

    if (!$pago) {

        echo json_encode([
            "success" => false,
            "mensaje" => "No se encontró el pago con ID: " . $id_pago
        ]);

        exit;
    }


    // ==========================================================
    // ÉXITO
    // ==========================================================

    echo json_encode([
        "success" => true,
        "pago" => $pago
    ]);

    exit;


} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "mensaje" => $e->getMessage(),
        "archivo" => $e->getFile(),
        "linea" => $e->getLine()
    ]);

    exit;
}