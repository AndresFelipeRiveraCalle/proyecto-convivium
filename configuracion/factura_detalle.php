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
// DESTINATARIOS DE CORREO
// Obtiene las personas activas con correo asociadas a la unidad.
// ==========================================================

$sqlDestinatariosCorreo = "

    SELECT u.id AS id_usuario,u.nombres,u.apellidos,u.correo,
        MAX(r.recibe_factura) AS recibe_factura,
        GROUP_CONCAT( DISTINCT r.tipo
            ORDER BY
                FIELD(r.tipo,'propietario','inquilino','residente')
            SEPARATOR ', '
        ) AS tipos_relacion

    FROM residente r
    INNER JOIN usuario u ON u.id = r.usuario_id

    WHERE r.unidad_id = :id_unidad AND r.activo = 1 AND r.fecha_hasta IS NULL AND u.correo IS NOT NULL
        AND TRIM(u.correo) <> ''
    GROUP BY u.id,u.nombres,u.apellidos,u.correo
    ORDER BY recibe_factura DESC,u.apellidos,u.nombres
";


$stmtDestinatariosCorreo =
    $conexion->prepare(
        $sqlDestinatariosCorreo
    );


$stmtDestinatariosCorreo->execute([

    ':id_unidad'
        => (int)$factura[
            'id_unidad'
        ]

]);


$destinatariosCorreo =
    $stmtDestinatariosCorreo->fetchAll(
        PDO::FETCH_ASSOC
    );

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
// RESUMEN DEL SALDO ANTERIOR
// Prepara un único registro para mostrar la cartera previa.
// ==========================================================

$cantidadSaldosAnteriores =
    count(
        $saldosAnteriores
    );


$hayMoraAnterior = false;

$mesesMoraAnterior = 0;


foreach (
    $saldosAnteriores
    as $saldoAnterior
) {

    if (
        !empty(
            $saldoAnterior[
                'fecha_vencimiento'
            ]
        )
        &&
        $saldoAnterior[
            'fecha_vencimiento'
        ] < $fechaCorteFactura
    ) {

        $hayMoraAnterior = true;


        $mesesMoraActual =
            (int)(
                $saldoAnterior[
                    'meses_mora'
                ]
                ?? 0
            );


        if (
            $mesesMoraActual >
            $mesesMoraAnterior
        ) {

            $mesesMoraAnterior =
                $mesesMoraActual;
        }
    }
}


$periodoAnteriorTexto =
    date(
        'm/Y',
        strtotime(
            $periodoFactura .
            ' -1 month'
        )
    );

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
    'Volver a facturas';


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


// ==========================================================
// HISTORIAL DE ENVÍOS
// Obtiene los correos enviados para esta factura.
// ==========================================================

$sqlEnviosFactura = "

    SELECT

        id_envio,
        tipo_envio,
        destinatario,
        nombre_destinatario,
        asunto,
        estado,
        fecha_envio,
        intentos,
        mensaje_error,
        message_id,
        fecha_creacion

    FROM envios_facturas

    WHERE
        id_factura =
            :id_factura

    ORDER BY
        id_envio DESC
";


$stmtEnviosFactura =
    $conexion->prepare(
        $sqlEnviosFactura
    );


$stmtEnviosFactura->execute([

    ':id_factura'
        => $idFactura

]);


$enviosFactura =
    $stmtEnviosFactura->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// RESUMEN DE ENVÍOS
// ==========================================================

$totalEnviosCorrectos = 0;

$ultimoEnvio = null;


