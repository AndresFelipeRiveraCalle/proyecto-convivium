<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// FUNCIÓN ESCAPAR
// ==========================================================

function e($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}


// ==========================================================
// VALIDAR ID FACTURA
// ==========================================================

$idFactura =
    isset($_GET['id'])
        ? (int)$_GET['id']
        : 0;


// ==========================================================
// URL DE RETORNO SEGÚN ORIGEN
// ==========================================================

$origen =
    trim(
        $_GET['origen']
        ?? ''
    );

$idUnidadRetorno =
    isset($_GET['id_unidad'])
        ? (int)$_GET['id_unidad']
        : 0;


switch ($origen) {

    case 'cartera_general':

        $urlVolver =
            BASE_URL .
            'configuracion/cartera.php';

        $textoVolver =
            '← Volver a cartera';

        break;


    case 'cartera_detalle':

        if ($idUnidadRetorno > 0) {

            $urlVolver =
                BASE_URL .
                'configuracion/cartera_detalle.php?id_unidad=' .
                $idUnidadRetorno;

            $textoVolver =
                '← Volver a cartera';

        } else {

            $urlVolver =
                BASE_URL .
                'configuracion/cartera.php';

            $textoVolver =
                '← Volver a cartera';
        }

        break;


    default:

        $urlVolver =
            BASE_URL .
            'configuracion/facturas_generadas.php';

        $textoVolver =
            '← Volver a facturas';

        break;
}


if ($idFactura <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/facturas_generadas.php?" .
        http_build_query([
            'tipo'  => 'warning',
            'texto' => 'Debe seleccionar una factura válida.'
        ])
    );

    exit;
}


// ==========================================================
// BUSCAR FACTURA
// ==========================================================

$sqlFactura = "
    SELECT
        f.id_factura,
        f.id_unidad,
        f.numero_factura,
        f.periodo,
        f.mes,
        f.fecha_generacion,
        f.fecha_vencimiento,
        f.subtotal,
        f.intereses,
        f.saldos_anteriores,
        f.total,
        f.estado,
        f.observaciones,
        f.fecha_creacion,
        f.fecha_actualizacion,

        u.codigo AS unidad_codigo,
        u.nombre AS unidad_nombre,
        u.area,
        u.coeficiente,

        dtu.nombre_grupo

    FROM facturas f

    INNER JOIN unidades u
        ON u.id_unidad =
           f.id_unidad

    LEFT JOIN detalle_tipos_unidad dtu
        ON dtu.id_tipo_config =
           u.id_tipo_config

    WHERE f.id_factura =
        :id_factura

    LIMIT 1
";


$stmtFactura =
    $conexion->prepare(
        $sqlFactura
    );


$stmtFactura->execute([

    ':id_factura'
        => $idFactura

]);


$factura =
    $stmtFactura->fetch(
        PDO::FETCH_ASSOC
    );


if (!$factura) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/facturas_generadas.php?" .
        http_build_query([
            'tipo'  => 'error',
            'texto' => 'La factura seleccionada no existe.'
        ])
    );

    exit;
}


// ==========================================================
// BUSCAR DETALLES DE FACTURA
// ==========================================================
//
// Si el detalle proviene de un CARGO, la cuota queda ligada
// mediante cargos_facturacion_cuotas.id_detalle.
//
// Eso nos permite mostrar el concepto como enlace al detalle
// explicativo del cargo.
//
// ==========================================================

$sqlDetalles = "
    SELECT
        fd.id_detalle,
        fd.id_factura,
        fd.id_concepto,
        fd.id_tarifa,
        fd.id_interes,
        fd.descripcion,
        fd.cantidad,
        fd.valor_unitario,
        fd.subtotal,
        fd.tipo_calculo,
        fd.base_calculo,

        cf.nombre AS concepto_nombre,
        cf.descripcion AS concepto_descripcion,

        tf.nombre AS tarifa_nombre,

        cfc.id_cuota,
        cfc.numero_cuota,

        cfu.id_cargo_unidad,

        c.id_cargo,
        c.nombre AS cargo_nombre,
        c.descripcion AS cargo_descripcion,
        c.numero_cuotas AS cargo_numero_cuotas,
        c.estado AS cargo_estado

    FROM facturas_detalle fd

    INNER JOIN conceptos_facturacion cf
        ON cf.id_concepto =
           fd.id_concepto

    LEFT JOIN tarifas_facturacion tf
        ON tf.id_tarifa =
           fd.id_tarifa

    LEFT JOIN cargos_facturacion_cuotas cfc
        ON cfc.id_detalle =
           fd.id_detalle

    LEFT JOIN cargos_facturacion_unidades cfu
        ON cfu.id_cargo_unidad =
           cfc.id_cargo_unidad

    LEFT JOIN cargos_facturacion c
        ON c.id_cargo =
           cfu.id_cargo

    WHERE fd.id_factura =
        :id_factura

    ORDER BY
        fd.id_detalle
