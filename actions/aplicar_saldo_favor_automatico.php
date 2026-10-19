<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// REDIRECCIÓN
// ==========================================================

function redireccionarSaldo($idSaldoFavor, $tipo, $texto)
{
    header(
        "Location: " .
        BASE_URL .
        "configuracion/aplicar_saldo_favor.php?" .
        http_build_query([
            'id_saldo_favor' => $idSaldoFavor,
            'tipo'           => $tipo,
            'texto'          => $texto
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
// ID SALDO FAVOR
// ==========================================================

$idSaldoFavor =
    isset($_POST['id_saldo_favor'])
        ? (int)$_POST['id_saldo_favor']
        : 0;


if ($idSaldoFavor <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/cartera.php?" .
        http_build_query([
            'tipo'  => 'warning',
            'texto' => 'Debe seleccionar un saldo a favor válido.'
        ])
    );

    exit;
}


try {

    $conexion->beginTransaction();


    // ======================================================
    // BLOQUEAR SALDO A FAVOR
    // ======================================================

    $sqlSaldo = "
        SELECT
            id_saldo_favor,
            id_unidad,
            id_pago,
            valor_original,
            valor_utilizado,
            saldo_disponible,
            estado
        FROM saldo_favor
        WHERE id_saldo_favor = :id_saldo_favor
        LIMIT 1
        FOR UPDATE
    ";

    $stmtSaldo = $conexion->prepare($sqlSaldo);

    $stmtSaldo->execute([
        ':id_saldo_favor' => $idSaldoFavor
    ]);

    $saldoFavor = $stmtSaldo->fetch(PDO::FETCH_ASSOC);

    if (!$saldoFavor) {
        throw new Exception(
            'El saldo a favor seleccionado no existe.'
        );
    }

    if (
        $saldoFavor['estado'] !== 'DISPONIBLE' ||
        (float)$saldoFavor['saldo_disponible'] <= 0
    ) {
        throw new Exception(
            'El saldo a favor seleccionado no tiene valor disponible.'
        );
    }


    $disponible =
        round(
            (float)$saldoFavor['saldo_disponible'],
            2
        );


    // ======================================================
    // CARTERA PENDIENTE
    // ======================================================

    $sqlCartera = "
        SELECT
            id_cartera,
            id_factura,
            saldo,
            fecha_vencimiento,
            periodo
        FROM cartera
        WHERE
            id_unidad = :id_unidad
            AND estado = 'PENDIENTE'
            AND saldo > 0
        ORDER BY
            fecha_vencimiento ASC,
            periodo ASC,
            id_cartera ASC
        FOR UPDATE
    ";

    $stmtCartera = $conexion->prepare($sqlCartera);

    $stmtCartera->execute([
        ':id_unidad' => (int)$saldoFavor['id_unidad']
    ]);

    $obligaciones =
        $stmtCartera->fetchAll(PDO::FETCH_ASSOC);


    if (empty($obligaciones)) {
        throw new Exception(
            'La unidad no tiene obligaciones pendientes para aplicar el saldo a favor.'
        );
    }


    // ======================================================
    // PREPARAR INSERT APLICACIÓN
    // ======================================================

    $sqlInsert = "
        INSERT INTO aplicaciones_saldo_favor
        (
            id_saldo_favor,
            id_cartera,
            valor_aplicado,
            fecha_aplicacion,
            tipo_aplicacion,
            observaciones
        )
        VALUES
        (
            :id_saldo_favor,
            :id_cartera,
            :valor_aplicado,
            NOW(),
            'AUTOMATICA',
            :observaciones
        )
    ";

    $stmtInsert = $conexion->prepare($sqlInsert);


    // ======================================================
    // PREPARAR UPDATE CARTERA
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
            AND estado = 'PENDIENTE'
    ";

    $stmtUpdateCartera =
        $conexion->prepare($sqlUpdateCartera);


    // ======================================================
    // APLICAR
    // ======================================================

    $totalAplicado = 0;
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


        $stmtInsert->execute([

            ':id_saldo_favor'
                => $idSaldoFavor,

            ':id_cartera'
                => (int)$obligacion['id_cartera'],

            ':valor_aplicado'
                => $valorAplicar,

            ':observaciones'
                => 'Aplicación automática de saldo a favor por antigüedad de vencimiento.'
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

        $totalAplicado += $valorAplicar;
        $cantidadAplicaciones++;


        if (!empty($obligacion['id_factura'])) {

            $facturasAfectadas[
                (int)$obligacion['id_factura']
            ] = true;
        }
    }


    // ======================================================
    // ACTUALIZAR SALDO A FAVOR
    // ======================================================

    $nuevoUtilizado =
        round(
            (float)$saldoFavor['valor_utilizado'] +
            $totalAplicado,
            2
        );

    $nuevoDisponible =
        round(
            (float)$saldoFavor['saldo_disponible'] -
            $totalAplicado,
            2
        );

    $nuevoEstado =
        $nuevoDisponible <= 0.009
            ? 'UTILIZADO'
            : 'DISPONIBLE';


    $sqlUpdateSaldo = "
        UPDATE saldo_favor
        SET
            valor_utilizado = :valor_utilizado,
            saldo_disponible = :saldo_disponible,
            estado = :estado,
            fecha_ultimo_uso = NOW()
        WHERE id_saldo_favor = :id_saldo_favor
    ";

    $stmtUpdateSaldo =
        $conexion->prepare($sqlUpdateSaldo);

    $stmtUpdateSaldo->execute([

        ':valor_utilizado'
            => $nuevoUtilizado,

        ':saldo_disponible'
            => max($nuevoDisponible, 0),

        ':estado'
            => $nuevoEstado,

        ':id_saldo_favor'
            => $idSaldoFavor
    ]);


    // ======================================================
    // ACTUALIZAR ESTADO FACTURAS
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


    $conexion->commit();


    redireccionarSaldo(
        $idSaldoFavor,
        'success',
        'Saldo a favor aplicado automáticamente. ' .
        'Valor aplicado: $' .
        number_format(
            $totalAplicado,
            2,
            ',',
            '.'
        ) .
        '. Aplicaciones creadas: ' .
        $cantidadAplicaciones .
        '. Saldo disponible restante: $' .
        number_format(
            max($nuevoDisponible, 0),
            2,
            ',',
            '.'
        ) .
        '.'
    );


} catch (Throwable $e) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    redireccionarSaldo(
        $idSaldoFavor,
        'error',
        $e->getMessage()
    );
}
