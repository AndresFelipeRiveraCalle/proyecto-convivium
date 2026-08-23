<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// RESPUESTA
// ==========================================================

function respuestaError($mensaje)
{
    header(
        "Location: " .
        BASE_URL .
        "configuracion/registrar_pago.php?tipo=error&mensaje=" .
        urlencode($mensaje)
    );

    exit;
}


// ==========================================================
// VALIDAR MÉTODO
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    respuestaError("Solicitud no válida.");
}


// ==========================================================
// DATOS DEL FORMULARIO
// ==========================================================

$idUnidad = filter_input(
    INPUT_POST,
    'id_unidad',
    FILTER_VALIDATE_INT
);

$fechaPago = trim(
    $_POST['fecha_pago'] ?? ''
);

$valor = (float)(
    $_POST['valor'] ?? 0
);

$medioPago = trim(
    $_POST['medio_pago'] ?? ''
);

$origenPago = trim(
    $_POST['origen_pago'] ?? 'MANUAL'
);

$referencia = trim(
    $_POST['referencia'] ?? ''
);

$referenciaExterna = trim(
    $_POST['referencia_externa'] ?? ''
);

$idExterno = trim(
    $_POST['id_externo'] ?? ''
);

$observaciones = trim(
    $_POST['observaciones'] ?? ''
);


// ==========================================================
// VALIDACIONES
// ==========================================================

if (!$idUnidad) {

    respuestaError(
        "Debe seleccionar una unidad."
    );
}


if (
    !preg_match(
        '/^\d{4}-\d{2}-\d{2}$/',
        $fechaPago
    )
) {

    respuestaError(
        "La fecha del pago no es válida."
    );
}


if ($valor <= 0) {

    respuestaError(
        "El valor del pago debe ser mayor que cero."
    );
}


$mediosPermitidos = [
    'EFECTIVO',
    'TRANSFERENCIA',
    'CONSIGNACION',
    'PSE',
    'TARJETA',
    'OTRO'
];


if (!in_array(
    $medioPago,
    $mediosPermitidos,
    true
)) {

    respuestaError(
        "El medio de pago no es válido."
    );
}


$origenesPermitidos = [
    'MANUAL',
    'BANCO',
    'PASARELA'
];


if (!in_array(
    $origenPago,
    $origenesPermitidos,
    true
)) {

    respuestaError(
        "El origen del pago no es válido."
    );
}


// ==========================================================
// VALIDAR UNIDAD
// ==========================================================

$sqlUnidad = "
    SELECT
        id_unidad,
        codigo,
        nombre
    FROM unidades
    WHERE id_unidad = :id_unidad
      AND activo = 1
    LIMIT 1
";

$stmtUnidad = $conexion->prepare(
    $sqlUnidad
);

$stmtUnidad->execute([
    ':id_unidad' => $idUnidad
]);

$unidad = $stmtUnidad->fetch(
    PDO::FETCH_ASSOC
);


if (!$unidad) {

    respuestaError(
        "La unidad seleccionada no existe o está inactiva."
    );
}


// ==========================================================
// INICIAR TRANSACCIÓN
// ==========================================================

