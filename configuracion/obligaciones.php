<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// FILTROS
// ==========================================================

$idUnidad = (int)($_GET['id_unidad'] ?? 0);

$estado = trim($_GET['estado'] ?? '');

$periodo = trim($_GET['periodo'] ?? '');


// ==========================================================
// CONSULTAR UNIDADES
// ==========================================================

$sqlUnidades = "
    SELECT
        id_unidad,
        codigo,
        nombre
    FROM unidades
    WHERE activo = 1
    ORDER BY codigo ASC
";

$stmtUnidades = $conexion->prepare($sqlUnidades);
$stmtUnidades->execute();

$unidades = $stmtUnidades->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// CONSULTAR PERÍODOS
// ==========================================================

$sqlPeriodos = "
    SELECT DISTINCT periodo
    FROM obligaciones
    ORDER BY periodo DESC
";

$stmtPeriodos = $conexion->prepare($sqlPeriodos);
$stmtPeriodos->execute();

$periodos = $stmtPeriodos->fetchAll(PDO::FETCH_COLUMN);


// ==========================================================
// CONSULTAR OBLIGACIONES
// ==========================================================

$sql = "
    SELECT
        o.id_obligacion,
        o.id_unidad,
        u.codigo AS unidad,
        u.nombre AS nombre_unidad,

        o.id_tarifa,
        tf.nombre AS tarifa,

        o.periodo,
        o.fecha_generacion,
        o.fecha_vencimiento,

        o.valor,
        o.valor_pagado,
        o.saldo,

        o.estado,
        o.observaciones

    FROM obligaciones o

    INNER JOIN unidades u
        ON u.id_unidad = o.id_unidad

    INNER JOIN tarifas_facturacion tf
        ON tf.id_tarifa = o.id_tarifa

    WHERE 1 = 1
";


// ==========================================================
// APLICAR FILTRO UNIDAD
// ==========================================================

if ($idUnidad > 0) {

    $sql .= "
        AND o.id_unidad = :id_unidad
    ";
}


// ==========================================================
// APLICAR FILTRO ESTADO
// ==========================================================

if ($estado !== '') {

    $sql .= "
        AND o.estado = :estado
    ";
}


// ==========================================================
// APLICAR FILTRO PERÍODO
// ==========================================================

if ($periodo !== '') {

    $sql .= "
        AND o.periodo = :periodo
    ";
}


// ==========================================================
// ORDEN
// ==========================================================

$sql .= "
    ORDER BY
        o.periodo DESC,
        u.codigo ASC,
        o.id_obligacion ASC
";


$stmt = $conexion->prepare($sql);


// ==========================================================
// PARÁMETROS
// ==========================================================

$params = [];

if ($idUnidad > 0) {

    $params[':id_unidad'] = $idUnidad;
}

if ($estado !== '') {

    $params[':estado'] = $estado;
}

if ($periodo !== '') {

    $params[':periodo'] = $periodo;
}


// ==========================================================
// EJECUTAR
// ==========================================================

$stmt->execute($params);

$obligaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// TOTALES
// ==========================================================

