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
// NORMALIZAR VALOR
// ==========================================================

function normalizarValor($valor)
{
    $valor = trim((string)$valor);
    $valor = str_replace(' ', '', $valor);

    if (
        strpos($valor, '.') !== false &&
        strpos($valor, ',') !== false
    ) {

        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);

    } elseif (
        strpos($valor, ',') !== false
    ) {

        $valor = str_replace(',', '.', $valor);
    }

    return round((float)$valor, 2);
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
// DATOS
// ==========================================================

$idPago =
    isset($_POST['id_pago'])
        ? (int)$_POST['id_pago']
        : 0;


$idCartera =
    isset($_POST['id_cartera'])
        ? (int)$_POST['id_cartera']
        : 0;


$valorAplicar =
    normalizarValor(
        $_POST['valor_aplicado']
        ?? 0
    );


if (
    $idPago <= 0 ||
    $idCartera <= 0
) {

    redireccionarPago(
        $idPago,
        'warning',
        'Debe seleccionar un pago y una obligación válidos.'
    );
}


if ($valorAplicar <= 0) {

    redireccionarPago(
        $idPago,
        'warning',
        'El valor a aplicar debe ser mayor que cero.'
    );
}


try {

    $conexion->beginTransaction();


    // ======================================================
    // BLOQUEAR PAGO
    // ======================================================

    $sqlPago = "
        SELECT
            id_pago,
            id_unidad,
            valor,
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
        throw new Exception(
            'El pago seleccionado no existe.'
        );
    }


    if ($pago['estado'] !== 'REGISTRADO') {
        throw new Exception(
            'El pago no está disponible para aplicación.'
        );
    }


    // ======================================================
    // DISPONIBLE DEL PAGO
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

    $yaAplicado =
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


    $disponible =
        round(
            (float)$pago['valor'] -
            $yaAplicado -
            $saldoConvertido,
            2
        );


    if ($disponible <= 0) {
        throw new Exception(
            'El pago ya se encuentra completamente aplicado.'
        );
    }


    if ($valorAplicar > $disponible + 0.009) {
        throw new Exception(
            'El valor solicitado supera el saldo disponible del pago.'
        );
    }


    // ======================================================
    // BLOQUEAR CARTERA
    // ======================================================

    $sqlCartera = "
        SELECT
            c.id_cartera,
            c.id_factura,
            c.id_unidad,
            c.id_detalle,
            c.descripcion,
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
        (int)$cartera['id_unidad'] !==
        (int)$pago['id_unidad']
    ) {

        throw new Exception(
            'El pago y la obligación pertenecen a unidades diferentes.'
        );
    }


    if (
        $cartera['estado'] === 'ANULADA' ||
        (float)$cartera['saldo'] <= 0.009
    ) {

        throw new Exception(
            'La obligación seleccionada no tiene saldo pendiente.'
        );
    }


    $saldoCartera =
        round(
            (float)$cartera['saldo'],
            2
        );


    if ($valorAplicar > $saldoCartera + 0.009) {

        throw new Exception(
            'El valor solicitado supera el saldo de la obligación.'
        );
    }


    // ======================================================
    // INSERTAR APLICACIÓN
    // ======================================================

    $sqlInsert = "
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
            'MANUAL',
            :observaciones
        )
    ";

    $stmtInsert = $conexion->prepare($sqlInsert);

    $stmtInsert->execute([

        ':id_pago'
            => $idPago,

        ':id_cartera'
            => $idCartera,

        ':valor_aplicado'
            => $valorAplicar,

        ':observaciones'
            => 'Aplicación manual realizada desde cartera.'
    ]);


    // ======================================================
    // ACTUALIZAR CARTERA
    // ======================================================

    $nuevoSaldo =
        round(
            $saldoCartera -
            $valorAplicar,
            2
        );


    $nuevoEstado =
        $nuevoSaldo <= 0.009
            ? 'PAGADA'
            : 'PENDIENTE';


    $sqlUpdate = "
        UPDATE cartera
        SET
            valor_pagado =
                valor_pagado + :valor_aplicado,

            saldo =
                :saldo,

            estado =
                :estado

        WHERE id_cartera =
            :id_cartera
    ";

    $stmtUpdate = $conexion->prepare($sqlUpdate);

    $stmtUpdate->execute([

        ':valor_aplicado'
            => $valorAplicar,

        ':saldo'
            => max(
                $nuevoSaldo,
                0
            ),

        ':estado'
            => $nuevoEstado,

        ':id_cartera'
            => $idCartera
    ]);


    // ======================================================
    // SI LA OBLIGACIÓN ES MORA, SINCRONIZAR intereses_cartera
    // ======================================================

    if (!empty($cartera['id_interes'])) {

        $sqlInteres = "
            SELECT
                id_interes,
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
            ':id_interes' => (int)$cartera['id_interes']
        ]);

        $interes = $stmtInteres->fetch(PDO::FETCH_ASSOC);

        if ($interes) {

            $nuevoPagadoInteres = round(
                (float)$interes['valor_pagado'] + $valorAplicar,
                2
            );

            $nuevoSaldoInteres = round(
                max((float)$interes['saldo'] - $valorAplicar, 0),
                2
            );

            $nuevoEstadoInteres =
                $nuevoSaldoInteres <= 0.009
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
                ':id_interes' => (int)$cartera['id_interes']
            ]);
        }
    }


    // ======================================================
    // ESTADO FACTURA
    // ======================================================

    if (!empty($cartera['id_factura'])) {

        actualizarEstadoFactura(
            $conexion,
            (int)$cartera['id_factura']
        );
    }


    // ======================================================
    // SALDO A FAVOR
    // Convierte el excedente si ya no quedan obligaciones pendientes.
    // ======================================================

    $remanentePago =
        round(
            $disponible - $valorAplicar,
            2
        );

    $saldoFavorGenerado =
        crearSaldoFavorSiCorresponde(
            $conexion,
            $idPago,
            (int)$pago['id_unidad'],
            $remanentePago
        );


    $conexion->commit();


    $mensaje =
        'Pago parcial aplicado correctamente por $' .
        number_format(
            $valorAplicar,
            2,
            ',',
            '.'
        ) .
        '.';

    if ($saldoFavorGenerado > 0.009) {
        $mensaje .=
            ' Se generó un saldo a favor por $' .
            number_format(
                $saldoFavorGenerado,
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
