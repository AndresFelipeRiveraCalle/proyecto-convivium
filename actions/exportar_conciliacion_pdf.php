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


// ==========================================================
// FILTROS
// ==========================================================

$estado =
    trim(
        $_GET['estado_conciliacion'] ?? ''
    );

$buscar =
    trim(
        $_GET['buscar'] ?? ''
    );

$fechaDesde =
    trim(
        $_GET['fecha_desde'] ?? ''
    );

$fechaHasta =
    trim(
        $_GET['fecha_hasta'] ?? ''
    );

$exportarTodo =
    isset($_GET['todos'])
    && (int)$_GET['todos'] === 1;


// ==========================================================
// WHERE
// ==========================================================

$where = [
    "eb.tipo_movimiento = 'INGRESO'"
];

$params = [];


if (
    !$exportarTodo
    &&
    in_array(
        $estado,
        [
            'PENDIENTE',
            'CONCILIADO',
            'RECHAZADO',
            'CON_DIFERENCIA'
        ],
        true
    )
) {

    $where[] = "
        eb.estado_conciliacion =
            :estado_conciliacion
    ";

    $params[':estado_conciliacion'] =
        $estado;
}


if ($buscar !== '') {

    $where[] = "
        (
            eb.descripcion LIKE :buscar
            OR eb.referencia LIKE :buscar
            OR eb.numero_documento LIKE :buscar
            OR eb.archivo_origen LIKE :buscar
            OR u.codigo LIKE :buscar
            OR p.referencia LIKE :buscar
        )
    ";

    $params[':buscar'] =
        '%' . $buscar . '%';
}


if ($fechaDesde !== '') {

    $where[] = "
        eb.fecha_movimiento >= :fecha_desde
    ";

    $params[':fecha_desde'] =
        $fechaDesde;
}


if ($fechaHasta !== '') {

    $where[] = "
        eb.fecha_movimiento <= :fecha_hasta
    ";

    $params[':fecha_hasta'] =
        $fechaHasta;
}


$whereSql =
    implode(
        ' AND ',
        $where
    );


// ==========================================================
// CONSULTA
// ==========================================================

$sql = "
    SELECT
        eb.id_extracto,
        eb.fecha_movimiento,
        eb.descripcion,
        eb.referencia AS referencia_bancaria,
        eb.valor AS valor_banco,
        eb.estado_conciliacion,
        eb.observaciones,

        cb.banco,
        cb.tipo_cuenta,
        cb.numero_cuenta,

        p.id_pago,
        p.fecha_pago,
        p.valor AS valor_pago,
        p.referencia AS referencia_pago,
        p.fecha_conciliacion,

        u.codigo AS unidad_codigo

    FROM extractos_bancarios eb

    LEFT JOIN cuentas_bancarias cb
        ON cb.id_cuenta_bancaria =
           eb.id_cuenta_bancaria

    LEFT JOIN pagos p
        ON p.id_extracto =
           eb.id_extracto

    LEFT JOIN unidades u
        ON u.id_unidad =
           p.id_unidad

    WHERE
        $whereSql

    ORDER BY
        eb.fecha_movimiento DESC,
        eb.id_extracto DESC
";


$stmt =
    $conexion->prepare(
        $sql
    );

$stmt->execute(
    $params
);

$registros =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// TOTALES
// ==========================================================

$totalBanco = 0;
$totalPago = 0;
$totalDiferencia = 0;

