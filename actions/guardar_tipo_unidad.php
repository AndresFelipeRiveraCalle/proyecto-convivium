<?php

require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../configuracion/basico.php");
    exit;
}

try {

    // ==========================================================
    // DATOS DEL FORMULARIO
    // ==========================================================

    $id_tipo_config = isset($_POST["id_tipo_config"])
        ? (int) $_POST["id_tipo_config"]
        : 0;

    $cantidad = isset($_POST["cantidad_unidades"])
        ? (int) $_POST["cantidad_unidades"]
        : 0;

    $area = isset($_POST["area_total"]) && $_POST["area_total"] !== ""
        ? (float) $_POST["area_total"]
        : null;

    $coeficiente = isset($_POST["coeficiente_total"]) && $_POST["coeficiente_total"] !== ""
        ? (float) $_POST["coeficiente_total"]
        : null;

    $observaciones = trim($_POST["observaciones"] ?? "");


    // ==========================================================
    // VALIDAR ID
    // ==========================================================

    if ($id_tipo_config <= 0) {

        $mensaje = urlencode(
            "No se encontró el tipo de unidad que desea modificar."
        );

        header(
            "Location: ../configuracion/basico.php?tipo=warning&texto=" . $mensaje
        );

        exit;
    }


    // ==========================================================
    // VALIDAR CANTIDAD
    // ==========================================================

    if ($cantidad <= 0) {

        $mensaje = urlencode(
            "La cantidad de unidades debe ser mayor que cero."
        );

        header(
            "Location: ../configuracion/basico.php?id=" .
            $id_tipo_config .
            "&tipo=warning&texto=" .
            $mensaje
        );

        exit;
    }


    // ==========================================================
    // VERIFICAR QUE EXISTA EL REGISTRO
    // ==========================================================

    $sql = "
        SELECT id_tipo_config
        FROM detalle_tipos_unidad
        WHERE id_tipo_config = :id_tipo_config
        AND activo = 1
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":id_tipo_config" => $id_tipo_config
    ]);

    $registro = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$registro) {

        $mensaje = urlencode(
            "El tipo de unidad que desea modificar no existe."
        );

        header(
            "Location: ../configuracion/basico.php?tipo=error&texto=" .
            $mensaje
        );

        exit;
    }


    // ==========================================================
    // ACTUALIZAR
    // ==========================================================

    $sql = "
        UPDATE detalle_tipos_unidad
        SET
            cantidad_unidades = :cantidad,
            area_total = :area,
            coeficiente_total = :coeficiente,
            observaciones = :observaciones
        WHERE id_tipo_config = :id_tipo_config
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":cantidad" => $cantidad,
        ":area" => $area,
        ":coeficiente" => $coeficiente,
        ":observaciones" => $observaciones,
        ":id_tipo_config" => $id_tipo_config
    ]);


    // ==========================================================
    // MENSAJE
    // ==========================================================

    $mensaje = urlencode(
        "La configuración del tipo de unidad fue actualizada correctamente."
    );

    header(
        "Location: ../configuracion/basico.php?id=" .
        $id_tipo_config .
        "&tipo=success&texto=" .
        $mensaje
    );

    exit;


} catch (PDOException $e) {

    $mensaje = urlencode(
        "Error al actualizar el tipo de unidad: " .
        $e->getMessage()
    );

    header(
        "Location: ../configuracion/basico.php?tipo=error&texto=" .
        $mensaje
    );
    exit;
}