try {

    $conexion->beginTransaction();


    // ======================================================
    // 1. CREAR PAGO
    // ======================================================

    $sqlPago = "
        INSERT INTO pagos (
            id_unidad,
            fecha_pago,
            valor,
            medio_pago,
            origen_pago,
            estado_conciliacion,
            referencia,
            referencia_externa,
            id_externo,
            observaciones,
            estado
        )
        VALUES (
            :id_unidad,
            :fecha_pago,
            :valor,
            :medio_pago,
            :origen_pago,
            'PENDIENTE',
            :referencia,
            :referencia_externa,
            :id_externo,
            :observaciones,
            'REGISTRADO'
        )
    ";

    $stmtPago = $conexion->prepare(
        $sqlPago
    );

    $stmtPago->execute([
        ':id_unidad'        => $idUnidad,
        ':fecha_pago'       => $fechaPago,
        ':valor'            => $valor,
        ':medio_pago'       => $medioPago,
        ':origen_pago'      => $origenPago,
        ':referencia'       => $referencia !== ''
                                ? $referencia
                                : null,
        ':referencia_externa' =>
            $referenciaExterna !== ''
                ? $referenciaExterna
                : null,
        ':id_externo'       =>
            $idExterno !== ''
                ? $idExterno
                : null,
        ':observaciones'    =>
            $observaciones !== ''
                ? $observaciones
                : null
    ]);


    $idPago = (int)$conexion->lastInsertId();


    // ======================================================
    // DINERO DISPONIBLE PARA APLICAR
    // ======================================================

    $disponible = round(
        $valor,
        2
    );


    // ======================================================
    // 2. BUSCAR INTERESES PENDIENTES
    //
    // LOS INTERESES SIEMPRE VAN PRIMERO
    // ======================================================

    if ($disponible > 0) {

        $sqlIntereses = "
            SELECT
                i.id_interes,
                i.id_cartera,
                i.fecha_vencimiento,
                i.valor_interes,
                i.valor_pagado,
                i.saldo

            FROM intereses_cartera i

            INNER JOIN cartera c
                ON c.id_cartera = i.id_cartera

            WHERE c.id_unidad = :id_unidad
              AND i.estado = 'PENDIENTE'
              AND i.saldo > 0
              AND c.estado <> 'ANULADA'

            ORDER BY
                i.fecha_vencimiento ASC,
                i.id_interes ASC
        ";

        $stmtIntereses = $conexion->prepare(
            $sqlIntereses
        );

        $stmtIntereses->execute([
            ':id_unidad' => $idUnidad
        ]);

        $intereses = $stmtIntereses->fetchAll(
            PDO::FETCH_ASSOC
        );


        foreach ($intereses as $interes) {

            if ($disponible <= 0) {
                break;
            }


            $saldoInteres = (float)$interes['saldo'];


            $aplicar = min(
                $disponible,
                $saldoInteres
            );


            $aplicar = round(
                $aplicar,
                2
            );


            if ($aplicar <= 0) {
                continue;
            }


            // ----------------------------------------------
            // ACTUALIZAR INTERÉS
            // ----------------------------------------------

            $nuevoPagado = round(
                (float)$interes['valor_pagado']
                + $aplicar,
                2
            );


            $nuevoSaldo = round(
                $saldoInteres
                - $aplicar,
                2
            );


            $nuevoEstado =
                $nuevoSaldo <= 0
                    ? 'PAGADO'
                    : 'PENDIENTE';


            $sqlActualizarInteres = "
                UPDATE intereses_cartera
                SET
                    valor_pagado = :valor_pagado,
                    saldo = :saldo,
                    estado = :estado
                WHERE id_interes = :id_interes
            ";

            $stmtActualizarInteres =
                $conexion->prepare(
                    $sqlActualizarInteres
                );

            $stmtActualizarInteres->execute([
                ':valor_pagado' => $nuevoPagado,
                ':saldo'        => $nuevoSaldo,
                ':estado'       => $nuevoEstado,
                ':id_interes'   =>
                    $interes['id_interes']
            ]);


            // ----------------------------------------------
            // REGISTRAR APLICACIÓN
            // ----------------------------------------------

            $sqlAplicacion = "
                INSERT INTO aplicaciones_pagos (
                    id_pago,
                    id_cartera,
                    valor_aplicado,
                    tipo_aplicacion,
                    observaciones
                )
                VALUES (
                    :id_pago,
                    :id_cartera,
                    :valor_aplicado,
                    'AUTOMATICA',
                    :observaciones
                )
            ";

            $stmtAplicacion =
                $conexion->prepare(
                    $sqlAplicacion
                );

            $stmtAplicacion->execute([
                ':id_pago' =>
                    $idPago,

                ':id_cartera' =>
                    $interes['id_cartera'],

                ':valor_aplicado' =>
                    $aplicar,

                ':observaciones' =>
                    'Aplicación automática a intereses.'
            ]);


            $disponible = round(
                $disponible - $aplicar,
                2
            );
        }
    }


    // ======================================================
    // 3. BUSCAR OBLIGACIONES
    //
    // PRIORIDAD CONFIGURADA POR LA UNIDAD
    //
    // SI NO EXISTE CONFIGURACIÓN:
    // SE UTILIZA EL ORDEN DE tipos_obligacion
    // ======================================================

    if ($disponible > 0) {

        $sqlCartera = "
            SELECT

                c.id_cartera,
                c.id_tipo_obligacion,
                c.periodo,
                c.valor_original,
                c.valor_pagado,
                c.saldo,
                c.fecha_vencimiento,

                t.nombre AS tipo_obligacion,

                COALESCE(
                    cp.prioridad,
                    t.orden_defecto,
                    999999
                ) AS prioridad

            FROM cartera c

            INNER JOIN tipos_obligacion t
                ON t.id_tipo_obligacion =
                   c.id_tipo_obligacion

            LEFT JOIN configuracion_pagos cp
                ON cp.id_unidad = c.id_unidad
               AND cp.id_tipo_obligacion =
                   c.id_tipo_obligacion
               AND cp.activo = 1

            WHERE c.id_unidad = :id_unidad
              AND c.estado = 'PENDIENTE'
              AND c.saldo > 0

            ORDER BY

                prioridad ASC,

                c.fecha_vencimiento ASC,

                c.periodo ASC,

                c.id_cartera ASC
        ";

        $stmtCartera = $conexion->prepare(
            $sqlCartera
        );

        $stmtCartera->execute([
            ':id_unidad' => $idUnidad
        ]);

        $obligaciones =
            $stmtCartera->fetchAll(
                PDO::FETCH_ASSOC
            );


        // ==================================================
        // 4. APLICAR A LAS OBLIGACIONES
        // ==================================================

        foreach (
            $obligaciones
            as $obligacion
        ) {

            if ($disponible <= 0) {
                break;
            }


            $saldoObligacion =
                (float)$obligacion['saldo'];


            $aplicar = min(
                $disponible,
                $saldoObligacion
            );


            $aplicar = round(
                $aplicar,
                2
            );


            if ($aplicar <= 0) {
                continue;
            }


            // ----------------------------------------------
            // NUEVOS VALORES
            // ----------------------------------------------

            $nuevoPagado = round(
                (float)$obligacion['valor_pagado']
                + $aplicar,
                2
            );


            $nuevoSaldo = round(
                $saldoObligacion
                - $aplicar,
                2
            );


            $nuevoEstado =
                $nuevoSaldo <= 0
                    ? 'PAGADA'
                    : 'PENDIENTE';


            // ----------------------------------------------
            // ACTUALIZAR CARTERA
            // ----------------------------------------------

            $sqlActualizarCartera = "
                UPDATE cartera
                SET
                    valor_pagado = :valor_pagado,
                    saldo = :saldo,
                    estado = :estado
                WHERE id_cartera = :id_cartera
            ";

            $stmtActualizarCartera =
                $conexion->prepare(
                    $sqlActualizarCartera
                );

            $stmtActualizarCartera->execute([
                ':valor_pagado' =>
                    $nuevoPagado,

                ':saldo' =>
                    $nuevoSaldo,

                ':estado' =>
                    $nuevoEstado,

                ':id_cartera' =>
                    $obligacion['id_cartera']
            ]);


            // ----------------------------------------------
            // REGISTRAR APLICACIÓN
            // ----------------------------------------------

            $sqlAplicacion = "
                INSERT INTO aplicaciones_pagos (
                    id_pago,
                    id_cartera,
                    valor_aplicado,
                    tipo_aplicacion,
                    observaciones
                )
                VALUES (
                    :id_pago,
                    :id_cartera,
                    :valor_aplicado,
                    'AUTOMATICA',
                    :observaciones
                )
            ";

            $stmtAplicacion =
                $conexion->prepare(
                    $sqlAplicacion
                );

            $stmtAplicacion->execute([
                ':id_pago' =>
                    $idPago,

                ':id_cartera' =>
                    $obligacion['id_cartera'],

                ':valor_aplicado' =>
                    $aplicar,

                ':observaciones' =>
                    'Aplicación automática según prioridad de cartera.'
            ]);


            // ----------------------------------------------
            // RESTAR DEL DINERO DISPONIBLE
            // ----------------------------------------------

            $disponible = round(
                $disponible - $aplicar,
                2
            );
        }
    }


    // ======================================================
    // 5. SALDO A FAVOR
    // ======================================================

    if ($disponible > 0) {

        $sqlSaldoFavor = "
            INSERT INTO saldo_favor (
                id_unidad,
                id_pago,
                valor_original,
                valor_utilizado,
                saldo_disponible,
                estado,
                fecha_generacion,
                observaciones
            )
            VALUES (
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

        $stmtSaldoFavor =
            $conexion->prepare(
                $sqlSaldoFavor
            );

        $stmtSaldoFavor->execute([
            ':id_unidad' =>
                $idUnidad,

            ':id_pago' =>
                $idPago,

            ':valor_original' =>
                $disponible,

            ':saldo_disponible' =>
                $disponible,

            ':observaciones' =>
                'Saldo generado por excedente del pago.'
        ]);
    }


    // ======================================================
    // 6. CONFIRMAR TRANSACCIÓN
    // ======================================================

    $conexion->commit();


    // ======================================================
    // REDIRECCIÓN
    // ======================================================

    header(
        "Location: " .
        BASE_URL .
        "configuracion/detalle_cartera.php" .
        "?id_unidad=" .
        $idUnidad .
        "&tipo=success&mensaje=" .
        urlencode(
            "Pago registrado y aplicado correctamente."
        )
    );

    exit;


} catch (Throwable $e) {


    // ======================================================
    // DESHACER TODO
    // ======================================================

    if (
        $conexion->inTransaction()
    ) {

        $conexion->rollBack();
    }


    // ======================================================
    // ERROR
    // ======================================================

    error_log(
        "Error guardar_pago.php: " .
        $e->getMessage()
    );


    header(
        "Location: " .
        BASE_URL .
        "configuracion/registrar_pago.php" .
        "?tipo=error&mensaje=" .
        urlencode(
            "No fue posible registrar el pago. " .
            $e->getMessage()
        )
    );

    exit;
}