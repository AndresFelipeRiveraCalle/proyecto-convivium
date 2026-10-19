<?php
/**
 * ==============================================================
 * CONVIVIUM - DETALLE DE CARTERA POR UNIDAD
 * ==============================================================
 * Archivo: configuracion/cartera_detalle.php
 *
 * Objetivo:
 * - Mostrar la cartera completa de una unidad.
 * - Agrupar obligaciones por factura.
 * - Separar visualmente capital/otros conceptos e intereses de mora.
 * - Mostrar pagos recibidos, aplicaciones realizadas y saldos a favor.
 * - Permitir usar pagos que todavía tengan valor disponible.
 *
 * Regla clave:
 * La deuda vive en `cartera` y cada abono queda registrado en
 * `aplicaciones_pagos`. Por eso una obligación puede recibir varios pagos
 * diferentes hasta que su saldo llegue a cero.
 *
 * La mora se identifica mediante `facturas_detalle.id_interes`.
 * Si ese campo tiene valor, la obligación corresponde a intereses.
 * ==============================================================
 */


require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// FUNCIONES DE PRESENTACIÓN
// ==========================================================
// e(): salida HTML segura.
// dinero(): formato monetario uniforme para toda la pantalla.

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
// 1. VALIDAR LA UNIDAD SOLICITADA
// ==========================================================
// Toda la pantalla depende de id_unidad. Si no es válido regresamos a cartera.php.

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
// 2. DATOS GENERALES DE LA UNIDAD
// ==========================================================
// Recuperamos información descriptiva para el encabezado de la cartera.

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
// 3. RESUMEN FINANCIERO DE LA UNIDAD
// ==========================================================
// Calcula total original, pagado, saldo y saldo vencido.
// No incluye obligaciones anuladas.

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
// 4. OBLIGACIONES AGRUPADAS POR FACTURA
// ==========================================================
// Una factura puede generar varias filas en `cartera` (una por detalle).
// Aquí se agrupan para presentar una sola fila resumen por factura.

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

        SUM(
            CASE
                WHEN fd.id_interes IS NOT NULL
                THEN 1
                ELSE 0
            END
        ) AS cantidad_intereses,

        COALESCE(
            SUM(
                CASE
                    WHEN fd.id_interes IS NOT NULL
                    THEN c.valor_original
                    ELSE 0
                END
            ),
            0
        ) AS valor_intereses,

        COALESCE(
            SUM(
                CASE
                    WHEN fd.id_interes IS NOT NULL
                    THEN c.valor_pagado
                    ELSE 0
                END
            ),
            0
        ) AS pagado_intereses,

        COALESCE(
            SUM(
                CASE
                    WHEN fd.id_interes IS NOT NULL
                    THEN c.saldo
                    ELSE 0
                END
            ),
            0
        ) AS saldo_intereses,

        COALESCE(
            SUM(
                CASE
                    WHEN fd.id_interes IS NULL
                    THEN c.valor_original
                    ELSE 0
                END
            ),
            0
        ) AS valor_capital_otros,

        COALESCE(
            SUM(
                CASE
                    WHEN fd.id_interes IS NULL
                    THEN c.saldo
                    ELSE 0
                END
            ),
            0
        ) AS saldo_capital_otros,

        GROUP_CONCAT(
            DISTINCT
            CASE
                WHEN fd.id_interes IS NOT NULL
                THEN CONCAT(
                    COALESCE(
                        cf.nombre,
                        'Intereses de mora'
                    ),
                    ' [MORA]'
                )
                ELSE COALESCE(
                    cf.nombre,
                    tobl.nombre,
                    c.descripcion
                )
            END
            ORDER BY c.id_cartera
            SEPARATOR ' | '
        ) AS conceptos,

        GROUP_CONCAT(
            DISTINCT c.descripcion
            ORDER BY c.id_cartera
            SEPARATOR ' | '
        ) AS descripciones,

        GROUP_CONCAT(
            DISTINCT
            CASE
                WHEN fd.id_interes IS NOT NULL
                THEN CONCAT(
                    'Mora sobre cartera #',
                    ic.id_cartera,
                    ' · Base ',
                    FORMAT(ic.valor_base, 2),
                    ' · Tasa ',
                    FORMAT(ic.tasa_interes, 2),
                    '% · Período ',
                    DATE_FORMAT(ic.periodo_interes, '%m/%Y'),
                    CASE
                        WHEN cfo.nombre IS NOT NULL
                        THEN CONCAT(
                            ' · Origen: ',
                            cfo.nombre
                        )
                        ELSE ''
                    END
                )
                ELSE NULL
            END
            ORDER BY c.id_cartera
            SEPARATOR ' | '
        ) AS detalle_mora,

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

            WHEN
                SUM(c.valor_pagado) > 0.009
                AND SUM(c.saldo) > 0.009
            THEN 'PARCIAL'

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

    LEFT JOIN intereses_cartera ic
        ON ic.id_interes =
           fd.id_interes

    LEFT JOIN cartera cartera_origen
        ON cartera_origen.id_cartera =
           ic.id_cartera

    LEFT JOIN facturas_detalle fd_origen
        ON fd_origen.id_detalle =
           cartera_origen.id_detalle

    LEFT JOIN conceptos_facturacion cfo
        ON cfo.id_concepto =
           fd_origen.id_concepto

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
// 5. DETALLE INDIVIDUAL DE OBLIGACIONES
// ==========================================================
// Este bloque permite ver cada concepto por separado y distinguir mora de capital.
// fd.id_interes != NULL significa que la obligación proviene de intereses de mora.
// ==========================================================

