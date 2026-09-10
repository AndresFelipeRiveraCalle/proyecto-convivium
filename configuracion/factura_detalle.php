<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// ID FACTURA
// ==========================================================

$idFactura = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


// ==========================================================
// VALIDAR ID
// ==========================================================

if ($idFactura <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/facturas_generadas.php" .
        "?tipo=warning&texto=" .
        urlencode(
            "Debe seleccionar una factura válida."
        )
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
        u.piso,
        u.area,
        u.coeficiente,

        dtu.nombre_grupo

    FROM facturas f

    INNER JOIN unidades u
        ON u.id_unidad =
           f.id_unidad

    INNER JOIN detalle_tipos_unidad dtu
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
    ':id_factura' =>
        $idFactura
]);


$factura =
    $stmtFactura->fetch(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// VALIDAR EXISTENCIA
// ==========================================================

if (!$factura) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/facturas_generadas.php" .
        "?tipo=error&texto=" .
        urlencode(
            "La factura seleccionada no existe."
        )
    );

    exit;
}


// ==========================================================
// BUSCAR DETALLE
// ==========================================================

$sqlDetalle = "
    SELECT
        fd.id_detalle,
        fd.id_concepto,
        fd.id_tarifa,
        fd.id_interes,
        fd.descripcion,
        fd.cantidad,
        fd.valor_unitario,
        fd.subtotal,
        fd.tipo_calculo,
        fd.base_calculo,
        fd.fecha_creacion,

        cf.nombre AS concepto_nombre,

        tf.nombre AS tarifa_nombre,

        ic.valor_interes

    FROM facturas_detalle fd

    INNER JOIN conceptos_facturacion cf
        ON cf.id_concepto =
           fd.id_concepto

    LEFT JOIN tarifas_facturacion tf
        ON tf.id_tarifa =
           fd.id_tarifa

    LEFT JOIN intereses_cartera ic
        ON ic.id_interes =
           fd.id_interes

    WHERE fd.id_factura =
        :id_factura

    ORDER BY
        fd.id_detalle ASC
";


$stmtDetalle =
    $conexion->prepare(
        $sqlDetalle
    );


$stmtDetalle->execute([
    ':id_factura' =>
        $idFactura
]);


$detalles =
    $stmtDetalle->fetchAll(
        PDO::FETCH_ASSOC
    );


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
// FUNCIÓN PERÍODO
// ==========================================================

function formatoPeriodoFactura(
    $anio,
    $mes
) {

    return
        str_pad(
            (string)$mes,
            2,
            '0',
            STR_PAD_LEFT
        )
        . '/'
        . $anio;
}


// ==========================================================
// FUNCIÓN TIPO CÁLCULO
// ==========================================================

