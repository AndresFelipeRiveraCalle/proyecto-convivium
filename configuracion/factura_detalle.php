<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// FUNCIONES
// Formatea texto, valores monetarios y fechas.
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


function fechaEs($fecha)
{
    if (empty($fecha)) {
        return '-';
    }

    return date(
        'd/m/Y',
        strtotime($fecha)
    );
}


// ==========================================================
// VALIDAR FACTURA
// Obtiene y valida la factura solicitada.
// ==========================================================

$idFactura =
    isset($_GET['id'])
        ? (int)$_GET['id']
        : 0;


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
// FACTURA
// Obtiene los datos principales de la factura y la unidad.
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
        u.piso,

        dtu.nombre_grupo,
        tv.nombre AS tipo_unidad

    FROM facturas f

    INNER JOIN unidades u
        ON u.id_unidad =
           f.id_unidad

    LEFT JOIN detalle_tipos_unidad dtu
        ON dtu.id_tipo_config =
           u.id_tipo_config

    LEFT JOIN tipos_vivienda tv
        ON tv.id_tipo_vivienda =
           dtu.id_tipo_vivienda

    WHERE
        f.id_factura =
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
// DATOS DE LA COPROPIEDAD
// Obtiene la versión activa de los datos generales.
// ==========================================================

$sqlCopropiedad = "
    SELECT
        id,
        nombre,
        nit,
        representante_legal,
        correo,
        telefono,
        direccion,
        sector,
        logo

    FROM datos_unidad

    WHERE
        es_actual = 1

    ORDER BY
        id DESC

    LIMIT 1
";


$stmtCopropiedad =
    $conexion->query(
        $sqlCopropiedad
    );


$copropiedad =
    $stmtCopropiedad->fetch(
        PDO::FETCH_ASSOC
    );


if (!$copropiedad) {

    $copropiedad = [
        'nombre' => 'Copropiedad',
        'nit' => '',
        'representante_legal' => '',
        'correo' => '',
        'telefono' => '',
        'direccion' => '',
        'sector' => '',
        'logo' => ''
    ];
}


// ==========================================================
// PERSONA PRINCIPAL DE FACTURACIÓN
// Prioriza la persona configurada para recibir factura.
// ==========================================================

$sqlPersona = "
    SELECT
        r.tipo,
        r.recibe_factura,

        u.id,
        u.nombres,
        u.apellidos,
        u.numero_documento,
        u.correo,
        u.telefono,
        u.celular,

        td.codigo AS tipo_documento_codigo,
        td.nombre AS tipo_documento

    FROM residente r

    INNER JOIN usuario u
        ON u.id =
           r.usuario_id

    LEFT JOIN tipos_documento td
        ON td.id_tipo_documento =
           u.id_tipo_documento

    WHERE
        r.unidad_id =
            :id_unidad

        AND r.activo = 1

        AND r.fecha_hasta IS NULL

    ORDER BY
        r.recibe_factura DESC,

        CASE r.tipo
            WHEN 'propietario' THEN 1
            WHEN 'inquilino' THEN 2
            ELSE 3
        END,

        u.apellidos,
        u.nombres

    LIMIT 1
";


$stmtPersona =
    $conexion->prepare(
        $sqlPersona
    );


$stmtPersona->execute([
    ':id_unidad'
        => (int)$factura['id_unidad']
]);


$persona =
    $stmtPersona->fetch(
        PDO::FETCH_ASSOC
    );


if (!$persona) {

    $persona = [
        'nombres' => '',
        'apellidos' => '',
        'numero_documento' => '',
        'correo' => '',
        'telefono' => '',
        'celular' => '',
        'tipo_documento_codigo' => '',
        'tipo_documento' => ''
    ];
}


// ==========================================================
// ESPACIOS VIGENTES
// Obtiene los espacios asociados a la unidad.
// ==========================================================

$sqlEspacios = "
    SELECT
        tipo_espacio,
        codigo

    FROM espacios_unidad

    WHERE
        id_unidad =
            :id_unidad

        AND activo = 1

        AND fecha_desde <=
            :fecha

        AND (
            fecha_hasta IS NULL
            OR fecha_hasta >=
               :fecha_fin
        )

    ORDER BY
        tipo_espacio,
        codigo
