<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

try {

    // ==========================================================
    // DATOS
    // ==========================================================

    $id_tarifa = isset($_POST['id_tarifa'])
        ? (int) $_POST['id_tarifa']
        : 0;

    $id_concepto = isset($_POST['id_concepto'])
        ? (int) $_POST['id_concepto']
        : 0;

    $id_tipo_config = isset($_POST['id_tipo_config'])
        ? (int) $_POST['id_tipo_config']
        : 0;

    $nombre = !empty($_POST['nombre'])
        ? trim($_POST['nombre'])
        : null;

    $valor = isset($_POST['valor'])
        ? (float) $_POST['valor']
        : 0;

    $fecha_inicio = !empty($_POST['fecha_inicio'])
        ? $_POST['fecha_inicio']
        : null;

    $fecha_fin = !empty($_POST['fecha_fin'])
        ? $_POST['fecha_fin']
        : null;

    $estado = isset($_POST['estado'])
        ? (int) $_POST['estado']
        : 1;

    $observaciones = !empty($_POST['observaciones'])
        ? trim($_POST['observaciones'])
        : null;


    // ==========================================================
    // VALIDACIONES
    // ==========================================================

    if (
        $id_tarifa <= 0 ||
        $id_concepto <= 0 ||
        $id_tipo_config <= 0 ||
        $valor < 0 ||
        empty($fecha_inicio)
    ) {

        header(
            "Location: " . BASE_URL .
            "configuracion/tarifas.php?mensaje=error"
        );

        exit;
    }


    // ==========================================================
    // VALIDAR DUPLICADO
    // ==========================================================

    $sql = "
        SELECT id_tarifa
        FROM tarifas_facturacion

        WHERE id_concepto = ?
        AND id_tipo_config = ?
        AND fecha_inicio = ?
        AND id_tarifa <> ?

        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $id_concepto,
        $id_tipo_config,
        $fecha_inicio,
        $id_tarifa
    ]);

    if ($stmt->fetch(PDO::FETCH_ASSOC)) {

        header(
            "Location: " . BASE_URL .
            "configuracion/tarifas.php?mensaje=existe"
        );

        exit;
    }


    // ==========================================================
    // ACTUALIZAR
    // ==========================================================

    $sql = "
        UPDATE tarifas_facturacion

        SET
            id_concepto = ?,
            id_tipo_config = ?,
            nombre = ?,
            valor = ?,
            fecha_inicio = ?,
            fecha_fin = ?,
            estado = ?,
            observaciones = ?

        WHERE id_tarifa = ?
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $id_concepto,
        $id_tipo_config,
        $nombre,
        $valor,
        $fecha_inicio,
        $fecha_fin,
        $estado,
        $observaciones,
        $id_tarifa
    ]);


    // ==========================================================
    // REDIRECCIÓN
    // ==========================================================

    header(
        "Location: " . BASE_URL .
        "configuracion/tarifas.php?mensaje=actualizado"
    );

    exit;


} catch (PDOException $e) {

    die(
        "Error al actualizar la tarifa: " .
        htmlspecialchars($e->getMessage())
    );
}