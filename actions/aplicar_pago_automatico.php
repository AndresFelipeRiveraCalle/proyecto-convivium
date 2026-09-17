<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// REDIRECCIONAR
// ==========================================================

function redireccionarPago($idPago, $tipo, $texto)
{
    header(
        "Location: " .
        BASE_URL .
        "configuracion/aplicar_pago.php?" .
        http_build_query([
            'id_pago' => $idPago,
            'tipo'    => $tipo,
            'texto'   => $texto
        ])
    );

    exit;
}


// ==========================================================
// ACTUALIZAR ESTADO DE FACTURA
// ==========================================================

function actualizarEstadoFactura(PDO $conexion, int $idFactura): void
{
    if ($idFactura <= 0) {
        return;
    }

    $sql = "
        SELECT
            COALESCE(SUM(valor_original), 0) AS total_original,
            COALESCE(SUM(valor_pagado), 0) AS total_pagado,
            COALESCE(SUM(saldo), 0) AS total_saldo
        FROM cartera
        WHERE
            id_factura = :id_factura
            AND estado <> 'ANULADA'
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        ':id_factura' => $idFactura
    ]);

    $resumen = $stmt->fetch(PDO::FETCH_ASSOC);

    $totalOriginal = (float)($resumen['total_original'] ?? 0);
    $totalPagado   = (float)($resumen['total_pagado'] ?? 0);
    $totalSaldo    = (float)($resumen['total_saldo'] ?? 0);

    if ($totalOriginal <= 0) {
        return;
    }

    if ($totalSaldo <= 0.009) {
        $nuevoEstado = 'PAGADA';
    } elseif ($totalPagado > 0) {
        $nuevoEstado = 'PARCIAL';
    } else {
        $nuevoEstado = 'GENERADA';
    }

    $sqlUpdate = "
        UPDATE facturas
        SET estado = :estado
        WHERE
            id_factura = :id_factura
            AND estado <> 'ANULADA'
    ";

    $stmtUpdate = $conexion->prepare($sqlUpdate);

    $stmtUpdate->execute([
        ':estado'     => $nuevoEstado,
        ':id_factura' => $idFactura
    ]);
}



// ==========================================================
// CREAR SALDO A FAVOR
// Convierte el excedente cuando la unidad ya no tiene cartera pendiente.
// ==========================================================

function crearSaldoFavorSiCorresponde(
    PDO $conexion,
    int $idPago,
    int $idUnidad,
    float $disponible
): float {
    $disponible = round($disponible, 2);

    if ($disponible <= 0.009) {
        return 0.00;
    }

    $sqlPendiente = "
        SELECT COUNT(*)
        FROM cartera
        WHERE
            id_unidad = :id_unidad
            AND estado <> 'ANULADA'
            AND saldo > 0.009
    ";

    $stmtPendiente = $conexion->prepare($sqlPendiente);
    $stmtPendiente->execute([
        ':id_unidad' => $idUnidad
    ]);

    if ((int)$stmtPendiente->fetchColumn() > 0) {
        return 0.00;
    }

    $sqlExistente = "
        SELECT
            COALESCE(SUM(valor_original), 0)
        FROM saldo_favor
        WHERE
            id_pago = :id_pago
            AND estado <> 'ANULADO'
    ";

    $stmtExistente = $conexion->prepare($sqlExistente);
    $stmtExistente->execute([
        ':id_pago' => $idPago
    ]);

    $yaConvertido =
        round(
            (float)$stmtExistente->fetchColumn(),
            2
        );

    if ($yaConvertido > 0.009) {
        return 0.00;
    }

    $sqlInsert = "
        INSERT INTO saldo_favor
        (
            id_unidad,
            id_pago,
            valor_original,
            valor_utilizado,
            saldo_disponible,
            estado,
            fecha_generacion,
            observaciones
        )
        VALUES
        (
            :id_unidad,
            :id_pago,
            :valor_original,
            0.00,
            :saldo_disponible,
            'DISPONIBLE',
            NOW(),
            :observaciones
        )
    ";

    $stmtInsert = $conexion->prepare($sqlInsert);
    $stmtInsert->execute([
        ':id_unidad'        => $idUnidad,
        ':id_pago'          => $idPago,
        ':valor_original'   => $disponible,
        ':saldo_disponible' => $disponible,
        ':observaciones'    => 'Saldo generado por excedente del pago.'
    ]);

    return $disponible;
}