";


$stmtEspacios =
    $conexion->prepare(
        $sqlEspacios
    );


$stmtEspacios->execute([
    ':id_unidad'
        => (int)$factura['id_unidad'],

    ':fecha'
        => $factura['fecha_generacion'],

    ':fecha_fin'
        => $factura['fecha_generacion']
]);


$espacios =
    $stmtEspacios->fetchAll(
        PDO::FETCH_ASSOC
    );


$parqueaderos = [];


foreach ($espacios as $espacio) {

    if (
        $espacio['tipo_espacio']
        === 'PARQUEADERO'
    ) {

        $parqueaderos[] =
            $espacio['codigo'];
    }
}

// ==========================================================
// DATOS DEL PERÍODO DE LA FACTURA
// Prepara la fecha de corte y el período mensual.
// ==========================================================

$periodoFactura =
    sprintf(
        '%04d-%02d-01',
        (int)$factura['periodo'],
        (int)$factura['mes']
    );


$fechaCorteFactura =
    $factura['fecha_generacion'];


// ==========================================================
// SALDOS ANTERIORES
// Obtiene las obligaciones pendientes de períodos anteriores.
// ==========================================================

$sqlSaldosAnteriores = "
    SELECT
        c.id_cartera,
        c.id_factura,
        c.id_detalle,
        c.periodo,
        c.descripcion,
        c.valor_original,
        c.valor_pagado,
        c.saldo,
        c.fecha_vencimiento,
        c.estado,

        f.numero_factura,

        fd.id_concepto,
        fd.id_interes,

        cf.nombre AS concepto_nombre,

        CASE
            WHEN c.fecha_vencimiento < :fecha_corte_mora
            THEN
                GREATEST(
                    1,
                    TIMESTAMPDIFF(
                        MONTH,
                        DATE_FORMAT(
                            c.fecha_vencimiento,
                            '%Y-%m-01'
                        ),
                        DATE_FORMAT(
                            :periodo_factura_mora,
                            '%Y-%m-01'
                        )
                    )
                )
            ELSE 0
        END AS meses_mora

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

    WHERE
        c.id_unidad =
            :id_unidad

        AND c.estado <>
            'ANULADA'

        AND c.saldo > 0.009

        AND c.periodo <
            :periodo_factura

        AND (
            c.id_factura IS NULL
            OR c.id_factura <>
               :id_factura
        )

    ORDER BY
        c.periodo,
        c.fecha_vencimiento,
        c.id_cartera
";


$stmtSaldosAnteriores =
    $conexion->prepare(
        $sqlSaldosAnteriores
    );


$stmtSaldosAnteriores->execute([

    ':fecha_corte_mora'
        => $fechaCorteFactura,

    ':periodo_factura_mora'
        => $periodoFactura,

    ':id_unidad'
        => (int)$factura[
            'id_unidad'
        ],

    ':periodo_factura'
        => $periodoFactura,

    ':id_factura'
        => $idFactura

]);


$saldosAnteriores =
    $stmtSaldosAnteriores->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// TOTAL DE SALDOS ANTERIORES
// Verifica el total que se mostrará en la factura.
// ==========================================================

$saldoAnteriorTotal = 0;


foreach ($saldosAnteriores as $saldoAnterior) {

    $saldoAnteriorTotal +=
        (float)$saldoAnterior[
            'saldo'
        ];
}

$saldoAnteriorTotal = round($saldoAnteriorTotal,2);


// ==========================================================
// TOTAL VISUAL DE LA FACTURA
// Suma el saldo anterior reconstruido y los cargos del mes.
// ==========================================================

$valorMesFactura =
    round(
        (float)$factura['subtotal']
        +
        (float)$factura['intereses'],
        2
    );


$totalPagarFactura =
    round(
        $saldoAnteriorTotal
        +
        $valorMesFactura,
        2
    );


