<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// FUNCIONES
// ==========================================================

function e($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

function dinero($valor)
{
    return '$' . number_format(
        (float)$valor,
        2,
        ',',
        '.'
    );
}


// ==========================================================
// VALIDAR UNIDAD
// ==========================================================

$idUnidad =
    isset($_GET['id_unidad'])
        ? (int)$_GET['id_unidad']
        : 0;


if ($idUnidad <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/cartera.php?" .
        http_build_query([
            'tipo'  => 'warning',
            'texto' => 'Debe seleccionar una unidad válida.'
        ])
    );

    exit;
}


// ==========================================================
// DATOS DE LA UNIDAD
// ==========================================================

$sqlUnidad = "
    SELECT
        u.id_unidad,
        u.codigo,
        u.nombre,
        u.piso,
        u.area,
        u.coeficiente,
        u.estado,

        dtu.nombre_grupo,

        tv.nombre AS tipo_unidad

    FROM unidades u

    LEFT JOIN detalle_tipos_unidad dtu
        ON dtu.id_tipo_config =
           u.id_tipo_config

    LEFT JOIN tipos_vivienda tv
        ON tv.id_tipo_vivienda =
           dtu.id_tipo_vivienda

    WHERE
        u.id_unidad =
            :id_unidad

    LIMIT 1
";


$stmtUnidad =
    $conexion->prepare(
        $sqlUnidad
    );


$stmtUnidad->execute([
    ':id_unidad'
        => $idUnidad
]);


$unidad =
    $stmtUnidad->fetch(
        PDO::FETCH_ASSOC
    );


if (!$unidad) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/cartera.php?" .
        http_build_query([
            'tipo'  => 'error',
            'texto' => 'La unidad seleccionada no existe.'
        ])
    );

    exit;
}


// ==========================================================
// RESUMEN DE CARTERA
// ==========================================================

$sqlResumen = "
    SELECT
        COUNT(*) AS obligaciones,

        COALESCE(
            SUM(valor_original),
            0
        ) AS valor_original,

        COALESCE(
            SUM(valor_pagado),
            0
        ) AS valor_pagado,

        COALESCE(
            SUM(saldo),
            0
        ) AS saldo,

        COALESCE(
            SUM(
                CASE
                    WHEN
                        estado = 'PENDIENTE'
                        AND saldo > 0
                        AND fecha_vencimiento < CURDATE()
                    THEN saldo
                    ELSE 0
                END
            ),
            0
        ) AS saldo_vencido

    FROM cartera

    WHERE
        id_unidad =
            :id_unidad

        AND estado <>
            'ANULADA'
";


$stmtResumen =
    $conexion->prepare(
        $sqlResumen
    );


$stmtResumen->execute([
    ':id_unidad'
        => $idUnidad
]);


