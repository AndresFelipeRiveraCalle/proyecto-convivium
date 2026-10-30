<?php
/**
 * ====
 * CONVIVIUM - DETALLE DE CARTERA POR UNIDAD
 * ====
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
 * ====
 */


require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/auth/requerir_sesion.php";
require_once ROOT_PATH . "/config/conexion.php";


//
// FUNCIONES DE PRESENTACIÓN
//
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


//  1. VALIDAR LA UNIDAD SOLICITADA
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

// FECHA DE CORTE
// Usa el período recibido o la fecha actual.


$periodo = trim($_GET['periodo'] ?? '');


if (
    $periodo !== ''&&preg_match(
        '/^\d{4}-(0[1-9]|1[0-2])$/',
        $periodo
    )
) {

    $fechaCorte =date('Y-m-t',strtotime(
            $periodo . '-01'
        )
    );

} else {
    $periodo = '';
    $fechaCorte = date('Y-m-d');
}

// 2. DATOS GENERALES DE LA UNIDAD
// Recuperamos información descriptiva para el encabezado de la cartera.

$sqlUnidad = "SELECT u.id_unidad, u.codigo, u.nombre, u.piso, u.area, u.coeficiente, u.estado,
        dtu.nombre_grupo, tv.nombre AS tipo_unidad
    FROM unidades u

    LEFT JOIN detalle_tipos_unidad dtu
        ON dtu.id_tipo_config =u.id_tipo_config

    LEFT JOIN tipos_vivienda tv
        ON tv.id_tipo_vivienda = dtu.id_tipo_vivienda

    WHERE
        u.id_unidad = :id_unidad
    LIMIT 1
";


$stmtUnidad =
    $conexion->prepare($sqlUnidad);

$stmtUnidad->execute([ ':id_unidad' => $idUnidad
]);

$unidad = $stmtUnidad->fetch(PDO::FETCH_ASSOC);

if (!$unidad) {

    header(
        "Location: " . BASE_URL . "configuracion/cartera.php?" .
        http_build_query([ 'tipo'  => 'error','texto' => 'La unidad seleccionada no existe.'])
    );
    exit;
}



// 3. RESUMEN FINANCIERO DE LA UNIDAD
// Calcula total original, pagado, saldo y saldo vencido.
// No incluye obligaciones anuladas.

$sqlResumen = " SELECT COUNT(*) AS obligaciones,
        COALESCE(SUM(valor_original),0) AS valor_original,
        COALESCE(SUM(valor_pagado),0) AS valor_pagado,
        COALESCE(SUM(saldo),0) AS saldo,
        COALESCE(SUM(
                CASE
                    WHEN
                        estado = 'PENDIENTE'
                        AND saldo > 0
                        AND fecha_vencimiento < :fecha_corte_resumen
                    THEN saldo
                    ELSE 0
                END
            ),
            0
        ) AS saldo_vencido

    FROM cartera

    WHERE id_unidad = :id_unidad AND estado <> 'ANULADA'
";

$stmtResumen =$conexion->prepare($sqlResumen);

$stmtResumen->execute([':id_unidad'=> $idUnidad,':fecha_corte_resumen'
        => $fechaCorte]);


$resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC);


// =====
// 4. OBLIGACIONES AGRUPADAS POR FACTURA
// Agrupa las obligaciones por factura.
//
// Las obligaciones sin factura se mantienen independientes.
//
// Las obligaciones anuladas se conservan como historial,
// pero no participan en los valores financieros activos.
// =====

