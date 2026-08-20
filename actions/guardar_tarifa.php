<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

try {

    // ==========================================================
    // DATOS DEL FORMULARIO
    // ==========================================================

    $id_concepto = !empty($_POST["id_concepto"])
        ? (int) $_POST["id_concepto"]
        : 0;

    $id_tipo_config = !empty($_POST["id_tipo_config"])
        ? (int) $_POST["id_tipo_config"]
        : 0;

    $nombre = !empty($_POST["nombre"])
        ? trim($_POST["nombre"])
        : null;

    $valor = isset($_POST["valor"])
        ? (float) $_POST["valor"]
        : 0;

    $fecha_inicio = !empty($_POST["fecha_inicio"])
        ? $_POST["fecha_inicio"]
        : null;

    $fecha_fin = !empty($_POST["fecha_fin"])
        ? $_POST["fecha_fin"]
        : null;

    $estado = isset($_POST["estado"])
        ? (int) $_POST["estado"]
        : 1;

    $observaciones = !empty($_POST["observaciones"])
        ? trim($_POST["observaciones"])
        : null;


    // ==========================================================
    // VALIDACIONES BÁSICAS
    // ==========================================================

    if ($id_concepto <= 0) {

        header(
            "Location: ../configuracion/tarifas.php?mensaje=error"
        );

        exit;
    }


    if ($id_tipo_config <= 0) {

        header(
            "Location: ../configuracion/tarifas.php?mensaje=error"
        );

        exit;
    }


    if ($valor < 0) {

        header(
            "Location: ../configuracion/tarifas.php?mensaje=error"
        );

        exit;
    }


    if (empty($fecha_inicio)) {

        header(
            "Location: ../configuracion/tarifas.php?mensaje=error"
        );

        exit;
    }


    // ==========================================================
    // VALIDAR FECHAS
    // ==========================================================

    if (
        !empty($fecha_fin) &&
        $fecha_fin < $fecha_inicio
    ) {

        header(
            "Location: ../configuracion/tarifas.php?mensaje=error"
        );

        exit;
    }


    // ==========================================================
    // VALIDAR QUE EL CONCEPTO EXISTA
    // ==========================================================

    $sql = "
        SELECT
            id_concepto
        FROM conceptos_facturacion
        WHERE id_concepto = ?
        AND estado = 1
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $id_concepto
    ]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {

        header(
            "Location: ../configuracion/tarifas.php?mensaje=error"
        );

        exit;
    }


    // ==========================================================
    // VALIDAR TIPO DE UNIDAD
    // ==========================================================

    $sql = "
        SELECT
            id_tipo_config
        FROM detalle_tipos_unidad
        WHERE id_tipo_config = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $id_tipo_config
    ]);

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {

        header(
            "Location: ../configuracion/tarifas.php?mensaje=error"
        );

        exit;
    }


    // ==========================================================
    // VALIDAR TARIFA DUPLICADA
    //
    // Una tarifa se considera duplicada cuando:
    //
    // - Es del mismo concepto
    // - Es para el mismo tipo de unidad
    // - Está activa
    // - Y sus períodos de vigencia se cruzan
    //
    // ==========================================================

    $sql = "
        SELECT
            id_tarifa
        FROM tarifas_facturacion
        WHERE id_concepto = ?
        AND id_tipo_config = ?
        AND estado = 1

        AND fecha_inicio <= COALESCE(?, '9999-12-31')

        AND (
            fecha_fin IS NULL
            OR fecha_fin >= ?
        )

        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $id_concepto,
        $id_tipo_config,
        $fecha_fin,
        $fecha_inicio
    ]);

    if ($stmt->fetch(PDO::FETCH_ASSOC)) {

        header(
            "Location: ../configuracion/tarifas.php?mensaje=existe"
        );

        exit;
    }


    // ==========================================================
    // INSERTAR TARIFA
    // ==========================================================

    $sql = "
        INSERT INTO tarifas_facturacion (
            id_concepto,
            id_tipo_config,
            nombre,
            valor,
            fecha_inicio,
            fecha_fin,
            estado,
            observaciones
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
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
        $observaciones
    ]);


    // ==========================================================
    // REDIRECCIÓN
    // ==========================================================

    header(
        "Location: ../configuracion/tarifas.php?mensaje=ok"
    );

    exit;


} catch (PDOException $e) {

    // ==========================================================
    // ERROR
    // ==========================================================

    header(
        "Location: ../configuracion/tarifas.php?mensaje=error"
    );

    exit;
}