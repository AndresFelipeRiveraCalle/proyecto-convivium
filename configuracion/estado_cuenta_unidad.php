<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/auth/requerir_sesion.php";
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
// PERSONA PRINCIPAL
// Prioriza quien recibe factura.
// ==========================================================

$sqlPersona = "
    SELECT
        usu.nombres,
        usu.apellidos,
        usu.numero_documento,
        usu.correo,

        r.tipo,
        r.recibe_factura

    FROM residente r

    INNER JOIN usuario usu
        ON usu.id =
           r.usuario_id

    WHERE
        r.unidad_id =
            :id_unidad

        AND r.activo = 1

        AND (
            r.fecha_hasta IS NULL
            OR r.fecha_hasta >= CURDATE()
        )

    ORDER BY
        r.recibe_factura DESC,

        CASE r.tipo
            WHEN 'propietario'
                THEN 1
            WHEN 'inquilino'
                THEN 2
            ELSE 3
        END,

        usu.apellidos,
        usu.nombres

    LIMIT 1
";


$stmtPersona =
    $conexion->prepare(
        $sqlPersona
    );


$stmtPersona->execute([
    ':id_unidad'
        => $idUnidad
]);


$persona =
    $stmtPersona->fetch(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// RESUMEN ACTUAL DE CARTERA
// ==========================================================

$sqlResumenCartera = "
    SELECT

        COALESCE(
            SUM(
                CASE
                    WHEN estado <> 'ANULADA'
                    THEN valor_original
                    ELSE 0
                END
            ),
            0
        ) AS total_cargos,

        COALESCE(
            SUM(
                CASE
                    WHEN estado <> 'ANULADA'
                    THEN valor_pagado
                    ELSE 0
                END
            ),
            0
        ) AS total_aplicado,

        COALESCE(
            SUM(
                CASE
                    WHEN estado <> 'ANULADA'
                    THEN saldo
                    ELSE 0
                END
            ),
            0
        ) AS saldo_cartera

    FROM cartera

    WHERE
        id_unidad =
            :id_unidad
";


$stmtResumenCartera =
    $conexion->prepare(
        $sqlResumenCartera
    );


$stmtResumenCartera->execute([
    ':id_unidad'
        => $idUnidad
]);


$resumenCartera =
    $stmtResumenCartera->fetch(
        PDO::FETCH_ASSOC
    );


$totalCargos =
    round(
        (float)(
            $resumenCartera[
                'total_cargos'
            ]
            ?? 0
        ),
        2
    );


$totalAplicado =
    round(
        (float)(
            $resumenCartera[
                'total_aplicado'
            ]
            ?? 0
        ),
        2
    );


$saldoCartera =
    round(
        (float)(
            $resumenCartera[
                'saldo_cartera'
            ]
            ?? 0
        ),
        2
    );


// ==========================================================
// SALDO A FAVOR DISPONIBLE
// Solo corresponde a esta unidad.
// ==========================================================

$sqlSaldoFavor = "
    SELECT
        COALESCE(
            SUM(
                saldo_disponible
            ),
            0
        ) AS saldo_favor_disponible

    FROM saldo_favor

    WHERE
        id_unidad =
            :id_unidad

        AND estado =
            'DISPONIBLE'

        AND saldo_disponible > 0
";


$stmtSaldoFavor =
    $conexion->prepare(
        $sqlSaldoFavor
    );


$stmtSaldoFavor->execute([
    ':id_unidad'
        => $idUnidad
]);


$saldoFavorDisponible =
    round(
        (float)$stmtSaldoFavor
            ->fetchColumn(),
        2
    );


$saldoNeto =
    round(
        $saldoCartera
        -
        $saldoFavorDisponible,
        2
    );


// ==========================================================
// MOVIMIENTOS
//
// DÉBITO:
//     Obligaciones creadas en cartera.
//
// CRÉDITO:
//     Aplicaciones de pagos.
//     Aplicaciones de saldo a favor.
//
// El saldo a favor disponible NO entra como crédito hasta
// que realmente sea aplicado a una obligación.
// ==========================================================

// ==========================================================
// MOVIMIENTOS
// Normalizamos las columnas de texto para evitar conflictos
// de collation entre las tablas utilizadas en el UNION.
// ==========================================================

$sqlMovimientos = "

    SELECT
        movimientos.*

    FROM
    (

        /* ==================================================
           CARGOS
        ================================================== */

        SELECT

            COALESCE(
                f.fecha_generacion, c.periodo
            ) AS fecha_movimiento,

            1 AS orden_movimiento,

            c.id_cartera AS id_movimiento,

            CONVERT(
                'CARGO' USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci AS tipo_movimiento,

            c.id_cartera, c.id_factura, NULL AS id_pago, NULL AS id_saldo_favor,

            CONVERT(
                COALESCE(
                    f.numero_factura,
                    CONCAT(
                        'Cartera #', c.id_cartera
                    )
                )
                USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci
                AS documento,

            CONVERT(
                COALESCE(
                    cf.nombre, tobl.nombre,
                    c.descripcion,
                    'Obligación'
                )
                USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci
                AS concepto,

            CONVERT(
                COALESCE(
                    c.descripcion,
                    ''
                )
                USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci
                AS detalle,

            fd.id_interes,

            c.valor_original
                AS debito,

            0
                AS credito

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
                :id_unidad_cargos

            AND c.estado <>
                'ANULADA'


        UNION ALL


        /* ==================================================
           APLICACIONES DE PAGOS
        ================================================== */

        SELECT

            ap.fecha_aplicacion
                AS fecha_movimiento,

            2 AS orden_movimiento,

            ap.id_aplicacion
                AS id_movimiento,

            CONVERT(
                'PAGO'
                USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci
                AS tipo_movimiento,

            ap.id_cartera,

            c.id_factura,

            ap.id_pago,

            NULL AS id_saldo_favor,

            CONVERT(
                COALESCE(
                    p.referencia,
                    CONCAT(
                        'Pago #',
                        p.id_pago
                    )
                )
                USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci
                AS documento,

            CONVERT(
                COALESCE(
                    cf.nombre,
                    c.descripcion,
                    'Aplicación de pago'
                )
                USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci
                AS concepto,

            CONVERT(
                COALESCE(
                    ap.observaciones,
                    ''
                )
                USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci
                AS detalle,

            fd.id_interes,

            0
                AS debito,

            ap.valor_aplicado
                AS credito

        FROM aplicaciones_pagos ap

        INNER JOIN pagos p
            ON p.id_pago =
               ap.id_pago

        INNER JOIN cartera c
            ON c.id_cartera =
               ap.id_cartera

        LEFT JOIN facturas_detalle fd
            ON fd.id_detalle =
               c.id_detalle

        LEFT JOIN conceptos_facturacion cf
            ON cf.id_concepto =
               fd.id_concepto

        WHERE
            c.id_unidad =
                :id_unidad_pagos


        UNION ALL


        /* ==================================================
           APLICACIONES DE SALDO A FAVOR
        ================================================== */

        SELECT

            asf.fecha_aplicacion
                AS fecha_movimiento,

            3 AS orden_movimiento,

            asf.id_aplicacion_saldo
                AS id_movimiento,

            CONVERT(
                'SALDO_FAVOR'
                USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci
                AS tipo_movimiento,

            asf.id_cartera,

            c.id_factura,

            sf.id_pago,

            sf.id_saldo_favor,

            CONVERT(
                CONCAT(
                    'Saldo favor #',
                    sf.id_saldo_favor
                )
                USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci
                AS documento,

            CONVERT(
                COALESCE(
                    cf.nombre,
                    c.descripcion,
                    'Aplicación de saldo a favor'
                )
                USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci
                AS concepto,

            CONVERT(
                COALESCE(
                    asf.observaciones,
                    ''
                )
                USING utf8mb4
            ) COLLATE utf8mb4_unicode_ci
                AS detalle,

            fd.id_interes,

            0
                AS debito,

            asf.valor_aplicado
                AS credito

        FROM aplicaciones_saldo_favor asf

        INNER JOIN saldo_favor sf
            ON sf.id_saldo_favor =
               asf.id_saldo_favor

        INNER JOIN cartera c
            ON c.id_cartera =
               asf.id_cartera

        LEFT JOIN facturas_detalle fd
            ON fd.id_detalle =
               c.id_detalle

        LEFT JOIN conceptos_facturacion cf
            ON cf.id_concepto =
               fd.id_concepto

        WHERE
            c.id_unidad =
                :id_unidad_saldo_cartera

            AND sf.id_unidad =
                :id_unidad_saldo_favor

    ) AS movimientos

    ORDER BY
        movimientos.fecha_movimiento ASC,
        movimientos.orden_movimiento ASC,
        movimientos.id_movimiento ASC
";


$stmtMovimientos =
    $conexion->prepare(
        $sqlMovimientos
    );


$stmtMovimientos->execute([

    ':id_unidad_cargos'
        => $idUnidad,

    ':id_unidad_pagos'
        => $idUnidad,

    ':id_unidad_saldo_cartera'
        => $idUnidad,

    ':id_unidad_saldo_favor'
        => $idUnidad

]);


$movimientos =
    $stmtMovimientos->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// CALCULAR SALDO ACUMULADO
// ==========================================================

$saldoAcumulado = 0;


foreach (
    $movimientos
    as $indice => $movimiento
) {

    $debito =
        round(
            (float)$movimiento['debito'],
            2
        );

    $credito =
        round(
            (float)$movimiento['credito'],
            2
        );


    $saldoAcumulado =
        round(
            $saldoAcumulado
            +
            $debito
            -
            $credito,
            2
        );


    $movimientos[
        $indice
    ]['saldo_acumulado'] =
        $saldoAcumulado;
}

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


        <!-- ==================================================
             ENCABEZADO
        =================================================== -->

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
                    Estado de cuenta
                </h2>

                <p>
                    Unidad
                    <strong>
                        <?= e($unidad['codigo']) ?>
                    </strong>
                </p>

            </div>


            <div
                style="
                    display:flex;
                    gap:8px;
                    flex-wrap:wrap;
                "
            >

                <a
                    href="<?= BASE_URL ?>configuracion/cartera_detalle.php?id_unidad=<?= $idUnidad ?>"
                    class="btn-limpiar"
                >
                    ← Volver a cartera
                </a>

            </div>

        </div>


        <br>


        <!-- ==================================================
             INFORMACIÓN DE LA UNIDAD
        =================================================== -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Información de la unidad
                </h3>

                <br>


                <div
                    style="
                        display:grid;
                        grid-template-columns:
                            repeat(auto-fit, minmax(170px, 1fr));
                        gap:15px;
                    "
                >

                    <div>

                        <small>
                            Unidad
                        </small>

                        <br>

                        <strong>
                            <?= e($unidad['codigo']) ?>
                        </strong>

                    </div>


                    <div>

                        <small>
                            Tipo
                        </small>

                        <br>

                        <strong>
                            <?= e(
                                $unidad['tipo_unidad']
                                ?? '-'
                            ) ?>
                        </strong>

                    </div>


                    <div>

                        <small>
                            Grupo
                        </small>

                        <br>

                        <strong>
                            <?= e(
                                $unidad['nombre_grupo']
                                ?? '-'
                            ) ?>
                        </strong>

                    </div>


                    <?php if ($persona): ?>

                        <div>

                            <small>
                                Responsable
                            </small>

                            <br>

                            <strong>
                                <?= e(
                                    trim(
                                        $persona['nombres']
                                        .
                                        ' '
                                        .
                                        $persona['apellidos']
                                    )
                                ) ?>
                            </strong>

                        </div>


                        <div>

                            <small>
                                Relación
                            </small>

                            <br>

                            <strong>
                                <?= e(
                                    ucfirst(
                                        $persona['tipo']
                                    )
                                ) ?>
                            </strong>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>


        <br>


        <!-- ==================================================
             RESUMEN
        =================================================== -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Resumen financiero
                </h3>

                <br>


                <div
                    style="
                        display:grid;
                        grid-template-columns:
                            repeat(auto-fit, minmax(160px, 1fr));
                        gap:15px;
                    "
                >

                    <div>

                        <small>
                            Total cargos
                        </small>

                        <br>

                        <strong>
                            <?= dinero(
                                $totalCargos
                            ) ?>
                        </strong>

                    </div>


                    <div>

                        <small>
                            Total aplicado
                        </small>

                        <br>

                        <strong>
                            <?= dinero(
                                $totalAplicado
                            ) ?>
                        </strong>

                    </div>


                    <div>

                        <small>
                            Saldo de cartera
                        </small>

                        <br>

                        <strong>
                            <?= dinero(
                                $saldoCartera
                            ) ?>
                        </strong>

                    </div>


                    <div>

                        <small>
                            Saldo a favor disponible
                        </small>

                        <br>

                        <strong>
                            <?= dinero(
                                $saldoFavorDisponible
                            ) ?>
                        </strong>

                    </div>


                    <div>

                        <small>
                            Posición neta
                        </small>

                        <br>

                        <strong>
                            <?= dinero(
                                $saldoNeto
                            ) ?>
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <br>


        <!-- ==================================================
             MOVIMIENTOS
        =================================================== -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Movimientos
                </h3>

                <br>


                <div class="tabla-responsive">

                    <table class="tabla">

                        <thead>

                            <tr>

                                <th>
                                    Fecha
                                </th>

                                <th>
                                    Movimiento
                                </th>

                                <th>
                                    Documento
                                </th>

                                <th>
                                    Concepto
                                </th>

                                <th>
                                    Tipo
                                </th>

                                <th>
                                    Débito
                                </th>

                                <th>
                                    Crédito
                                </th>

                                <th>
                                    Saldo
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (
                            empty(
                                $movimientos
                            )
                        ): ?>

                            <tr>

                                <td
                                    colspan="8"
                                    align="center"
                                >
                                    La unidad no tiene movimientos financieros.
                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach (
                                $movimientos
                                as $movimiento
                            ): ?>

                                <tr>

                                    <td>

                                        <?= e(
                                            date(
                                                'd/m/Y',
                                                strtotime(
                                                    $movimiento[
                                                        'fecha_movimiento'
                                                    ]
                                                )
                                            )
                                        ) ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            $movimiento[
                                                'tipo_movimiento'
                                            ] === 'CARGO'
                                        ): ?>

                                            Cargo

                                        <?php elseif (
                                            $movimiento[
                                                'tipo_movimiento'
                                            ] === 'PAGO'
                                        ): ?>

                                            Pago

                                        <?php else: ?>

                                            Saldo a favor

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $movimiento[
                                                'documento'
                                            ]
                                            ?? '-'
                                        ) ?>

                                    </td>


                                    <td>

                                        <strong>

                                            <?= e(
                                                $movimiento[
                                                    'concepto'
                                                ]
                                                ?? 'Movimiento'
                                            ) ?>

                                        </strong>


                                        <?php if (
                                            !empty(
                                                $movimiento[
                                                    'detalle'
                                                ]
                                            )
                                        ): ?>

                                            <br>

                                            <small>

                                                <?= e(
                                                    $movimiento[
                                                        'detalle'
                                                    ]
                                                ) ?>

                                            </small>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            !empty(
                                                $movimiento[
                                                    'id_interes'
                                                ]
                                            )
                                        ): ?>

                                            INTERÉS

                                        <?php else: ?>

                                            CAPITAL

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            (float)$movimiento[
                                                'debito'
                                            ] > 0
                                        ): ?>

                                            <?= dinero(
                                                $movimiento[
                                                    'debito'
                                                ]
                                            ) ?>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            (float)$movimiento[
                                                'credito'
                                            ] > 0
                                        ): ?>

                                            <?= dinero(
                                                $movimiento[
                                                    'credito'
                                                ]
                                            ) ?>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <strong>

                                            <?= dinero(
                                                $movimiento[
                                                    'saldo_acumulado'
                                                ]
                                            ) ?>

                                        </strong>

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