$sqlCartera = "SELECT
        /*CLAVE DE AGRUPACIÓN */
        CASE
            WHEN c.id_factura IS NOT NULL
            THEN CONCAT('F-',c.id_factura)

            ELSE CONCAT('C-',c.id_cartera
            )
        END AS clave_agrupacion,

        c.id_factura,c.id_unidad,

        /*REFERENCIA PARA OBLIGACIONES SIN FACTURA*/
        MIN(c.id_cartera) AS id_cartera_referencia,

        /* PERÍODO Y VENCIMIENTO */
        MIN(c.periodo) AS periodo,

        MIN(c.fecha_vencimiento) AS fecha_vencimiento,


        /*TOTALES ACTIVOS
           Las obligaciones anuladas no suman.*/

        COALESCE(
            SUM(
                CASE
                    WHEN
                        c.estado <> 'ANULADA'
                    THEN
                        c.valor_original
                    ELSE
                        0
                END
            ),
            0
        ) AS valor_original,

        COALESCE(
            SUM(
                CASE
                    WHEN
                        c.estado <> 'ANULADA'
                    THEN
                        c.valor_pagado
                    ELSE
                        0
                END
            ),
            0
        ) AS valor_pagado,

        COALESCE(
            SUM(
                CASE
                    WHEN
                        c.estado <> 'ANULADA'
                    THEN
                        c.saldo
                    ELSE
                        0
                END
            ),
            0
        ) AS saldo,


        /* FACTURA*/

        f.numero_factura,
        f.estado AS estado_factura,

        /*CANTIDAD DE CONCEPTOS
           Incluye anulados porque forman parte del historial.*/

        COUNT(
            c.id_cartera
        ) AS cantidad_conceptos,


        /* CANTIDAD DE INTERESES ACTIVOS */

        SUM(
            CASE
                WHEN
                    fd.id_interes IS NOT NULL AND c.estado <> 'ANULADA'
                THEN
                    1
                ELSE
                    0
            END
        ) AS cantidad_intereses,


        /*VALOR ORIGINAL DE INTERESES */

        COALESCE(
            SUM(
                CASE
                    WHEN
                        fd.id_interes IS NOT NULL
                        AND c.estado <> 'ANULADA'
                    THEN
                        c.valor_original
                    ELSE
                        0
                END
            ),
            0
        ) AS valor_intereses,

        /*INTERESES PAGADOS */

        COALESCE(
            SUM(
                CASE
                    WHEN
                        fd.id_interes IS NOT NULL
                        AND c.estado <> 'ANULADA'
                    THEN
                        c.valor_pagado
                    ELSE
                        0
                END
            ),
            0
        ) AS pagado_intereses,


        /* SALDO DE INTERESES */

        COALESCE(
            SUM(
                CASE
                    WHEN
                        fd.id_interes IS NOT NULL AND c.estado <> 'ANULADA'
                    THEN
                        c.saldo
                    ELSE
                        0
                END
            ),
            0
        ) AS saldo_intereses,

        /*CAPITAL / OTROS CONCEPTOS */

        COALESCE(
            SUM(
                CASE
                    WHEN
                        fd.id_interes IS NULL AND c.estado <> 'ANULADA'
                    THEN
                        c.valor_original
                    ELSE
                        0
                END
            ),
            0
        ) AS valor_capital_otros,

        /*SALDO CAPITAL / OTROS */

        COALESCE(
            SUM(
                CASE

                    WHEN
                        fd.id_interes IS NULL
                        AND c.estado <> 'ANULADA'

                    THEN
                        c.saldo

                    ELSE
                        0

                END
            ),
            0
        ) AS saldo_capital_otros,


        /* ==================================================
           CONCEPTOS
           Conserva también los anulados para historial.
        ================================================== */

        GROUP_CONCAT(

            DISTINCT

            CASE

                WHEN
                    fd.id_interes IS NOT NULL

                THEN CONCAT(

                    COALESCE(
                        cf.nombre,
                        'Intereses de mora'
                    ),

                    CASE

                        WHEN
                            c.estado = 'ANULADA'

                        THEN
                            ' [MORA - ANULADA]'

                        ELSE
                            ' [MORA]'

                    END

                )


                ELSE CONCAT(

                    COALESCE(
                        cf.nombre,
                        tobl.nombre,
                        c.descripcion,
                        'Obligación'
                    ),

                    CASE

                        WHEN
                            c.estado = 'ANULADA'

                        THEN
                            ' [ANULADA]'

                        ELSE
                            ''

                    END

                )

            END

            ORDER BY
                c.id_cartera

            SEPARATOR ' | '

        ) AS conceptos,


        /* ==================================================
           DESCRIPCIONES
        ================================================== */

        GROUP_CONCAT(

            DISTINCT
            c.descripcion

            ORDER BY
                c.id_cartera

            SEPARATOR ' | '

        ) AS descripciones,


        /* ==================================================
           DETALLE DE MORA
           Solo muestra intereses activos.
        ================================================== */

        GROUP_CONCAT(

            DISTINCT

            CASE

                WHEN
                    fd.id_interes IS NOT NULL
                    AND c.estado <> 'ANULADA'

                THEN CONCAT(

                    'Mora sobre cartera #',
                    ic.id_cartera,

                    ' · Base ',
                    FORMAT(
                        ic.valor_base,
                        2
                    ),

                    ' · Tasa ',
                    FORMAT(
                        ic.tasa_interes,
                        2
                    ),

                    '% · Período ',
                    DATE_FORMAT(
                        ic.periodo_interes,
                        '%m/%Y'
                    ),

                    CASE

                        WHEN
                            cfo.nombre IS NOT NULL

                        THEN CONCAT(
                            ' · Origen: ',
                            cfo.nombre
                        )

                        ELSE
                            ''

                    END

                )

                ELSE
                    NULL

            END

            ORDER BY
                c.id_cartera

            SEPARATOR ' | '

        ) AS detalle_mora,


        /* ==================================================
           INDICADOR DE VENCIMIENTO
           Utiliza la fecha de corte seleccionada.
        ================================================== */

        CASE

            WHEN

                SUM(
                    CASE

                        WHEN
                            c.estado = 'PENDIENTE'
                            AND c.saldo > 0.009
                            AND c.fecha_vencimiento <
                                :fecha_corte_vencida

                        THEN
                            c.saldo

                        ELSE
                            0

                    END
                ) > 0

            THEN
                1

            ELSE
                0

        END AS vencida,


        /* ==================================================
           ESTADO AGRUPADO
        ================================================== */

        CASE

            /* ----------------------------------------------
               Todas las obligaciones están anuladas
            ---------------------------------------------- */

            WHEN

                SUM(
                    CASE

                        WHEN
                            c.estado = 'ANULADA'

                        THEN
                            1

                        ELSE
                            0

                    END
                )

                =

                COUNT(
                    c.id_cartera
                )

            THEN
                'ANULADA'


            /* ----------------------------------------------
               No existe saldo activo
            ---------------------------------------------- */

            WHEN

                SUM(
                    CASE

                        WHEN
                            c.estado <> 'ANULADA'

                        THEN
                            c.saldo

                        ELSE
                            0

                    END
                ) <= 0.009

            THEN
                'PAGADA'


            /* ----------------------------------------------
               Existe pago y todavía existe saldo
            ---------------------------------------------- */

            WHEN

                SUM(
                    CASE

                        WHEN
                            c.estado <> 'ANULADA'

                        THEN
                            c.valor_pagado

                        ELSE
                            0

                    END
                ) > 0.009

                AND

                SUM(
                    CASE

                        WHEN
                            c.estado <> 'ANULADA'

                        THEN
                            c.saldo

                        ELSE
                            0

                    END
                ) > 0.009

            THEN
                'PARCIAL'


            /* ----------------------------------------------
               Todavía no existen pagos
            ---------------------------------------------- */

            ELSE
                'PENDIENTE'

        END AS estado


    /* =
       CARTERA
    = */

    FROM cartera c


    /* =
       FACTURA
    = */

    LEFT JOIN facturas f

        ON f.id_factura =
           c.id_factura


    /* =
       DETALLE DE FACTURA
    = */

    LEFT JOIN facturas_detalle fd

        ON fd.id_detalle =
           c.id_detalle


    /* =
       CONCEPTO
    = */

    LEFT JOIN conceptos_facturacion cf

        ON cf.id_concepto =
           fd.id_concepto


    /* =
       INTERÉS DE MORA
    = */

    LEFT JOIN intereses_cartera ic

        ON ic.id_interes =
           fd.id_interes


    /* =
       CARTERA QUE ORIGINÓ EL INTERÉS
    = */

    LEFT JOIN cartera cartera_origen

        ON cartera_origen.id_cartera =
           ic.id_cartera


    /* =
       DETALLE ORIGINAL
    = */

    LEFT JOIN facturas_detalle fd_origen

        ON fd_origen.id_detalle =
           cartera_origen.id_detalle


    /* =
       CONCEPTO ORIGINAL
    = */

    LEFT JOIN conceptos_facturacion cfo

        ON cfo.id_concepto =
           fd_origen.id_concepto


    /* =
       TIPO DE OBLIGACIÓN
    = */

    LEFT JOIN tipos_obligacion tobl

        ON tobl.id_tipo_obligacion =
           c.id_tipo_obligacion


    /* =
       UNIDAD
    = */

    WHERE

        c.id_unidad =
            :id_unidad


    /* =
       AGRUPACIÓN
       Facturas juntas.
       Obligaciones sin factura independientes.
    = */

    GROUP BY

        CASE

            WHEN c.id_factura IS NOT NULL

            THEN CONCAT(
                'F-',
                c.id_factura
            )

            ELSE CONCAT(
                'C-',
                c.id_cartera
            )

        END,

        c.id_factura,
        c.id_unidad,
        f.numero_factura,
        f.estado


    /* =
       ORDEN
    = */

    ORDER BY

        periodo DESC,

        fecha_vencimiento DESC,

        CASE

            WHEN
                c.id_factura IS NULL

            THEN
                MIN(
                    c.id_cartera
                )

            ELSE
                c.id_factura

        END DESC