";


$stmtDetalles =
    $conexion->prepare(
        $sqlDetalles
    );


$stmtDetalles->execute([

    ':id_factura'
        => $idFactura

]);


$detalles =
    $stmtDetalles->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// TEXTO PERÍODO
// ==========================================================

$periodoTexto =
    str_pad(
        (string)$factura['mes'],
        2,
        '0',
        STR_PAD_LEFT
    )
    . '/'
    . $factura['periodo'];

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
                    Detalle de factura
                </h2>

                <p>
                    Información general y conceptos cobrados.
                </p>

            </div>


            <div>

                <a
                    href="<?= e($urlVolver) ?>"
                    class="btn-limpiar"
                >
                    <?= e($textoVolver) ?>
                </a>

            </div>

        </div>


        <br>


        <!-- ======================================================
             INFORMACIÓN GENERAL
        ======================================================= -->

        <div class="bloque filtros">


            <div class="form-card">


                <h3>
                    Datos de la factura
                </h3>


                <br>


                <div
                    style="
                        display:grid;
                        grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
                        gap:18px;
                    "
                >


                    <div>

                        <strong>
                            Número de factura
                        </strong>

                        <p>
                            <?= e($factura['numero_factura']) ?>
                        </p>

                    </div>


                    <div>

                        <strong>
                            Unidad
                        </strong>

                        <p>
                            <?= e($factura['unidad_codigo']) ?>
                        </p>

                    </div>


                    <div>

                        <strong>
                            Grupo
                        </strong>

                        <p>
                            <?= e($factura['nombre_grupo'] ?? '') ?>
                        </p>

                    </div>


                    <div>

                        <strong>
                            Período
                        </strong>

                        <p>
                            <?= e($periodoTexto) ?>
                        </p>

                    </div>


                    <div>

                        <strong>
                            Estado
                        </strong>

                        <p>

                            <?php if ($factura['estado'] === 'GENERADA'): ?>

                                <span class="activo">
                                    GENERADA
                                </span>

                            <?php elseif ($factura['estado'] === 'PAGADA'): ?>

                                <span class="activo">
                                    PAGADA
                                </span>

                            <?php else: ?>

                                <?= e($factura['estado']) ?>

                            <?php endif; ?>

                        </p>

                    </div>


                    <div>

                        <strong>
                            Fecha generación
                        </strong>

                        <p>

                            <?= e(
                                date(
                                    'd/m/Y',
                                    strtotime(
                                        $factura['fecha_generacion']
                                    )
                                )
                            ) ?>

                        </p>

                    </div>


                    <div>

                        <strong>
                            Fecha vencimiento
                        </strong>

                        <p>

                            <?= e(
                                date(
                                    'd/m/Y',
                                    strtotime(
                                        $factura['fecha_vencimiento']
                                    )
                                )
                            ) ?>

                        </p>

                    </div>


                    <div>

                        <strong>
                            Área
                        </strong>

                        <p>

                            <?= number_format(
                                (float)$factura['area'],
                                2,
                                ',',
                                '.'
                            ) ?>

                        </p>

                    </div>


                    <div>

                        <strong>
                            Coeficiente
                        </strong>

                        <p>

                            <?= number_format(
                                (float)$factura['coeficiente'],
                                8,
                                ',',
                                '.'
                            ) ?>

                        </p>

                    </div>


                </div>


            </div>


        </div>


        <br>


        <!-- ======================================================
             CONCEPTOS FACTURADOS
        ======================================================= -->

        <div class="bloque filtros">


            <div class="form-card">


                <h3>
                    Conceptos facturados
                </h3>


                <p>
                    Los conceptos que provienen de un cargo permiten consultar
                    la explicación y trazabilidad del cobro directamente desde
                    el nombre del concepto.
                </p>


                <br>


                <div class="tabla-responsive">


                    <table class="tabla">


                        <thead>

                            <tr>

                                <th>
                                    Concepto
                                </th>

                                <th>
                                    Descripción
                                </th>

                                <th>
                                    Tipo cálculo
                                </th>

                                <th>
                                    Cantidad
                                </th>

                                <th>
                                    Base
                                </th>

                                <th>
                                    Valor unitario
                                </th>

                                <th>
                                    Subtotal
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (empty($detalles)): ?>


                            <tr>

                                <td
                                    colspan="7"
                                    align="center"
                                >
                                    La factura no tiene detalles registrados.
                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach ($detalles as $detalle): ?>


                                <tr>


                                    <!-- ==================================
                                         CONCEPTO
                                    =================================== -->

                                    <td>


                                        <?php if (
                                            !empty(
                                                $detalle['id_cargo']
                                            )
                                        ): ?>


                                            <a
                                                href="<?= BASE_URL ?>configuracion/cargo_detalle.php?id=<?= (int)$detalle['id_cargo'] ?>"
                                                title="Ver explicación del cobro"
                                            >

                                                <strong>

                                                    <?= e(
                                                        $detalle[
                                                            'concepto_nombre'
                                                        ]
                                                    ) ?>

                                                </strong>

                                            </a>


                                            <br>


                                            <small>

                                                Cargo:
                                                <?= e(
                                                    $detalle[
                                                        'cargo_nombre'
                                                    ]
                                                ) ?>

                                            </small>


                                        <?php else: ?>


                                            <strong>

                                                <?= e(
                                                    $detalle[
                                                        'concepto_nombre'
                                                    ]
                                                ) ?>

                                            </strong>


                                        <?php endif; ?>


                                    </td>


                                    <!-- ==================================
                                         DESCRIPCIÓN
                                    =================================== -->

                                    <td>

                                        <?= e(
                                            $detalle['descripcion']
                                        ) ?>


                                        <?php if (
                                            !empty(
                                                $detalle['id_cargo']
                                            )
                                        ): ?>


                                            <br>


                                            <small>

                                                Cuota
                                                <?= (int)$detalle[
                                                    'numero_cuota'
                                                ] ?>
                                                /
                                                <?= (int)$detalle[
                                                    'cargo_numero_cuotas'
                                                ] ?>

                                            </small>


                                        <?php endif; ?>


                                    </td>


                                    <!-- ==================================
                                         TIPO CÁLCULO
                                    =================================== -->

                                    <td>

                                        <?= e(
                                            $detalle[
                                                'tipo_calculo'
                                            ]
                                        ) ?>

                                    </td>


                                    <!-- ==================================
                                         CANTIDAD
                                    =================================== -->

                                    <td>

                                        <?= number_format(
                                            (float)$detalle[
                                                'cantidad'
                                            ],
                                            4,
                                            ',',
                                            '.'
                                        ) ?>

                                    </td>


                                    <!-- ==================================
                                         BASE
                                    =================================== -->

                                    <td>

                                        <?php if (
                                            $detalle[
                                                'base_calculo'
                                            ] !== null
                                        ): ?>

                                            <?= number_format(
                                                (float)$detalle[
                                                    'base_calculo'
                                                ],
                                                4,
                                                ',',
                                                '.'
                                            ) ?>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </td>


                                    <!-- ==================================
                                         VALOR UNITARIO
                                    =================================== -->

                                    <td>

                                        $<?= number_format(
                                            (float)$detalle[
                                                'valor_unitario'
                                            ],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>

                                    </td>


                                    <!-- ==================================
                                         SUBTOTAL
                                    =================================== -->

                                    <td>

                                        <strong>

                                            $<?= number_format(
                                                (float)$detalle[
                                                    'subtotal'
                                                ],
                                                2,
                                                ',',
                                                '.'
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


        <br>


        <!-- ======================================================
             TOTALES
        ======================================================= -->

        <div class="bloque filtros">


            <div class="form-card">


                <h3>
                    Totales
                </h3>


                <br>


                <div
                    style="
                        max-width:520px;
                        margin-left:auto;
                    "
                >


                    <table class="tabla">


                        <tbody>


                            <tr>

                                <th>
                                    Subtotal
                                </th>

                                <td>

                                    $<?= number_format(
                                        (float)$factura['subtotal'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>

                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Intereses
                                </th>

                                <td>

                                    $<?= number_format(
                                        (float)$factura['intereses'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>

                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Saldos anteriores
                                </th>

                                <td>

                                    $<?= number_format(
                                        (float)$factura['saldos_anteriores'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>

                                </td>

                            </tr>


                            <tr>

                                <th>
                                    Total
                                </th>

                                <td>

                                    <strong>

                                        $<?= number_format(
                                            (float)$factura['total'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>

                                    </strong>

                                </td>

                            </tr>


                        </tbody>


                    </table>


                </div>


            </div>


        </div>


        <?php if (
            !empty(
                $factura['observaciones']
            )
        ): ?>


            <br>


            <div class="bloque filtros">


                <div class="form-card">


                    <h3>
                        Observaciones
                    </h3>


                    <p>

                        <?= nl2br(
                            e(
                                $factura[
                                    'observaciones'
                                ]
                            )
                        ) ?>

                    </p>


                </div>


            </div>


        <?php endif; ?>


    </main>


</div>


</body>

</html>
