<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/auth/requerir_sesion.php";
require_once ROOT_PATH . "/config/conexion.php";


function redirigirDiferencia(
    string $tipo,
    string $texto
): void {
    header(
        "Location: " . BASE_URL .
        "configuracion/conciliacion.php?" .
        http_build_query([
            'estado_conciliacion' => 'PENDIENTE',
            'tipo' => $tipo,
            'texto' => $texto
        ])
    );
    exit;
}


// VALIDAR MÉTODO

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigirDiferencia(
        'warning',
        'Solicitud no válida.'
    );
}


// DATOS

$idExtracto = (int)($_POST['id_extracto'] ?? 0);
$idPago = (int)($_POST['id_pago'] ?? 0);

if ($idExtracto <= 0 || $idPago <= 0) {
    redirigirDiferencia(
        'warning',
        'Debe seleccionar un movimiento y un pago válidos.'
    );
}


try {
    $conexion->beginTransaction();

    // BLOQUEAR Y VALIDAR MOVIMIENTO

    $sqlExtracto = "
        SELECT
            id_extracto,
            fecha_movimiento,
            valor,
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
            'Solo se pueden marcar diferencias en movimientos de ingreso.'
        );
    }

    if ($extracto['estado_conciliacion'] !== 'PENDIENTE') {
        throw new Exception(
            'El movimiento ya fue procesado.'
        );
    }


    // BLOQUEAR Y VALIDAR PAGO

    $sqlPago = "
        SELECT
            id_pago,
            id_extracto,
            fecha_pago,
            valor,
            estado_conciliacion,
            estado
        FROM pagos
        WHERE id_pago = :id_pago
        LIMIT 1
        FOR UPDATE
    ";

    $stmtPago = $conexion->prepare($sqlPago);
    $stmtPago->execute([
        ':id_pago' => $idPago
    ]);

    $pago = $stmtPago->fetch(PDO::FETCH_ASSOC);

    if (!$pago) {
        throw new Exception('El pago seleccionado no existe.');
    }

    if ($pago['estado'] !== 'REGISTRADO') {
        throw new Exception(
            'El pago no está disponible para revisión.'
        );
    }

    if ($pago['estado_conciliacion'] !== 'PENDIENTE') {
        throw new Exception(
            'El pago ya fue procesado.'
        );
    }

    if (!empty($pago['id_extracto'])) {
        throw new Exception(
            'El pago ya tiene un movimiento bancario asociado.'
        );
    }


    // VALIDAR DIFERENCIA DE VALOR

    $diferenciaValor = abs(
        (float)$extracto['valor'] - (float)$pago['valor']
    );

    if ($diferenciaValor <= 0.01) {
        throw new Exception(
            'Los valores coinciden. Usa la opción Conciliar.'
        );
    }

    $toleranciaValor = max(
        1000,
        (float)$pago['valor'] * 0.01
    );

    if ($diferenciaValor > $toleranciaValor) {
        throw new Exception(
            'La diferencia supera el rango permitido para esta revisión.'
        );
    }


    // VALIDAR DIFERENCIA DE FECHAS

    $fechaExtracto = new DateTime($extracto['fecha_movimiento']);
    $fechaPago = new DateTime($pago['fecha_pago']);

    $diferenciaDias = abs(
        (int)$fechaExtracto
            ->diff($fechaPago)
            ->format('%r%a')
    );

    if ($diferenciaDias > 3) {
        throw new Exception(
            'La diferencia entre las fechas supera los 3 días.'
        );
    }


    // EVITAR ASOCIAR EL MOVIMIENTO A OTRO PAGO

    $sqlUsoExtracto = "
        SELECT id_pago
        FROM pagos
        WHERE id_extracto = :id_extracto
          AND id_pago <> :id_pago
        LIMIT 1
        FOR UPDATE
    ";

    $stmtUsoExtracto = $conexion->prepare($sqlUsoExtracto);
    $stmtUsoExtracto->execute([
        ':id_extracto' => $idExtracto,
        ':id_pago' => $idPago
    ]);

    if ($stmtUsoExtracto->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception(
            'El movimiento ya está asociado a otro pago.'
        );
    }


    // MARCAR EL PAGO CON DIFERENCIA

    $sqlActualizarPago = "
        UPDATE pagos
        SET
            id_extracto = :id_extracto,
            estado_conciliacion = 'CON_DIFERENCIA',
            fecha_conciliacion = NOW()
        WHERE
            id_pago = :id_pago
            AND estado = 'REGISTRADO'
            AND estado_conciliacion = 'PENDIENTE'
            AND id_extracto IS NULL
    ";

    $stmtActualizarPago = $conexion->prepare($sqlActualizarPago);
    $stmtActualizarPago->execute([
        ':id_extracto' => $idExtracto,
        ':id_pago' => $idPago
    ]);

    if ($stmtActualizarPago->rowCount() !== 1) {
        throw new Exception(
            'No fue posible actualizar el pago.'
        );
    }


    // MARCAR EL MOVIMIENTO CON DIFERENCIA

    $observacion = 'Vinculado con pago #' . $idPago .
        ' como CON_DIFERENCIA. Diferencia: $' .
        number_format($diferenciaValor, 2, ',', '.');

    $sqlActualizarExtracto = "
        UPDATE extractos_bancarios
        SET
            estado_conciliacion = 'CON_DIFERENCIA',
            observaciones = CASE
                WHEN observaciones IS NULL
                     OR TRIM(observaciones) = ''
                    THEN :observacion_nueva

                WHEN CHAR_LENGTH(observaciones)
                     + CHAR_LENGTH(:separador_longitud)
                     + CHAR_LENGTH(:observacion_agregada_longitud)<= 255
                    THEN CONCAT(
                        observaciones,
                        :separador_texto,
                        :observacion_agregada_texto
                    )

                ELSE observaciones
            END
        WHERE
            id_extracto = :id_extracto
            AND tipo_movimiento = 'INGRESO'
            AND estado_conciliacion = 'PENDIENTE'
    ";

    $stmtActualizarExtracto = $conexion->prepare(
        $sqlActualizarExtracto
    );

    $stmtActualizarExtracto->execute([
        ':observacion_nueva' => $observacion,
        ':separador_longitud' => ' | ',
        ':separador_texto' => ' | ',
        ':observacion_agregada_longitud' => $observacion,
        ':observacion_agregada_texto' => $observacion,
        ':id_extracto' => $idExtracto
    ]);

    if ($stmtActualizarExtracto->rowCount() !== 1) {
        throw new Exception(
            'No fue posible actualizar el movimiento bancario.'
        );
    }


    $conexion->commit();

    redirigirDiferencia(
        'success',
        'El movimiento #' . $idExtracto .
        ' quedó vinculado al pago #' . $idPago .
        ' con estado CON_DIFERENCIA.'
    );

} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    redirigirDiferencia(
        'error',
        $e->getMessage()
    );
}