";


// =====
// PREPARAR CONSULTA
// =====

$stmtCartera =
    $conexion->prepare(
        $sqlCartera
    );


// =====
// EJECUTAR CONSULTA
// =====

$stmtCartera->execute([

    ':id_unidad'
        => $idUnidad,

    ':fecha_corte_vencida'
        => $fechaCorte

]);


// =====
// OBLIGACIONES
// =====

$obligaciones =
    $stmtCartera->fetchAll(
        PDO::FETCH_ASSOC
    );


// =====
// FACTURAS VENCIDAS PARA PESTAÑAS
// Solo incluye facturas con saldo vencido.
// =====

$facturasVencidas = [];


foreach (
    $obligaciones
    as $obligacion
) {

    if (
        empty(
            $obligacion[
                'id_factura'
            ]
        )
    ) {
        continue;
    }


    if (
        (int)(
            $obligacion[
                'vencida'
            ]
            ?? 0
        ) !== 1
    ) {
        continue;
    }


    if (
        (float)(
            $obligacion[
                'saldo'
            ]
            ?? 0
        ) <= 0.009
    ) {
        continue;
    }


    $facturasVencidas[] =
        $obligacion;
}


//
// 5. DETALLE INDIVIDUAL DE OBLIGACIONES
//
// Este bloque permite ver cada concepto por separado y distinguir mora de capital.
// fd.id_interes != NULL significa que la obligación proviene de intereses de mora.
//

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