$sqlDetalleObligaciones = "
    SELECT
        c.id_cartera,
        c.id_factura,
        c.id_detalle,
        c.periodo,
        c.descripcion AS cartera_descripcion,
        c.valor_original,
        c.valor_pagado,
        c.saldo,
        c.fecha_vencimiento,
        c.estado,

        fd.id_concepto,
        fd.id_interes,
        fd.descripcion AS detalle_descripcion,

        cf.nombre AS concepto_nombre,

        ic.id_interes AS interes_id,
        ic.id_cartera AS id_cartera_origen,
        ic.periodo_interes,
        ic.tasa_interes,
        ic.valor_base,
        ic.valor_interes,

        co.descripcion AS descripcion_origen,
        cfo.nombre AS concepto_origen

    FROM cartera c

    LEFT JOIN facturas_detalle fd
        ON fd.id_detalle = c.id_detalle

    LEFT JOIN conceptos_facturacion cf
        ON cf.id_concepto = fd.id_concepto

    LEFT JOIN intereses_cartera ic
        ON ic.id_interes = fd.id_interes

    LEFT JOIN cartera co
        ON co.id_cartera = ic.id_cartera

    LEFT JOIN facturas_detalle fdo
        ON fdo.id_detalle = co.id_detalle

    LEFT JOIN conceptos_facturacion cfo
        ON cfo.id_concepto = fdo.id_concepto

    WHERE
        c.id_unidad = :id_unidad

    ORDER BY
        c.periodo DESC,
        c.fecha_vencimiento DESC,
        c.id_factura DESC,
        CASE
            WHEN fd.id_interes IS NOT NULL THEN 2
            ELSE 1
        END,
        c.id_cartera
";

$stmtDetalleObligaciones =
    $conexion->prepare(
        $sqlDetalleObligaciones
    );

$stmtDetalleObligaciones->execute([
    ':id_unidad' => $idUnidad
]);

$detalleObligaciones =
    $stmtDetalleObligaciones->fetchAll(
        PDO::FETCH_ASSOC
    );

$detallePorFactura = [];

foreach ($detalleObligaciones as $detalleObligacion) {

    $claveFactura =
        !empty($detalleObligacion['id_factura'])
            ? 'factura_' . (int)$detalleObligacion['id_factura']
            : 'cartera_' . (int)$detalleObligacion['id_cartera'];

    if (!isset($detallePorFactura[$claveFactura])) {
        $detallePorFactura[$claveFactura] = [];
    }

    $detallePorFactura[$claveFactura][] =
        $detalleObligacion;
}


