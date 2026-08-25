<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

try {

    // ==========================================================
    // DATOS DEL FORMULARIO
    // ==========================================================

    $nombre = isset($_POST["nombre"])
        ? trim($_POST["nombre"])
        : "";

    $descripcion = !empty($_POST["descripcion"])
        ? trim($_POST["descripcion"])
        : null;

    $tipo_calculo = isset($_POST["tipo_calculo"])
        ? trim($_POST["tipo_calculo"])
        : "";

    $id_cuenta_contable = !empty($_POST["id_cuenta_contable"])
        ? (int) $_POST["id_cuenta_contable"]
        : null;

    $obligatorio = isset($_POST["obligatorio"])
        ? 1
        : 0;

    $estado = isset($_POST["estado"])
        ? (int) $_POST["estado"]
        : 1;


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
    // VALIDAR CUENTA CONTABLE
    // ==========================================================

    if ($id_cuenta_contable !== null) {

        $sql = "
            SELECT id_cuenta_contable
            FROM cuentas_contables
            WHERE id_cuenta_contable = :id
              AND estado = 1
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sql);

        $stmt->execute([
            ":id" => $id_cuenta_contable
        ]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {

            $mensaje = urlencode(
                "La cuenta contable seleccionada no existe o está inactiva."
            );

            header(
                "Location: " .
                BASE_URL .
                "configuracion/conceptos_facturacion.php?tipo=warning&texto=$mensaje"
            );

            exit;
        }
    }


    // ==========================================================
    // VALIDAR ESTADO
    // ==========================================================

    if ($estado !== 0 && $estado !== 1) {
        $estado = 1;
    }


    // ==========================================================
    // VERIFICAR CONCEPTO DUPLICADO
    // ==========================================================

    $sql = "
        SELECT id_concepto
        FROM conceptos_facturacion
        WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:nombre))
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":nombre" => $nombre
    ]);

    if ($stmt->fetch(PDO::FETCH_ASSOC)) {

        $mensaje = urlencode(
            "Ya existe un concepto de facturación con ese nombre."
        );

        header(
            "Location: " .
            BASE_URL .
            "configuracion/conceptos_facturacion.php?tipo=warning&texto=$mensaje"
        );

        exit;
    }


    // ==========================================================
    // INSERTAR CONCEPTO
    // ==========================================================

    $sql = "
        INSERT INTO conceptos_facturacion
        (
            nombre,
            descripcion,
            tipo_calculo,
            id_cuenta_contable,
            obligatorio,
            estado
        )
        VALUES
        (
            :nombre,
            :descripcion,
            :tipo_calculo,
            :id_cuenta_contable,
            :obligatorio,
            :estado
        )
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":nombre" => $nombre,
        ":descripcion" => $descripcion,
        ":tipo_calculo" => $tipo_calculo,
        ":id_cuenta_contable" => $id_cuenta_contable,
        ":obligatorio" => $obligatorio,
        ":estado" => $estado
    ]);


    // ==========================================================
    // MENSAJE DE ÉXITO
    // ==========================================================

    $mensaje = urlencode(
        "Concepto de facturación creado correctamente."
    );

    header(
        "Location: " .
        BASE_URL .
        "configuracion/conceptos_facturacion.php?tipo=success&texto=$mensaje"
    );

    exit;


} catch (PDOException $e) {

    $mensaje = urlencode(
        "Error al guardar el concepto: " . $e->getMessage()
    );

    header(
        "Location: " .
        BASE_URL .
        "configuracion/conceptos_facturacion.php?tipo=error&texto=$mensaje"
    );

    exit;
}