foreach ($registros as $fila) {

    $totalBanco +=
        (float)$fila['valor_banco'];

    if (!empty($fila['id_pago'])) {

        $totalPago +=
            (float)$fila['valor_pago'];

        $totalDiferencia +=
            abs(
                (float)$fila['valor_banco']
                -
                (float)$fila['valor_pago']
            );
    }
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>
        Reporte de conciliaciones
    </title>

    <style>

        @page {
            size: A4 landscape;
            margin: 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            color: #222;
            margin: 0;
            background: #fff;
            font-size: 10px;
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 16px;
        }

        .toolbar button {
            padding: 8px 14px;
            border: 1px solid #999;
            background: #fff;
            cursor: pointer;
            border-radius: 5px;
        }

        h1 {
            margin: 0 0 4px 0;
            font-size: 20px;
        }

        .subtitulo {
            margin-bottom: 14px;
            color: #555;
        }

        .resumen {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin: 12px 0 16px 0;
        }

        .card {
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 6px;
        }

        .card strong {
            display: block;
            font-size: 14px;
            margin-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #cfcfcf;
            padding: 5px 6px;
            vertical-align: top;
        }

        th {
            background: #f0f0f0;
            font-weight: bold;
        }

        .estado {
            font-weight: bold;
        }

        .detalle {
            max-width: 260px;
        }

        .pie {
            margin-top: 14px;
            font-size: 9px;
            color: #666;
        }

        @media print {

            .toolbar {
                display: none;
            }

            body {
                font-size: 8.5px;
            }

            tr {
                page-break-inside: avoid;
            }
        }

    </style>

</head>


<body>


<div class="toolbar">

    <button
        type="button"
        onclick="window.print();"
    >
        Guardar / imprimir PDF
    </button>

    <button
        type="button"
        onclick="window.close();"
    >
        Cerrar
    </button>

</div>


<h1>
    Reporte de conciliaciones bancarias
</h1>

<div class="subtitulo">

    <?php if ($exportarTodo): ?>

        Todos los estados de conciliación

    <?php elseif ($estado !== ''): ?>

        Estado:
        <strong><?= e($estado) ?></strong>

    <?php else: ?>

        Todos los estados

    <?php endif; ?>

    <?php if ($fechaDesde !== '' || $fechaHasta !== ''): ?>

        |
        Rango:
        <?= e($fechaDesde !== '' ? $fechaDesde : 'Inicio') ?>
        -
        <?= e($fechaHasta !== '' ? $fechaHasta : 'Hoy') ?>

    <?php endif; ?>

</div>


<div class="resumen">

    <div class="card">
        Registros
        <strong><?= count($registros) ?></strong>
    </div>

    <div class="card">
        Total movimientos
        <strong><?= dinero($totalBanco) ?></strong>
    </div>

    <div class="card">
        Total pagos relacionados
        <strong><?= dinero($totalPago) ?></strong>
    </div>

    <div class="card">
        Diferencias
        <strong><?= dinero($totalDiferencia) ?></strong>
    </div>

</div>


<table>

    <thead>

        <tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Banco / cuenta</th>
            <th>Descripción</th>
            <th>Ref. banco</th>
            <th>Valor banco</th>
            <th>Estado</th>
            <th>Pago</th>
            <th>Unidad</th>
            <th>Valor pago</th>
            <th>Diferencia</th>
            <th>Fecha conciliación</th>
            <th>Observaciones</th>
        </tr>

    </thead>

    <tbody>

    <?php if (empty($registros)): ?>

        <tr>
            <td colspan="13" style="text-align:center;">
                No existen registros para los filtros seleccionados.
            </td>
        </tr>

    <?php else: ?>

        <?php foreach ($registros as $fila): ?>

            <?php

                $diferencia =
                    !empty($fila['id_pago'])
                        ? abs(
                            (float)$fila['valor_banco']
                            -
                            (float)$fila['valor_pago']
                        )
                        : null;

            ?>

            <tr>

                <td>
                    #<?= (int)$fila['id_extracto'] ?>
                </td>

                <td>
                    <?= e($fila['fecha_movimiento']) ?>
                </td>

                <td>
                    <?= e($fila['banco'] ?? '-') ?>

                    <?php if (!empty($fila['tipo_cuenta'])): ?>
                        <br>
                        <?= e($fila['tipo_cuenta']) ?>
                    <?php endif; ?>

                    <?php if (!empty($fila['numero_cuenta'])): ?>
                        <br>
                        <?= e($fila['numero_cuenta']) ?>
                    <?php endif; ?>
                </td>

                <td class="detalle">
                    <?= e($fila['descripcion'] ?? '-') ?>
                </td>

                <td>
                    <?= e($fila['referencia_bancaria'] ?? '-') ?>
                </td>

                <td>
                    <?= dinero($fila['valor_banco']) ?>
                </td>

                <td class="estado">
                    <?= e($fila['estado_conciliacion']) ?>
                </td>

                <td>
                    <?= !empty($fila['id_pago'])
                        ? '#' . (int)$fila['id_pago']
                        : '-'
                    ?>
                </td>

                <td>
                    <?= e($fila['unidad_codigo'] ?? '-') ?>
                </td>

                <td>
                    <?= !empty($fila['id_pago'])
                        ? dinero($fila['valor_pago'])
                        : '-'
                    ?>
                </td>

                <td>
                    <?= $diferencia !== null
                        ? dinero($diferencia)
                        : '-'
                    ?>
                </td>

                <td>
                    <?= e($fila['fecha_conciliacion'] ?? '-') ?>
                </td>

                <td class="detalle">
                    <?= e($fila['observaciones'] ?? '-') ?>
                </td>

            </tr>

        <?php endforeach; ?>

    <?php endif; ?>

    </tbody>

</table>


<div class="pie">

    Generado:
    <?= e(date('d/m/Y H:i:s')) ?>

</div>


</body>

</html>
