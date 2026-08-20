<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

try {

    // ==========================================================
    // DATOS DEL FORMULARIO
    // ==========================================================

    $id_concepto = isset($_POST["id_concepto"])
        ? (int) $_POST["id_concepto"]
        : 0;

    $nombre = isset($_POST["nombre"])
        ? trim($_POST["nombre"])
        : "";

    $descripcion = !empty($_POST["descripcion"])
        ? trim($_POST["descripcion"])
        : null;

    $tipo_calculo = isset($_POST["tipo_calculo"])
        ? $_POST["tipo_calculo"]
        : "FIJO";

    $id_cuenta_contable = !empty($_POST["id_cuenta_contable"])
        ? (int) $_POST["id_cuenta_contable"]
        : null;

    $obligatorio = isset($_POST["obligatorio"])
        ? (int) $_POST["obligatorio"]
        : 0;

    $estado = isset($_POST["estado"])
        ? (int) $_POST["estado"]
        : 1;


    // ==========================================================
    // VALIDAR ID
    // ==========================================================

    if ($id_concepto <= 0) {

        $mensaje = urlencode(
            "El concepto de facturación no es válido."
        );

        header(
            "Location: " .
            BASE_URL .
            "configuracion/conceptos_facturacion.php?tipo=warning&texto=$mensaje"
        );

        exit;
    }


    // ==========================================================
    // VALIDAR NOMBRE
    // ==========================================================

    if ($nombre === "") {

        $mensaje = urlencode(
            "Debe ingresar el nombre del concepto."
        );

        header(
            "Location: " .
            BASE_URL .
            "configuracion/conceptos_facturacion.php?tipo=warning&texto=$mensaje"
        );

        exit;
    }


    // ==========================================================
    // VALIDAR TIPO DE CÁLCULO
    // ==========================================================

    $tiposPermitidos = [
        "FIJO",
        "METRO_CUADRADO",
        "COEFICIENTE",
        "PORCENTAJE"
    ];

    if (!in_array($tipo_calculo, $tiposPermitidos, true)) {

        $mensaje = urlencode(
            "El tipo de cálculo seleccionado no es válido."
        );

        header(
            "Location: " .
            BASE_URL .
            "configuracion/conceptos_facturacion.php?tipo=warning&texto=$mensaje"
        );

        exit;
    }


    // ==========================================================
    // VALIDAR DUPLICADO
    // ==========================================================
    // IMPORTANTE:
    // Se excluye el concepto que estamos editando.
    // ==========================================================

    $sql = "
        SELECT COUNT(*)
        FROM conceptos_facturacion
        WHERE nombre = :nombre
        AND id_concepto <> :id_concepto
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":nombre" => $nombre,
        ":id_concepto" => $id_concepto
    ]);

    if ($stmt->fetchColumn() > 0) {

        $mensaje = urlencode(
            "Ya existe otro concepto de facturación con ese nombre."
        );

        header(
            "Location: " .
            BASE_URL .
            "configuracion/conceptos_facturacion.php?tipo=warning&texto=$mensaje"
        );

        exit;
    }


    // ==========================================================
    // ACTUALIZAR
    // ==========================================================

    $sql = "
        UPDATE conceptos_facturacion
        SET
            nombre = :nombre,
            descripcion = :descripcion,
            tipo_calculo = :tipo_calculo,
            id_cuenta_contable = :id_cuenta_contable,
            obligatorio = :obligatorio,
            estado = :estado

        WHERE id_concepto = :id_concepto
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([

        ":nombre" => $nombre,

        ":descripcion" => $descripcion,

        ":tipo_calculo" => $tipo_calculo,

        ":id_cuenta_contable" => $id_cuenta_contable,

        ":obligatorio" => $obligatorio,

        ":estado" => $estado,

        ":id_concepto" => $id_concepto

    ]);


    // ==========================================================
    // MENSAJE DE ÉXITO
    // ==========================================================

    $mensaje = urlencode(
        "Concepto de facturación actualizado correctamente."
    );

    header(
        "Location: " .
        BASE_URL .
        "configuracion/conceptos_facturacion.php?tipo=success&texto=$mensaje"
    );

    exit;


} catch (PDOException $e) {

    $mensaje = urlencode(
        "Error al actualizar el concepto: " .
        $e->getMessage()
    );

    header(
        "Location: " .
        BASE_URL .
        "configuracion/conceptos_facturacion.php?tipo=error&texto=$mensaje"
    );

    exit;
}