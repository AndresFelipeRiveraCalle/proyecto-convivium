<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . BASE_URL . "configuracion/basico.php");
    exit;
}

try {

    $idAgrupacion = intval($_POST["id_agrupacion"] ?? 0);
    $idTipoConfig = intval($_POST["id_tipo_config"] ?? 0);
    $cantidad     = intval($_POST["cantidad"] ?? 0);

    // ==========================================================
    // VALIDACIONES
    // ==========================================================

    if ($idAgrupacion <= 0) {
        throw new Exception("Debe seleccionar una agrupación.");
    }

    if ($idTipoConfig <= 0) {
        throw new Exception("Debe seleccionar un tipo de unidad.");
    }

    if ($cantidad < 0) {
        throw new Exception("La cantidad no puede ser negativa.");
    }

    // ==========================================================
    // VERIFICAR AGRUPACIÓN
    // ==========================================================

    $stmt = $conexion->prepare("
        SELECT id_agrupacion
        FROM agrupaciones
        WHERE id_agrupacion = :id
          AND activo = 1
    ");

    $stmt->execute([
        ":id" => $idAgrupacion
    ]);

    if (!$stmt->fetch()) {
        throw new Exception("La agrupación seleccionada no existe o está inactiva.");
    }

    // ==========================================================
    // VERIFICAR TIPO DE UNIDAD
    // ==========================================================

    $stmt = $conexion->prepare("
        SELECT id_tipo_config, nombre_grupo, cantidad_unidades
        FROM detalle_tipos_unidad
        WHERE id_tipo_config = :id
          AND activo = 1
    ");

    $stmt->execute([
        ":id" => $idTipoConfig
    ]);

    $tipoUnidad = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tipoUnidad) {
        throw new Exception("El tipo de unidad seleccionado no existe o está inactivo.");
    }

    // ==========================================================
    // VERIFICAR CANTIDAD TOTAL DISPONIBLE
    // ==========================================================

    $stmt = $conexion->prepare("
        SELECT COALESCE(SUM(cantidad), 0)
        FROM agrupacion_tipos_unidad
        WHERE id_tipo_config = :id_tipo_config
          AND activo = 1
          AND id_agrupacion <> :id_agrupacion
    ");

    $stmt->execute([
        ":id_tipo_config" => $idTipoConfig,
        ":id_agrupacion"  => $idAgrupacion
    ]);

    $cantidadYaAsignada = (int) $stmt->fetchColumn();

    $cantidadTotal = (int) $tipoUnidad["cantidad_unidades"];

    if (($cantidadYaAsignada + $cantidad) > $cantidadTotal) {

        $disponible = $cantidadTotal - $cantidadYaAsignada;

        throw new Exception(
            "La cantidad supera el total configurado para {$tipoUnidad['nombre_grupo']}. " .
            "Disponible para asignar: {$disponible}."
        );
    }

    // ==========================================================
    // INSERTAR / ACTUALIZAR
    // ==========================================================

    $stmt = $conexion->prepare("
        INSERT INTO agrupacion_tipos_unidad
        (
            id_agrupacion,
            id_tipo_config,
            cantidad,
            activo
        )
        VALUES
        (
            :id_agrupacion,
            :id_tipo_config,
            :cantidad,
            1
        )
        ON DUPLICATE KEY UPDATE
            cantidad = VALUES(cantidad),
            activo = 1
    ");

    $stmt->execute([
        ":id_agrupacion" => $idAgrupacion,
        ":id_tipo_config" => $idTipoConfig,
        ":cantidad" => $cantidad
    ]);

    // ==========================================================
    // FINAL
    // ==========================================================

    $mensaje = urlencode(
        "La distribución fue guardada correctamente."
    );

    header(
        "Location: " .
        BASE_URL .
        "configuracion/basico.php?tipo=success&texto=" .
        $mensaje
    );

    exit;

} catch (Exception $e) {

    $mensaje = urlencode(
        "Error al guardar la distribución: " . $e->getMessage()
    );

    header(
        "Location: " .
        BASE_URL .
        "configuracion/basico.php?tipo=error&texto=" .
        $mensaje
    );

    exit;
}