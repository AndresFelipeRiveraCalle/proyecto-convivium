<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// ID DE LA OBLIGACIÓN
// ==========================================================

$idObligacion = (int)($_GET['id'] ?? 0);

if ($idObligacion <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/obligaciones.php?tipo=error&mensaje=" .
        urlencode("Obligación no válida.")
    );

    exit;
}


// ==========================================================
// BUSCAR OBLIGACIÓN PRINCIPAL
// ==========================================================

$sqlPrincipal = "
    SELECT
        o.id_obligacion,
        o.id_unidad,

        u.codigo AS unidad,
        u.nombre AS nombre_unidad,

        o.periodo

    FROM obligaciones o

    INNER JOIN unidades u
        ON u.id_unidad = o.id_unidad

    WHERE o.id_obligacion = :id_obligacion

    LIMIT 1
";

$stmtPrincipal = $conexion->prepare($sqlPrincipal);

$stmtPrincipal->execute([
    ':id_obligacion' => $idObligacion
]);

$principal = $stmtPrincipal->fetch(PDO::FETCH_ASSOC);


// ==========================================================
// VALIDAR EXISTENCIA
// ==========================================================

if (!$principal) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/obligaciones.php?tipo=error&mensaje=" .
        urlencode("La obligación no existe.")
    );

    exit;
}


$idUnidad = (int)$principal['id_unidad'];

$periodo = $principal['periodo'];


// ==========================================================
// BUSCAR TODAS LAS OBLIGACIONES DEL COBRO
// ==========================================================

$sqlObligaciones = "
    SELECT

        o.id_obligacion,

        o.id_unidad,

        o.id_tarifa,

        tf.nombre AS tarifa,

        tf.valor AS valor_tarifa,

        tf.id_concepto,

        c.nombre AS concepto,

        c.descripcion AS descripcion_concepto,

        c.tipo_calculo,

        o.periodo,

        o.fecha_generacion,

        o.fecha_vencimiento,

        o.valor,

        o.valor_pagado,

        o.saldo,

        o.estado,

        o.observaciones

    FROM obligaciones o

    INNER JOIN tarifas_facturacion tf
        ON tf.id_tarifa = o.id_tarifa

    LEFT JOIN conceptos_facturacion c
        ON c.id_concepto = tf.id_concepto

    WHERE o.id_unidad = :id_unidad
      AND o.periodo = :periodo

    ORDER BY

        c.nombre ASC,
        o.id_obligacion ASC
";

$stmtObligaciones = $conexion->prepare($sqlObligaciones);

$stmtObligaciones->execute([
    ':id_unidad' => $idUnidad,
    ':periodo' => $periodo
]);

$obligaciones = $stmtObligaciones->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// TOTALES
// ==========================================================

$totalValor = 0;
$totalPagado = 0;
$totalSaldo = 0;

foreach ($obligaciones as $obligacion) {

    $totalValor += (float)$obligacion['valor'];

    $totalPagado += (float)$obligacion['valor_pagado'];

    $totalSaldo += (float)$obligacion['saldo'];
}


// ==========================================================
// FORMATO MONEDA
// ==========================================================

function formatoMoneda($valor)
{
    return '$ ' . number_format(
        (float)$valor,
        2,
        ',',
        '.'
    );
}


// ==========================================================
// FORMATO FECHA
// ==========================================================

function formatoFecha($fecha)
{
    if (empty($fecha)) {
        return '';
    }

    return date(
        'd/m/Y',
        strtotime($fecha)
    );
}


// ==========================================================
// FORMATO PERÍODO
// ==========================================================

function formatoPeriodo($periodo)
{
    if (empty($periodo)) {
        return '';
    }

    $fecha = strtotime($periodo);

    $meses = [
        1  => 'Enero',
        2  => 'Febrero',
        3  => 'Marzo',
        4  => 'Abril',
        5  => 'Mayo',
        6  => 'Junio',
        7  => 'Julio',
        8  => 'Agosto',
        9  => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre'
    ];

    return $meses[(int)date('n', $fecha)]
        . ' '
        . date('Y', $fecha);
}