// ==========================================================
// DETALLES DE FACTURA
// Obtiene conceptos, cargos e información de mora.
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

        ic.id_cartera AS id_cartera_origen,
        ic.periodo_interes,
        ic.fecha_calculo AS interes_fecha_calculo,
        ic.fecha_vencimiento AS interes_fecha_vencimiento,
        ic.tasa_interes AS interes_tasa,
        ic.valor_base AS interes_valor_base,
        ic.valor_interes AS interes_valor,

        co.descripcion AS mora_descripcion,
        co.fecha_vencimiento AS mora_fecha_vencimiento,
        co.saldo AS mora_saldo_actual,

        fdo.id_concepto AS mora_id_concepto,

        cfo.nombre AS mora_concepto,

        fo.numero_factura AS mora_factura_origen,

        CASE
            WHEN
                ic.id_interes IS NOT NULL
                AND co.fecha_vencimiento IS NOT NULL
                AND ic.periodo_interes IS NOT NULL
            THEN
                GREATEST(
                    1,
                    TIMESTAMPDIFF(
                        MONTH,
                        DATE_FORMAT(
                            co.fecha_vencimiento,
                            '%Y-%m-01'
                        ),
                        DATE_FORMAT(
                            ic.periodo_interes,
                            '%Y-%m-01'
                        )
                    )
                )

            ELSE NULL
        END AS meses_mora,

        cfc.id_cuota,
        cfc.numero_cuota,

        cfu.id_cargo_unidad,

        cg.id_cargo,
        cg.nombre AS cargo_nombre,
        cg.descripcion AS cargo_descripcion,
        cg.numero_cuotas AS cargo_numero_cuotas,
        cg.estado AS cargo_estado

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

    LEFT JOIN cartera co
        ON co.id_cartera =
           ic.id_cartera

    LEFT JOIN facturas_detalle fdo
        ON fdo.id_detalle =
           co.id_detalle

    LEFT JOIN conceptos_facturacion cfo
        ON cfo.id_concepto =
           fdo.id_concepto

    LEFT JOIN facturas fo
        ON fo.id_factura =
           co.id_factura

    LEFT JOIN cargos_facturacion_cuotas cfc
        ON cfc.id_detalle =
           fd.id_detalle

    LEFT JOIN cargos_facturacion_unidades cfu
        ON cfu.id_cargo_unidad =
           cfc.id_cargo_unidad

    LEFT JOIN cargos_facturacion cg
        ON cg.id_cargo =
           cfu.id_cargo

    WHERE
        fd.id_factura =
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
// RESUMEN DE INTERESES
// Calcula los intereses incluidos en esta factura.
// ==========================================================

$cantidadIntereses = 0;

$valorIntereses = 0;


foreach ($detalles as $detalle) {

    if (
        !empty(
            $detalle['id_interes']
        )
    ) {

        $cantidadIntereses++;

        $valorIntereses +=
            (float)$detalle['subtotal'];
    }
}


// ==========================================================
// TEXTOS
// Prepara datos para presentación.
// ==========================================================

$meses = [
    1 => 'ENERO',
    2 => 'FEBRERO',
    3 => 'MARZO',
    4 => 'ABRIL',
    5 => 'MAYO',
    6 => 'JUNIO',
    7 => 'JULIO',
    8 => 'AGOSTO',
    9 => 'SEPTIEMBRE',
    10 => 'OCTUBRE',
    11 => 'NOVIEMBRE',
    12 => 'DICIEMBRE'
];


$periodoTexto =
    (
        $meses[
            (int)$factura['mes']
        ]
        ?? ''
    )
    .
    ' '
    .
    $factura['periodo'];


$nombrePersona =
    trim(
        $persona['nombres']
        .
        ' '
        .
        $persona['apellidos']
    );


$telefonoPersona =
    !empty(
        $persona['celular']
    )
        ? $persona['celular']
        : $persona['telefono'];


$documentoPersona =
    trim(
        (
            $persona[
                'tipo_documento_codigo'
            ]
            ?? ''
        )
        .
        ' '
        .
        (
            $persona[
                'numero_documento'
            ]
            ?? ''
        )
    );


// ==========================================================
// LOGO
// Construye la URL del logo configurado.
// ==========================================================

