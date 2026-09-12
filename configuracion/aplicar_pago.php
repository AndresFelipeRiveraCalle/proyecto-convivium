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
// VALIDAR PAGO
// ==========================================================

$idPago =
    isset($_GET['id_pago'])
        ? (int)$_GET['id_pago']
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


// ==========================================================
// PAGO
// ==========================================================

$sqlPago = "
    SELECT
        p.id_pago,
        p.id_unidad,
        p.fecha_pago,
        p.valor,
        p.medio_pago,
        p.origen_pago,
        p.estado_conciliacion,
        p.referencia,
        p.referencia_externa,
        p.estado,

        u.codigo AS unidad_codigo,
        u.nombre AS unidad_nombre,

        dtu.nombre_grupo,

        COALESCE(
            SUM(ap.valor_aplicado),
            0
        ) AS valor_aplicado

    FROM pagos p

    INNER JOIN unidades u
        ON u.id_unidad =
           p.id_unidad

    LEFT JOIN detalle_tipos_unidad dtu
        ON dtu.id_tipo_config =
           u.id_tipo_config

    LEFT JOIN aplicaciones_pagos ap
        ON ap.id_pago =
           p.id_pago

    WHERE
        p.id_pago =
            :id_pago

    GROUP BY
        p.id_pago,
        p.id_unidad,
        p.fecha_pago,
        p.valor,
        p.medio_pago,
        p.origen_pago,
        p.estado_conciliacion,
        p.referencia,
        p.referencia_externa,
        p.estado,
        u.codigo,
        u.nombre,
        dtu.nombre_grupo

    LIMIT 1
";


$stmtPago = $conexion->prepare($sqlPago);

$stmtPago->execute([
    ':id_pago' => $idPago
]);

$pago = $stmtPago->fetch(PDO::FETCH_ASSOC);


if (!$pago) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/cartera.php?" .
        http_build_query([
            'tipo'  => 'error',
            'texto' => 'El pago seleccionado no existe.'
        ])
    );

    exit;
}


$valorDisponible =
    round(
        (float)$pago['valor'] -
        (float)$pago['valor_aplicado'],
        2
    );


// ==========================================================
// OBLIGACIONES PENDIENTES
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
        c.estado,

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


$stmtCartera =
    $conexion->prepare($sqlCartera);


$stmtCartera->execute([
    ':id_unidad'
        => (int)$pago['id_unidad']
]);


$obligaciones =
    $stmtCartera->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// APLICACIONES YA REALIZADAS
// ==========================================================

$sqlAplicaciones = "
    SELECT
        ap.id_aplicacion,
        ap.id_cartera,
        ap.valor_aplicado,
        ap.fecha_aplicacion,
        ap.tipo_aplicacion,
        ap.observaciones,

        c.descripcion AS cartera_descripcion,

        f.numero_factura,

        cf.nombre AS concepto

    FROM aplicaciones_pagos ap

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

    WHERE ap.id_pago =
        :id_pago

    ORDER BY
        ap.fecha_aplicacion,
        ap.id_aplicacion
";


$stmtAplicaciones =
    $conexion->prepare($sqlAplicaciones);


$stmtAplicaciones->execute([
    ':id_pago'
        => $idPago
]);


