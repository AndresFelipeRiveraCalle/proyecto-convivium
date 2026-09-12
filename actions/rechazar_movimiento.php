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

$observacion =
    trim(
        $_POST['observacion'] ?? ''
    );


if ($idExtracto <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/conciliacion.php?" .
        http_build_query([
            'tipo'  => 'warning',
            'texto' => 'Debe seleccionar un movimiento bancario válido.'
        ])
    );

    exit;
}


// ==========================================================
// TRANSACCIÓN
// ==========================================================

try {

    $conexion->beginTransaction();


    $sqlExtracto = "
        SELECT
            id_extracto,
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


    if ($extracto['estado_conciliacion'] !== 'PENDIENTE') {

        throw new Exception(
            'El movimiento bancario ya fue procesado.'
        );
    }


    // ======================================================
    // VERIFICAR QUE NO ESTÉ ASOCIADO A UN PAGO
    // ======================================================

    $sqlPago = "
        SELECT
            id_pago

        FROM pagos

        WHERE id_extracto = :id_extracto

        LIMIT 1
    ";

    $stmtPago =
        $conexion->prepare($sqlPago);

    $stmtPago->execute([
        ':id_extracto' => $idExtracto
    ]);


    if ($stmtPago->fetch(PDO::FETCH_ASSOC)) {

        throw new Exception(
            'No se puede rechazar un movimiento que ya está asociado a un pago.'
        );
    }


    if ($observacion === '') {

        $observacion =
            'Movimiento descartado para conciliación de pagos.';
    }


    // ======================================================
    // ACTUALIZAR
    // ======================================================

    $sqlActualizar = "
        UPDATE extractos_bancarios

        SET
            estado_conciliacion = 'RECHAZADO',

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

    $stmtActualizar =
        $conexion->prepare($sqlActualizar);

    $stmtActualizar->execute([
        ':observacion_nueva'    => $observacion,
        ':observacion_agregada' => $observacion,
        ':id_extracto'          => $idExtracto
    ]);


    if ($stmtActualizar->rowCount() !== 1) {

        throw new Exception(
            'No fue posible rechazar el movimiento bancario.'
        );
    }


    $conexion->commit();


    header(
        "Location: " .
        BASE_URL .
        "configuracion/conciliacion.php?" .
        http_build_query([
            'tipo'        => 'success',
            'texto'       => 'Movimiento bancario #' .
                             $idExtracto .
                             ' marcado como rechazado.',
            'rechazado'   => 1,
            'id_extracto' => $idExtracto
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
