<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: " .
        BASE_URL .
        "pagos.php?tipo=error&mensaje=" .
        urlencode("Solicitud no válida.")
    );

    exit;
}

try {

    // ==========================================================
    // DATOS DEL FORMULARIO
    // ==========================================================

    $id_pago = isset($_POST["id_pago"])
        ? (int) $_POST["id_pago"]
        : 0;

    $id_unidad = isset($_POST["id_unidad"])
        ? (int) $_POST["id_unidad"]
        : 0;

    $fecha_pago = trim(
        $_POST["fecha_pago"] ?? ""
    );

    $valor = isset($_POST["valor"])
        ? (float) $_POST["valor"]
        : 0;

    $medio_pago = trim(
        $_POST["medio_pago"] ?? ""
    );

    $origen_pago = trim(
        $_POST["origen_pago"] ?? ""
    );

    $referencia = trim(
        $_POST["referencia"] ?? ""
    );

    $observaciones = trim(
        $_POST["observaciones"] ?? ""
    );


    // ==========================================================
    // VALIDACIONES BÁSICAS
    // ==========================================================

    if ($id_pago <= 0) {
        throw new Exception(
            "El pago que intenta actualizar no es válido."
        );
    }

    if ($id_unidad <= 0) {
        throw new Exception(
            "Debe seleccionar una unidad."
        );
    }

    if ($fecha_pago === "") {
        throw new Exception(
            "Debe indicar la fecha del pago."
        );
    }

    if ($valor <= 0) {
        throw new Exception(
            "El valor del pago debe ser mayor que cero."
        );
    }


    // ==========================================================
    // VALIDAR MEDIO DE PAGO
    // ==========================================================

    $mediosPermitidos = [
        "EFECTIVO",
        "TRANSFERENCIA",
        "CONSIGNACION",
        "PSE",
        "TARJETA",
        "OTRO"
    ];

    if (!in_array(
        $medio_pago,
        $mediosPermitidos,
        true
    )) {

        throw new Exception(
            "El medio de pago no es válido."
        );
    }


    // ==========================================================
    // VALIDAR ORIGEN
    // ==========================================================

    $origenesPermitidos = [
        "MANUAL",
        "BANCO",
        "PASARELA"
    ];

    if (!in_array(
        $origen_pago,
        $origenesPermitidos,
        true
    )) {

        throw new Exception(
            "El origen del pago no es válido."
        );
    }


    // ==========================================================
    // VERIFICAR QUE EL PAGO EXISTA
    // ==========================================================

    $sqlPago = "
        SELECT
            id_pago
        FROM pagos
        WHERE id_pago = :id_pago
          AND estado = 'REGISTRADO'
        LIMIT 1
    ";

    $stmtPago = $conexion->prepare($sqlPago);

    $stmtPago->execute([
        ":id_pago" => $id_pago
    ]);

    $pagoExistente = $stmtPago->fetch(
        PDO::FETCH_ASSOC
    );

    if (!$pagoExistente) {

        throw new Exception(
            "El pago no existe o está anulado."
        );
    }


    // ==========================================================
    // VERIFICAR QUE LA UNIDAD EXISTA
    // ==========================================================

    $sqlUnidad = "
        SELECT
            id_unidad
        FROM unidades
        WHERE id_unidad = :id_unidad
          AND activo = 1
        LIMIT 1
    ";

    $stmtUnidad = $conexion->prepare(
        $sqlUnidad
    );

    $stmtUnidad->execute([
        ":id_unidad" => $id_unidad
    ]);

    $unidad = $stmtUnidad->fetch(
        PDO::FETCH_ASSOC
    );

    if (!$unidad) {

        throw new Exception(
            "La unidad seleccionada no existe o está inactiva."
        );
    }


    // ==========================================================
    // VALIDAR PAGO DUPLICADO
    //
    // Regla:
    // misma unidad + mismo mes + misma referencia
    //
    // EXCLUIMOS el pago que estamos editando.
    // ==========================================================

    if ($referencia !== "") {

        $sqlDuplicado = "
            SELECT
                id_pago
            FROM pagos
            WHERE id_unidad = :id_unidad
              AND id_pago <> :id_pago
              AND estado = 'REGISTRADO'
              AND referencia = :referencia
              AND YEAR(fecha_pago) = YEAR(:fecha_pago)
              AND MONTH(fecha_pago) = MONTH(:fecha_pago)
            LIMIT 1
        ";

        $stmtDuplicado = $conexion->prepare(
            $sqlDuplicado
        );

        $stmtDuplicado->execute([
            ":id_unidad"   => $id_unidad,
            ":id_pago"     => $id_pago,
            ":referencia"  => $referencia,
            ":fecha_pago"  => $fecha_pago
        ]);

        $duplicado = $stmtDuplicado->fetch(
            PDO::FETCH_ASSOC
        );

        if ($duplicado) {

            throw new Exception(
                "Ya existe otro pago registrado para esta unidad, mes y referencia."
            );
        }
    }


    // ==========================================================
    // ACTUALIZAR PAGO
    // ==========================================================

    $sql = "
        UPDATE pagos
        SET
            id_unidad = :id_unidad,
            fecha_pago = :fecha_pago,
            valor = :valor,
            medio_pago = :medio_pago,
            origen_pago = :origen_pago,
            referencia = :referencia,
            observaciones = :observaciones
        WHERE id_pago = :id_pago
          AND estado = 'REGISTRADO'
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":id_unidad"     => $id_unidad,
        ":fecha_pago"    => $fecha_pago,
        ":valor"         => $valor,
        ":medio_pago"    => $medio_pago,
        ":origen_pago"   => $origen_pago,
        ":referencia"    =>
            $referencia !== ""
                ? $referencia
                : null,
        ":observaciones" =>
            $observaciones !== ""
                ? $observaciones
                : null,
        ":id_pago"       => $id_pago
    ]);


    // ==========================================================
    // REDIRECCIÓN
    // ==========================================================

    header(
        "Location: " .
        BASE_URL .
        "pagos.php?tipo=success&mensaje=" .
        urlencode(
            "Pago actualizado correctamente."
        )
    );

    exit;


} catch (Exception $e) {

    header(
        "Location: " .
        BASE_URL .
        "pagos.php?tipo=error&mensaje=" .
        urlencode(
            $e->getMessage()
        )
    );

    exit;
}