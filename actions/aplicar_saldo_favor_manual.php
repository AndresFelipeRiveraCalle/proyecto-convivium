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
// ACTUALIZAR ESTADO FACTURA
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
// DATOS
// ==========================================================

$idSaldoFavor =
    isset($_POST['id_saldo_favor'])
        ? (int)$_POST['id_saldo_favor']
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
    $idSaldoFavor <= 0 ||
    $idCartera <= 0
) {

    redireccionarSaldo(
        $idSaldoFavor,
        'warning',
        'Debe seleccionar un saldo a favor y una obligación válidos.'
    );
}


if ($valorAplicar <= 0) {

    redireccionarSaldo(
        $idSaldoFavor,
        'warning',
        'El valor a aplicar debe ser mayor que cero.'
    );
}


try {

    $conexion->beginTransaction();


    // ======================================================
    // BLOQUEAR SALDO FAVOR
    // ======================================================

    $sqlSaldo = "
        SELECT
            id_saldo_favor,
            id_unidad,
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


    if ($valorAplicar > $disponible + 0.009) {
        throw new Exception(
            'El valor solicitado supera el saldo a favor disponible.'
        );
    }


    // ======================================================
    // BLOQUEAR CARTERA
    // ======================================================

    $sqlCartera = "
        SELECT
            id_cartera,
            id_factura,
            id_unidad,
            saldo,
            estado
        FROM cartera
        WHERE id_cartera = :id_cartera
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
        (int)$saldoFavor['id_unidad']
    ) {
        throw new Exception(
            'El saldo a favor y la obligación pertenecen a unidades diferentes.'
        );
    }


    if (
        $cartera['estado'] !== 'PENDIENTE' ||
        (float)$cartera['saldo'] <= 0
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

        ':id_saldo_favor'
            => $idSaldoFavor,

        ':id_cartera'
            => $idCartera,

        ':valor_aplicado'
            => $valorAplicar,

        ':observaciones'
            => 'Aplicación manual de saldo a favor.'
    ]);


    // ======================================================
    // UPDATE CARTERA
    // ======================================================

    $nuevoSaldoCartera =
        round(
            $saldoCartera -
            $valorAplicar,
            2
        );


    $nuevoEstadoCartera =
        $nuevoSaldoCartera <= 0.009
            ? 'PAGADA'
            : 'PENDIENTE';


    $sqlUpdateCartera = "
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

    $stmtUpdateCartera =
        $conexion->prepare($sqlUpdateCartera);

    $stmtUpdateCartera->execute([

        ':valor_aplicado'
            => $valorAplicar,

        ':saldo'
            => max($nuevoSaldoCartera, 0),

        ':estado'
            => $nuevoEstadoCartera,

        ':id_cartera'
            => $idCartera
    ]);


    // ======================================================
    // UPDATE SALDO FAVOR
    // ======================================================

    $nuevoUtilizado =
        round(
            (float)$saldoFavor['valor_utilizado'] +
            $valorAplicar,
            2
        );

    $nuevoDisponible =
        round(
            $disponible -
            $valorAplicar,
            2
        );


    $nuevoEstadoSaldo =
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
            => $nuevoEstadoSaldo,

        ':id_saldo_favor'
            => $idSaldoFavor
    ]);


    // ======================================================
    // ESTADO FACTURA
    // ======================================================

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
        number_format(
            $valorAplicar,
            2,
            ',',
            '.'
        ) .
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