foreach (
    $enviosFactura
    as $envioFactura
) {

    if (
        $envioFactura['estado']
        === 'ENVIADO'
    ) {

        $totalEnviosCorrectos++;

        if (
            $ultimoEnvio === null
        ) {

            $ultimoEnvio =
                $envioFactura;
        }
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
                <a
                    href="<?= BASE_URL ?>actions/generar_factura_pdf.php?id=<?= (int)$idFactura ?>"
                    class="factura-btn"
                    target="_blank"
                >
                    Ver PDF
                </a>
                <button
                    type="button"
                    class="factura-btn"
                    id="btnAbrirEnvioFactura"
                >
                    Enviar factura
                </button>

                <?php if (!empty($enviosFactura)): ?>

                    <button
                        type="button"
                        class="factura-btn"
                        id="btnHistorialEnvios"
                    >
                        Historial de envíos
                    </button>

                <?php endif; ?>

                <button
                    type="button"
                    class="factura-btn"
                    onclick="window.print()"
                >
                    Imprimir / Guardar PDF
                </button>

            </div>

        </div>

        <?php if (
            $ultimoEnvio !== null
        ): ?>

            <div class="factura-envio-resumen">

                <div>

                    <strong>
                        Último envío:
                    </strong>

                    <?= date(
                        'd/m/Y H:i',
                        strtotime(
                            $ultimoEnvio[
                                'fecha_envio'
                            ]
                        )
                    ) ?>

                </div>


                <div>

                    <strong>
                        Destinatarios enviados:
                    </strong>

                    <?= (int)$totalEnviosCorrectos ?>

                </div>


                <div>

                    <strong>
                        Último destinatario:
                    </strong>

                    <?= e(
                        $ultimoEnvio[
                            'destinatario'
                        ]
                    ) ?>

                </div>

            </div>

        <?php endif; ?>

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
                    SALDO ANTERIOR ACUMULADO
                =================================================== -->

                <?php if (
                    $saldoAnteriorTotal > 0.009
                ): ?>

                    <tr class="fila-saldo-anterior">


                        <!-- CONCEPTO -->

                        <td>

                            <strong>
                                Saldo anterior acumulado
                            </strong>


                            <span class="factura-concepto-aclaracion">

                                <?= (int)$cantidadSaldosAnteriores ?>

                                <?= $cantidadSaldosAnteriores === 1
                                    ? 'obligación pendiente'
                                    : 'obligaciones pendientes'
                                ?>

                            </span>

                        </td>


                        <!-- PERÍODO / ESTADO -->

                        <td class="factura-periodo-estado">

                            <div>

                                <strong>
                                    Hasta:
                                </strong>

                                <?= e(
                                    $periodoAnteriorTexto
                                ) ?>

                            </div>


                            <?php if (
                                $hayMoraAnterior
                            ): ?>

                                <div class="estado-mora-texto">

                                    En mora

                                    <?php if (
                                        $mesesMoraAnterior > 0
                                    ): ?>

                                        · hasta

                                        <strong>

                                            <?= $mesesMoraAnterior ?>

                                            <?= $mesesMoraAnterior === 1
                                                ? 'mes'
                                                : 'meses'
                                            ?>

                                        </strong>

                                    <?php endif; ?>

                                </div>

                            <?php else: ?>

                                <div class="estado-periodo-actual">
                                    Pendiente
                                </div>

                            <?php endif; ?>

                        </td>


                        <!-- SALDO ANTERIOR -->

                        <td class="numero">

                            <strong>

                                <?= dinero(
                                    $saldoAnteriorTotal
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
                                    $saldoAnteriorTotal
                                ) ?>

                            </strong>

                        </td>

                    </tr>

                <?php endif; ?>
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

<!-- =========================================================
     MODAL ENVIAR FACTURA
========================================================= -->

<div
    id="modalEnviarFactura"
    class="modal"
    style="display:none;"
    >

    <div
        class="modal-contenido modal-envio-factura-contenido"
    >

        <div class="modal-header">

            <div>

                <h3>
                    Enviar factura
                </h3>

                <div
                    class="modal-envio-subtitulo"
                >
                    <?= e($factura['numero_factura']) ?>
                    · Unidad
                    <?= e($factura['unidad_codigo']) ?>
                </div>

            </div>


            <button
                type="button"
                class="modal-cerrar"
                id="cerrarModalEnviarFactura"
            >
                &times;
            </button>

        </div>


        <form
            method="POST"
            action="<?= BASE_URL ?>actions/enviar_factura.php"
            id="formEnviarFactura"
        >

            <input
                type="hidden"
                name="id_factura"
                value="<?= (int)$idFactura ?>"
            >


            <div class="modal-envio-descripcion">

                Seleccione las personas a las que desea
                enviar esta factura.

                <strong>
                    Los destinatarios configurados para
                    recibir factura aparecen seleccionados
                    automáticamente.
                </strong>

            </div>


            <?php if (
                empty(
                    $destinatariosCorreo
                )
            ): ?>

                <div class="modal-envio-vacio">

                    Esta unidad no tiene personas activas
                    con una dirección de correo registrada.

                </div>

            <?php else: ?>


                <div
                    class="modal-envio-destinatarios"
                >

                    <?php foreach (
                        $destinatariosCorreo
                        as $destinatario
                    ): ?>


                        <?php

                        $nombreDestinatario =
                            trim(
                                $destinatario['nombres']
                                .
                                ' '
                                .
                                $destinatario['apellidos']
                            );


                        $recibeFactura =
                            (int)$destinatario[
                                'recibe_factura'
                            ] === 1;


                        $tiposRelacion =
                            $destinatario[
                                'tipos_relacion'
                            ]
                            ?? '';

                        ?>


                        <label
                            class="modal-envio-persona"
                        >

                            <div
                                class="modal-envio-check"
                            >

                                <input
                                    type="checkbox"
                                    name="destinatarios[]"
                                    value="<?= (int)$destinatario['id_usuario'] ?>"
                                    <?= $recibeFactura ? 'checked' : '' ?>
                                >

                            </div>


                            <div
                                class="modal-envio-info"
                            >

                                <div
                                    class="modal-envio-nombre"
                                >
                                    <?= e($nombreDestinatario) ?>
                                </div>


                                <div
                                    class="modal-envio-correo"
                                >
                                    <?= e($destinatario['correo']) ?>
                                </div>


                                <div
                                    class="modal-envio-detalle"
                                >

                                    <?= e(
                                        ucfirst(
                                            str_replace(
                                                ',',
                                                ' ·',
                                                $tiposRelacion
                                            )
                                        )
                                    ) ?>


                                    <?php if (
                                        $recibeFactura
                                    ): ?>

                                        <span
                                            class="modal-envio-principal"
                                        >
                                            Recibe factura
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </label>


                    <?php endforeach; ?>

                </div>


                <div
                    id="mensajeSeleccionDestinatarios"
                    class="modal-envio-validacion"
                    style="display:none;"
                >
                    Debe seleccionar al menos un destinatario.
                </div>


            <?php endif; ?>


            <div class="form-actions">

                <button
                    type="button"
                    class="btn-limpiar"
                    id="cancelarEnvioFactura"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="btn-primary"
                    <?= empty($destinatariosCorreo)
                        ? 'disabled'
                        : ''
                    ?>
                >
                    Enviar factura
                </button>

            </div>

        </form>

    </div>

</div>

<!-- =========================================================
     MODAL HISTORIAL DE ENVÍOS
========================================================= -->

<div
    id="modalHistorialEnvios"
    class="modal"
    style="display:none;"
>

    <div
        class="modal-contenido modal-historial-envios-contenido"
    >

        <div class="modal-header">

            <div>

                <h3>
                    Historial de envíos
                </h3>

                <div class="modal-envio-subtitulo">

                    <?= e(
                        $factura[
                            'numero_factura'
                        ]
                    ) ?>

                    · Unidad

                    <?= e(
                        $factura[
                            'unidad_codigo'
                        ]
                    ) ?>

                </div>

            </div>


            <button
                type="button"
                class="modal-cerrar"
                id="cerrarHistorialEnvios"
            >
                &times;
            </button>

        </div>


        <?php if (empty($enviosFactura)): ?>

            <div class="modal-envio-vacio">

                Esta factura todavía no tiene
                registros de envío.

            </div>

        <?php else: ?>


            <div class="historial-envios-lista">


                <?php foreach (
                    $enviosFactura
                    as $envio
                ): ?>


                    <div class="historial-envio-item">


                        <div class="historial-envio-principal">

                            <div>

                                <strong>

                                    <?= e(
                                        $envio[
                                            'nombre_destinatario'
                                        ]
                                    ) ?>

                                </strong>


                                <div
                                    class="historial-envio-correo"
                                >

                                    <?= e(
                                        $envio[
                                            'destinatario'
                                        ]
                                    ) ?>

                                </div>

                            </div>


                            <div>

                                <?php if (
                                    $envio['estado']
                                    === 'ENVIADO'
                                ): ?>

                                    <span
                                        class="historial-envio-estado enviado"
                                    >
                                        ENVIADO
                                    </span>

                                <?php elseif (
                                    $envio['estado']
                                    === 'ERROR'
                                ): ?>

                                    <span
                                        class="historial-envio-estado error"
                                    >
                                        ERROR
                                    </span>

                                <?php else: ?>

                                    <span
                                        class="historial-envio-estado pendiente"
                                    >

                                        <?= e(
                                            $envio[
                                                'estado'
                                            ]
                                        ) ?>

                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>


                        <div class="historial-envio-datos">


                            <span>

                                <strong>
                                    Fecha:
                                </strong>

                                <?php if (
                                    !empty(
                                        $envio[
                                            'fecha_envio'
                                        ]
                                    )
                                ): ?>

                                    <?= date(
                                        'd/m/Y H:i:s',
                                        strtotime(
                                            $envio[
                                                'fecha_envio'
                                            ]
                                        )
                                    ) ?>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </span>


                            <span>

                                <strong>
                                    Tipo:
                                </strong>

                                <?= e(
                                    $envio[
                                        'tipo_envio'
                                    ]
                                ) ?>

                            </span>


                            <span>

                                <strong>
                                    Intentos:
                                </strong>

                                <?= (int)$envio[
                                    'intentos'
                                ] ?>

                            </span>

                        </div>


                        <?php if (
                            !empty(
                                $envio[
                                    'mensaje_error'
                                ]
                            )
                        ): ?>

                            <div
                                class="historial-envio-error"
                            >

                                <?= e(
                                    $envio[
                                        'mensaje_error'
                                    ]
                                ) ?>

                            </div>

                        <?php endif; ?>


                    </div>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


        <div class="form-actions">

            <button
                type="button"
                class="btn-limpiar"
                id="cerrarHistorialEnviosAbajo"
            >
                Cerrar
            </button>

        </div>

    </div>

</div>
<script>

// ==========================================================
// MODALES DE FACTURA
// Controla envío e historial desde un solo bloque.
// ==========================================================

document.addEventListener(
    'DOMContentLoaded',
    function () {


        // ==================================================
        // MODAL ENVIAR FACTURA
        // ==================================================

        const modalEnvio =
            document.getElementById(
                'modalEnviarFactura'
            );


        const btnAbrirEnvio =
            document.getElementById(
                'btnAbrirEnvioFactura'
            );


        const btnCerrarEnvio =
            document.getElementById(
                'cerrarModalEnviarFactura'
            );


        const btnCancelarEnvio =
            document.getElementById(
                'cancelarEnvioFactura'
            );


        const formularioEnvio =
            document.getElementById(
                'formEnviarFactura'
            );


        const mensajeSeleccion =
            document.getElementById(
                'mensajeSeleccionDestinatarios'
            );


        // ==================================================
        // ABRIR ENVÍO
        // ==================================================

        function abrirModalEnvio() {

            if (!modalEnvio) {
                return;
            }


            modalEnvio.style.display =
                'flex';
        }


        // ==================================================
        // CERRAR ENVÍO
        // ==================================================

        function cerrarModalEnvio() {

            if (!modalEnvio) {
                return;
            }


            modalEnvio.style.display =
                'none';


            if (
                mensajeSeleccion
            ) {

                mensajeSeleccion.style.display =
                    'none';
            }
        }


        // ==================================================
        // EVENTOS ENVÍO
        // ==================================================

        if (
            btnAbrirEnvio
        ) {

            btnAbrirEnvio.addEventListener(
                'click',
                abrirModalEnvio
            );
        }


        if (
            btnCerrarEnvio
        ) {

            btnCerrarEnvio.addEventListener(
                'click',
                cerrarModalEnvio
            );
        }


        if (
            btnCancelarEnvio
        ) {

            btnCancelarEnvio.addEventListener(
                'click',
                cerrarModalEnvio
            );
        }


        if (
            modalEnvio
        ) {

            modalEnvio.addEventListener(
                'click',
                function (event) {

                    if (
                        event.target ===
                        modalEnvio
                    ) {

                        cerrarModalEnvio();
                    }
                }
            );
        }


        // ==================================================
        // VALIDAR ENVÍO
        // ==================================================

        if (
            formularioEnvio
        ) {

            formularioEnvio.addEventListener(
                'submit',
                function (event) {

                    const seleccionados =
                        formularioEnvio.querySelectorAll(
                            'input[name="destinatarios[]"]:checked'
                        );


                    if (
                        seleccionados.length === 0
                    ) {

                        event.preventDefault();


                        if (
                            mensajeSeleccion
                        ) {

                            mensajeSeleccion.style.display =
                                'block';
                        }


                        return;
                    }


                    if (
                        mensajeSeleccion
                    ) {

                        mensajeSeleccion.style.display =
                            'none';
                    }


                    const cantidad =
                        seleccionados.length;


                    const textoConfirmacion =
                        cantidad === 1
                            ? '¿Desea enviar la factura a 1 destinatario?'
                            : '¿Desea enviar la factura a ' +
                              cantidad +
                              ' destinatarios?';


                    if (
                        !confirm(
                            textoConfirmacion
                        )
                    ) {

                        event.preventDefault();
                    }
                }
            );
        }



        // ==================================================
        // MODAL HISTORIAL DE ENVÍOS
        // ==================================================

        const modalHistorial =
            document.getElementById(
                'modalHistorialEnvios'
            );


        const btnHistorial =
            document.getElementById(
                'btnHistorialEnvios'
            );


        const btnCerrarHistorial =
            document.getElementById(
                'cerrarHistorialEnvios'
            );


        const btnCerrarHistorialAbajo =
            document.getElementById(
                'cerrarHistorialEnviosAbajo'
            );


        // ==================================================
        // ABRIR HISTORIAL
        // ==================================================

        function abrirHistorialEnvios() {

            if (!modalHistorial) {
                return;
            }


            modalHistorial.style.display =
                'flex';
        }


        // ==================================================
        // CERRAR HISTORIAL
        // ==================================================

        function cerrarHistorialEnvios() {

            if (!modalHistorial) {
                return;
            }


            modalHistorial.style.display =
                'none';
        }


        // ==================================================
        // EVENTOS HISTORIAL
        // ==================================================

        if (
            btnHistorial
        ) {

            btnHistorial.addEventListener(
                'click',
                abrirHistorialEnvios
            );
        }


        if (
            btnCerrarHistorial
        ) {

            btnCerrarHistorial.addEventListener(
                'click',
                cerrarHistorialEnvios
            );
        }


        if (
            btnCerrarHistorialAbajo
        ) {

            btnCerrarHistorialAbajo.addEventListener(
                'click',
                cerrarHistorialEnvios
            );
        }


        if (
            modalHistorial
        ) {

            modalHistorial.addEventListener(
                'click',
                function (event) {

                    if (
                        event.target ===
                        modalHistorial
                    ) {

                        cerrarHistorialEnvios();
                    }
                }
            );
        }



        // ==================================================
        // CERRAR CON ESC
        // ==================================================

        document.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key !==
                    'Escape'
                ) {

                    return;
                }


                cerrarModalEnvio();

                cerrarHistorialEnvios();
            }
        );

    }
);

</script>

</body>

</html>