$totalObligaciones = count($obligaciones);

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


        <!-- ==================================================
             ENCABEZADO
        =================================================== -->

        <div class="form-card">


            <div class="form-header">

                <div>

                    <h1>
                        Obligaciones
                    </h1>

                    <p>
                        Consulta las obligaciones financieras
                        generadas para las unidades.
                    </p>

                </div>


                <div>

                    <a
                        href="<?= BASE_URL ?>configuracion/generar_obligacion.php"
                        class="btn-primary"
                    >
                        ⚙ Generar obligaciones
                    </a>

                </div>

            </div>


            <!-- ==================================================
                 RESUMEN
            =================================================== -->

            <div class="cards-resumen">


                <div class="card-resumen">

                    <span>
                        Obligaciones
                    </span>

                    <strong>
                        <?= $totalObligaciones ?>
                    </strong>

                </div>


                <div class="card-resumen">

                    <span>
                        Valor total
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
                        Saldo
                    </span>

                    <strong>
                        <?= formatoMoneda($totalSaldo) ?>
                    </strong>

                </div>


            </div>


            <!-- ==================================================
                 FILTROS
            =================================================== -->

            <form
                method="GET"
                action="<?= BASE_URL ?>configuracion/obligaciones.php"
                class="filtros"
            >


                <div class="form-group">

                    <label for="periodo">
                        Período
                    </label>

                    <select
                        name="periodo"
                        id="periodo"
                    >

                        <option value="">
                            Todos los períodos
                        </option>


                        <?php foreach ($periodos as $p): ?>

                            <option
                                value="<?= htmlspecialchars($p) ?>"
                                <?= $periodo === $p
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                <?= htmlspecialchars(
                                    formatoPeriodo($p)
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="id_unidad">
                        Unidad
                    </label>

                    <select
                        name="id_unidad"
                        id="id_unidad"
                    >

                        <option value="">
                            Todas las unidades
                        </option>


                        <?php foreach ($unidades as $unidad): ?>

                            <option
                                value="<?= (int)$unidad['id_unidad'] ?>"
                                <?= $idUnidad ==
                                    $unidad['id_unidad']
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                <?= htmlspecialchars(
                                    $unidad['codigo']
                                ) ?>

                                -
                                <?= htmlspecialchars(
                                    $unidad['nombre']
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="estado">
                        Estado
                    </label>

                    <select
                        name="estado"
                        id="estado"
                    >

                        <option value="">
                            Todos
                        </option>

                        <option
                            value="Pendiente"
                            <?= $estado === 'Pendiente'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Pendiente
                        </option>

                        <option
                            value="Pagada"
                            <?= $estado === 'Pagada'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Pagada
                        </option>

                        <option
                            value="Abono"
                            <?= $estado === 'Abono'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Abono
                        </option>

                        <option
                            value="Vencida"
                            <?= $estado === 'Vencida'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Vencida
                        </option>

                        <option
                            value="Anulada"
                            <?= $estado === 'Anulada'
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Anulada
                        </option>

                    </select>

                </div>


                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        🔎 Filtrar
                    </button>


                    <a
                        href="<?= BASE_URL ?>configuracion/obligaciones.php"
                        class="btn-secondary"
                    >
                        Limpiar
                    </a>

                </div>


            </form>


            <!-- ==================================================
                 TABLA
            =================================================== -->

            <div class="tabla-responsive">

                <table class="tabla">

                    <thead>

                        <tr>

                            <th>
                                Unidad
                            </th>

                            <th>
                                Tarifa
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

                            <th>
                                Acción
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (empty($obligaciones)): ?>


                        <tr>

                            <td
                                colspan="9"
                                style="text-align:center;"
                            >

                                No existen obligaciones
                                para los filtros seleccionados.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $obligaciones
                            as $obligacion
                        ): ?>


                            <tr>


                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $obligacion['unidad']
                                        ) ?>
                                    </strong>

                                    <br>

                                    <small>
                                        <?= htmlspecialchars(
                                            $obligacion[
                                                'nombre_unidad'
                                            ]
                                        ) ?>
                                    </small>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $obligacion['tarifa']
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        formatoPeriodo(
                                            $obligacion['periodo']
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <?= formatoFecha(
                                        $obligacion[
                                            'fecha_vencimiento'
                                        ]
                                    ) ?>

                                </td>


                                <td>

                                    <?= formatoMoneda(
                                        $obligacion['valor']
                                    ) ?>

                                </td>


                                <td>

                                    <?= formatoMoneda(
                                        $obligacion[
                                            'valor_pagado'
                                        ]
                                    ) ?>

                                </td>


                                <td>

                                    <strong>
                                        <?= formatoMoneda(
                                            $obligacion['saldo']
                                        ) ?>
                                    </strong>

                                </td>


                                <td>

                                    <span
                                        class="estado <?= claseEstado(
                                            $obligacion['estado']
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $obligacion['estado']
                                        ) ?>

                                    </span>

                                </td>


                                <td>

                                    <a
                                        href="<?= BASE_URL ?>configuracion/ver_obligacion.php?id=<?= (int)$obligacion['id_obligacion'] ?>"
                                        class="btn-secondary"
                                    >
                                        Ver
                                    </a>

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


</body>

</html>