$logoUrl = '';


if (
    !empty(
        $copropiedad['logo']
    )
) {

    $logoGuardado =
        ltrim(
            $copropiedad['logo'],
            '/'
        );


    if (
        strpos(
            $logoGuardado,
            'assets/'
        ) === 0
    ) {

        $logoUrl =
            BASE_URL .
            $logoGuardado;

    } else {

        $logoUrl =
            BASE_URL .
            'assets/logos/' .
            $logoGuardado;
    }
}


// ==========================================================
// RETORNO
// Conserva la navegación según la pantalla de origen.
// ==========================================================

$urlVolver =
    BASE_URL .
    'configuracion/facturas_generadas.php';


$textoVolver =
    '← Volver a facturas';


$origen =
    $_GET['origen'] ?? '';


if (
    $origen ===
    'cartera_general'
) {

    $urlVolver =
        BASE_URL .
        'configuracion/cartera.php';

    $textoVolver =
        '← Volver a cartera';
}


if (
    $origen ===
        'cartera_detalle'
    &&
    !empty(
        $_GET['id_unidad']
    )
) {

    $urlVolver =
        BASE_URL .
        'configuracion/cartera_detalle.php?' .
        http_build_query([
            'id_unidad'
                => (int)$_GET['id_unidad']
        ]);


    $textoVolver =
        '← Volver al detalle de cartera';
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


        <!-- ======================================================
             BARRA SUPERIOR
        ======================================================= -->

        <div class="factura-toolbar">

            <div>

                <h2 style="margin-bottom:4px;">
                    Detalle de factura
                </h2>

                <div style="color:#64748b;">
                    Visualización de la cuenta de cobro generada.
                </div>

            </div>


            <div class="factura-toolbar-acciones">

                <a
                    href="<?= e($urlVolver) ?>"
                    class="factura-btn"
                >
                    <?= e($textoVolver) ?>
                </a>


                <button
                    type="button"
                    class="factura-btn"
                    onclick="window.print()"
                >
                    🖨 Imprimir / Guardar PDF
                </button>

            </div>

        </div>


        <!-- ======================================================
             DOCUMENTO
        ======================================================= -->

        <section class="factura-documento">
            <!-- ==================================================
                 CABECERA
            =================================================== -->
            <div class="factura-cabecera">
                <div class="factura-logo">

                    <?php if ($logoUrl !== ''): ?>
                        <img
                            src="<?= e($logoUrl) ?>"
                            alt="Logo copropiedad"
                        >
                    <?php else: ?>

                        <div class="factura-logo-placeholder">
                            Convivium
                        </div>

                        <small>Propiedad Horizontal</small>
                    <?php endif; ?>

                </div>

                <div class="factura-empresa">
                    <h2>
                        <?= e($copropiedad['nombre']) ?>
                    </h2>

                    <?php if (
                        !empty($copropiedad['nit'])): ?>

                        <p>
                            NIT.<?= e($copropiedad['nit']) ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($copropiedad['direccion'])): ?>
                        <p>
                            <?= e($copropiedad['direccion']) ?>
                        </p>
                    <?php endif; ?>


                    <?php if (
                        !empty(
                            $copropiedad[
                                'telefono'
                            ]
                        )
                        ||
                        !empty(
                            $copropiedad[
                                'correo'
                            ]
                        )
                    ): ?>

                        <p>

                            <?php if (
                                !empty(
                                    $copropiedad[
                                        'telefono'
                                    ]
                                )
                            ): ?>

                                Tel.
                                <?= e(
                                    $copropiedad[
                                        'telefono'
                                    ]
                                ) ?>

                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $copropiedad[
                                        'telefono'
                                    ]
                                )
                                &&
                                !empty(
                                    $copropiedad[
                                        'correo'
                                    ]
                                )
                            ): ?>
                                &nbsp; · &nbsp;
                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $copropiedad[
                                        'correo'
                                    ]
                                )
                            ): ?>

                                <?= e(
                                    $copropiedad[
                                        'correo'
                                    ]
                                ) ?>

                            <?php endif; ?>

                        </p>

                    <?php endif; ?>

                </div>


                <div class="factura-periodo">

                    <small>
                        CUENTA DE COBRO
                    </small>

                    <strong>
                        <?= e(
                            $periodoTexto
                        ) ?>
                    </strong>

                </div>


            </div>


            <!-- ==================================================
                 DATOS PRINCIPALES
            =================================================== -->

            <div class="factura-meta">


                <div class="factura-meta-item">

                    <span>
                        Número de factura
                    </span>

                    <strong>
                        <?= e(
                            $factura[
                                'numero_factura'
                            ]
                        ) ?>
                    </strong>

                </div>


                <div class="factura-meta-item">

                    <span>
                        Referencia de pago
                    </span>

                    <strong>

                        <?= str_pad(
                            (string)$factura[
                                'id_factura'
                            ],
                            10,
                            '0',
                            STR_PAD_LEFT
                        ) ?>

                    </strong>

                </div>


                <div class="factura-meta-item">

                    <span>
                        Fecha de emisión
                    </span>

                    <strong>

                        <?= e(
                            fechaEs(
                                $factura[
                                    'fecha_generacion'
                                ]
                            )
                        ) ?>

                    </strong>

                </div>


                <div class="factura-meta-item">

                    <span>
                        Fecha de vencimiento
                    </span>

                    <strong>

                        <?= e(
                            fechaEs(
                                $factura[
                                    'fecha_vencimiento'
                                ]
                            )
                        ) ?>

                    </strong>

                </div>


            </div>


            <!-- ==================================================
                 PERSONA Y UNIDAD
            =================================================== -->

            <div class="factura-cliente">


                <div class="factura-cliente-bloque">

                    <div class="factura-titulo-campo">
                        Propietario(s) / Residente(s)
                    </div>


                    <div class="factura-persona-nombre">

                        <?= $nombrePersona !== ''
                            ? e(
                                $nombrePersona
                            )
                            : 'Sin persona de facturación asignada'
                        ?>

                    </div>


                    <?php if (
                        !empty(
                            $persona[
                                'correo'
                            ]
                        )
                    ): ?>

                        <div>

                            Correo:

                            <?= e(
                                $persona[
                                    'correo'
                                ]
                            ) ?>

                        </div>

                    <?php endif; ?>


                    <?php if (
                        !empty(
                            $telefonoPersona
                        )
                    ): ?>

                        <div>

                            Teléfono:

                            <?= e(
                                $telefonoPersona
                            ) ?>

                        </div>

                    <?php endif; ?>

                </div>


                <div class="factura-cliente-bloque">

                    <div class="factura-titulo-campo">
                        Identificación
                    </div>


                    <strong>

                        <?= $documentoPersona !== ''
                            ? e(
                                $documentoPersona
                            )
                            : '-'
                        ?>

                    </strong>


                    <div class="factura-datos-unidad">


                        <div class="factura-unidad-item">

                            <div class="factura-titulo-campo">
                                Unidad
                            </div>

                            <strong>

                                <?= e(
                                    $factura[
                                        'unidad_codigo'
                                    ]
                                ) ?>

                            </strong>

                        </div>


                        <div class="factura-unidad-item">

                            <div class="factura-titulo-campo">
                                Tipo
                            </div>

                            <strong>

                                <?= e(
                                    $factura[
                                        'tipo_unidad'
                                    ]
                                    ?? '-'
                                ) ?>

                            </strong>

                        </div>


                        <div class="factura-unidad-item">

                            <div class="factura-titulo-campo">
                                Grupo
                            </div>

                            <strong>

                                <?= e(
                                    $factura[
                                        'nombre_grupo'
                                    ]
                                    ?? '-'
                                ) ?>

                            </strong>

                        </div>


                        <div class="factura-unidad-item">

                            <div class="factura-titulo-campo">
                                Parqueadero(s)
                            </div>

                            <strong>

                                <?= !empty(
                                    $parqueaderos
                                )
                                    ? e(
                                        implode(
                                            ', ',
                                            $parqueaderos
                                        )
                                    )
                                    : '-'
                                ?>

                            </strong>

                        </div>


                    </div>


                </div>


            </div>


            <!-- ==================================================
                 RESUMEN DE MORA
            =================================================== -->

            <?php if (
                $cantidadIntereses > 0
            ): ?>

                <div class="factura-resumen-mora">

                    <strong>
                        Mora del período:
                    </strong>

                    esta factura incluye

                    <?= (int)$cantidadIntereses ?>

                    <?= $cantidadIntereses === 1
                        ? 'interés mensual'
                        : 'intereses mensuales'
                    ?>

                    por un total de

                    <strong>
                        <?= dinero(
                            $valorIntereses
                        ) ?>
                    </strong>.

                </div>

            <?php endif; ?>


            <!-- ==================================================
                 CONCEPTOS
            =================================================== -->

            <table class="factura-tabla">


                <thead>

                    <tr>

                        <th>
                            Concepto
                        </th>

                        <th>
                            Período / Estado
                        </th>

                        <th class="numero">
                            Saldo anterior
                        </th>

                        <th class="numero">
                            Este mes
                        </th>

                        <th class="numero">
                            Total a pagar
                        </th>

                    </tr>

                </thead>


                <tbody>

                <!-- ==================================================
                    SALDOS ANTERIORES
                =================================================== -->

                <?php foreach ($saldosAnteriores as $saldoAnterior): ?>
                    <tr class="fila-saldo-anterior">
                        <!-- CONCEPTO -->
                        <td>
                            <strong>

                                <?= e(
                                    $saldoAnterior[
                                        'concepto_nombre'
                                    ]
                                    ??
                                    $saldoAnterior[
                                        'descripcion'
                                    ]
                                    ??
                                    'Saldo anterior'
                                ) ?>

                            </strong>

                        </td>


                        <!-- PERÍODO / ESTADO -->

                        <td class="factura-periodo-estado">

                            <div>

                                <strong>
                                    Período:
                                </strong>

                                <?= e(
                                    date(
                                        'm/Y',
                                        strtotime(
                                            $saldoAnterior[
                                                'periodo'
                                            ]
                                        )
                                    )
                                ) ?>

                            </div>


                            <?php if (
                                !empty(
                                    $saldoAnterior[
                                        'fecha_vencimiento'
                                    ]
                                )
                                &&
                                $saldoAnterior[
                                    'fecha_vencimiento'
                                ] < $fechaCorteFactura
                            ): ?>

                                <div class="estado-mora-texto">

                                    En mora:

                                    <strong>

                                        <?= (int)$saldoAnterior[
                                            'meses_mora'
                                        ] ?>

                                        <?= (int)$saldoAnterior[
                                            'meses_mora'
                                        ] === 1
                                            ? 'mes'
                                            : 'meses'
                                        ?>

                                    </strong>

                                </div>

                            <?php else: ?>

                                <div class="estado-al-dia-texto">

                                    Al día

                                </div>

                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $saldoAnterior[
                                        'fecha_vencimiento'
                                    ]
                                )
                            ): ?>

                                <div class="factura-concepto-aclaracion">

                                    Venció:
                                    <?= e(
                                        fechaEs(
                                            $saldoAnterior[
                                                'fecha_vencimiento'
                                            ]
                                        )
                                    ) ?>

                                </div>

                            <?php endif; ?>

                        </td>


                        <!-- SALDO ANTERIOR -->

                        <td class="numero">

                            <strong>

                                <?= dinero(
                                    $saldoAnterior[
                                        'saldo_al_corte'
                                    ]
                                    ??
                                    $saldoAnterior[
                                        'saldo'
                                    ]
                                ) ?>

                            </strong>

                        </td>


                        <!-- ESTE MES -->

                        <td class="numero">

                            <?= dinero(0) ?>

                        </td>


                        <!-- TOTAL -->

                        <td class="numero">

                            <strong>

                                <?= dinero(
                                    $saldoAnterior[
                                        'saldo_al_corte'
                                    ]
                                    ??
                                    $saldoAnterior[
                                        'saldo'
                                    ]
                                ) ?>

                            </strong>

                        </td>

                    </tr>

                <?php endforeach; ?>
                <?php if (
                    empty(
                        $detalles
                    )
                ): ?>


                    <tr>

                        <td colspan="5">

                            No existen conceptos registrados.

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach ($detalles as $detalle): ?>


                        <tr<?= !empty(
                            $detalle[
                                'id_interes'
                            ]
                        )
                            ? ' class="fila-interes"'
                            : ''
                        ?>>


                            <td>


                                <?php if (
                                    !empty(
                                        $detalle[
                                            'id_cargo'
                                        ]
                                    )
                                ): ?>


                                    <a
                                        href="<?= BASE_URL ?>configuracion/cargo_detalle.php?id=<?= (int)$detalle['id_cargo'] ?>"
                                        class="factura-concepto-link"
                                    >

                                        <?= e(
                                            $detalle[
                                                'concepto_nombre'
                                            ]
                                        ) ?>

                                    </a>


                                    <span class="factura-concepto-aclaracion">

                                        <?= e(
                                            $detalle[
                                                'descripcion'
                                            ]
                                        ) ?>

                                    </span>


                                <?php else: ?>


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
                                                'id_interes'
                                            ]
                                        )
                                    ): ?>


                                        <span class="factura-concepto-aclaracion">

                                            <?= e(
                                                $detalle[
                                                    'descripcion'
                                                ]
                                            ) ?>

                                        </span>


                                        <span class="factura-mora">

                                            Obligación origen:

                                            <strong>

                                                <?= e(
                                                    $detalle[
                                                        'mora_concepto'
                                                    ]
                                                    ??
                                                    $detalle[
                                                        'mora_descripcion'
                                                    ]
                                                    ??
                                                    'Obligación'
                                                ) ?>

                                            </strong>


                                            <?php if (
                                                !empty(
                                                    $detalle[
                                                        'mora_factura_origen'
                                                    ]
                                                )
                                            ): ?>

                                                · Factura:

                                                <?= e(
                                                    $detalle[
                                                        'mora_factura_origen'
                                                    ]
                                                ) ?>

                                            <?php endif; ?>


                                            <br>


                                            Saldo base:

                                            <strong>

                                                <?= dinero(
                                                    $detalle[
                                                        'interes_valor_base'
                                                    ]
                                                    ??
                                                    $detalle[
                                                        'base_calculo'
                                                    ]
                                                ) ?>

                                            </strong>


                                            · Tasa:

                                            <strong>

                                                <?= number_format(
                                                    (float)(
                                                        $detalle[
                                                            'interes_tasa'
                                                        ]
                                                        ?? 0
                                                    ),
                                                    2,
                                                    ',',
                                                    '.'
                                                ) ?>%

                                            </strong>


                                            · Interés:

                                            <strong>

                                                <?= dinero(
                                                    $detalle[
                                                        'interes_valor'
                                                    ]
                                                    ??
                                                    $detalle[
                                                        'subtotal'
                                                    ]
                                                ) ?>

                                            </strong>

                                        </span>


                                    <?php elseif (
                                        !empty(
                                            $detalle[
                                                'tarifa_nombre'
                                            ]
                                        )
                                    ): ?>


                                        <span class="factura-concepto-aclaracion">

                                            Tarifa:

                                            <?= e(
                                                $detalle[
                                                    'tarifa_nombre'
                                                ]
                                            ) ?>

                                        </span>


                                    <?php endif; ?>


                                <?php endif; ?>


                            </td>

                            <td class="factura-periodo-estado">

                                <div>

                                    <strong>
                                        Período:
                                    </strong>

                                    <?= e(
                                        str_pad(
                                            (string)$factura[
                                                'mes'
                                            ],
                                            2,
                                            '0',
                                            STR_PAD_LEFT
                                        )
                                    ) ?>

                                    /

                                    <?= e(
                                        $factura[
                                            'periodo'
                                        ]
                                    ) ?>

                                </div>


                                <?php if (
                                    !empty(
                                        $detalle[
                                            'id_interes'
                                        ]
                                    )
                                ): ?>

                                    <div class="estado-mora-texto">

                                        En mora:

                                        <strong>

                                            <?= (int)(
                                                $detalle[
                                                    'meses_mora'
                                                ]
                                                ?? 1
                                            ) ?>

                                            <?= (int)(
                                                $detalle[
                                                    'meses_mora'
                                                ]
                                                ?? 1
                                            ) === 1
                                                ? 'mes'
                                                : 'meses'
                                            ?>

                                        </strong>

                                    </div>

                                <?php else: ?>

                                    <div class="estado-periodo-actual">

                                        Período actual

                                    </div>

                                <?php endif; ?>

                            </td>
                            <td class="numero">

                                <?= dinero(0) ?>

                            </td>


                            <td class="numero">

                                <?= dinero(
                                    $detalle[
                                        'subtotal'
                                    ]
                                ) ?>

                            </td>


                            <td class="numero">

                                <strong>

                                    <?= dinero(
                                        $detalle[
                                            'subtotal'
                                        ]
                                    ) ?>

                                </strong>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php endif; ?>


                <tr class="total-row">
                    <th colspan="2">
                        TOTAL A PAGAR
                    </th>

                    <td class="numero">

                        <?= dinero(
                            $saldoAnteriorTotal
                        ) ?>

                    </td>


                    <td class="numero">

                        <?= dinero(
                            $valorMesFactura
                        ) ?>

                    </td>


                    <td class="numero">

                        <?= dinero(
                            $totalPagarFactura
                        ) ?>

                    </td>

                </tr>


                </tbody>


            </table>


            <!-- ==================================================
                 MENSAJE
            =================================================== -->

            <?php if (
                $factura[
                    'estado'
                ] === 'PAGADA'
            ): ?>


                <div class="factura-nota">

                    ¡Gracias por su pago oportuno!
                    Su cuenta se encuentra al día.

                </div>


            <?php endif; ?>


            <!-- ==================================================
                 PARTE INFERIOR
            =================================================== -->

            <div class="factura-inferior">


                <div>


                    <div class="factura-panel">

                        <h4>
                            Estado de la factura
                        </h4>

                        <span class="factura-estado">

                            <?= e(
                                $factura[
                                    'estado'
                                ]
                            ) ?>

                        </span>

                    </div>


                    <div
                        class="factura-panel"
                        style="margin-top:14px;"
                    >

                        <h4>
                            Fecha límite de pago
                        </h4>

                        <div class="factura-fecha-limite">

                            <?= e(
                                fechaEs(
                                    $factura[
                                        'fecha_vencimiento'
                                    ]
                                )
                            ) ?>

                        </div>

                    </div>


                </div>


                <div class="factura-panel">

                    <h4 style="text-align:center;">
                        Pago electrónico
                    </h4>

                    <div class="factura-qr-placeholder">

                        El código QR de pago se incorporará
                        cuando conectemos la factura con el
                        módulo de pagos / cuenta bancaria.

                    </div>

                </div>


                <div class="factura-panel">

                    <h4>
                        Información de pago
                    </h4>


                    <p>

                        Esta sección quedará conectada con

                        <strong>
                            cuentas_bancarias
                        </strong>

                        cuando integremos el módulo de pagos.

                    </p>


                    <p>

                        <strong>
                            Referencia:
                        </strong>

                        <?= str_pad(
                            (string)$factura[
                                'id_factura'
                            ],
                            10,
                            '0',
                            STR_PAD_LEFT
                        ) ?>

                    </p>


                    <p>
                        <strong>Total:</strong>
                        <?= dinero($totalPagarFactura) ?>
                    </p>

                </div>


            </div>


            <!-- ==================================================
                 OBSERVACIONES
            =================================================== -->

            <?php if (
                !empty(
                    $factura[
                        'observaciones'
                    ]
                )
            ): ?>


                <div class="factura-observaciones">

                    <strong>
                        Observaciones:
                    </strong>

                    <br>

                    <?= nl2br(
                        e(
                            $factura[
                                'observaciones'
                            ]
                        )
                    ) ?>

                </div>


            <?php endif; ?>


        </section>


    </main>


</div>


</body>

</html>