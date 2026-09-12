<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


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


try {

    $conexion->beginTransaction();


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
        throw new Exception('El movimiento bancario no existe.');
    }

    if ($extracto['tipo_movimiento'] !== 'INGRESO') {
        throw new Exception('Solo se pueden relacionar movimientos de tipo INGRESO.');
    }

    if ($extracto['estado_conciliacion'] !== 'PENDIENTE') {
        throw new Exception('El movimiento bancario ya fue procesado.');
    }


    $sqlPago = "
        SELECT
            id_pago,
            id_extracto,
            fecha_pago,
            valor,
            estado,
            estado_conciliacion

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
        throw new Exception('El pago seleccionado no existe.');
    }

    if ($pago['estado'] !== 'REGISTRADO') {
        throw new Exception('El pago seleccionado no está disponible.');
    }

    if ($pago['estado_conciliacion'] !== 'PENDIENTE') {
        throw new Exception('El pago ya fue procesado.');
    }

    if (!empty($pago['id_extracto'])) {
        throw new Exception('El pago ya tiene un movimiento bancario asociado.');
    }


    $dias =
        abs(
            (int)(
                new DateTime($extracto['fecha_movimiento'])
            )
            ->diff(
                new DateTime($pago['fecha_pago'])
            )
            ->format('%r%a')
        );

    if ($dias > 3) {
        throw new Exception('La diferencia de fechas supera los 3 días.');
    }


    $diferencia =
        abs(
            (float)$extracto['valor']
            -
            (float)$pago['valor']
        );

    $tolerancia =
        max(
            1000,
            (float)$pago['valor'] * 0.01
        );


    if ($diferencia <= 0.01) {
        throw new Exception('Los valores coinciden. Este caso debe conciliarse normalmente.');
    }

    if ($diferencia > $tolerancia) {
        throw new Exception(
            'La diferencia supera la tolerancia permitida de ' .
            number_format($tolerancia, 2, ',', '.')
        );
    }


    $sqlUso = "
        SELECT id_pago
        FROM pagos
        WHERE id_extracto = :id_extracto
        LIMIT 1
    ";

    $stmtUso =
        $conexion->prepare($sqlUso);

    $stmtUso->execute([
        ':id_extracto' => $idExtracto
    ]);

    if ($stmtUso->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception('Este movimiento ya está asociado a otro pago.');
    }


    $sqlActualizarPago = "
        UPDATE pagos
        SET
            id_extracto = :id_extracto,
            estado_conciliacion = 'CON_DIFERENCIA',
            fecha_conciliacion = NOW()
        WHERE id_pago = :id_pago
    ";

    $stmtActualizarPago =
        $conexion->prepare($sqlActualizarPago);

    $stmtActualizarPago->execute([
        ':id_extracto' => $idExtracto,
        ':id_pago'     => $idPago
    ]);


    $observacion =
        'Relacionado con pago #' .
        $idPago .
        ' con diferencia de $' .
        number_format($diferencia, 2, ',', '.');


    $sqlActualizarExtracto = "
        UPDATE extractos_bancarios
        SET
            estado_conciliacion = 'CON_DIFERENCIA',
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

    $stmtActualizarExtracto =
        $conexion->prepare($sqlActualizarExtracto);

    $stmtActualizarExtracto->execute([
        ':obs_nueva'    => $observacion,
        ':obs_agregada' => $observacion,
        ':id_extracto'  => $idExtracto
    ]);


    $conexion->commit();


    header(
        "Location: " .
        BASE_URL .
        "configuracion/conciliacion.php?" .
        http_build_query([
            'estado_conciliacion' => 'CON_DIFERENCIA',
            'tipo'                 => 'success',
            'texto'                => 'Movimiento #' . $idExtracto .
                                      ' relacionado con el pago #' . $idPago .
                                      ' con una diferencia de $' .
                                      number_format($diferencia, 2, ',', '.')
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