// ==========================================================
// CLASE ESTADO
// ==========================================================

function claseEstado($estado)
{
    switch ($estado) {

        case 'Pagada':
            return 'estado-pagada';

        case 'Pendiente':
            return 'estado-pendiente';

        case 'Abono':
            return 'estado-abono';

        case 'Vencida':
            return 'estado-vencida';

        case 'Anulada':
            return 'estado-anulada';

        default:
            return '';
    }
}


// ==========================================================
// IDENTIFICAR GRUPO DEL CONCEPTO
// ==========================================================

function grupoConcepto($obligacion)
{
    $concepto = strtolower(
        trim(
            $obligacion['concepto'] ?? ''
        )
    );

    $tarifa = strtolower(
        trim(
            $obligacion['tarifa'] ?? ''
        )
    );

    $texto = $concepto . ' ' . $tarifa;


    if (
        strpos($texto, 'administr') !== false
    ) {
        return 'Administración';
    }


    if (
        strpos($texto, 'parqueadero') !== false ||
        strpos($texto, 'parqueadero') !== false
    ) {
        return 'Parqueaderos';
    }


    if (
        strpos($texto, 'multa') !== false
    ) {
        return 'Multas';
    }


    if (
        strpos($texto, 'interes') !== false ||
        strpos($texto, 'interés') !== false
    ) {
        return 'Intereses';
    }


    if (
        strpos($texto, 'anterior') !== false ||
        strpos($texto, 'saldo') !== false
    ) {
        return 'Saldos anteriores';
    }


    return 'Otros conceptos';
}


// ==========================================================
// AGRUPAR OBLIGACIONES
// ==========================================================

$grupos = [
    'Administración' => [],
    'Parqueaderos' => [],
    'Multas' => [],
    'Saldos anteriores' => [],
    'Intereses' => [],
    'Otros conceptos' => []
];