//
// 6. PAGOS RECIBIDOS POR LA UNIDAD
//
// `valor_disponible` se obtiene restando al valor del pago las aplicaciones realizadas.
// Si queda disponible, ese mismo pago todavía puede distribuirse a otras obligaciones.
//

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


// =====
// 7. HISTORIAL DE APLICACIONES
// Une aplicaciones de pagos y aplicaciones de saldo a favor.
// =====

$sqlAplicaciones = "

    /* =
       APLICACIONES DE PAGOS
    = */

    SELECT

        'PAGO' AS origen_aplicacion,

        ap.id_aplicacion AS id_movimiento,

        ap.id_pago,

        NULL AS id_saldo_favor,

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

        cf.nombre AS concepto,

        fd.id_interes


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
            :id_unidad_pago


    UNION ALL


    /* =
       APLICACIONES DE SALDO A FAVOR
    = */

    SELECT

        'SALDO_FAVOR' AS origen_aplicacion,

        asf.id_aplicacion_saldo AS id_movimiento,

        sf.id_pago,

        asf.id_saldo_favor,

        asf.id_cartera,

        asf.valor_aplicado,

        asf.fecha_aplicacion,

        asf.tipo_aplicacion,

        asf.observaciones,

        p.fecha_pago,

        p.referencia,

        c.descripcion AS cartera_descripcion,

        c.periodo,

        f.numero_factura,

        cf.nombre AS concepto,

        fd.id_interes


    FROM aplicaciones_saldo_favor asf


    INNER JOIN saldo_favor sf

        ON sf.id_saldo_favor =
           asf.id_saldo_favor


    INNER JOIN pagos p

        ON p.id_pago =
           sf.id_pago


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

        c.id_unidad =
            :id_unidad_saldo


    /* =
       ORDEN GENERAL
    = */

    ORDER BY

        fecha_aplicacion DESC,

        id_movimiento DESC
";


// =====
// PREPARAR HISTORIAL
// =====

$stmtAplicaciones =
    $conexion->prepare(
        $sqlAplicaciones
    );


// =====
// EJECUTAR HISTORIAL
// =====

$stmtAplicaciones->execute([

    ':id_unidad_pago'
        => $idUnidad,

    ':id_unidad_saldo'
        => $idUnidad

]);


// =====
// RESULTADOS
// =====

$aplicaciones =
    $stmtAplicaciones->fetchAll(
        PDO::FETCH_ASSOC
    );


