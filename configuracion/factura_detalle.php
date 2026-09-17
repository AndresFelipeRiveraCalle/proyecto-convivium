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
        ON u.id_unidad = f.id_unidad

    LEFT JOIN detalle_tipos_unidad dtu
        ON dtu.id_tipo_config = u.id_tipo_config

    LEFT JOIN tipos_vivienda tv
        ON tv.id_tipo_vivienda = dtu.id_tipo_vivienda

    WHERE f.id_factura = :id_factura

    LIMIT 1
";


$stmtFactura =
    $conexion->prepare(
        $sqlFactura
    );


$stmtFactura->execute([
    ':id_factura' => $idFactura
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

    WHERE es_actual = 1

    ORDER BY id DESC

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
        ON u.id = r.usuario_id

    LEFT JOIN tipos_documento td
        ON td.id_tipo_documento = u.id_tipo_documento

    WHERE
        r.unidad_id = :id_unidad
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
// ESPACIOS VIGENTES DE LA UNIDAD
// ==========================================================

$sqlEspacios = "
    SELECT
        tipo_espacio,
        codigo

    FROM espacios_unidad

    WHERE
        id_unidad = :id_unidad
        AND activo = 1
        AND fecha_desde <= :fecha
        AND (
            fecha_hasta IS NULL
            OR fecha_hasta >= :fecha2
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

    ':fecha2'
        => $factura['fecha_generacion']
]);


$espacios =
    $stmtEspacios->fetchAll(
        PDO::FETCH_ASSOC
    );


$parqueaderos = [];

$otrosEspacios = [];


foreach ($espacios as $espacio) {

    if ($espacio['tipo_espacio'] === 'PARQUEADERO') {

        $parqueaderos[] =
            $espacio['codigo'];

    } else {

        $otrosEspacios[] =
            $espacio['codigo'];
    }
}


// ==========================================================
// DETALLES DE FACTURA
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

        ic.periodo_interes,
        ic.fecha_calculo AS interes_fecha_calculo,
        ic.dias_mora AS interes_dias_mora,
        ic.tasa_interes AS interes_tasa,
        ic.valor_base AS interes_valor_base,
        ic.valor_interes AS interes_valor,

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
        ON cf.id_concepto = fd.id_concepto

    LEFT JOIN tarifas_facturacion tf
        ON tf.id_tarifa = fd.id_tarifa

    LEFT JOIN intereses_cartera ic
        ON ic.id_interes = fd.id_interes

    LEFT JOIN cargos_facturacion_cuotas cfc
        ON cfc.id_detalle = fd.id_detalle

    LEFT JOIN cargos_facturacion_unidades cfu
        ON cfu.id_cargo_unidad = cfc.id_cargo_unidad

    LEFT JOIN cargos_facturacion c
        ON c.id_cargo = cfu.id_cargo

    WHERE fd.id_factura = :id_factura

    ORDER BY fd.id_detalle
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
// TEXTOS
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
    ($meses[(int)$factura['mes']] ?? '') .
    ' ' .
    $factura['periodo'];


$nombrePersona =
    trim(
        $persona['nombres'] .
        ' ' .
        $persona['apellidos']
    );


$telefonoPersona =
    !empty($persona['celular'])
        ? $persona['celular']
        : $persona['telefono'];


$documentoPersona =
    trim(
        ($persona['tipo_documento_codigo'] ?? '') .
        ' ' .
        ($persona['numero_documento'] ?? '')
    );


// ==========================================================
// LOGO
// ==========================================================

$logoUrl = '';

if (!empty($copropiedad['logo'])) {

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
// RETORNO SEGÚN ORIGEN
// ==========================================================

$urlVolver = BASE_URL . 'configuracion/facturas_generadas.php';
$textoVolver = '← Volver a facturas';

$origen = $_GET['origen'] ?? '';

if ($origen === 'cartera_general') {
    $urlVolver = BASE_URL . 'configuracion/cartera.php';
    $textoVolver = '← Volver a cartera';
}

if (
    $origen === 'cartera_detalle'
    && !empty($_GET['id_unidad'])
) {
    $urlVolver =
        BASE_URL .
        'configuracion/cartera_detalle.php?' .
        http_build_query([
            'id_unidad' => (int)$_GET['id_unidad']
        ]);

    $textoVolver = '← Volver al detalle de cartera';
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <?php include ROOT_PATH . "/includes/head.php"; ?>


    <style>

        /* ======================================================
           PÁGINA DE DETALLE
        ======================================================= */

        .factura-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .factura-toolbar-acciones {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .factura-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #0f172a;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .factura-btn:hover {
            background: #f8fafc;
        }


        /* ======================================================
           DOCUMENTO
        ======================================================= */

        .factura-documento {
            max-width: 1050px;
            margin: 0 auto 30px auto;
            background: #fff;
            border: 1px solid #dbe3ec;
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            color: #132238;
        }

        .factura-cabecera {
            display: grid;
            grid-template-columns: 210px 1fr 260px;
            gap: 22px;
            align-items: center;
        }

        .factura-logo {
            text-align: center;
        }

        .factura-logo img {
            max-width: 180px;
            max-height: 95px;
            object-fit: contain;
        }

        .factura-logo-placeholder {
            font-size: 28px;
            font-weight: 800;
            color: #153b5c;
        }

        .factura-empresa {
            text-align: center;
        }

        .factura-empresa h2 {
            margin: 0 0 6px 0;
            font-size: 23px;
            color: #10243e;
        }

        .factura-empresa p {
            margin: 3px 0;
            font-size: 14px;
        }

        .factura-periodo {
            background: #eaf4fb;
            border-radius: 10px;
            text-align: center;
            padding: 18px 14px;
        }

        .factura-periodo small {
            display: block;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .factura-periodo strong {
            display: block;
            font-size: 22px;
        }


        /* ======================================================
           DATOS PRINCIPALES
        ======================================================= */

        .factura-meta {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            margin-top: 22px;
            border: 1px solid #d4dee9;
            border-radius: 8px;
            overflow: hidden;
        }

        .factura-meta-item {
            padding: 12px;
            text-align: center;
            border-right: 1px solid #d4dee9;
            background: #fbfdff;
        }

        .factura-meta-item:last-child {
            border-right: none;
        }

        .factura-meta-item span {
            display: block;
            font-size: 12px;
            margin-bottom: 4px;
            color: #64748b;
        }

        .factura-meta-item strong {
            font-size: 17px;
        }


        /* ======================================================
           PERSONA / UNIDAD
        ======================================================= */

        .factura-cliente {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            margin-top: 12px;
            border: 1px solid #d4dee9;
            border-radius: 8px;
            overflow: hidden;
        }

        .factura-cliente-bloque {
            padding: 15px;
        }

        .factura-cliente-bloque + .factura-cliente-bloque {
            border-left: 1px solid #d4dee9;
        }

        .factura-titulo-campo {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .factura-persona-nombre {
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .factura-datos-unidad {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
            margin-top: 12px;
        }

        .factura-unidad-item {
            border-top: 1px solid #e2e8f0;
            padding: 10px 8px 0 8px;
        }

        .factura-unidad-item:first-child {
            padding-left: 0;
        }


        /* ======================================================
           TABLA DE CONCEPTOS
        ======================================================= */

        .factura-tabla {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        .factura-tabla th {
            background: #dceefb;
            color: #10243e;
            padding: 11px 10px;
            border: 1px solid #c5d9e8;
            text-align: left;
        }

        .factura-tabla td {
            padding: 10px;
            border: 1px solid #d8e2ec;
            vertical-align: top;
        }

        .factura-tabla .numero {
            text-align: right;
            white-space: nowrap;
        }

        .factura-tabla .total-row th,
        .factura-tabla .total-row td {
            background: #dceefb;
            font-size: 17px;
            font-weight: 800;
        }

        .factura-concepto-link {
            color: #0f4d7a;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .factura-concepto-link:hover {
            color: #0b6ca8;
        }

        .factura-concepto-aclaracion {
            display: block;
            margin-top: 4px;
            font-size: 11px;
            color: #64748b;
        }

        .fila-interes td {
            background: #fff9e8;
        }

        .factura-interes-detalle {
            display: block;
            margin-top: 5px;
            font-size: 11px;
            color: #7a5a12;
            line-height: 1.45;
        }


        /* ======================================================
           BLOQUES INFERIORES
        ======================================================= */

        .factura-inferior {
            display: grid;
            grid-template-columns: 1fr 1.15fr 1fr;
            gap: 14px;
            margin-top: 18px;
        }

        .factura-panel {
            border: 1px solid #d8e2ec;
            border-radius: 8px;
            padding: 15px;
            min-height: 115px;
            background: #fbfdff;
        }

        .factura-panel h4 {
            margin: 0 0 10px 0;
            color: #174f7a;
        }

        .factura-estado {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 8px;
            background: #d8f5dc;
            color: #176c2b;
            font-weight: 800;
        }

        .factura-fecha-limite {
            font-size: 23px;
            font-weight: 800;
            margin-top: 9px;
        }

        .factura-qr-placeholder {
            height: 120px;
            border: 2px dashed #b9c6d3;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #64748b;
            padding: 15px;
        }

        .factura-nota {
            margin-top: 18px;
            padding: 13px;
            background: #e8f7ea;
            color: #256b2e;
            text-align: center;
            border-radius: 7px;
            font-weight: 700;
        }

        .factura-observaciones {
            margin-top: 18px;
            border-top: 1px solid #dbe3ec;
            padding-top: 14px;
            color: #475569;
        }


        /* ======================================================
           RESPONSIVE
        ======================================================= */

        @media (max-width: 900px) {

            .factura-cabecera {
                grid-template-columns: 1fr;
            }

            .factura-meta {
                grid-template-columns: repeat(2, 1fr);
            }

            .factura-meta-item {
                border-bottom: 1px solid #d4dee9;
            }

            .factura-cliente {
                grid-template-columns: 1fr;
            }

            .factura-cliente-bloque + .factura-cliente-bloque {
                border-left: none;
                border-top: 1px solid #d4dee9;
            }

            .factura-inferior {
                grid-template-columns: 1fr;
            }
        }


        /* ======================================================
           IMPRESIÓN
        ======================================================= */

        @media print {

            body {
                background: #fff !important;
            }

            header,
            .sidebar,
            .factura-toolbar,
            .no-print {
                display: none !important;
            }

            .contenedor {
                display: block !important;
            }

            .contenido {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .factura-documento {
                max-width: none;
                width: 100%;
                margin: 0;
                border: none;
                border-radius: 0;
                box-shadow: none;
                padding: 8mm;
            }

            .factura-concepto-link {
                color: #000;
                text-decoration: none;
            }

            .fila-interes td {
                background: #fff !important;
            }

            @page {
                size: A4 portrait;
                margin: 8mm;
            }
        }

    </style>

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

                        <small>
                            Propiedad Horizontal
                        </small>

                    <?php endif; ?>

                </div>


                <div class="factura-empresa">

                    <h2>
                        <?= e($copropiedad['nombre']) ?>
                    </h2>


                    <?php if (!empty($copropiedad['nit'])): ?>

                        <p>
                            NIT. <?= e($copropiedad['nit']) ?>
                        </p>

                    <?php endif; ?>


                    <?php if (!empty($copropiedad['direccion'])): ?>

                        <p>
                            <?= e($copropiedad['direccion']) ?>
                        </p>

                    <?php endif; ?>


                    <?php if (
                        !empty($copropiedad['telefono']) ||
                        !empty($copropiedad['correo'])
                    ): ?>

                        <p>

                            <?php if (!empty($copropiedad['telefono'])): ?>

                                Tel.
                                <?= e($copropiedad['telefono']) ?>

                            <?php endif; ?>


                            <?php if (
                                !empty($copropiedad['telefono']) &&
                                !empty($copropiedad['correo'])
                            ): ?>

                                &nbsp; · &nbsp;

                            <?php endif; ?>


                            <?php if (!empty($copropiedad['correo'])): ?>

                                <?= e($copropiedad['correo']) ?>

                            <?php endif; ?>

                        </p>

                    <?php endif; ?>

                </div>


                <div class="factura-periodo">

                    <small>
                        CUENTA DE COBRO
                    </small>

                    <strong>
                        <?= e($periodoTexto) ?>
                    </strong>

                </div>


            </div>


            <!-- ==================================================
                 META
            =================================================== -->

            <div class="factura-meta">


                <div class="factura-meta-item">

                    <span>
                        Número de factura
                    </span>

                    <strong>
                        <?= e($factura['numero_factura']) ?>
                    </strong>

                </div>


                <div class="factura-meta-item">

                    <span>
                        Referencia de pago
                    </span>

                    <strong>
                        <?= str_pad(
                            (string)$factura['id_factura'],
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
                                $factura['fecha_generacion']
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
                                $factura['fecha_vencimiento']
                            )
                        ) ?>
                    </strong>

                </div>


            </div>


            <!-- ==================================================
                 PERSONA + UNIDAD
            =================================================== -->

            <div class="factura-cliente">


                <div class="factura-cliente-bloque">

                    <div class="factura-titulo-campo">
                        Propietario(s) / Residente(s)
                    </div>


                    <div class="factura-persona-nombre">

                        <?= $nombrePersona !== ''
                            ? e($nombrePersona)
                            : 'Sin persona de facturación asignada'
                        ?>

                    </div>


                    <?php if (!empty($persona['correo'])): ?>

                        <div>
                            Correo:
                            <?= e($persona['correo']) ?>
                        </div>

                    <?php endif; ?>


                    <?php if (!empty($telefonoPersona)): ?>

                        <div>
                            Teléfono:
                            <?= e($telefonoPersona) ?>
                        </div>

                    <?php endif; ?>

                </div>


                <div class="factura-cliente-bloque">


                    <div class="factura-titulo-campo">
                        Identificación
                    </div>


                    <strong>
                        <?= $documentoPersona !== ''
                            ? e($documentoPersona)
                            : '-'
                        ?>
                    </strong>


                    <div class="factura-datos-unidad">


                        <div class="factura-unidad-item">

                            <div class="factura-titulo-campo">
                                Unidad
                            </div>

                            <strong>
                                <?= e($factura['unidad_codigo']) ?>
                            </strong>

                        </div>


                        <div class="factura-unidad-item">

                            <div class="factura-titulo-campo">
                                Tipo
                            </div>

                            <strong>
                                <?= e(
                                    $factura['tipo_unidad']
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
                                    $factura['nombre_grupo']
                                    ?? '-'
                                ) ?>
                            </strong>

                        </div>


                        <div class="factura-unidad-item">

                            <div class="factura-titulo-campo">
                                Parqueadero(s)
                            </div>

                            <strong>

                                <?= !empty($parqueaderos)
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
                 CONCEPTOS
            =================================================== -->

            <table class="factura-tabla">


                <thead>

                    <tr>

                        <th>
                            Concepto
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


                <?php if (empty($detalles)): ?>


                    <tr>

                        <td colspan="4">
                            No existen conceptos registrados.
                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach ($detalles as $detalle): ?>


                        <tr<?= !empty($detalle['id_interes']) ? ' class="fila-interes"' : '' ?>>


                            <td>


                                <?php if (
                                    !empty(
                                        $detalle['id_cargo']
                                    )
                                ): ?>


                                    <a
                                        href="<?= BASE_URL ?>configuracion/cargo_detalle.php?id=<?= (int)$detalle['id_cargo'] ?>"
                                        class="factura-concepto-link"
                                        title="Ver explicación del cobro"
                                    >

                                        <?= e(
                                            $detalle[
                                                'concepto_nombre'
                                            ]
                                        ) ?>

                                    </a>


                                    <span class="factura-concepto-aclaracion">

                                        <?= e(
                                            $detalle['descripcion']
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


                                    <?php if (!empty($detalle['id_interes'])): ?>

                                        <span class="factura-concepto-aclaracion">
                                            <?= e($detalle['descripcion']) ?>
                                        </span>

                                        <span class="factura-interes-detalle">
                                            Base: <?= dinero($detalle['interes_valor_base'] ?? $detalle['base_calculo']) ?>
                                            · Tasa: <?= number_format((float)($detalle['interes_tasa'] ?? 0), 2, ',', '.') ?>%
                                            <?php if (!empty($detalle['periodo_interes'])): ?>
                                                · Período: <?= e(date('m/Y', strtotime($detalle['periodo_interes']))) ?>
                                            <?php endif; ?>
                                        </span>

                                    <?php endif; ?>


                                    <?php if (
                                        empty($detalle['id_interes']) &&
                                        !empty(
                                            $detalle['tarifa_nombre']
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


                            <td class="numero">

                                <?php
                                    /*
                                     * Todavía no estamos trayendo cartera histórica
                                     * por concepto. Por ahora queda en cero.
                                     */
                                ?>

                                $0,00

                            </td>


                            <td class="numero">

                                <?= dinero(
                                    $detalle['subtotal']
                                ) ?>

                            </td>


                            <td class="numero">

                                <strong>

                                    <?= dinero(
                                        $detalle['subtotal']
                                    ) ?>

                                </strong>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php endif; ?>


                    <tr class="total-row">

                        <th>
                            TOTAL A PAGAR
                        </th>

                        <td class="numero">

                            <?= dinero(
                                $factura[
                                    'saldos_anteriores'
                                ]
                            ) ?>

                        </td>

                        <td class="numero">

                            <?= dinero(
                                $factura[
                                    'subtotal'
                                ] +
                                $factura[
                                    'intereses'
                                ]
                            ) ?>

                        </td>

                        <td class="numero">

                            <?= dinero(
                                $factura['total']
                            ) ?>

                        </td>

                    </tr>


                </tbody>


            </table>


            <!-- ==================================================
                 MENSAJE
            =================================================== -->

            <?php if (
                in_array(
                    $factura['estado'],
                    ['PAGADA'],
                    true
                )
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
                                $factura['estado']
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
                        <strong>cuentas_bancarias</strong>
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

                        <strong>
                            Total:
                        </strong>

                        <?= dinero(
                            $factura['total']
                        ) ?>

                    </p>

                </div>


            </div>


            <!-- ==================================================
                 OBSERVACIONES
            =================================================== -->

            <?php if (
                !empty(
                    $factura['observaciones']
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
