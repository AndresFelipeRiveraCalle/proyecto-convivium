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
// VALIDAR SALDO FAVOR
// ==========================================================

$idSaldoFavor =
    isset($_GET['id_saldo_favor'])
        ? (int)$_GET['id_saldo_favor']
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


// ==========================================================
// DATOS SALDO FAVOR
// ==========================================================

$sqlSaldo = "
    SELECT
        sf.id_saldo_favor,
        sf.id_unidad,
        sf.id_pago,
        sf.valor_original,
        sf.valor_utilizado,
        sf.saldo_disponible,
        sf.estado,
        sf.fecha_generacion,
        sf.fecha_ultimo_uso,
        sf.observaciones,

        p.fecha_pago,
        p.referencia,

        u.codigo AS unidad_codigo,
        u.nombre AS unidad_nombre,

        dtu.nombre_grupo

    FROM saldo_favor sf

    INNER JOIN unidades u
        ON u.id_unidad =
           sf.id_unidad

    LEFT JOIN detalle_tipos_unidad dtu
        ON dtu.id_tipo_config =
           u.id_tipo_config

    INNER JOIN pagos p
        ON p.id_pago =
           sf.id_pago

    WHERE
        sf.id_saldo_favor =
            :id_saldo_favor

    LIMIT 1
";


$stmtSaldo = $conexion->prepare($sqlSaldo);

$stmtSaldo->execute([
    ':id_saldo_favor'
        => $idSaldoFavor
]);

$saldoFavor = $stmtSaldo->fetch(PDO::FETCH_ASSOC);


if (!$saldoFavor) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/cartera.php?" .
        http_build_query([
            'tipo'  => 'error',
            'texto' => 'El saldo a favor seleccionado no existe.'
        ])
    );

    exit;
}


// ==========================================================
// CARTERA PENDIENTE
// ==========================================================

$sqlCartera = "
    SELECT
        c.id_cartera,
        c.id_factura,
        c.periodo,
        c.descripcion,
        c.valor_original,
        c.valor_pagado,
        c.saldo,
        c.fecha_vencimiento,

        f.numero_factura,

        cf.nombre AS concepto,

        tobl.nombre AS tipo_obligacion

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

        AND c.estado =
            'PENDIENTE'

        AND c.saldo > 0

    ORDER BY
        c.fecha_vencimiento ASC,
        c.periodo ASC,
        c.id_cartera ASC
";


$stmtCartera = $conexion->prepare($sqlCartera);

$stmtCartera->execute([
    ':id_unidad'
        => (int)$saldoFavor['id_unidad']
]);

$obligaciones =
    $stmtCartera->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// HISTORIAL DE APLICACIONES
// ==========================================================

$sqlAplicaciones = "
    SELECT
        asf.id_aplicacion_saldo,
        asf.id_cartera,
        asf.valor_aplicado,
        asf.fecha_aplicacion,
        asf.tipo_aplicacion,
        asf.observaciones,

        c.periodo,
        c.descripcion AS cartera_descripcion,

        f.numero_factura,

        cf.nombre AS concepto

    FROM aplicaciones_saldo_favor asf

    INNER JOIN cartera c
        ON c.id_cartera =
           asf.id_cartera

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
        asf.id_saldo_favor =
            :id_saldo_favor

    ORDER BY
        asf.fecha_aplicacion,
        asf.id_aplicacion_saldo
";


$stmtAplicaciones =
    $conexion->prepare($sqlAplicaciones);

$stmtAplicaciones->execute([
    ':id_saldo_favor'
        => $idSaldoFavor
]);