// ==========================================================
// 6. PAGOS RECIBIDOS POR LA UNIDAD
// ==========================================================
// `valor_disponible` se obtiene restando al valor del pago las aplicaciones realizadas.
// Si queda disponible, ese mismo pago todavía puede distribuirse a otras obligaciones.
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

        COALESCE(
            (
                SELECT SUM(sf.valor_original)
                FROM saldo_favor sf
                WHERE
                    sf.id_pago = p.id_pago
                    AND sf.estado <> 'ANULADO'
            ),
            0
        ) AS valor_saldo_favor,

        (
            p.valor -
            COALESCE(
                SUM(ap.valor_aplicado),
                0
            ) -
            COALESCE(
                (
                    SELECT SUM(sf.valor_original)
                    FROM saldo_favor sf
                    WHERE
                        sf.id_pago = p.id_pago
                        AND sf.estado <> 'ANULADO'
                ),
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
// 7. HISTORIAL DE APLICACIONES
// ==========================================================
// Cada fila indica qué pago afectó qué obligación y por qué valor. DE PAGOS
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

    <style>
        .mora-badge {
            display: inline-block;
            margin-top: 5px;
            padding: 3px 8px;
            border-radius: 999px;
            background: #fff3cd;
            color: #7a5300;
            border: 1px solid #ffe08a;
            font-size: 12px;
            font-weight: 700;
        }

        .mora-detalle {
            margin-top: 6px;
            padding: 8px 10px;
            border-left: 3px solid #d39e00;
            background: #fffaf0;
            color: #6b4b00;
            font-size: 12px;
            line-height: 1.45;
            border-radius: 4px;
        }

        .mora-desglose {
            margin-top: 6px;
            font-size: 12px;
            line-height: 1.45;
            color: #475569;
        }


        .detalle-obligaciones {
            margin-top: 10px;
            padding: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }

        .detalle-obligaciones table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .detalle-obligaciones th,
        .detalle-obligaciones td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .detalle-obligaciones th {
            text-align: left;
            color: #475569;
            font-size: 12px;
        }

        .detalle-obligaciones tr:last-child td {
            border-bottom: none;
        }

        .detalle-mora-fila {
            background: #fffaf0;
        }

        .detalle-capital-fila {
            background: #ffffff;
        }

        .etiqueta-capital,
        .etiqueta-mora {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .etiqueta-capital {
            background: #eef2ff;
            color: #3730a3;
        }

        .etiqueta-mora {
            background: #fff3cd;
            color: #7a5300;
        }

        .obligacion-descripcion {
            margin-top: 3px;
            color: #64748b;
            font-size: 12px;
            line-height: 1.4;
        }
    </style>

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

                                        <?php if (
                                            (int)($fila['cantidad_intereses'] ?? 0) > 0
                                        ): ?>

                                            <br>

                                            <span class="mora-badge">
                                                MORA
                                            </span>

                                            <div class="mora-desglose">
                                                Capital / otros:
                                                <strong>
                                                    <?= dinero($fila['valor_capital_otros'] ?? 0) ?>
                                                </strong>
                                                · Intereses:
                                                <strong>
                                                    <?= dinero($fila['valor_intereses'] ?? 0) ?>
                                                </strong>
                                                <br>
                                                Saldo capital / otros:
                                                <strong>
                                                    <?= dinero($fila['saldo_capital_otros'] ?? 0) ?>
                                                </strong>
                                                · Saldo mora:
                                                <strong>
                                                    <?= dinero($fila['saldo_intereses'] ?? 0) ?>
                                                </strong>
                                            </div>

                                            <?php if (
                                                !empty($fila['detalle_mora'])
                                            ): ?>

                                                <div class="mora-detalle">
                                                    <?= e($fila['detalle_mora']) ?>
                                                </div>

                                            <?php endif; ?>

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

                                        <?php elseif (
                                            $fila['estado'] === 'PARCIAL'
                                        ): ?>

                                            <span style="font-weight:700; color:#9a6700;">
                                                PARCIAL
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

                                <?php
                                    $claveDetalleFactura =
                                        !empty($fila['id_factura'])
                                            ? 'factura_' . (int)$fila['id_factura']
                                            : '';

                                    $detallesFactura =
                                        $claveDetalleFactura !== ''
                                        && isset($detallePorFactura[$claveDetalleFactura])
                                            ? $detallePorFactura[$claveDetalleFactura]
                                            : [];
                                ?>

                                <?php if (!empty($detallesFactura)): ?>

                                    <tr>
                                        <td colspan="9">

                                            <div class="detalle-obligaciones">

                                                <strong>
                                                    Detalle por obligación
                                                </strong>

                                                <div class="tabla-responsive" style="margin-top:8px;">

                                                    <table>

                                                        <thead>
                                                            <tr>
                                                                <th>Tipo</th>
                                                                <th>Concepto / origen</th>
                                                                <th>Valor original</th>
                                                                <th>Pagado</th>
                                                                <th>Saldo</th>
                                                                <th>Vencimiento</th>
                                                                <th>Estado</th>
                                                            </tr>
                                                        </thead>

                                                        <tbody>

                                                        <?php foreach ($detallesFactura as $detalleObligacion): ?>

                                                            <?php
                                                                $esMora =
                                                                    !empty(
                                                                        $detalleObligacion['id_interes']
                                                                    );
                                                            ?>

                                                            <tr class="<?= $esMora ? 'detalle-mora-fila' : 'detalle-capital-fila' ?>">

                                                                <td>
                                                                    <?php if ($esMora): ?>
                                                                        <span class="etiqueta-mora">
                                                                            MORA
                                                                        </span>
                                                                    <?php else: ?>
                                                                        <span class="etiqueta-capital">
                                                                            CAPITAL / OTRO
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </td>

                                                                <td>
                                                                    <strong>
                                                                        <?= e(
                                                                            $detalleObligacion['concepto_nombre']
                                                                            ?? $detalleObligacion['cartera_descripcion']
                                                                            ?? '-'
                                                                        ) ?>
                                                                    </strong>

                                                                    <?php if (!empty($detalleObligacion['detalle_descripcion'])): ?>
                                                                        <div class="obligacion-descripcion">
                                                                            <?= e($detalleObligacion['detalle_descripcion']) ?>
                                                                        </div>
                                                                    <?php endif; ?>

                                                                    <?php if ($esMora): ?>
                                                                        <div class="mora-detalle">
                                                                            Origen: cartera #<?= (int)$detalleObligacion['id_cartera_origen'] ?>

                                                                            <?php if (!empty($detalleObligacion['concepto_origen'])): ?>
                                                                                · <?= e($detalleObligacion['concepto_origen']) ?>
                                                                            <?php endif; ?>

                                                                            <br>
                                                                            Base:
                                                                            <strong>
                                                                                <?= dinero($detalleObligacion['valor_base'] ?? 0) ?>
                                                                            </strong>
                                                                            · Tasa:
                                                                            <strong>
                                                                                <?= number_format(
                                                                                    (float)($detalleObligacion['tasa_interes'] ?? 0),
                                                                                    2,
                                                                                    ',',
                                                                                    '.'
                                                                                ) ?>%
                                                                            </strong>

                                                                            <?php if (!empty($detalleObligacion['periodo_interes'])): ?>
                                                                                · Período:
                                                                                <strong>
                                                                                    <?= e(
                                                                                        date(
                                                                                            'm/Y',
                                                                                            strtotime(
                                                                                                $detalleObligacion['periodo_interes']
                                                                                            )
                                                                                        )
                                                                                    ) ?>
                                                                                </strong>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </td>

                                                                <td>
                                                                    <?= dinero($detalleObligacion['valor_original']) ?>
                                                                </td>

                                                                <td>
                                                                    <?= dinero($detalleObligacion['valor_pagado']) ?>
                                                                </td>

                                                                <td>
                                                                    <strong>
                                                                        <?= dinero($detalleObligacion['saldo']) ?>
                                                                    </strong>
                                                                </td>

                                                                <td>
                                                                    <?= e(
                                                                        date(
                                                                            'd/m/Y',
                                                                            strtotime(
                                                                                $detalleObligacion['fecha_vencimiento']
                                                                            )
                                                                        )
                                                                    ) ?>
                                                                </td>

                                                                <td>
                                                                    <?php if ($detalleObligacion['estado'] === 'PAGADA'): ?>
                                                                        <span class="activo">PAGADA</span>
                                                                    <?php elseif ($detalleObligacion['estado'] === 'ANULADA'): ?>
                                                                        <span class="inactivo">ANULADA</span>
                                                                    <?php else: ?>
                                                                        PENDIENTE
                                                                    <?php endif; ?>
                                                                </td>

                                                            </tr>

                                                        <?php endforeach; ?>

                                                        </tbody>

                                                    </table>

                                                </div>

                                            </div>

                                        </td>
                                    </tr>

                                <?php endif; ?>

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

                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
                    <h3 style="margin:0;">
                        Pagos registrados
                    </h3>

                    <a
                        href="<?= BASE_URL ?>configuracion/registrar_pago.php?id_unidad=<?= (int)$idUnidad ?>"
                        class="btn-filtrar"
                        style="text-decoration:none; display:inline-block;"
                    >
                        + Registrar nuevo pago
                    </a>
                </div>

                <p style="margin-top:6px; color:#64748b;">
                    Cada pago conserva su propio saldo disponible. Si una obligación continúa pendiente,
                    puede recibir nuevos abonos desde cualquier otro pago registrado de esta misma unidad.
                </p>

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
                                                href="<?= BASE_URL ?>configuracion/aplicar_pago.php?id_pago=<?= (int)$pago['id_pago'] ?>"
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
