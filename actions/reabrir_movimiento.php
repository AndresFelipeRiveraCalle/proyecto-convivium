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
            'estado_conciliacion' => 'RECHAZADO',
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


if ($idExtracto <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/conciliacion.php?" .
        http_build_query([
            'estado_conciliacion' => 'RECHAZADO',
            'tipo'  => 'warning',
            'texto' => 'Debe seleccionar un movimiento válido.'
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
    // BLOQUEAR MOVIMIENTO
    // ======================================================

    $sqlExtracto = "
        SELECT
            id_extracto,
            estado_conciliacion,
            observaciones

        FROM extractos_bancarios

        WHERE id_extracto = :id_extracto

        FOR UPDATE
    ";

    $stmtExtracto =
        $conexion->prepare(
            $sqlExtracto
        );

    $stmtExtracto->execute([
        ':id_extracto' => $idExtracto
    ]);

    $extracto =
        $stmtExtracto->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$extracto) {

        throw new Exception(
            'El movimiento bancario no existe.'
        );
    }


    if (
        $extracto['estado_conciliacion']
        !== 'RECHAZADO'
    ) {

        throw new Exception(
            'Solo se pueden reabrir movimientos rechazados.'
        );
    }


    // ======================================================
    // VALIDAR QUE NO ESTÉ ASOCIADO A UN PAGO
    // ======================================================

    $sqlPago = "
        SELECT
            id_pago

        FROM pagos

        WHERE id_extracto = :id_extracto

        LIMIT 1
    ";

    $stmtPago =
        $conexion->prepare(
            $sqlPago
        );

    $stmtPago->execute([
        ':id_extracto' => $idExtracto
    ]);


    if ($stmtPago->fetch(PDO::FETCH_ASSOC)) {

        throw new Exception(
            'No se puede reabrir un movimiento asociado a un pago.'
        );
    }


    // ======================================================
    // REABRIR MOVIMIENTO
    // ======================================================

    $observacion =
        'Movimiento reabierto para nueva conciliación.';


    $sqlActualizar = "
        UPDATE extractos_bancarios

        SET
            estado_conciliacion = 'PENDIENTE',

            observaciones = CASE

                WHEN observaciones IS NULL
                     OR TRIM(observaciones) = ''

                THEN :obs_nueva

                ELSE CONCAT(
                    observaciones,
                    ' | ',
                    :obs_agregada
                )

            END

        WHERE id_extracto = :id_extracto
    ";

    $stmtActualizar =
        $conexion->prepare(
            $sqlActualizar
        );

    $stmtActualizar->execute([
        ':obs_nueva'    => $observacion,
        ':obs_agregada' => $observacion,
        ':id_extracto'  => $idExtracto
    ]);


    if ($stmtActualizar->rowCount() !== 1) {

        throw new Exception(
            'No fue posible reabrir el movimiento.'
        );
    }


    $conexion->commit();


    header(
        "Location: " .
        BASE_URL .
        "configuracion/conciliacion.php?" .
        http_build_query([
            'tipo'        => 'success',
            'texto'       => 'Movimiento #' .
                             $idExtracto .
                             ' reabierto correctamente.',
            'reabierto'   => 1,
            'id_extracto' => $idExtracto
        ])
    );

    exit;


} catch (Throwable $e) {

    if (
        $conexion->inTransaction()
    ) {

        $conexion->rollBack();
    }


    header(
        "Location: " .
        BASE_URL .
        "configuracion/conciliacion.php?" .
        http_build_query([
            'estado_conciliacion' => 'RECHAZADO',
            'tipo'  => 'error',
            'texto' => $e->getMessage()
        ])
    );

    exit;
}