$aplicaciones =
    $stmtAplicaciones->fetchAll(
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
                    Aplicar pago
                </h2>

                <p>
                    Unidad <?= e($pago['unidad_codigo']) ?>
                </p>

            </div>


            <div>

                <a
                    href="<?= BASE_URL ?>configuracion/cartera_detalle.php?id_unidad=<?= (int)$pago['id_unidad'] ?>"
                    class="btn-limpiar"
                >
                    ← Volver a cartera
                </a>

            </div>

        </div>


        <br>


        <!-- ======================================================
             MENSAJES
        ======================================================= -->

        <?php if (
            !empty($_GET['texto'])
        ): ?>

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
             PAGO
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Información del pago
                </h3>

                <br>

                <div class="tabla-responsive">

                    <table class="tabla">

                        <thead>

                            <tr>

                                <th>Pago</th>
                                <th>Fecha</th>
                                <th>Unidad</th>
                                <th>Referencia</th>
                                <th>Valor</th>
                                <th>Aplicado</th>
                                <th>Disponible</th>
                                <th>Estado</th>

                            </tr>

                        </thead>

                        <tbody>

                            <tr>

                                <td>
                                    #<?= (int)$pago['id_pago'] ?>
                                </td>

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
                                    <?= e($pago['unidad_codigo']) ?>
                                </td>

                                <td>
                                    <?= e(
                                        $pago['referencia']
                                        ?? '-'
                                    ) ?>
                                </td>

                                <td>
                                    <?= dinero($pago['valor']) ?>
                                </td>

                                <td>
                                    <?= dinero(
                                        $pago['valor_aplicado']
                                    ) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= dinero($valorDisponible) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= e($pago['estado']) ?>
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
                    El sistema aplicará el saldo disponible primero
                    a las obligaciones con vencimiento más antiguo.
                </p>

                <br>


                <?php if (
                    $valorDisponible > 0 &&
                    !empty($obligaciones) &&
                    $pago['estado'] === 'REGISTRADO'
                ): ?>

                    <form
                        method="POST"
                        action="<?= BASE_URL ?>actions/aplicar_pago_automatico.php"
                        onsubmit="return confirm('¿Desea aplicar automáticamente el saldo disponible del pago?');"
                    >

                        <input
                            type="hidden"
                            name="id_pago"
                            value="<?= (int)$idPago ?>"
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
                                <th>Valor original</th>
                                <th>Pagado</th>
                                <th>Saldo</th>
                                <th>Aplicar</th>

                            </tr>

                        </thead>

                        <tbody>


                        <?php if (empty($obligaciones)): ?>

                            <tr>

                                <td
                                    colspan="8"
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
                                                <?= e(
                                                    $fila['descripcion']
                                                ) ?>
                                            </small>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            date(
                                                'd/m/Y',
                                                strtotime(
                                                    $fila[
                                                        'fecha_vencimiento'
                                                    ]
                                                )
                                            )
                                        ) ?>

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

                                        <?php if (
                                            $valorDisponible > 0 &&
                                            $pago['estado'] === 'REGISTRADO'
                                        ): ?>

                                            <form
                                                method="POST"
                                                action="<?= BASE_URL ?>actions/aplicar_pago_manual.php"
                                                style="
                                                    display:flex;
                                                    gap:6px;
                                                    align-items:center;
                                                "
                                            >

                                                <input
                                                    type="hidden"
                                                    name="id_pago"
                                                    value="<?= (int)$idPago ?>"
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
                                                            $valorDisponible,
                                                            (float)$fila['saldo']
                                                        )
                                                    ) ?>"
                                                    value="<?= e(
                                                        min(
                                                            $valorDisponible,
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
                    Aplicaciones realizadas con este pago
                </h3>

                <br>

                <div class="tabla-responsive">

                    <table class="tabla">

                        <thead>

                            <tr>

                                <th>Fecha</th>
                                <th>Factura</th>
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
                                    colspan="6"
                                    align="center"
                                >
                                    Este pago todavía no tiene aplicaciones.
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
                                        <?= e(
                                            $aplicacion[
                                                'numero_factura'
                                            ] ?? '-'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $aplicacion[
                                                'concepto'
                                            ]
                                            ?? $aplicacion[
                                                'cartera_descripcion'
                                            ]
                                            ?? 'Obligación'
                                        ) ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= dinero(
                                                $aplicacion[
                                                    'valor_aplicado'
                                                ]
                                            ) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= e(
                                            $aplicacion[
                                                'tipo_aplicacion'
                                            ]
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $aplicacion[
                                                'observaciones'
                                            ] ?? ''
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