// SALDOS A FAVOR DE LA UNIDAD//

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


        <!--ENCABEZADO -->

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
                <p>
                    <small>
                        Fecha de corte:
                        <strong>
                            <?= e(date('d/m/Y', strtotime($fechaCorte))) ?>
                        </strong>
                    </small>
                </p>
            </div>


            <div>

                <a
                    href="<?= BASE_URL ?>configuracion/cartera.php"
                    class="btn-limpiar"
                >
                    Volver a cartera
                </a>

            </div>

        </div>


        <br>


        <!--DATOS UNIDAD -->

        <div class="bloque filtros">

            <h3>Unidad</h3>
            <div class="factura-vencida-dato">
                <small>Código</small>

                <strong>
                    <?= e($unidad['codigo']) ?>
                </strong>
            </div>

            <div class="factura-vencida-dato">
                <small>Grupo</small>

                <strong>
                    <?= e($unidad['nombre_grupo'] ?? '-') ?>
                </strong>
            </div>

            <div class="factura-vencida-dato">
                <small>Tipo</small>

                <strong>
                    <?= e($unidad['tipo_unidad'] ?? '-') ?>
                </strong>
            </div>

            <div class="factura-vencida-dato">
                <small>Área</small>

                <strong>
                    <?= number_format(
                        (float)$unidad['area'],
                        2,
                        ',',
                        '.'
                    ) ?>
                </strong>
            </div>

            <div class="factura-vencida-dato">
                <small>Coeficiente</small>

                <strong >
                    <?= number_format(
                        (float)$unidad['coeficiente'],
                        8,
                        ',',
                        '.'
                    ) ?>
                </strong>
            </div>

        </div>


        <br>


        <!--RESUMEN -->

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


                <!-- =====
                    FACTURAS VENCIDAS
                ====== -->

                <?php if (
                    !empty(
                        $facturasVencidas
                    )
                ): ?>
                    <div class="bloque filtros">
                        <div class="form-card">
                            <h3>Facturas vencidas</h3>

                            <p style="margin-top:6px; color:#64748b;">
                                Seleccione una factura para consultarsus obligaciones pendientes.
                            </p>

                            <br>

                            <!-- PESTAÑAS -->
                            <div class="cartera-tabs">
                                <?php foreach ($facturasVencidas as $indice => $facturaVencida): ?>


                                    <button
                                        type="button"
                                        class="cartera-tab <?= $indice === 0 ? 'activo-tab' : '' ?>"
                                        data-tab="factura-tab-<?= (int)$facturaVencida['id_factura'] ?>"
                                        data-id-factura="<?= (int)$facturaVencida['id_factura'] ?>"
                                    >

                                        <?= e(
                                            $facturaVencida[
                                                'numero_factura'
                                            ]
                                            ??
                                            'Factura #' .
                                            $facturaVencida[
                                                'id_factura'
                                            ]
                                        ) ?>


                                        <small>

                                            <?= e(
                                                date(
                                                    'm/Y',
                                                    strtotime(
                                                        $facturaVencida[
                                                            'periodo'
                                                        ]
                                                    )
                                                )
                                            ) ?>

                                            · Apto.
                                            <?= e(
                                                $unidad['codigo']
                                            ) ?>
                                        </small>
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <!-- CONTENIDO -->
                            <?php foreach (
                                $facturasVencidas
                                as $indice => $facturaVencida
                            ): ?>

                                <?php

                                    $claveFactura =
                                        'factura_' .
                                        (int)$facturaVencida[
                                            'id_factura'
                                        ];


                                    $detallesVencidos =
                                        $detallePorFactura[
                                            $claveFactura
                                        ]
                                        ?? [];

                                ?>


                                <div
                                    id="factura-tab-<?= (int)$facturaVencida['id_factura'] ?>"
                                    class="cartera-tab-contenido <?= $indice === 0 ? 'activo' : '' ?>"
                                >


                                    <!-- RESUMEN FACTURA -->

                                    <div class="factura-vencida-resumen">

                                    <div class="factura-vencida-dato">

                                        <small>
                                            Apartamento / Unidad
                                        </small>

                                        <strong>
                                            <?= e(
                                                $unidad['codigo']
                                            ) ?>
                                        </strong>

                                    </div>


                                    <div class="factura-vencida-dato">

                                        <small>
                                            Tipo de unidad
                                        </small>

                                        <strong>
                                            <?= e(
                                                $unidad['tipo_unidad']
                                                ?? '-'
                                            ) ?>
                                        </strong>

                                    </div>


                                    <div class="factura-vencida-dato">

                                        <small>
                                            Grupo
                                        </small>

                                        <strong>
                                            <?= e(
                                                $unidad['nombre_grupo']
                                                ?? '-'
                                            ) ?>
                                        </strong>

                                    </div>
                                        <div class="factura-vencida-dato">
                                            <small>Factura</small>

                                            <strong>
                                                <?= e(
                                                    $facturaVencida[
                                                        'numero_factura'
                                                    ]
                                                    ?? '-'
                                                ) ?>
                                            </strong>
                                        </div>


                                        <div class="factura-vencida-dato">

                                            <small>
                                                Período
                                            </small>

                                            <strong>

                                                <?= e(
                                                    date(
                                                        'm/Y',
                                                        strtotime(
                                                            $facturaVencida[
                                                                'periodo'
                                                            ]
                                                        )
                                                    )
                                                ) ?>

                                            </strong>

                                        </div>


                                        <div class="factura-vencida-dato">

                                            <small>
                                                Vencimiento
                                            </small>

                                            <strong>

                                                <?= e(
                                                    date(
                                                        'd/m/Y',
                                                        strtotime(
                                                            $facturaVencida[
                                                                'fecha_vencimiento'
                                                            ]
                                                        )
                                                    )
                                                ) ?>

                                            </strong>

                                        </div>


                                        <div class="factura-vencida-dato">

                                            <small>
                                                Saldo pendiente
                                            </small>

                                            <strong>

                                                <?= dinero(
                                                    $facturaVencida[
                                                        'saldo'
                                                    ]
                                                ) ?>

                                            </strong>

                                        </div>


                                        <div class="factura-vencida-dato">

                                            <small>
                                                Estado
                                            </small>

                                            <strong style="color:#b42318;">
                                                EN MORA
                                            </strong>

                                        </div>


                                    </div>


                                    <!-- DETALLE -->

                                    <div class="tabla-responsive">
                                        <table class="tabla">
                                            <thead>
                                                <tr>
                                                    <th>Obligacion</th>
                                                    <th>Tipo</th>
                                                    <th>Concepto</th>
                                                    <th>Original</th>
                                                    <th>Pagado</th>
                                                    <th>Saldo</th>
                                                    <th>Vencimiento</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                            <?php if (empty($detallesVencidos)): ?>
                                                <tr>
                                                    <td colspan="8" align="center">
                                                        No existen conceptos asociados a esta factura.
                                                    </td>
                                                </tr>
                                            <?php else: ?>

                                                <?php foreach ($detallesVencidos as $detalle): ?>

                                                    <?php $esInteres =!empty(
                                                        $detalle['id_interes']);
                                                    ?>

                                                    <tr>
                                                        <td>
                                                            <strong>
                                                                #<?= (int)$detalle[
                                                                    'id_cartera'
                                                                ] ?>
                                                            </strong>
                                                        </td>
                                                        <td>
                                                            <?php if (
                                                                $esInteres
                                                            ): ?>

                                                                <span class="inactivo">
                                                                    MORA
                                                                </span>

                                                            <?php else: ?>

                                                                <span class="activo">
                                                                    CAPITAL
                                                                </span>

                                                            <?php endif; ?>
                                                            <small style="display:block; margin-top:3px; color:#64748b;">

                                                                Factura:
                                                                <?= e(
                                                                    $facturaVencida[
                                                                        'numero_factura'
                                                                    ]
                                                                    ?? '-'
                                                                ) ?>


                                                            </small>
                                                        </td>


                                                        <td>


                                                            <strong>

                                                                <?= e($detalle['concepto_nombre']??
                                                                    $detalle['cartera_descripcion']??
                                                                    'Obligación'
                                                                ) ?>

                                                            </strong>


                                                            <?php if ($esInteres): ?>

                                                                <div style="margin-top:5px;">
                                                                    <small style="color:#9a4d00;">
                                                                        Interés originado por:
                                                                        <strong>
                                                                            Cartera
                                                                            #<?= (int)($detalle['id_cartera_origen']
                                                                                ?? 0
                                                                            ) ?>
                                                                        </strong>

                                                                        <?php if (
                                                                            !empty(
                                                                                $detalle[
                                                                                    'concepto_origen'
                                                                                ]
                                                                            )
                                                                        ): ?>

                                                                            ·
                                                                            <?= e(
                                                                                $detalle[
                                                                                    'concepto_origen'
                                                                                ]
                                                                            ) ?>

                                                                        <?php endif; ?>

                                                                    </small>


                                                                    <br>


                                                                    <small>

                                                                        Base:

                                                                        <strong>
                                                                            <?= dinero(
                                                                                $detalle[
                                                                                    'valor_base'
                                                                                ]
                                                                                ?? 0
                                                                            ) ?>
                                                                        </strong>

                                                                        · Tasa:

                                                                        <strong>
                                                                            <?= number_format(
                                                                                (float)(
                                                                                    $detalle[
                                                                                        'tasa_interes'
                                                                                    ]
                                                                                    ?? 0
                                                                                ),
                                                                                2,
                                                                                ',',
                                                                                '.'
                                                                            ) ?>%
                                                                        </strong>

                                                                    </small>

                                                                </div>

                                                            <?php endif; ?>


                                                        </td>


                                                        <td>

                                                            <?= dinero(
                                                                $detalle[
                                                                    'valor_original'
                                                                ]
                                                            ) ?>

                                                        </td>


                                                        <td>

                                                            <?= dinero(
                                                                $detalle[
                                                                    'valor_pagado'
                                                                ]
                                                            ) ?>

                                                        </td>


                                                        <td>

                                                            <strong>

                                                                <?= dinero(
                                                                    $detalle[
                                                                        'saldo'
                                                                    ]
                                                                ) ?>

                                                            </strong>

                                                        </td>


                                                        <td>

                                                            <?= e(
                                                                date(
                                                                    'd/m/Y',
                                                                    strtotime(
                                                                        $detalle[
                                                                            'fecha_vencimiento'
                                                                        ]
                                                                    )
                                                                )
                                                            ) ?>

                                                        </td>


                                                        <td>

                                                            <?php if (
                                                                $detalle[
                                                                    'estado'
                                                                ] === 'PAGADA'
                                                            ): ?>

                                                                <span class="activo">
                                                                    PAGADA
                                                                </span>


                                                            <?php elseif (
                                                                $detalle[
                                                                    'estado'
                                                                ] === 'ANULADA'
                                                            ): ?>

                                                                <span class="inactivo">
                                                                    ANULADA
                                                                </span>


                                                            <?php else: ?>

                                                                <span class="inactivo">
                                                                    PENDIENTE
                                                                </span>

                                                            <?php endif; ?>

                                                        </td>


                                                    </tr>


                                                <?php endforeach; ?>


                                            <?php endif; ?>


                                            </tbody>


                                        </table>

                                    </div>


                                    <br>


                                    <a
                                        href="<?= BASE_URL ?>configuracion/factura_detalle.php?id=<?= (int)$facturaVencida['id_factura'] ?>&origen=cartera_detalle&id_unidad=<?= (int)$idUnidad ?>"
                                        class="btn-filtrar"
                                    >
                                        Ver factura completa
                                    </a>


                                </div>


                            <?php endforeach; ?>


                        </div>

                    </div>


                    <br>

                <?php endif; ?>


            </div>



        </div>


        <br>


        <!--CARTERA -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3 id="tituloDetalleFacturaSeleccionada">
                    Detalle de factura seleccionada
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

                                <?php
                                    $idFacturaFila =
                                        !empty($fila['id_factura'])
                                            ? (int)$fila['id_factura']
                                            : 0;
                                ?>

                                <tr
                                    class="fila-cartera-factura"
                                    data-id-factura="<?= $idFacturaFila ?>"
                                    style="display:none;"
                                >

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
                                                class="btn-filtrar"
                                            >
                                                Ver factura
                                            </a>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </td>

                                </tr>

                                <?php
                                    $claveDetalleFactura = !empty($fila['id_factura'])
                                            ? 'factura_' . (int)$fila['id_factura']
                                            : 'cartera_' .(int)$fila['id_cartera_referencia'];

                                    $detallesFactura =isset(
                                            $detallePorFactura[$claveDetalleFactura])
                                            ? $detallePorFactura[$claveDetalleFactura]
                                            : [];
                                ?>

                                <?php if (!empty($detallesFactura)): ?>

                                    <tr
                                        class="fila-cartera-detalle"
                                        data-id-factura="<?= $idFacturaFila ?>"
                                        style="display:none;"
                                    >

                                        <td colspan="9">
                                            <div class="tabla-detalle-factura">
                                                <strong>Detalle por obligación</strong>

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


        <!--SALDOS A FAVOR -->

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
                                                class="btn-filtrar"
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


        <!--PAGOS -->

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
                        Registrar nuevo pago
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
                                                class="btn-filtrar"
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


        <!--HISTORIAL DE APLICACIONES-->

        <div class="bloque filtros">

            <div class="form-card">


                <h3>
                    Historial de aplicaciones
                </h3>


                <p style="margin-top:6px; color:#64748b;">

                    Movimientos aplicados a las obligaciones de esta unidad,
                    incluyendo pagos registrados y saldos a favor utilizados.

                </p>


                <br>


                <div class="tabla-responsive">


                    <table class="tabla">


                        <thead>

                            <tr>

                                <th>
                                    Fecha aplicación
                                </th>

                                <th>
                                    Origen
                                </th>

                                <th>
                                    Referencia
                                </th>

                                <th>
                                    Factura
                                </th>

                                <th>
                                    Período
                                </th>

                                <th>
                                    Concepto
                                </th>

                                <th>
                                    Valor aplicado
                                </th>

                                <th>
                                    Tipo
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (
                            empty(
                                $aplicaciones
                            )
                        ): ?>


                            <tr>

                                <td
                                    colspan="8"
                                    align="center"
                                >

                                    Todavía no existen aplicaciones registradas.

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach (
                                $aplicaciones
                                as $aplicacion
                            ): ?>


                                <tr>


                                    <!-- FECHA -->

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


                                    <!-- ORIGEN -->

                                    <td>


                                        <?php if (
                                            $aplicacion[
                                                'origen_aplicacion'
                                            ] === 'PAGO'
                                        ): ?>


                                            <span class="activo">

                                                PAGO

                                            </span>


                                        <?php else: ?>


                                            <span
                                                style="
                                                    display:inline-block;
                                                    padding:4px 8px;
                                                    border-radius:6px;
                                                    background:#e8f1ff;
                                                    color:#174f7a;
                                                    font-weight:700;
                                                "
                                            >

                                                SALDO A FAVOR

                                            </span>


                                        <?php endif; ?>


                                    </td>


                                    <!-- REFERENCIA -->

                                    <td>


                                        <?php if (
                                            $aplicacion[
                                                'origen_aplicacion'
                                            ] === 'PAGO'
                                        ): ?>


                                            Pago
                                            #<?= (int)$aplicacion[
                                                'id_pago'
                                            ] ?>


                                        <?php else: ?>


                                            Saldo
                                            #<?= (int)$aplicacion[
                                                'id_saldo_favor'
                                            ] ?>


                                            <?php if (
                                                !empty(
                                                    $aplicacion[
                                                        'id_pago'
                                                    ]
                                                )
                                            ): ?>

                                                <br>

                                                <small>

                                                    Pago origen:
                                                    #<?= (int)$aplicacion[
                                                        'id_pago'
                                                    ] ?>

                                                </small>

                                            <?php endif; ?>


                                        <?php endif; ?>


                                        <?php if (
                                            !empty(
                                                $aplicacion[
                                                    'referencia'
                                                ]
                                            )
                                        ): ?>


                                            <br>


                                            <small>

                                                <?= e(
                                                    $aplicacion[
                                                        'referencia'
                                                    ]
                                                ) ?>

                                            </small>


                                        <?php endif; ?>


                                    </td>


                                    <!-- FACTURA -->

                                    <td>

                                        <?= e(
                                            $aplicacion[
                                                'numero_factura'
                                            ]
                                            ?? '-'
                                        ) ?>

                                    </td>


                                    <!-- PERÍODO -->

                                    <td>


                                        <?php if (
                                            !empty(
                                                $aplicacion[
                                                    'periodo'
                                                ]
                                            )
                                        ): ?>


                                            <?= e(
                                                date(
                                                    'm/Y',
                                                    strtotime(
                                                        $aplicacion[
                                                            'periodo'
                                                        ]
                                                    )
                                                )
                                            ) ?>


                                        <?php else: ?>

                                            -

                                        <?php endif; ?>


                                    </td>


                                    <!-- CONCEPTO -->

                                    <td>


                                        <strong>

                                            <?= e(
                                                $aplicacion[
                                                    'concepto'
                                                ]
                                                ??
                                                $aplicacion[
                                                    'cartera_descripcion'
                                                ]
                                                ??
                                                'Obligación'
                                            ) ?>

                                        </strong>


                                        <?php if (
                                            !empty(
                                                $aplicacion[
                                                    'id_interes'
                                                ]
                                            )
                                        ): ?>


                                            <br>


                                            <small
                                                style="
                                                    color:#9a4d00;
                                                    font-weight:700;
                                                "
                                            >

                                                Interés de mora

                                            </small>


                                        <?php endif; ?>


                                    </td>


                                    <!-- VALOR -->

                                    <td>

                                        <strong>

                                            <?= dinero(
                                                $aplicacion[
                                                    'valor_aplicado'
                                                ]
                                            ) ?>

                                        </strong>

                                    </td>


                                    <!-- TIPO -->

                                    <td>

                                        <?= e(
                                            $aplicacion[
                                                'tipo_aplicacion'
                                            ]
                                        ) ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            </div>

    </main>

