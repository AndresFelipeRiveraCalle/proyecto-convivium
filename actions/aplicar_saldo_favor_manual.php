<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/auth/requerir_sesion.php";
require_once ROOT_PATH . "/config/conexion.php";


//
// REDIRECCIÓN
//

function redireccionarSaldo($idSaldoFavor, $tipo, $texto)
{
    header(
        "Location: " .
        BASE_URL .
        "configuracion/aplicar_saldo_favor.php?" .
        http_build_query([
            'id_saldo_favor' => (int)$idSaldoFavor,
            'tipo' => $tipo,
            'texto' => $texto
        ])
    );

    exit;
}


//
// NORMALIZAR VALOR
// Acepta decimales con punto o coma y formato colombiano 1.234,56.
//

function normalizarValor($valor): float
{
    $valor = preg_replace(
        '/\s+/u',
        '',
        trim((string)$valor)
    );

    if ($valor === null || $valor === '') {
        throw new InvalidArgumentException(
            'El valor a aplicar no es válido.'
        );
    }

    if (strpos($valor, ',') !== false) {
        if (strpos($valor, '.') !== false) {
            if (!preg_match('/^\d{1,3}(?:\.\d{3})+,\d{1,2}$/', $valor)) {
                throw new InvalidArgumentException(
                    'El formato del valor no es válido.'
                );
            }

            $valor = str_replace('.', '', $valor);
        } elseif (!preg_match('/^\d+,\d{1,2}$/', $valor)) {
            throw new InvalidArgumentException(
                'El formato del valor no es válido.'
            );
        }

        $valor = str_replace(',', '.', $valor);
    } elseif (!preg_match('/^\d+(?:\.\d{1,2})?$/', $valor)) {
        throw new InvalidArgumentException(
            'El formato del valor no es válido.'
        );
    }

    if (!is_numeric($valor)) {
        throw new InvalidArgumentException(
            'El valor a aplicar no es válido.'
        );
    }

    $resultado = round((float)$valor, 2);

    if (!is_finite($resultado) || $resultado <= 0) {
        throw new InvalidArgumentException(
            'El valor a aplicar debe ser mayor que cero.'
        );
    }

    return $resultado;
}


//
// ACTUALIZAR ESTADO DE FACTURA
//

function actualizarEstadoFactura(PDO $conexion, int $idFactura): void
{
    if ($idFactura <= 0) {
        return;
    }

    $sqlResumen = "
        SELECT
            COALESCE(SUM(valor_original), 0) AS total_original,
            COALESCE(SUM(valor_pagado), 0) AS total_pagado,
            COALESCE(SUM(saldo), 0) AS total_saldo
        FROM cartera
        WHERE id_factura = :id_factura
          AND estado <> 'ANULADA'
    ";

    $stmtResumen = $conexion->prepare($sqlResumen);
    $stmtResumen->execute([
        ':id_factura' => $idFactura
    ]);

    $resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC);

    $totalOriginal = (float)($resumen['total_original'] ?? 0);
    $totalPagado = (float)($resumen['total_pagado'] ?? 0);
    $totalSaldo = (float)($resumen['total_saldo'] ?? 0);

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

    $sqlActualizarFactura = "
        UPDATE facturas
        SET estado = :estado
        WHERE id_factura = :id_factura
          AND estado <> 'ANULADA'
    ";

    $stmtActualizarFactura = $conexion->prepare($sqlActualizarFactura);
    $stmtActualizarFactura->execute([
        ':estado' => $nuevoEstado,
        ':id_factura' => $idFactura
    ]);
}


//
// VALIDAR MÉTODO
//

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header(
        "Location: " .
        BASE_URL .
        "configuracion/cartera.php"
    );

    exit;
}


//
// DATOS
//

$idSaldoFavor = 0;
$idCartera = 0;

