<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/auth/requerir_sesion.php";
require_once ROOT_PATH . "/config/conexion.php";

function redireccionarSaldo($idSaldoFavor, $tipo, $texto): void
{
    header(
        "Location: " .
        BASE_URL .
        "configuracion/aplicar_saldo_favor.php?" .
        http_build_query([
            'id_saldo_favor' => (int)$idSaldoFavor,
            'tipo'           => $tipo,
            'texto'          => $texto
        ])
    );

    exit;
}

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
        WHERE id_factura = :id_factura
          AND estado <> 'ANULADA'
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([':id_factura' => $idFactura]);
    $resumen = $stmt->fetch(PDO::FETCH_ASSOC);

    $totalOriginal = (float)($resumen['total_original'] ?? 0);
    $totalPagado = (float)($resumen['total_pagado'] ?? 0);
    $totalSaldo = (float)($resumen['total_saldo'] ?? 0);

    if ($totalOriginal <= 0) {
        return;
    }

    if ($totalSaldo <= 0.009) {
        $nuevoEstado = 'PAGADA';
    } elseif ($totalPagado > 0.009) {
        $nuevoEstado = 'PARCIAL';
    } else {
        $nuevoEstado = 'GENERADA';
    }

    $sqlUpdate = "
        UPDATE facturas
        SET estado = :estado
        WHERE id_factura = :id_factura
          AND estado <> 'ANULADA'
    ";

    $stmtUpdate = $conexion->prepare($sqlUpdate);
    $stmtUpdate->execute([
        ':estado' => $nuevoEstado,
        ':id_factura' => $idFactura
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "configuracion/cartera.php");
    exit;
}

$idSaldoFavor = isset($_POST['id_saldo_favor'])
    ? (int)$_POST['id_saldo_favor']
    : 0;

if ($idSaldoFavor <= 0) {
    redireccionarSaldo(
        $idSaldoFavor,
        'warning',
        'Debe seleccionar un saldo a favor válido.'
    );
}