</div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const botones =
            document.querySelectorAll(
                '.cartera-tab'
            );

        const contenidos =
            document.querySelectorAll(
                '.cartera-tab-contenido'
            );

        const filasFacturas =
            document.querySelectorAll(
                '.fila-cartera-factura'
            );

        const filasDetalles =
            document.querySelectorAll(
                '.fila-cartera-detalle'
            );


        // ==================================================
        // MOSTRAR SOLO LA FACTURA SELECCIONADA
        // ==================================================

        function mostrarFacturaSeleccionada(
            idFactura
        ) {

            filasFacturas.forEach(
                function (fila) {

                    const idFila =
                        fila.dataset.idFactura;

                    fila.style.display =
                        idFila === idFactura
                            ? ''
                            : 'none';

                }
            );


            filasDetalles.forEach(
                function (fila) {

                    const idFila =
                        fila.dataset.idFactura;

                    fila.style.display =
                        idFila === idFactura
                            ? ''
                            : 'none';

                }
            );

        }


        // ==================================================
        // CAMBIO DE PESTAÑA
        // ==================================================

        botones.forEach(
            function (boton) {

                boton.addEventListener(
                    'click',
                    function () {

                        const idTab =
                            this.dataset.tab;

                        const idFactura =
                            this.dataset.idFactura;


                        // Quitar pestaña activa

                        botones.forEach(
                            function (btn) {

                                btn.classList.remove(
                                    'activo-tab'
                                );

                            }
                        );


                        // Ocultar contenidos superiores

                        contenidos.forEach(
                            function (contenido) {

                                contenido.classList.remove(
                                    'activo'
                                );

                            }
                        );


                        // Activar botón

                        this.classList.add(
                            'activo-tab'
                        );


                        // Mostrar contenido superior

                        const contenidoActivo =
                            document.getElementById(
                                idTab
                            );


                        if (
                            contenidoActivo
                        ) {

                            contenidoActivo.classList.add(
                                'activo'
                            );

                        }


                        // Mostrar detalle inferior
                        // únicamente de esta factura.

                        if (
                            idFactura
                        ) {

                            mostrarFacturaSeleccionada(
                                idFactura
                            );

                        }

                    }
                );

            }
        );


        // ==================================================
        // CARGA INICIAL
        // ==================================================

        const primeraPestana =
            document.querySelector(
                '.cartera-tab'
            );


        if (
            primeraPestana
            &&
            primeraPestana.dataset.idFactura
        ) {

            mostrarFacturaSeleccionada(
                primeraPestana.dataset.idFactura
            );

        }

    }
);

</script>


</body>

</html>