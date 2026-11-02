<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/auth/requerir_sesion.php";
require_once ROOT_PATH . "/config/conexion.php";


function redirigirReaperturaDiferencia(
    string $tipo,
    string $texto,
    string $estado = 'CON_DIFERENCIA'
): void {
    header(
        "Location: " . BASE_URL .
        "configuracion/conciliacion.php?" .
        http_build_query([
            'estado_conciliacion' => $estado,
            'tipo' => $tipo,
            'texto' => $texto
        ])
    );
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigirReaperturaDiferencia(
        'warning',
        'Solicitud no válida.'
    );
}


$idExtracto = (int)($_POST['id_extracto'] ?? 0);

if ($idExtracto <= 0) {
    redirigirReaperturaDiferencia(
        'warning',
        'Debe seleccionar un movimiento bancario válido.'
    );
}


try {
    $conexion->beginTransaction();

    // BLOQUEAR Y VALIDAR MOVIMIENTO

    $sqlExtracto = "
        SELECT
            id_extracto,
            tipo_movimiento,
            estado_conciliacion,
            observaciones
        FROM extractos_bancarios
        WHERE id_extracto = :id_extracto
        LIMIT 1
        FOR UPDATE
    ";

    $stmtExtracto = $conexion->prepare($sqlExtracto);
    $stmtExtracto->execute([
        ':id_extracto' => $idExtracto
    ]);

    $extracto = $stmtExtracto->fetch(PDO::FETCH_ASSOC);

    if (!$extracto) {
        throw new Exception('El movimiento bancario no existe.');
    }

    if ($extracto['tipo_movimiento'] !== 'INGRESO') {
        throw new Exception(
            'Solo se pueden reabrir diferencias de movimientos de ingreso.'
        );
    }

    if ($extracto['estado_conciliacion'] !== 'CON_DIFERENCIA') {
        throw new Exception(
            'Solo se pueden reabrir movimientos marcados CON_DIFERENCIA.'
        );
    }


    // BLOQUEAR EL PAGO VINCULADO

    $sqlPagos = "
        SELECT
            id_pago,
            id_extracto,
            estado,
            estado_conciliacion
        FROM pagos
        WHERE id_extracto = :id_extracto
        FOR UPDATE
    ";

    $stmtPagos = $conexion->prepare($sqlPagos);
    $stmtPagos->execute([
        ':id_extracto' => $idExtracto
    ]);

    $pagosVinculados = $stmtPagos->fetchAll(PDO::FETCH_ASSOC);

    if (count($pagosVinculados) !== 1) {
        throw new Exception(
            'Debe existir exactamente un pago vinculado para reabrir esta diferencia.'
        );
    }

    $pago = $pagosVinculados[0];

    if (
        $pago['estado'] !== 'REGISTRADO'
        || $pago['estado_conciliacion'] !== 'CON_DIFERENCIA'
        || (int)$pago['id_extracto'] !== $idExtracto
    ) {
        throw new Exception(
            'El pago asociado ya no está en estado CON_DIFERENCIA.'
        );
    }

    $idPago = (int)$pago['id_pago'];


    // DESVINCULAR Y DEVOLVER EL PAGO A PENDIENTE

    $sqlActualizarPago = "
        UPDATE pagos
        SET
            id_extracto = NULL,
            estado_conciliacion = 'PENDIENTE',
            fecha_conciliacion = NULL
        WHERE
            id_pago = :id_pago
            AND id_extracto = :id_extracto
            AND estado = 'REGISTRADO'
            AND estado_conciliacion = 'CON_DIFERENCIA'
    ";

    $stmtActualizarPago = $conexion->prepare($sqlActualizarPago);
    $stmtActualizarPago->execute([
        ':id_pago' => $idPago,
        ':id_extracto' => $idExtracto
    ]);

    if ($stmtActualizarPago->rowCount() !== 1) {
        throw new Exception(
            'No fue posible devolver el pago a estado pendiente.'
        );
    }


    // REABRIR EL MOVIMIENTO Y CONSERVAR SU HISTORIAL

    $observacion =
        'Diferencia reabierta; pago #' . $idPago .
        ' desvinculado para revisión.';

    $sqlActualizarExtracto = "
        UPDATE extractos_bancarios
        SET
            estado_conciliacion = 'PENDIENTE',
            observaciones = CASE
                WHEN observaciones IS NULL
                     OR TRIM(observaciones) = ''
                    THEN :observacion_nueva

                WHEN CHAR_LENGTH(observaciones)
                     + CHAR_LENGTH(:separador_longitud)
                     + CHAR_LENGTH(:observacion_longitud) <= 255
                    THEN CONCAT(
                        observaciones,
                        :separador_texto,
                        :observacion_texto
                    )

                ELSE observaciones
            END
        WHERE
            id_extracto = :id_extracto
            AND estado_conciliacion = 'CON_DIFERENCIA'
    ";

    $stmtActualizarExtracto = $conexion->prepare($sqlActualizarExtracto);
    $stmtActualizarExtracto->execute([
        ':observacion_nueva' => $observacion,
        ':separador_longitud' => ' | ',
        ':observacion_longitud' => $observacion,
        ':separador_texto' => ' | ',
        ':observacion_texto' => $observacion,
        ':id_extracto' => $idExtracto
    ]);

    if ($stmtActualizarExtracto->rowCount() !== 1) {
        throw new Exception(
            'No fue posible reabrir el movimiento bancario.'
        );
    }


    $conexion->commit();

    redirigirReaperturaDiferencia(
        'success',
        'La diferencia se reabrió. El pago #' . $idPago .
        ' y el movimiento #' . $idExtracto .
        ' volvieron a estado pendiente.',
        'PENDIENTE'
    );

} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    redirigirReaperturaDiferencia(
        'error',
        $e->getMessage()
    );
}