try {
    $conexion->beginTransaction();

    // Bloquear el saldo a favor.
    $sqlSaldo = "
        SELECT
            id_saldo_favor,
            id_unidad,
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
        throw new Exception('El saldo a favor seleccionado no existe.');
    }

    $valorOriginal = round((float)$saldoFavor['valor_original'], 2);
    $valorUtilizado = round((float)$saldoFavor['valor_utilizado'], 2);
    $disponible = round((float)$saldoFavor['saldo_disponible'], 2);

    if (
        abs($valorOriginal - $valorUtilizado - $disponible) > 0.009
    ) {
        throw new Exception(
            'El saldo a favor tiene valores inconsistentes. ' .
            'No se aplicó ningún valor.'
        );
    }

    if (
        $saldoFavor['estado'] !== 'DISPONIBLE' ||
        $disponible <= 0.009
    ) {
        throw new Exception(
            'El saldo a favor seleccionado no tiene valor disponible.'
        );
    }

    // Bloquear obligaciones pendientes y procesarlas por vencimiento.
    $sqlCartera = "
        SELECT
            c.id_cartera,
            c.id_factura,
            c.id_detalle,
            c.valor_original,
            c.valor_pagado,
            c.saldo,
            c.estado,
            c.fecha_vencimiento,
            c.periodo,
            fd.id_interes
        FROM cartera c
        LEFT JOIN facturas_detalle fd
            ON fd.id_detalle = c.id_detalle
        WHERE c.id_unidad = :id_unidad
          AND c.estado = 'PENDIENTE'
          AND c.saldo > 0
        ORDER BY
            c.fecha_vencimiento ASC,
            c.periodo ASC,
            c.id_cartera ASC
        FOR UPDATE
    ";

    $stmtCartera = $conexion->prepare($sqlCartera);
    $stmtCartera->execute([
        ':id_unidad' => (int)$saldoFavor['id_unidad']
    ]);

    $obligaciones = $stmtCartera->fetchAll(PDO::FETCH_ASSOC);

    if (!$obligaciones) {
        throw new Exception(
            'La unidad no tiene obligaciones pendientes para aplicar el saldo a favor.'
        );
    }

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

    $totalAplicado = 0.00;
    $cantidadAplicaciones = 0;
    $facturasAfectadas = [];

    foreach ($obligaciones as $obligacion) {
        if ($disponible <= 0.009) {
            break;
        }

        $idCartera = (int)$obligacion['id_cartera'];
        $valorCartera = round((float)$obligacion['valor_original'], 2);
        $pagadoCartera = round((float)$obligacion['valor_pagado'], 2);
        $saldoCartera = round((float)$obligacion['saldo'], 2);

        if (
            $saldoCartera <= 0 ||
            abs($valorCartera - $pagadoCartera - $saldoCartera) > 0.009
        ) {
            throw new Exception(
                'La obligación de cartera #' . $idCartera .
                ' tiene valores inconsistentes. No se aplicó ningún valor.'
            );
        }

        $valorAplicar = round(min($disponible, $saldoCartera), 2);

        if ($valorAplicar <= 0.009) {
            continue;
        }

        $interes = null;
        $idInteres = !empty($obligacion['id_interes'])
            ? (int)$obligacion['id_interes']
            : 0;

        // Validar y bloquear el interés antes de modificar la cartera.
        if ($idInteres > 0) {
            $sqlInteres = "
                SELECT
                    id_interes,
                    valor_interes,
                    valor_pagado,
                    saldo,
                    estado
                FROM intereses_cartera
                WHERE id_interes = :id_interes
                LIMIT 1
                FOR UPDATE
            ";

            $stmtInteres = $conexion->prepare($sqlInteres);
            $stmtInteres->execute([
                ':id_interes' => $idInteres
            ]);

            $interes = $stmtInteres->fetch(PDO::FETCH_ASSOC);

            if (!$interes) {
                throw new Exception(
                    'La obligación de cartera #' . $idCartera .
                    ' tiene un interés asociado que no existe.'
                );
            }

            $valorInteres = round((float)$interes['valor_interes'], 2);
            $pagadoInteres = round((float)$interes['valor_pagado'], 2);
            $saldoInteres = round((float)$interes['saldo'], 2);

            if (
                abs($valorInteres - $pagadoInteres - $saldoInteres) > 0.009 ||
                abs($valorInteres - $valorCartera) > 0.009 ||
                abs($pagadoInteres - $pagadoCartera) > 0.009 ||
                abs($saldoInteres - $saldoCartera) > 0.009
            ) {
                throw new Exception(
                    'La cartera y el interés #' . $idInteres .
                    ' tienen saldos inconsistentes. No se aplicó ningún valor.'
                );
            }
        }

        $nuevoSaldoCartera = round($saldoCartera - $valorAplicar, 2);
        if ($nuevoSaldoCartera <= 0.009) {
            $nuevoSaldoCartera = 0.00;
        }

        $nuevoPagadoCartera = round($pagadoCartera + $valorAplicar, 2);
        $nuevoEstadoCartera = $nuevoSaldoCartera <= 0.009
            ? 'PAGADA'
            : 'PENDIENTE';

        $stmtInsert->execute([
            ':id_saldo_favor' => $idSaldoFavor,
            ':id_cartera' => $idCartera,
            ':valor_aplicado' => $valorAplicar,
            ':observaciones' =>
                'Aplicación automática de saldo a favor por antigüedad de vencimiento.'
        ]);

        $sqlUpdateCartera = "
            UPDATE cartera
            SET
                valor_pagado = :valor_pagado,
                saldo = :saldo,
                estado = :estado
            WHERE id_cartera = :id_cartera
              AND estado = 'PENDIENTE'
        ";

        $stmtUpdateCartera = $conexion->prepare($sqlUpdateCartera);
        $stmtUpdateCartera->execute([
            ':valor_pagado' => $nuevoPagadoCartera,
            ':saldo' => $nuevoSaldoCartera,
            ':estado' => $nuevoEstadoCartera,
            ':id_cartera' => $idCartera
        ]);

        if ($stmtUpdateCartera->rowCount() !== 1) {
            throw new Exception(
                'No fue posible actualizar la obligación de cartera #' .
                $idCartera . '.'
            );
        }

        if ($interes) {
            $nuevoSaldoInteres = round(
                (float)$interes['saldo'] - $valorAplicar,
                2
            );

            if ($nuevoSaldoInteres <= 0.009) {
                $nuevoSaldoInteres = 0.00;
            }

            $nuevoPagadoInteres = round(
                (float)$interes['valor_pagado'] + $valorAplicar,
                2
            );

            $nuevoEstadoInteres = $nuevoSaldoInteres <= 0.009
                ? 'PAGADO'
                : 'PENDIENTE';

            $sqlUpdateInteres = "
                UPDATE intereses_cartera
                SET
                    valor_pagado = :valor_pagado,
                    saldo = :saldo,
                    estado = :estado
                WHERE id_interes = :id_interes
            ";

            $stmtUpdateInteres = $conexion->prepare($sqlUpdateInteres);
            $stmtUpdateInteres->execute([
                ':valor_pagado' => $nuevoPagadoInteres,
                ':saldo' => $nuevoSaldoInteres,
                ':estado' => $nuevoEstadoInteres,
                ':id_interes' => $idInteres
            ]);
        }

        $disponible = round($disponible - $valorAplicar, 2);
        $totalAplicado = round($totalAplicado + $valorAplicar, 2);
        $cantidadAplicaciones++;

        if (!empty($obligacion['id_factura'])) {
            $facturasAfectadas[(int)$obligacion['id_factura']] = true;
        }
    }

    if ($cantidadAplicaciones === 0 || $totalAplicado <= 0.009) {
        throw new Exception(
            'No se pudo aplicar el saldo a favor a ninguna obligación.'
        );
    }

    $nuevoUtilizado = round($valorUtilizado + $totalAplicado, 2);
    $nuevoDisponible = round($valorOriginal - $nuevoUtilizado, 2);

    if ($nuevoDisponible <= 0.009) {
        $nuevoDisponible = 0.00;
    }

    $nuevoEstadoSaldo = $nuevoDisponible <= 0.009
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

    $stmtUpdateSaldo = $conexion->prepare($sqlUpdateSaldo);
    $stmtUpdateSaldo->execute([
        ':valor_utilizado' => $nuevoUtilizado,
        ':saldo_disponible' => $nuevoDisponible,
        ':estado' => $nuevoEstadoSaldo,
        ':id_saldo_favor' => $idSaldoFavor
    ]);

    foreach (array_keys($facturasAfectadas) as $idFactura) {
        actualizarEstadoFactura($conexion, (int)$idFactura);
    }

    $conexion->commit();

    redireccionarSaldo(
        $idSaldoFavor,
        'success',
        'Saldo a favor aplicado automáticamente. ' .
        'Valor aplicado: $' .
        number_format($totalAplicado, 2, ',', '.') .
        '. Aplicaciones creadas: ' .
        $cantidadAplicaciones .
        '. Saldo disponible restante: $' .
        number_format($nuevoDisponible, 2, ',', '.') .
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