$aplicaciones =
    $stmtAplicaciones->fetchAll(PDO::FETCH_ASSOC);

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
                    Aplicar saldo a favor
                </h2>

                <p>
                    Unidad <?= e($saldoFavor['unidad_codigo']) ?>
                </p>

            </div>


            <div>

                <a
                    href="<?= BASE_URL ?>configuracion/cartera_detalle.php?id_unidad=<?= (int)$saldoFavor['id_unidad'] ?>"
                    class="btn-limpiar"
                >
                    ← Volver a cartera
                </a>

            </div>

        </div>


        <br>


        <?php if (!empty($_GET['texto'])): ?>

            <div class="info-box">

                <strong>
                    <?= e(
                        strtoupper(
                            $_GET['tipo'] ?? 'info'
                        )
                    ) ?>
                </strong>

                <p>
                    <?= e($_GET['texto']) ?>
                </p>

            </div>

            <br>

        <?php endif; ?>


        <!-- ======================================================
             RESUMEN SALDO FAVOR
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Saldo a favor
                </h3>

                <br>

                <div class="tabla-responsive">

                    <table class="tabla">

                        <thead>

                            <tr>

                                <th>ID</th>
                                <th>Pago origen</th>
                                <th>Fecha</th>
                                <th>Valor original</th>
                                <th>Utilizado</th>
                                <th>Disponible</th>
                                <th>Estado</th>

                            </tr>

                        </thead>

                        <tbody>

                            <tr>

                                <td>
                                    #<?= (int)$saldoFavor['id_saldo_favor'] ?>
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
                                            <?= e($saldoFavor['referencia']) ?>
                                        </small>

                                    <?php endif; ?>

                                </td>

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
                                    <?= e($saldoFavor['estado']) ?>
                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <br>


        <!-- ======================================================
             AUTOMÁTICA
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Aplicación automática
                </h3>

                <p>
                    Se aplicará primero a las obligaciones con vencimiento más antiguo.
                </p>

                <br>

                <?php if (
                    $saldoFavor['estado'] === 'DISPONIBLE' &&
                    (float)$saldoFavor['saldo_disponible'] > 0 &&
                    !empty($obligaciones)
                ): ?>

                    <form
                        method="POST"
                        action="<?= BASE_URL ?>actions/aplicar_saldo_favor_automatico.php"
                        onsubmit="return confirm('¿Desea aplicar automáticamente este saldo a favor?');"
                    >

                        <input
                            type="hidden"
                            name="id_saldo_favor"
                            value="<?= (int)$idSaldoFavor ?>"
                        >

                        <button
                            type="submit"
                            class="btn-filtrar"
                        >
                            Aplicar automáticamente
                        </button>

                    </form>

                <?php else: ?>

                    <p>
                        No existe saldo disponible o no hay obligaciones pendientes.
                    </p>

                <?php endif; ?>

            </div>

        </div>


        <br>


        <!-- ======================================================
             MANUAL
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Aplicación manual
                </h3>

                <br>

                <div class="tabla-responsive">

                    <table class="tabla">

                        <thead>

                            <tr>

                                <th>Factura</th>
                                <th>Período</th>
                                <th>Concepto</th>
                                <th>Vencimiento</th>
                                <th>Saldo</th>
                                <th>Aplicar</th>

                            </tr>

                        </thead>

                        <tbody>


                        <?php if (empty($obligaciones)): ?>

                            <tr>

                                <td
                                    colspan="6"
                                    align="center"
                                >
                                    La unidad no tiene obligaciones pendientes.
                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach ($obligaciones as $fila): ?>

                                <tr>

                                    <td>
                                        <?= e(
                                            $fila['numero_factura']
                                            ?? '-'
                                        ) ?>
                                    </td>


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

                                        <strong>

                                            <?= e(
                                                $fila['concepto']
                                                ?? $fila['tipo_obligacion']
                                                ?? 'Obligación'
                                            ) ?>

                                        </strong>

                                        <?php if (
                                            !empty(
                                                $fila['descripcion']
                                            )
                                        ): ?>

                                            <br>

                                            <small>
                                                <?= e($fila['descripcion']) ?>
                                            </small>

                                        <?php endif; ?>

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
                                    </td>


                                    <td>
                                        <strong>
                                            <?= dinero($fila['saldo']) ?>
                                        </strong>
                                    </td>


                                    <td>

                                        <?php if (
                                            $saldoFavor['estado'] === 'DISPONIBLE' &&
                                            (float)$saldoFavor['saldo_disponible'] > 0
                                        ): ?>

                                            <form
                                                method="POST"
                                                action="<?= BASE_URL ?>actions/aplicar_saldo_favor_manual.php"
                                                style="
                                                    display:flex;
                                                    gap:6px;
                                                    align-items:center;
                                                "
                                            >

                                                <input
                                                    type="hidden"
                                                    name="id_saldo_favor"
                                                    value="<?= (int)$idSaldoFavor ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id_cartera"
                                                    value="<?= (int)$fila['id_cartera'] ?>"
                                                >

                                                <input
                                                    type="number"
                                                    name="valor_aplicado"
                                                    step="0.01"
                                                    min="0.01"
                                                    max="<?= e(
                                                        min(
                                                            (float)$saldoFavor['saldo_disponible'],
                                                            (float)$fila['saldo']
                                                        )
                                                    ) ?>"
                                                    value="<?= e(
                                                        min(
                                                            (float)$saldoFavor['saldo_disponible'],
                                                            (float)$fila['saldo']
                                                        )
                                                    ) ?>"
                                                    required
                                                    style="min-width:135px;"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn-secondary"
                                                >
                                                    Aplicar
                                                </button>

                                            </form>

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
             HISTORIAL
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Historial de aplicaciones
                </h3>

                <br>

                <div class="tabla-responsive">

                    <table class="tabla">

                        <thead>

                            <tr>

                                <th>Fecha</th>
                                <th>Factura</th>
                                <th>Período</th>
                                <th>Concepto</th>
                                <th>Valor aplicado</th>
                                <th>Tipo</th>
                                <th>Observación</th>

                            </tr>

                        </thead>

                        <tbody>


                        <?php if (empty($aplicaciones)): ?>

                            <tr>

                                <td
                                    colspan="7"
                                    align="center"
                                >
                                    Este saldo a favor todavía no tiene aplicaciones.
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
                                                    $aplicacion['fecha_aplicacion']
                                                )
                                            )
                                        ) ?>
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

                                    <td>
                                        <?= e(
                                            $aplicacion['observaciones']
                                            ?? ''
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