function nombreTipoCalculo($tipo)
{
    switch ($tipo) {

        case 'FIJO':
            return 'Valor fijo';

        case 'METRO_CUADRADO':
            return 'Metro cuadrado';

        case 'COEFICIENTE':
            return 'Coeficiente';

        case 'PORCENTAJE':
            return 'Porcentaje';

        default:
            return $tipo;
    }
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <?php include ROOT_PATH . "/includes/head.php"; ?>

</head>


<body>


<?php include ROOT_PATH . "/includes/header.php"; ?>

<?php require_once ROOT_PATH . "/includes/mensajes.php"; ?>


<div class="contenedor">


    <?php include ROOT_PATH . "/includes/sidebar.php"; ?>


    <main class="contenido">


        <!-- ======================================================
             ENCABEZADO
        ======================================================= -->

        <div class="form-header">


            <div>

                <h2>
                    Detalle de factura
                </h2>

                <p>

                    Consulte la información y los conceptos
                    incluidos en la factura.

                </p>

            </div>


            <div>

                <a
                    href="<?= BASE_URL ?>configuracion/facturas_generadas.php"
                    class="btn-secondary"
                >

                    ← Facturas generadas

                </a>

            </div>


        </div>


        <br>


        <!-- ======================================================
             CABECERA FACTURA
        ======================================================= -->

        <div class="bloque filtros">


            <div class="form-card">


                <h3>
                    <?= e(
                        $factura['numero_factura']
                    ) ?>
                </h3>


                <br>


                <table class="tabla">


                    <tbody>


                        <tr>

                            <th>
                                Unidad
                            </th>

                            <td>

                                <strong>

                                    <?= e(
                                        $factura[
                                            'unidad_codigo'
                                        ]
                                    ) ?>

                                </strong>


                                <?php if (
                                    !empty(
                                        $factura[
                                            'unidad_nombre'
                                        ]
                                    )
                                ): ?>

                                    -
                                    <?= e(
                                        $factura[
                                            'unidad_nombre'
                                        ]
                                    ) ?>

                                <?php endif; ?>

                            </td>


                            <th>
                                Grupo
                            </th>

                            <td>

                                <?= e(
                                    $factura[
                                        'nombre_grupo'
                                    ]
                                ) ?>

                            </td>

                        </tr>


                        <tr>

                            <th>
                                Período
                            </th>

                            <td>

                                <?= e(
                                    formatoPeriodoFactura(
                                        $factura[
                                            'periodo'
                                        ],
                                        $factura[
                                            'mes'
                                        ]
                                    )
                                ) ?>

                            </td>


                            <th>
                                Estado
                            </th>

                            <td>

                                <?php if (
                                    $factura['estado']
                                    === 'GENERADA'
                                ): ?>

                                    <span class="activo">
                                        GENERADA
                                    </span>

                                <?php elseif (
                                    $factura['estado']
                                    === 'ANULADA'
                                ): ?>

                                    <span class="inactivo">
                                        ANULADA
                                    </span>

                                <?php else: ?>

                                    <?= e(
                                        $factura[
                                            'estado'
                                        ]
                                    ) ?>

                                <?php endif; ?>

                            </td>

                        </tr>


                        <tr>

                            <th>
                                Fecha generación
                            </th>

                            <td>

                                <?= date(
                                    'd/m/Y',
                                    strtotime(
                                        $factura[
                                            'fecha_generacion'
                                        ]
                                    )
                                ) ?>

                            </td>


                            <th>
                                Fecha vencimiento
                            </th>

                            <td>

                                <?= date(
                                    'd/m/Y',
                                    strtotime(
                                        $factura[
                                            'fecha_vencimiento'
                                        ]
                                    )
                                ) ?>

                            </td>

                        </tr>


                        <tr>

                            <th>
                                Área
                            </th>

                            <td>

                                <?= number_format(
                                    (float)$factura['area'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>

                                m²

                            </td>


                            <th>
                                Coeficiente
                            </th>

                            <td>

                                <?= number_format(
                                    (float)$factura[
                                        'coeficiente'
                                    ],
                                    8,
                                    ',',
                                    '.'
                                ) ?>

                            </td>

                        </tr>


                    </tbody>


                </table>


            </div>


        </div>


        <br>


        <!-- ======================================================
             DETALLE FACTURADO
        ======================================================= -->

        <div class="bloque filtros">


            <div class="form-card">


                <h3>
                    Conceptos facturados
                </h3>


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


                        <?php if (
                            empty(
                                $detalles
                            )
                        ): ?>


                            <tr>

                                <td
                                    colspan="7"
                                    align="center"
                                >

                                    Esta factura no tiene
                                    detalles registrados.

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach (
                                $detalles
                                as $detalle
                            ): ?>


                                <tr>


                                    <td>

                                        <strong>

                                            <?= e(
                                                $detalle[
                                                    'concepto_nombre'
                                                ]
                                            ) ?>

                                        </strong>


                                        <?php if (
                                            !empty(
                                                $detalle[
                                                    'tarifa_nombre'
                                                ]
                                            )
                                        ): ?>

                                            <br>

                                            <small>

                                                Tarifa:
                                                <?= e(
                                                    $detalle[
                                                        'tarifa_nombre'
                                                    ]
                                                ) ?>

                                            </small>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $detalle[
                                                'descripcion'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            nombreTipoCalculo(
                                                $detalle[
                                                    'tipo_calculo'
                                                ]
                                            )
                                        ) ?>

                                    </td>


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


                <table class="tabla">


                    <tbody>


                        <tr>

                            <th>
                                Subtotal
                            </th>

                            <td>

                                $<?= number_format(
                                    (float)$factura[
                                        'subtotal'
                                    ],
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
                                    (float)$factura[
                                        'intereses'
                                    ],
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
                                    (float)$factura[
                                        'saldos_anteriores'
                                    ],
                                    2,
                                    ',',
                                    '.'
                                ) ?>

                            </td>

                        </tr>


                        <tr>

                            <th>
                                Total factura
                            </th>

                            <td>

                                <strong>

                                    $<?= number_format(
                                        (float)$factura[
                                            'total'
                                        ],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>

                                </strong>

                            </td>

                        </tr>


                    </tbody>


                </table>


                <?php if (
                    !empty(
                        $factura[
                            'observaciones'
                        ]
                    )
                ): ?>


                    <br>


                    <div class="info-box">


                        <strong>
                            Observaciones
                        </strong>


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


                <?php endif; ?>


            </div>


        </div>


    </main>


</div>


</body>

</html>