// ==========================================================
// VALIDAR MÉTODO
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/cartera.php"
    );

    exit;
}


// ==========================================================
// ID PAGO
// ==========================================================

$idPago =
    isset($_POST['id_pago'])
        ? (int)$_POST['id_pago']
        : 0;


if ($idPago <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/cartera.php?" .
        http_build_query([
            'tipo'  => 'warning',
            'texto' => 'Debe seleccionar un pago válido.'
        ])
    );

    exit;
}


try {

    // ======================================================
    // TRANSACCIÓN
    // ======================================================

    $conexion->beginTransaction();


    // ======================================================
    // BLOQUEAR PAGO
    // ======================================================

    $sqlPago = "
        SELECT
            id_pago,
            id_unidad,
            fecha_pago,
            valor,
            referencia,
            estado,
            estado_conciliacion
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
        throw new Exception(
            'El pago seleccionado no existe.'
        );
    }

    if ($pago['estado'] !== 'REGISTRADO') {
        throw new Exception(
            'El pago no está disponible para aplicación porque su estado es ' .
            $pago['estado'] . '.'
        );
    }


    // ======================================================
    // VALOR YA APLICADO
    // ======================================================

    $sqlAplicado = "
        SELECT
            COALESCE(SUM(valor_aplicado), 0)
        FROM aplicaciones_pagos
        WHERE id_pago = :id_pago
    ";

    $stmtAplicado = $conexion->prepare($sqlAplicado);

    $stmtAplicado->execute([
        ':id_pago' => $idPago
    ]);

    $valorAplicadoActual =
        (float)$stmtAplicado->fetchColumn();


    // ======================================================
    // SALDO YA CONVERTIDO
    // Resta el excedente que ya pasó a saldo a favor.
    // ======================================================

    $sqlSaldoConvertido = "
        SELECT
            COALESCE(SUM(valor_original), 0)
        FROM saldo_favor
        WHERE
            id_pago = :id_pago
            AND estado <> 'ANULADO'
    ";

    $stmtSaldoConvertido =
        $conexion->prepare($sqlSaldoConvertido);

    $stmtSaldoConvertido->execute([
        ':id_pago' => $idPago
    ]);

    $saldoConvertido =
        (float)$stmtSaldoConvertido->fetchColumn();


    $valorPago =
        (float)$pago['valor'];

    $disponible =
        round(
            $valorPago -
            $valorAplicadoActual -
            $saldoConvertido,
            2
        );


    if ($disponible <= 0) {
        throw new Exception(
            'El pago ya se encuentra completamente aplicado.'
        );
    }


    // ======================================================
    // CARTERA PENDIENTE DE LA UNIDAD
    // ======================================================
    //
    // Regla automática:
    // primero la deuda con vencimiento más antiguo.
    //
    // ======================================================

    $sqlCartera = "
        SELECT
            id_cartera,
            id_factura,
            descripcion,
            saldo,
            fecha_vencimiento,
            periodo
        FROM cartera
        WHERE
            id_unidad = :id_unidad
            AND estado <> 'ANULADA'
            AND saldo > 0.009
        ORDER BY
            fecha_vencimiento ASC,
            periodo ASC,
            id_cartera ASC
        FOR UPDATE
    ";

    $stmtCartera = $conexion->prepare($sqlCartera);

    $stmtCartera->execute([
        ':id_unidad' => (int)$pago['id_unidad']
    ]);

    $obligaciones =
        $stmtCartera->fetchAll(PDO::FETCH_ASSOC);


    if (empty($obligaciones)) {
        throw new Exception(
            'La unidad no tiene obligaciones pendientes para aplicar este pago.'
        );
    }


    // ======================================================
    // INSERT APLICACIÓN
    // ======================================================

    $sqlInsertAplicacion = "
        INSERT INTO aplicaciones_pagos
        (
            id_pago,
            id_cartera,
            valor_aplicado,
            fecha_aplicacion,
            tipo_aplicacion,
            observaciones
        )
        VALUES
        (
            :id_pago,
            :id_cartera,
            :valor_aplicado,
            NOW(),
            'AUTOMATICA',
            :observaciones
        )
    ";

    $stmtInsertAplicacion =
        $conexion->prepare($sqlInsertAplicacion);


    // ======================================================
    // ACTUALIZAR CARTERA
    // ======================================================

    $sqlUpdateCartera = "
        UPDATE cartera
        SET
            valor_pagado =
                valor_pagado + :valor_aplicado,

            saldo =
                GREATEST(
                    saldo - :valor_aplicado_saldo,
                    0
                ),

            estado =
                CASE
                    WHEN
                        GREATEST(
                            saldo - :valor_aplicado_estado,
                            0
                        ) <= 0.009
                    THEN 'PAGADA'
                    ELSE 'PENDIENTE'
                END

        WHERE
            id_cartera = :id_cartera
            AND estado <> 'ANULADA'
    ";

    $stmtUpdateCartera =
        $conexion->prepare($sqlUpdateCartera);


    // ======================================================
    // APLICAR
    // ======================================================

    $totalAplicadoAhora = 0;
    $cantidadAplicaciones = 0;
    $facturasAfectadas = [];


    foreach ($obligaciones as $obligacion) {

        if ($disponible <= 0.009) {
            break;
        }

        $saldoObligacion =
            round(
                (float)$obligacion['saldo'],
                2
            );

        if ($saldoObligacion <= 0) {
            continue;
        }

        $valorAplicar =
            round(
                min(
                    $disponible,
                    $saldoObligacion
                ),
                2
            );

        if ($valorAplicar <= 0) {
            continue;
        }


        $stmtInsertAplicacion->execute([

            ':id_pago'
                => $idPago,

            ':id_cartera'
                => (int)$obligacion['id_cartera'],

            ':valor_aplicado'
                => $valorAplicar,

            ':observaciones'
                => 'Aplicación automática por antigüedad de vencimiento.'
        ]);


        $stmtUpdateCartera->execute([

            ':valor_aplicado'
                => $valorAplicar,

            ':valor_aplicado_saldo'
                => $valorAplicar,

            ':valor_aplicado_estado'
                => $valorAplicar,

            ':id_cartera'
                => (int)$obligacion['id_cartera']
        ]);


        if ($stmtUpdateCartera->rowCount() !== 1) {

            throw new Exception(
                'No fue posible actualizar la obligación de cartera #' .
                $obligacion['id_cartera'] .
                '.'
            );
        }


        $disponible =
            round(
                $disponible - $valorAplicar,
                2
            );

        $totalAplicadoAhora +=
            $valorAplicar;

        $cantidadAplicaciones++;


        if (!empty($obligacion['id_factura'])) {

            $facturasAfectadas[
                (int)$obligacion['id_factura']
            ] = true;
        }
    }


    // ======================================================
    // ACTUALIZAR ESTADOS DE FACTURAS
    // ======================================================

    foreach (
        array_keys($facturasAfectadas)
        as $idFactura
    ) {

        actualizarEstadoFactura(
            $conexion,
            (int)$idFactura
        );
    }


    // ======================================================
    // SALDO A FAVOR
    // Convierte el excedente si ya no quedan obligaciones pendientes.
    // ======================================================

    $saldoFavorGenerado =
        crearSaldoFavorSiCorresponde(
            $conexion,
            $idPago,
            (int)$pago['id_unidad'],
            $disponible
        );

    if ($saldoFavorGenerado > 0.009) {
        $disponible = 0.00;
    }


    // ======================================================
    // COMMIT
    // Confirma las aplicaciones y el posible saldo a favor.
    // ======================================================

    $conexion->commit();


    $mensaje =
        'Aplicación automática realizada. ' .
        'Valor aplicado: $' .
        number_format(
            $totalAplicadoAhora,
            2,
            ',',
            '.'
        ) .
        '. Aplicaciones creadas: ' .
        $cantidadAplicaciones .
        '.';

    if ($saldoFavorGenerado > 0.009) {
        $mensaje .=
            ' Saldo a favor generado: $' .
            number_format(
                $saldoFavorGenerado,
                2,
                ',',
                '.'
            ) .
            '.';
    } else {
        $mensaje .=
            ' Saldo disponible del pago: $' .
            number_format(
                $disponible,
                2,
                ',',
                '.'
            ) .
            '.';
    }


    redireccionarPago(
        $idPago,
        'success',
        $mensaje
    );


} catch (Throwable $e) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    redireccionarPago(
        $idPago,
        'error',
        $e->getMessage()
    );
}