try {
    $idSaldoFavor = isset($_POST['id_saldo_favor'])
        ? (int)$_POST['id_saldo_favor']
        : 0;

    $idCartera = isset($_POST['id_cartera'])
        ? (int)$_POST['id_cartera']
        : 0;

    $valorAplicar = normalizarValor(
        $_POST['valor_aplicado'] ?? ''
    );

    if ($idSaldoFavor <= 0 || $idCartera <= 0) {
        throw new Exception(
            'Debe seleccionar un saldo a favor y una obligación válidos.'
        );
    }


    //
    // TRANSACCIÓN
    //

    $conexion->beginTransaction();


    //
    // BLOQUEAR Y VALIDAR SALDO A FAVOR
    //

    $sqlSaldo = "
        SELECT
            id_saldo_favor,
            id_unidad,
            valor_original,
            saldo_disponible,
            valor_utilizado,
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

    $valorOriginalSaldo = round(
        (float)$saldoFavor['valor_original'],
        2
    );

    $valorUtilizadoSaldo = round(
        (float)$saldoFavor['valor_utilizado'],
        2
    );

    $disponible = round(
        (float)$saldoFavor['saldo_disponible'],
        2
    );

    $diferenciaSaldoFavor = round(
        $valorOriginalSaldo
        - $valorUtilizadoSaldo
        - $disponible,
        2
    );

    if (abs($diferenciaSaldoFavor) > 0.009) {
        throw new Exception(
            'El saldo a favor presenta una inconsistencia entre su valor original, utilizado y disponible. No se aplicó.'
        );
    }

    if (
        $saldoFavor['estado'] !== 'DISPONIBLE'
        || $disponible <= 0.009
    ) {
        throw new Exception(
            'El saldo a favor seleccionado no tiene valor disponible.'
        );
    }

    if ($valorAplicar > $disponible + 0.009) {
        throw new Exception(
            'El valor solicitado supera el saldo a favor disponible.'
        );
    }


    //
    // BLOQUEAR Y VALIDAR CARTERA
    //

    $sqlCartera = "
        SELECT
            c.id_cartera,
            c.id_factura,
            c.id_detalle,
            c.id_unidad,
            c.valor_original,
            c.valor_pagado,
            c.saldo,
            c.estado,
            fd.id_interes
        FROM cartera c
        LEFT JOIN facturas_detalle fd
            ON fd.id_detalle = c.id_detalle
        WHERE c.id_cartera = :id_cartera
        LIMIT 1
        FOR UPDATE
    ";

    $stmtCartera = $conexion->prepare($sqlCartera);
    $stmtCartera->execute([
        ':id_cartera' => $idCartera
    ]);

    $cartera = $stmtCartera->fetch(PDO::FETCH_ASSOC);

    if (!$cartera) {
        throw new Exception(
            'La obligación seleccionada no existe.'
        );
    }

    if (
        (int)$cartera['id_unidad']
        !== (int)$saldoFavor['id_unidad']
    ) {
        throw new Exception(
            'El saldo a favor y la obligación pertenecen a unidades diferentes.'
        );
    }

    if (
        $cartera['estado'] !== 'PENDIENTE'
        || (float)$cartera['saldo'] <= 0.009
    ) {
        throw new Exception(
            'La obligación seleccionada no tiene saldo pendiente.'
        );
    }

    $valorOriginalCartera = round(
        (float)$cartera['valor_original'],
        2
    );

    $valorPagadoCartera = round(
        (float)$cartera['valor_pagado'],
        2
    );

    $saldoCartera = round(
        (float)$cartera['saldo'],
        2
    );

    $diferenciaCartera = round(
        $valorOriginalCartera
        - $valorPagadoCartera
        - $saldoCartera,
        2
    );

    if (abs($diferenciaCartera) > 0.009) {
        throw new Exception(
            'La obligación presenta una inconsistencia entre su valor original, pagado y saldo. No se aplicó.'
        );
    }

    if ($valorAplicar > $saldoCartera + 0.009) {
        throw new Exception(
            'El valor solicitado supera el saldo de la obligación.'
        );
    }


    //
    // VALIDAR Y BLOQUEAR INTERÉS, SI APLICA
    //

    $interes = null;
    $idInteres = !empty($cartera['id_interes'])
        ? (int)$cartera['id_interes']
        : 0;

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
                'La obligación corresponde a un interés de mora, pero el registro del interés no existe.'
            );
        }

        $saldoInteres = round((float)$interes['saldo'], 2);
        $valorInteres = round((float)$interes['valor_interes'], 2);
        $valorPagadoInteres = round((float)$interes['valor_pagado'], 2);

        if (
            $interes['estado'] !== 'PENDIENTE'
            || $saldoInteres <= 0.009
        ) {
            throw new Exception(
                'El interés de mora asociado no tiene saldo pendiente.'
            );
        }

        if (abs($saldoInteres - $saldoCartera) > 0.009) {
            throw new Exception(
                'El saldo de cartera no coincide con el saldo del interés de mora. No se aplicó.'
            );
        }

        if (
            abs(
                round(
                    $valorInteres
                    - $valorPagadoInteres
                    - $saldoInteres,
                    2
                )
            ) > 0.009
        ) {
            throw new Exception(
                'El registro del interés de mora presenta un saldo inconsistente. No se aplicó.'
            );
        }
    }


    //
    // REGISTRAR APLICACIÓN
    //

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
            'MANUAL',
            :observaciones
        )
    ";

    $stmtInsert = $conexion->prepare($sqlInsert);
    $stmtInsert->execute([
        ':id_saldo_favor' => $idSaldoFavor,
        ':id_cartera' => $idCartera,
        ':valor_aplicado' => $valorAplicar,
        ':observaciones' => 'Aplicación manual de saldo a favor.'
    ]);


    //
    // ACTUALIZAR CARTERA
    //

    $nuevoSaldoCartera = round(
        $saldoCartera - $valorAplicar,
        2
    );

    if ($nuevoSaldoCartera <= 0.009) {
        $nuevoSaldoCartera = 0;
    }

    $nuevoPagadoCartera = round(
        $valorPagadoCartera + $valorAplicar,
        2
    );

    $nuevoEstadoCartera = $nuevoSaldoCartera <= 0
        ? 'PAGADA'
        : 'PENDIENTE';

    $sqlUpdateCartera = "
        UPDATE cartera
        SET
            valor_pagado = :valor_pagado,
            saldo = :saldo,
            estado = :estado
        WHERE id_cartera = :id_cartera
    ";

    $stmtUpdateCartera = $conexion->prepare($sqlUpdateCartera);
    $stmtUpdateCartera->execute([
        ':valor_pagado' => $nuevoPagadoCartera,
        ':saldo' => $nuevoSaldoCartera,
        ':estado' => $nuevoEstadoCartera,
        ':id_cartera' => $idCartera
    ]);


    //
    // ACTUALIZAR INTERÉS DE MORA, SI APLICA
    //

    if ($interes !== null) {
        $nuevoPagadoInteres = round(
            (float)$interes['valor_pagado'] + $valorAplicar,
            2
        );

        $nuevoSaldoInteres = round(
            (float)$interes['saldo'] - $valorAplicar,
            2
        );

        if ($nuevoSaldoInteres <= 0.009) {
            $nuevoSaldoInteres = 0;
        }

        $nuevoEstadoInteres = $nuevoSaldoInteres <= 0
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


    //
    // ACTUALIZAR SALDO A FAVOR
    //

    $nuevoUtilizado = round(
        $valorUtilizadoSaldo + $valorAplicar,
        2
    );

    $nuevoDisponible = round(
        $disponible - $valorAplicar,
        2
    );

    if ($nuevoDisponible <= 0.009) {
        $nuevoDisponible = 0;
    }

    $nuevoEstadoSaldo = $nuevoDisponible <= 0
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


    //
    // ACTUALIZAR ESTADO DE FACTURA
    //

    if (!empty($cartera['id_factura'])) {
        actualizarEstadoFactura(
            $conexion,
            (int)$cartera['id_factura']
        );
    }


    $conexion->commit();

    redireccionarSaldo(
        $idSaldoFavor,
        'success',
        'Saldo a favor aplicado manualmente por $' .
        number_format($valorAplicar, 2, ',', '.') .
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