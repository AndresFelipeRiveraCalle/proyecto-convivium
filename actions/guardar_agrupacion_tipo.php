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
    $cantidad = intval($_POST["cantidad"] ?? 0);

    if ($idAgrupacion <= 0) {
        throw new Exception("La agrupación no es válida.");
    }

    if ($idTipoConfig <= 0) {
        throw new Exception("Debe seleccionar un tipo de unidad.");
    }

    if ($cantidad <= 0) {
        throw new Exception("La cantidad debe ser mayor que cero.");
    }

    // ==========================================================
    // VERIFICAR QUE EL TIPO DE UNIDAD EXISTA
    // ==========================================================

    $stmt = $conexion->prepare("
        SELECT id_tipo_config, cantidad_unidades
        FROM detalle_tipos_unidad
        WHERE id_tipo_config = :id_tipo_config
          AND activo = 1
    ");

    $stmt->execute([
        ":id_tipo_config" => $idTipoConfig
    ]);

    $tipo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tipo) {
        throw new Exception("El tipo de unidad seleccionado no existe.");
    }

    // ==========================================================
    // VERIFICAR SI YA EXISTE EN LA AGRUPACIÓN
    // ==========================================================

    $stmt = $conexion->prepare("
        SELECT id_agrupacion_tipo
        FROM agrupacion_tipos_unidad
        WHERE id_agrupacion = :id_agrupacion
          AND id_tipo_config = :id_tipo_config
          AND activo = 1
    ");

    $stmt->execute([
        ":id_agrupacion" => $idAgrupacion,
        ":id_tipo_config" => $idTipoConfig
    ]);

    if ($stmt->fetch()) {
        throw new Exception(
            "Este tipo de unidad ya está configurado en esta agrupación."
        );
    }

    // ==========================================================
    // VERIFICAR CANTIDAD GLOBAL
    // ==========================================================

    $stmt = $conexion->prepare("
        SELECT COALESCE(SUM(cantidad), 0)
        FROM agrupacion_tipos_unidad
        WHERE id_tipo_config = :id_tipo_config
          AND activo = 1
    ");

    $stmt->execute([
        ":id_tipo_config" => $idTipoConfig
    ]);

    $cantidadAsignada = (int)$stmt->fetchColumn();

    $cantidadDisponible =
        (int)$tipo["cantidad_unidades"] - $cantidadAsignada;

    if ($cantidad > $cantidadDisponible) {

        throw new Exception(
            "La cantidad supera las unidades disponibles. " .
            "Disponibles: " . $cantidadDisponible
        );
    }

    // ==========================================================
    // INSERTAR
    // ==========================================================

    $stmt = $conexion->prepare("
        INSERT INTO agrupacion_tipos_unidad
        (
            id_agrupacion,
            id_tipo_config,
            cantidad
        )
        VALUES
        (
            :id_agrupacion,
            :id_tipo_config,
            :cantidad
        )
    ");

    $stmt->execute([
        ":id_agrupacion" => $idAgrupacion,
        ":id_tipo_config" => $idTipoConfig,
        ":cantidad" => $cantidad
    ]);

    $mensaje = urlencode(
        "El tipo de unidad fue agregado correctamente."
    );

    header(
        "Location: " .
        BASE_URL .
        "configuracion/basico.php?agrupacion=" .
        $idAgrupacion .
        "&tipo=success&texto=" .
        $mensaje
    );

    exit;

} catch (Exception $e) {

    $mensaje = urlencode($e->getMessage());

    header(
        "Location: " .
        BASE_URL .
        "configuracion/basico.php?agrupacion=" .
        ($idAgrupacion ?? 0) .
        "&tipo=error&texto=" .
        $mensaje
    );

    exit;
}