$resumen =
    $stmtResumen->fetch(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// OBLIGACIONES DE CARTERA
// ==========================================================

$sqlCartera = "
    SELECT
        c.id_factura,
        c.id_unidad,

        MIN(c.periodo) AS periodo,
        MIN(c.fecha_vencimiento) AS fecha_vencimiento,

        SUM(c.valor_original) AS valor_original,
        SUM(c.valor_pagado) AS valor_pagado,
        SUM(c.saldo) AS saldo,

        f.numero_factura,
        f.estado AS estado_factura,

        COUNT(c.id_cartera) AS cantidad_conceptos,

        GROUP_CONCAT(
            DISTINCT COALESCE(
                cf.nombre,
                tobl.nombre,
                c.descripcion
            )
            ORDER BY c.id_cartera
            SEPARATOR ' | '
        ) AS conceptos,

        GROUP_CONCAT(
            DISTINCT c.descripcion
            ORDER BY c.id_cartera
            SEPARATOR ' | '
        ) AS descripciones,

        CASE
            WHEN
                SUM(
                    CASE
                        WHEN
                            c.estado = 'PENDIENTE'
                            AND c.saldo > 0
                            AND c.fecha_vencimiento < CURDATE()
                        THEN c.saldo
                        ELSE 0
                    END
                ) > 0
            THEN 1
            ELSE 0
        END AS vencida,

        CASE
            WHEN
                SUM(
                    CASE
                        WHEN c.estado = 'ANULADA'
                        THEN 1
                        ELSE 0
                    END
                ) = COUNT(c.id_cartera)
            THEN 'ANULADA'

            WHEN
                SUM(c.saldo) <= 0.009
            THEN 'PAGADA'

            ELSE 'PENDIENTE'
        END AS estado

    FROM cartera c

    LEFT JOIN facturas f
        ON f.id_factura =
           c.id_factura

    LEFT JOIN facturas_detalle fd
        ON fd.id_detalle =
           c.id_detalle

    LEFT JOIN conceptos_facturacion cf
        ON cf.id_concepto =
           fd.id_concepto

    LEFT JOIN tipos_obligacion tobl
        ON tobl.id_tipo_obligacion =
           c.id_tipo_obligacion

    WHERE
        c.id_unidad =
            :id_unidad

    GROUP BY
        c.id_factura,
        c.id_unidad,
        f.numero_factura,
        f.estado

    ORDER BY
        periodo DESC,
        fecha_vencimiento DESC,
        c.id_factura DESC
";


$stmtCartera =
    $conexion->prepare(
        $sqlCartera
    );


$stmtCartera->execute([
    ':id_unidad'
        => $idUnidad
]);


$obligaciones =
    $stmtCartera->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// PAGOS DE LA UNIDAD
// ==========================================================

$sqlPagos = "
    SELECT
        p.id_pago,
        p.fecha_pago,
        p.valor,
        p.medio_pago,
        p.origen_pago,
        p.estado_conciliacion,
        p.referencia,
        p.referencia_externa,
        p.estado,

        COALESCE(
            SUM(ap.valor_aplicado),
            0
        ) AS valor_aplicado,

        (
            p.valor -
            COALESCE(
                SUM(ap.valor_aplicado),
                0
            )
        ) AS valor_disponible

    FROM pagos p

    LEFT JOIN aplicaciones_pagos ap
        ON ap.id_pago =
           p.id_pago

    WHERE
        p.id_unidad =
            :id_unidad

    GROUP BY
        p.id_pago,
        p.fecha_pago,
        p.valor,
        p.medio_pago,
        p.origen_pago,
        p.estado_conciliacion,
        p.referencia,
        p.referencia_externa,
        p.estado

    ORDER BY
        p.fecha_pago DESC,
        p.id_pago DESC
";


$stmtPagos =
    $conexion->prepare(
        $sqlPagos
    );


$stmtPagos->execute([
    ':id_unidad'
        => $idUnidad
]);


$pagos =
    $stmtPagos->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// APLICACIONES DE PAGOS
// ==========================================================

$sqlAplicaciones = "
    SELECT
        ap.id_aplicacion,
        ap.id_pago,
        ap.id_cartera,
        ap.valor_aplicado,
        ap.fecha_aplicacion,
        ap.tipo_aplicacion,
        ap.observaciones,

        p.fecha_pago,
        p.referencia,

        c.descripcion AS cartera_descripcion,
        c.periodo,

        f.numero_factura,

        cf.nombre AS concepto

    FROM aplicaciones_pagos ap

    INNER JOIN pagos p
        ON p.id_pago =
           ap.id_pago

    INNER JOIN cartera c
        ON c.id_cartera =
           ap.id_cartera

    LEFT JOIN facturas f
        ON f.id_factura =
           c.id_factura

    LEFT JOIN facturas_detalle fd
        ON fd.id_detalle =
           c.id_detalle

    LEFT JOIN conceptos_facturacion cf
        ON cf.id_concepto =
           fd.id_concepto

    WHERE
        c.id_unidad =
            :id_unidad

    ORDER BY
        ap.fecha_aplicacion DESC,
        ap.id_aplicacion DESC
";


$stmtAplicaciones =
    $conexion->prepare(
        $sqlAplicaciones
    );


$stmtAplicaciones->execute([
    ':id_unidad'
        => $idUnidad
]);


$aplicaciones =
    $stmtAplicaciones->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// SALDOS A FAVOR DE LA UNIDAD
// ==========================================================

$sqlSaldosFavor = "
    SELECT
        sf.id_saldo_favor,
        sf.id_pago,
        sf.valor_original,
        sf.valor_utilizado,
        sf.saldo_disponible,
        sf.estado,
        sf.fecha_generacion,
        sf.fecha_ultimo_uso,
        sf.observaciones,

        p.referencia,
        p.fecha_pago

    FROM saldo_favor sf

    INNER JOIN pagos p
        ON p.id_pago =
           sf.id_pago

    WHERE
        sf.id_unidad =
            :id_unidad

    ORDER BY
        CASE
            WHEN sf.estado = 'DISPONIBLE'
            THEN 0
            ELSE 1
        END,
        sf.fecha_generacion DESC,
        sf.id_saldo_favor DESC
";


$stmtSaldosFavor =
    $conexion->prepare(
        $sqlSaldosFavor
    );


$stmtSaldosFavor->execute([
    ':id_unidad'
        => $idUnidad
]);


$saldosFavor =
    $stmtSaldosFavor->fetchAll(
        PDO::FETCH_ASSOC
    );


?>

<!DOCTYPE html>

<html lang="es">

<head>

    <?php include ROOT_PATH . "/includes/head.php"; ?>

</head>


<body>


<?php include ROOT_PATH . "/includes/header.php"; ?>


<div class="contenedor">


    <?php include ROOT_PATH . "/includes/sidebar.php"; ?>


    <main class="contenido">


        <!-- ======================================================
             ENCABEZADO
        ======================================================= -->

        <div
            style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                gap:15px;
                flex-wrap:wrap;
            "
        >

            <div>

                <h2>
                    Detalle de cartera
                </h2>

                <p>
                    Unidad <?= e($unidad['codigo']) ?>
                </p>

            </div>


            <div>

                <a
                    href="<?= BASE_URL ?>configuracion/cartera.php"
                    class="btn-limpiar"
                >
                    ← Volver a cartera
                </a>

            </div>

        </div>


        <br>


        <!-- ======================================================
             DATOS UNIDAD
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Unidad
                </h3>

                <br>

                <div
                    style="
                        display:grid;
                        grid-template-columns:
                            repeat(
                                auto-fit,
                                minmax(170px,1fr)
                            );
                        gap:15px;
                    "
                >

                    <div>
                        <small>Código</small>
                        <br>
                        <strong>
                            <?= e($unidad['codigo']) ?>
                        </strong>
                    </div>

                    <div>
                        <small>Grupo</small>
                        <br>
                        <strong>
                            <?= e($unidad['nombre_grupo'] ?? '-') ?>
                        </strong>
                    </div>

                    <div>
                        <small>Tipo</small>
                        <br>
                        <strong>
                            <?= e($unidad['tipo_unidad'] ?? '-') ?>
                        </strong>
                    </div>

                    <div>
                        <small>Área</small>
                        <br>
                        <strong>
                            <?= number_format(
                                (float)$unidad['area'],
                                2,
                                ',',
                                '.'
                            ) ?>
                        </strong>
                    </div>

                    <div>
                        <small>Coeficiente</small>
                        <br>
                        <strong>
                            <?= number_format(
                                (float)$unidad['coeficiente'],
                                8,
                                ',',
                                '.'
                            ) ?>
                        </strong>
                    </div>

                    <div>
                        <small>Estado unidad</small>
                        <br>
                        <strong>
                            <?= e($unidad['estado']) ?>
                        </strong>
                    </div>

                </div>

            </div>

        </div>


        <br>


        <!-- ======================================================
             RESUMEN
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Resumen financiero
                </h3>

                <br>

                <div class="tabla-responsive">

                    <table class="tabla">

                        <thead>

                            <tr>

                                <th>
                                    Obligaciones
                                </th>

                                <th>
                                    Valor original
                                </th>

                                <th>
                                    Valor pagado
                                </th>

                                <th>
                                    Saldo pendiente
                                </th>

                                <th>
                                    Saldo vencido
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <tr>

                                <td>
                                    <?= (int)($resumen['obligaciones'] ?? 0) ?>
                                </td>

                                <td>
                                    <?= dinero(
                                        $resumen['valor_original'] ?? 0
                                    ) ?>
                                </td>

                                <td>
                                    <?= dinero(
                                        $resumen['valor_pagado'] ?? 0
                                    ) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= dinero(
                                            $resumen['saldo'] ?? 0
                                        ) ?>
                                    </strong>
                                </td>

                                <td>

                                    <?php if (
                                        (float)($resumen['saldo_vencido'] ?? 0) > 0
                                    ): ?>

                                        <span class="inactivo">
                                            <?= dinero(
                                                $resumen['saldo_vencido']
                                            ) ?>
                                        </span>

                                    <?php else: ?>

                                        <?= dinero(0) ?>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <br>


        <!-- ======================================================
             CARTERA
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Obligaciones
                </h3>

                <br>

                <div class="tabla-responsive">

                    <table class="tabla">

                        <thead>

                            <tr>

                                <th>Período</th>
                                <th>Factura</th>
                                <th>Concepto</th>
                                <th>Valor original</th>
                                <th>Pagado</th>
                                <th>Saldo</th>
                                <th>Vencimiento</th>
                                <th>Estado</th>
                                <th>Acción</th>

                            </tr>

                        </thead>

                        <tbody>


                        <?php if (empty($obligaciones)): ?>

                            <tr>

                                <td
                                    colspan="9"
                                    align="center"
                                >
                                    La unidad no tiene obligaciones registradas.
                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach ($obligaciones as $fila): ?>

                                <tr>

                                    <td>

                                        <?= e(
                                            date(
                                                'm/Y',
                                                strtotime(
                                                    $fila['periodo']
                                                )
                                            )
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $fila['numero_factura']
                                            ?? '-'
                                        ) ?>

                                    </td>


                                    <td>

                                        <strong>
                                            <?= (int)$fila['cantidad_conceptos'] ?>
                                            concepto<?= (int)$fila['cantidad_conceptos'] === 1 ? '' : 's' ?>
                                        </strong>

                                        <br>

                                        <small>
                                            <?= e($fila['conceptos'] ?? '') ?>
                                        </small>

                                        <?php if (
                                            !empty($fila['descripciones'])
                                        ): ?>

                                            <br>

                                            <small>
                                                <?= e($fila['descripciones']) ?>
                                            </small>

                                        <?php endif; ?>

                                    </td>


                                    <td>
                                        <?= dinero(
                                            $fila['valor_original']
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= dinero(
                                            $fila['valor_pagado']
                                        ) ?>
                                    </td>


                                    <td>

                                        <strong>

                                            <?= dinero(
                                                $fila['saldo']
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?= e(
                                            date(
                                                'd/m/Y',
                                                strtotime(
                                                    $fila['fecha_vencimiento']
                                                )
                                            )
                                        ) ?>


                                        <?php if (
                                            (int)$fila['vencida'] === 1
                                        ): ?>

                                            <br>

                                            <span class="inactivo">
                                                VENCIDA
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            $fila['estado'] === 'PAGADA'
                                        ): ?>

                                            <span class="activo">
                                                PAGADA
                                            </span>

                                        <?php elseif (
                                            $fila['estado'] === 'ANULADA'
                                        ): ?>

                                            <span class="inactivo">
                                                ANULADA
                                            </span>

                                        <?php else: ?>

                                            PENDIENTE

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            !empty(
                                                $fila['id_factura']
                                            )
                                        ): ?>

                                            <a
                                                href="<?= BASE_URL ?>configuracion/factura_detalle.php?id=<?= (int)$fila['id_factura'] ?>&origen=cartera_detalle&id_unidad=<?= (int)$idUnidad ?>"
                                                class="btn-secondary"
                                            >
                                                Ver factura
                                            </a>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <br>


        <!-- ======================================================
             SALDOS A FAVOR
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Saldos a favor
                </h3>

                <br>

                <div class="tabla-responsive">

                    <table class="tabla">

                        <thead>

                            <tr>

                                <th>Fecha</th>
                                <th>Pago origen</th>
                                <th>Valor original</th>
                                <th>Utilizado</th>
                                <th>Disponible</th>
                                <th>Estado</th>
                                <th>Acción</th>

                            </tr>

                        </thead>

                        <tbody>


                        <?php if (empty($saldosFavor)): ?>

                            <tr>

                                <td
                                    colspan="7"
                                    align="center"
                                >
                                    La unidad no tiene saldos a favor registrados.
                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach ($saldosFavor as $saldoFavor): ?>

                                <tr>

                                    <td>
                                        <?= e(
                                            date(
                                                'd/m/Y',
                                                strtotime(
                                                    $saldoFavor['fecha_generacion']
                                                )
                                            )
                                        ) ?>
                                    </td>


                                    <td>

                                        #<?= (int)$saldoFavor['id_pago'] ?>

                                        <?php if (
                                            !empty(
                                                $saldoFavor['referencia']
                                            )
                                        ): ?>

                                            <br>

                                            <small>
                                                <?= e(
                                                    $saldoFavor['referencia']
                                                ) ?>
                                            </small>

                                        <?php endif; ?>

                                    </td>


                                    <td>
                                        <?= dinero(
                                            $saldoFavor['valor_original']
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= dinero(
                                            $saldoFavor['valor_utilizado']
                                        ) ?>
                                    </td>


                                    <td>
                                        <strong>
                                            <?= dinero(
                                                $saldoFavor['saldo_disponible']
                                            ) ?>
                                        </strong>
                                    </td>


                                    <td>
                                        <?= e(
                                            $saldoFavor['estado']
                                        ) ?>
                                    </td>


                                    <td>

                                        <?php if (
                                            $saldoFavor['estado'] === 'DISPONIBLE' &&
                                            (float)$saldoFavor['saldo_disponible'] > 0
                                        ): ?>

                                            <a
                                                href="<?= BASE_URL ?>configuracion/aplicar_saldo_favor.php?id_saldo_favor=<?= (int)$saldoFavor['id_saldo_favor'] ?>"
                                                class="btn-secondary"
                                            >
                                                Aplicar saldo
                                            </a>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <br>


        <!-- ======================================================
             PAGOS
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Pagos registrados
                </h3>

                <br>

                <div class="tabla-responsive">

                    <table class="tabla">

                        <thead>

                            <tr>

                                <th>Fecha</th>
                                <th>Referencia</th>
                                <th>Medio</th>
                                <th>Valor</th>
                                <th>Aplicado</th>
                                <th>Disponible</th>
                                <th>Conciliación</th>
                                <th>Estado</th>
                                <th>Acción</th>

                            </tr>

                        </thead>

                        <tbody>


                        <?php if (empty($pagos)): ?>

                            <tr>

                                <td
                                    colspan="9"
                                    align="center"
                                >
                                    No existen pagos registrados para esta unidad.
                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach ($pagos as $pago): ?>

                                <tr>

                                    <td>

                                        <?= e(
                                            date(
                                                'd/m/Y',
                                                strtotime(
                                                    $pago['fecha_pago']
                                                )
                                            )
                                        ) ?>

                                    </td>


                                    <td>
                                        <?= e(
                                            $pago['referencia']
                                            ?? '-'
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= e(
                                            $pago['medio_pago']
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= dinero(
                                            $pago['valor']
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= dinero(
                                            $pago['valor_aplicado']
                                        ) ?>
                                    </td>


                                    <td>

                                        <strong>

                                            <?= dinero(
                                                $pago['valor_disponible']
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>
                                        <?= e(
                                            $pago['estado_conciliacion']
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= e(
                                            $pago['estado']
                                        ) ?>
                                    </td>

                                    <td>

                                        <?php if (
                                            $pago['estado'] === 'REGISTRADO' &&
                                            (float)$pago['valor_disponible'] > 0
                                        ): ?>

                                            <a
                                                href="<?= BASE_URL ?>configuracion/aplicar_pagos.php?id_pago=<?= (int)$pago['id_pago'] ?>"
                                                class="btn-secondary"
                                            >
                                                Aplicar pago
                                            </a>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <br>


        <!-- ======================================================
             APLICACIONES
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Aplicaciones de pagos
                </h3>

                <br>

                <div class="tabla-responsive">

                    <table class="tabla">

                        <thead>

                            <tr>

                                <th>Fecha aplicación</th>
                                <th>Pago</th>
                                <th>Factura</th>
                                <th>Período</th>
                                <th>Concepto</th>
                                <th>Valor aplicado</th>
                                <th>Tipo</th>

                            </tr>

                        </thead>

                        <tbody>


                        <?php if (empty($aplicaciones)): ?>

                            <tr>

                                <td
                                    colspan="7"
                                    align="center"
                                >
                                    Todavía no existen aplicaciones de pago.
                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach ($aplicaciones as $aplicacion): ?>

                                <tr>

                                    <td>

                                        <?= e(
                                            date(
                                                'd/m/Y H:i',
                                                strtotime(
                                                    $aplicacion[
                                                        'fecha_aplicacion'
                                                    ]
                                                )
                                            )
                                        ) ?>

                                    </td>


                                    <td>

                                        #<?= (int)$aplicacion['id_pago'] ?>

                                        <?php if (
                                            !empty(
                                                $aplicacion['referencia']
                                            )
                                        ): ?>

                                            <br>

                                            <small>
                                                <?= e(
                                                    $aplicacion['referencia']
                                                ) ?>
                                            </small>

                                        <?php endif; ?>

                                    </td>


                                    <td>
                                        <?= e(
                                            $aplicacion['numero_factura']
                                            ?? '-'
                                        ) ?>
                                    </td>


                                    <td>

                                        <?= e(
                                            date(
                                                'm/Y',
                                                strtotime(
                                                    $aplicacion['periodo']
                                                )
                                            )
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $aplicacion['concepto']
                                            ?? $aplicacion['cartera_descripcion']
                                            ?? 'Obligación'
                                        ) ?>

                                    </td>


                                    <td>

                                        <strong>

                                            <?= dinero(
                                                $aplicacion['valor_aplicado']
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>
                                        <?= e(
                                            $aplicacion['tipo_aplicacion']
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </div>

        </div>


    </main>


</div>


</body>

</html>