foreach ($obligaciones as $obligacion) {

    $grupo = grupoConcepto($obligacion);

    $grupos[$grupo][] = $obligacion;
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


        <div class="form-card">


            <!-- ==================================================
                 ENCABEZADO
            =================================================== -->

            <div class="form-header">

                <div>

                    <h1>
                        Detalle de cuenta de cobro
                    </h1>

                    <p>
                        Detalle de los conceptos cobrados a la unidad.
                    </p>

                </div>


                <div>

                    <a
                        href="<?= BASE_URL ?>configuracion/obligaciones.php"
                        class="btn-secondary"
                    >
                        ← Obligaciones
                    </a>

                </div>

            </div>


            <!-- ==================================================
                 INFORMACIÓN DE LA UNIDAD
            =================================================== -->

            <div class="info-box">

                <strong>
                    Información de la cuenta
                </strong>


                <p>

                    <strong>
                        Unidad:
                    </strong>

                    <?= htmlspecialchars(
                        $principal['unidad']
                    ) ?>

                    -

                    <?= htmlspecialchars(
                        $principal['nombre_unidad']
                    ) ?>

                    <br>


                    <strong>
                        Período:
                    </strong>

                    <?= htmlspecialchars(
                        formatoPeriodo($periodo)
                    ) ?>

                </p>

            </div>


            <!-- ==================================================
                 RESUMEN FINANCIERO
            =================================================== -->

            <div class="cards-resumen">


                <div class="card-resumen">

                    <span>
                        Total cobrado
                    </span>

                    <strong>
                        <?= formatoMoneda($totalValor) ?>
                    </strong>

                </div>


                <div class="card-resumen">

                    <span>
                        Total pagado
                    </span>

                    <strong>
                        <?= formatoMoneda($totalPagado) ?>
                    </strong>

                </div>


                <div class="card-resumen">

                    <span>
                        Saldo pendiente
                    </span>

                    <strong>
                        <?= formatoMoneda($totalSaldo) ?>
                    </strong>

                </div>


            </div>


            <!-- ==================================================
                 DETALLE POR CONCEPTO
            =================================================== -->

            <?php foreach ($grupos as $nombreGrupo => $items): ?>


                <?php if (!empty($items)): ?>


                    <div class="info-box">


                        <h3>
                            <?= htmlspecialchars(
                                $nombreGrupo
                            ) ?>
                        </h3>


                        <div class="tabla-responsive">


                            <table class="tabla">


                                <thead>

                                    <tr>

                                        <th>
                                            Concepto
                                        </th>

                                        <th>
                                            Período
                                        </th>

                                        <th>
                                            Vencimiento
                                        </th>

                                        <th>
                                            Valor
                                        </th>

                                        <th>
                                            Pagado
                                        </th>

                                        <th>
                                            Saldo
                                        </th>

                                        <th>
                                            Estado
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>


                                <?php foreach ($items as $item): ?>


                                    <tr>


                                        <td>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $item['concepto']
                                                    ??
                                                    $item['tarifa']
                                                ) ?>

                                            </strong>


                                            <?php if (
                                                !empty(
                                                    $item['descripcion_concepto']
                                                )
                                            ): ?>

                                                <br>

                                                <small>

                                                    <?= htmlspecialchars(
                                                        $item[
                                                            'descripcion_concepto'
                                                        ]
                                                    ) ?>

                                                </small>

                                            <?php endif; ?>


                                            <br>

                                            <small>

                                                <?= htmlspecialchars(
                                                    $item['tarifa']
                                                ) ?>

                                            </small>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                formatoPeriodo(
                                                    $item['periodo']
                                                )
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= formatoFecha(
                                                $item[
                                                    'fecha_vencimiento'
                                                ]
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= formatoMoneda(
                                                $item['valor']
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= formatoMoneda(
                                                $item[
                                                    'valor_pagado'
                                                ]
                                            ) ?>

                                        </td>


                                        <td>

                                            <strong>

                                                <?= formatoMoneda(
                                                    $item['saldo']
                                                ) ?>

                                            </strong>

                                        </td>


                                        <td>

                                            <span
                                                class="estado <?= claseEstado(
                                                    $item['estado']
                                                ) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    $item['estado']
                                                ) ?>

                                            </span>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                                </tbody>


                            </table>


                        </div>


                    </div>


                <?php endif; ?>


            <?php endforeach; ?>


            <!-- ==================================================
                 TOTAL
            =================================================== -->

            <div class="info-box">


                <h3>
                    Resumen de la cuenta
                </h3>


                <div class="tabla-responsive">


                    <table class="tabla">


                        <tbody>


                            <tr>

                                <td>
                                    <strong>
                                        Total cobrado
                                    </strong>
                                </td>

                                <td style="text-align:right;">

                                    <strong>
                                        <?= formatoMoneda(
                                            $totalValor
                                        ) ?>
                                    </strong>

                                </td>

                            </tr>


                            <tr>

                                <td>
                                    Total pagado
                                </td>

                                <td style="text-align:right;">

                                    <?= formatoMoneda(
                                        $totalPagado
                                    ) ?>

                                </td>

                            </tr>


                            <tr>

                                <td>
                                    <strong>
                                        Saldo pendiente
                                    </strong>
                                </td>

                                <td style="text-align:right;">

                                    <strong>
                                        <?= formatoMoneda(
                                            $totalSaldo
                                        ) ?>
                                    </strong>

                                </td>

                            </tr>


                        </tbody>


                    </table>


                </div>


            </div>


            <!-- ==================================================
                 ACCIONES
            =================================================== -->

            <div class="form-actions">


                <a
                    href="<?= BASE_URL ?>configuracion/obligaciones.php"
                    class="btn-secondary"
                >
                    ← Volver a obligaciones
                </a>


                <?php if ($totalSaldo > 0): ?>

                    <button
                        type="button"
                        class="btn-primary"
                        disabled
                    >
                        💰 Registrar pago
                    </button>

                <?php endif; ?>


            </div>


        </div>


    </main>

</div>


</body>

</html>