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

    $disponible =
        round(
            (float)$pago['valor'] -
            $yaAplicado,
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
            id_cartera,
            id_factura,
            id_unidad,
            descripcion,
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
        (int)$pago['id_unidad']
    ) {

        throw new Exception(
            'El pago y la obligación pertenecen a unidades diferentes.'
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
    // ESTADO FACTURA
    // ======================================================

    if (!empty($cartera['id_factura'])) {

        actualizarEstadoFactura(
            $conexion,
            (int)$cartera['id_factura']
        );
    }


    $conexion->commit();


    redireccionarPago(
        $idPago,
        'success',
        'Aplicación manual realizada correctamente por $' .
        number_format(
            $valorAplicar,
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

    redireccionarPago(
        $idPago,
        'error',
        $e->getMessage()
    );
}
