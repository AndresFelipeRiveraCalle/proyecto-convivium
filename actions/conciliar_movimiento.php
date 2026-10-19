<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// VALIDAR MÉTODO
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/conciliacion.php?" .
        http_build_query([
            'tipo'  => 'warning',
            'texto' => 'Solicitud no válida.'
        ])
    );

    exit;
}


// ==========================================================
// DATOS
// ==========================================================

$idExtracto =
    isset($_POST['id_extracto'])
        ? (int)$_POST['id_extracto']
        : 0;

$idPago =
    isset($_POST['id_pago'])
        ? (int)$_POST['id_pago']
        : 0;


if ($idExtracto <= 0 || $idPago <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/conciliacion.php?" .
        http_build_query([
            'tipo'  => 'warning',
            'texto' => 'Debe seleccionar un movimiento y un pago válidos.'
        ])
    );

    exit;
}


// ==========================================================
// TRANSACCIÓN
// ==========================================================

try {

    $conexion->beginTransaction();


    // ======================================================
    // BLOQUEAR MOVIMIENTO BANCARIO
    // ======================================================

    $sqlExtracto = "
        SELECT
            id_extracto,
            fecha_movimiento,
            valor,
            tipo_movimiento,
            estado_conciliacion

        FROM extractos_bancarios

        WHERE id_extracto = :id_extracto

        FOR UPDATE
    ";

    $stmtExtracto =
        $conexion->prepare($sqlExtracto);

    $stmtExtracto->execute([
        ':id_extracto' => $idExtracto
    ]);

    $extracto =
        $stmtExtracto->fetch(PDO::FETCH_ASSOC);


    if (!$extracto) {

        throw new Exception(
            'El movimiento bancario no existe.'
        );
    }


    if ($extracto['tipo_movimiento'] !== 'INGRESO') {

        throw new Exception(
            'Solo se pueden conciliar movimientos de tipo INGRESO.'
        );
    }


    if ($extracto['estado_conciliacion'] !== 'PENDIENTE') {

        throw new Exception(
            'El movimiento bancario ya fue procesado.'
        );
    }


    // ======================================================
    // BLOQUEAR PAGO
    // ======================================================

    $sqlPago = "
        SELECT
            id_pago,
            id_unidad,
            id_extracto,
            fecha_pago,
            valor,
            estado_conciliacion,
            estado

        FROM pagos

        WHERE id_pago = :id_pago

        FOR UPDATE
    ";

    $stmtPago =
        $conexion->prepare($sqlPago);

    $stmtPago->execute([
        ':id_pago' => $idPago
    ]);

    $pago =
        $stmtPago->fetch(PDO::FETCH_ASSOC);


    if (!$pago) {

        throw new Exception(
            'El pago seleccionado no existe.'
        );
    }


    if ($pago['estado'] !== 'REGISTRADO') {

        throw new Exception(
            'El pago seleccionado no está disponible para conciliación.'
        );
    }


    if ($pago['estado_conciliacion'] !== 'PENDIENTE') {

        throw new Exception(
            'El pago ya fue conciliado o procesado.'
        );
    }


    if (!empty($pago['id_extracto'])) {

        throw new Exception(
            'El pago ya tiene un movimiento bancario asociado.'
        );
    }


    // ======================================================
    // VALIDAR VALOR
    // ======================================================

    $diferenciaValor =
        abs(
            (float)$extracto['valor'] -
            (float)$pago['valor']
        );


    if ($diferenciaValor > 0.01) {

        throw new Exception(
            'El valor del pago no coincide con el movimiento bancario.'
        );
    }


    // ======================================================
    // VALIDAR FECHA ±3 DÍAS
    // ======================================================

    $fechaExtracto =
        new DateTime($extracto['fecha_movimiento']);

    $fechaPago =
        new DateTime($pago['fecha_pago']);

    $diferenciaDias =
        abs(
            (int)$fechaExtracto
                ->diff($fechaPago)
                ->format('%r%a')
        );


    if ($diferenciaDias > 3) {

        throw new Exception(
            'La diferencia entre la fecha bancaria y la fecha del pago supera los 3 días.'
        );
    }


    // ======================================================
    // VALIDAR QUE EL EXTRACTO NO ESTÉ EN OTRO PAGO
    // ======================================================

    $sqlUsoExtracto = "
        SELECT
            id_pago

        FROM pagos

        WHERE id_extracto = :id_extracto

        LIMIT 1
    ";

    $stmtUsoExtracto =
        $conexion->prepare($sqlUsoExtracto);

    $stmtUsoExtracto->execute([
        ':id_extracto' => $idExtracto
    ]);


    if ($stmtUsoExtracto->fetch(PDO::FETCH_ASSOC)) {

        throw new Exception(
            'Este movimiento bancario ya está asociado a otro pago.'
        );
    }


    // ======================================================
    // ACTUALIZAR PAGO
    // ======================================================

    $sqlActualizarPago = "
        UPDATE pagos

        SET
            id_extracto = :id_extracto,
            estado_conciliacion = 'CONCILIADO',
            fecha_conciliacion = NOW()

        WHERE id_pago = :id_pago
    ";

    $stmtActualizarPago =
        $conexion->prepare($sqlActualizarPago);

    $stmtActualizarPago->execute([
        ':id_extracto' => $idExtracto,
        ':id_pago'     => $idPago
    ]);


    if ($stmtActualizarPago->rowCount() !== 1) {

        throw new Exception(
            'No fue posible actualizar el pago.'
        );
    }


    // ======================================================
    // ACTUALIZAR MOVIMIENTO
    // ======================================================

    $observacionConciliacion =
        'Conciliado con pago #' . $idPago;

    /*
     * Usamos dos parámetros distintos porque PDO/MySQL puede fallar
     * cuando el mismo placeholder nombrado aparece más de una vez.
     */

    $sqlActualizarExtracto = "
        UPDATE extractos_bancarios

        SET
            estado_conciliacion = 'CONCILIADO',

            observaciones = CASE

                WHEN observaciones IS NULL
                     OR TRIM(observaciones) = ''

                THEN :observacion_nueva

                ELSE CONCAT(
                    observaciones,
                    ' | ',
                    :observacion_agregada
                )

            END

        WHERE id_extracto = :id_extracto
    ";

    $stmtActualizarExtracto =
        $conexion->prepare($sqlActualizarExtracto);

    $stmtActualizarExtracto->execute([
        ':observacion_nueva'    => $observacionConciliacion,
        ':observacion_agregada' => $observacionConciliacion,
        ':id_extracto'          => $idExtracto
    ]);


    if ($stmtActualizarExtracto->rowCount() !== 1) {

        throw new Exception(
            'No fue posible actualizar el movimiento bancario.'
        );
    }


    // ======================================================
    // CONFIRMAR
    // ======================================================

    $conexion->commit();


    header(
        "Location: " .
        BASE_URL .
        "configuracion/conciliacion.php?" .
        http_build_query([
            'tipo'        => 'success',
            'texto'       => 'Movimiento #' . $idExtracto .
                             ' conciliado correctamente con el pago #' .
                             $idPago . '.',
            'conciliado'  => 1,
            'id_extracto' => $idExtracto,
            'id_pago'     => $idPago
        ])
    );

    exit;


} catch (Throwable $e) {

    if ($conexion->inTransaction()) {

        $conexion->rollBack();
    }


    header(
        "Location: " .
        BASE_URL .
        "configuracion/conciliacion.php?" .
        http_build_query([
            'tipo'  => 'error',
            'texto' => $e->getMessage()
        ])
    );

    exit;
}
