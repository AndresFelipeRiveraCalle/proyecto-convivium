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

$buscar =
    trim(
        $_GET['buscar'] ?? ''
    );

$estado =
    trim(
        $_GET['estado'] ?? ''
    );

$periodo =
    trim(
        $_GET['periodo'] ?? ''
    );


// ==========================================================
// WHERE DINÁMICO
// ==========================================================

$where = [
    "1 = 1"
];

$params = [];


// ==========================================================
// FILTRO DE BÚSQUEDA
// Busca por unidad, factura, descripción o concepto.
// ==========================================================

if ($buscar !== '') {

    $where[] = "
        (
            u.codigo LIKE :buscar_unidad
            OR f.numero_factura LIKE :buscar_factura
            OR c.descripcion LIKE :buscar_descripcion
            OR cf.nombre LIKE :buscar_concepto
        )
    ";

    $valorBuscar = '%' . $buscar . '%';

    $params[':buscar_unidad'] = $valorBuscar;
    $params[':buscar_factura'] = $valorBuscar;
    $params[':buscar_descripcion'] = $valorBuscar;
    $params[':buscar_concepto'] = $valorBuscar;
}


if (
    in_array(
        $estado,
        ['PENDIENTE', 'PAGADA', 'ANULADA'],
        true
    )
) {

    $where[] = "
        c.estado = :estado
    ";

    $params[':estado'] =
        $estado;
}


if ($periodo !== '') {

    $where[] = "
        DATE_FORMAT(
            c.periodo,
            '%Y-%m'
        ) = :periodo
    ";

    $params[':periodo'] =
        $periodo;
}


$whereSql =
    implode(
        ' AND ',
        $where
    );


// ==========================================================
// RESUMEN GENERAL
// ==========================================================

$sqlResumen = "
    SELECT
        COUNT(*) AS total_registros,

        COALESCE(
            SUM(c.valor_original),
            0
        ) AS total_original,

        COALESCE(
            SUM(c.valor_pagado),
            0
        ) AS total_pagado,

        COALESCE(
            SUM(c.saldo),
            0
        ) AS total_saldo,

        COALESCE(
            SUM(
                CASE
                    WHEN
                        c.estado = 'PENDIENTE'
                        AND c.saldo > 0
                        AND c.fecha_vencimiento < CURDATE()
                    THEN c.saldo
                    ELSE 0
                END
            ),
            0
        ) AS total_vencido

    FROM cartera c

    INNER JOIN unidades u
        ON u.id_unidad =
           c.id_unidad

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
        $whereSql
";


$stmtResumen =
    $conexion->prepare(
        $sqlResumen
    );


$stmtResumen->execute(
    $params
);


$resumen =
    $stmtResumen->fetch(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// LISTADO
// ==========================================================

$sql = "
    SELECT
        c.id_factura,
        c.id_unidad,
        MIN(c.periodo) AS periodo,
        MIN(c.fecha_vencimiento) AS fecha_vencimiento,

        SUM(c.valor_original) AS valor_original,
        SUM(c.valor_pagado) AS valor_pagado,
        SUM(c.saldo) AS saldo,

        u.codigo AS unidad_codigo,
        u.nombre AS unidad_nombre,

        dtu.nombre_grupo,

        f.numero_factura,
        f.estado AS estado_factura,

        COUNT(c.id_cartera) AS cantidad_conceptos,

        SUM(
            CASE
                WHEN fd.id_interes IS NOT NULL
                THEN 1
                ELSE 0
            END
        ) AS cantidad_intereses,

        COALESCE(
            SUM(
                CASE
                    WHEN fd.id_interes IS NOT NULL
                    THEN c.valor_original
                    ELSE 0
                END
            ),
            0
        ) AS valor_intereses,

        COALESCE(
            SUM(
                CASE
                    WHEN fd.id_interes IS NOT NULL
                    THEN c.valor_pagado
                    ELSE 0
                END
            ),
            0
        ) AS pagado_intereses,

        COALESCE(
            SUM(
                CASE
                    WHEN fd.id_interes IS NOT NULL
                    THEN c.saldo
                    ELSE 0
                END
            ),
            0
        ) AS saldo_intereses,

        GROUP_CONCAT(
            DISTINCT
            CASE
                WHEN fd.id_interes IS NOT NULL
                THEN CONCAT(
                    COALESCE(
                        cf.nombre,
                        'Intereses de mora'
                    ),
                    ' [MORA]'
                )
                ELSE COALESCE(
                    cf.nombre,
                    tobl.nombre,
                    c.descripcion
                )
            END
            ORDER BY c.id_cartera
            SEPARATOR ' | '
        ) AS conceptos,

        CASE
            WHEN
                SUM(
                    CASE
                        WHEN
                            c.estado = 'PENDIENTE'
                            AND c.saldo > 0
                            AND c.fecha_vencimiento < CURDATE()
                        THEN c.saldo
                        ELSE 0
                    END
                ) > 0
            THEN 1
            ELSE 0
        END AS vencida,

        CASE
            WHEN
                SUM(CASE WHEN c.estado = 'ANULADA' THEN 1 ELSE 0 END)
                    = COUNT(c.id_cartera)
            THEN 'ANULADA'

            WHEN
                SUM(c.saldo) <= 0.009
            THEN 'PAGADA'

            ELSE 'PENDIENTE'
        END AS estado

    FROM cartera c

    INNER JOIN unidades u
        ON u.id_unidad =
           c.id_unidad

    LEFT JOIN detalle_tipos_unidad dtu
        ON dtu.id_tipo_config =
           u.id_tipo_config

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
        $whereSql

    GROUP BY
        c.id_factura,
        c.id_unidad,
        u.codigo,
        u.nombre,
        dtu.nombre_grupo,
        f.numero_factura,
        f.estado

    ORDER BY
        vencida DESC,
        fecha_vencimiento,
        u.codigo,
        c.id_factura
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

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <?php include ROOT_PATH . "/includes/head.php"; ?>

    <style>
        .mora-badge {
            display: inline-block;
            margin-top: 5px;
            padding: 3px 8px;
            border-radius: 999px;
            background: #fff3cd;
            color: #7a5300;
            border: 1px solid #ffe08a;
            font-size: 12px;
            font-weight: 700;
        }

        .mora-resumen {
            display: block;
            margin-top: 5px;
            color: #7a5300;
            font-size: 12px;
            line-height: 1.4;
        }
    </style>

</head>


<body>


<?php include ROOT_PATH . "/includes/header.php"; ?>


<div class="contenedor">


    <?php include ROOT_PATH . "/includes/sidebar.php"; ?>


    <main class="contenido">


        <!-- ======================================================
             TÍTULO
        ======================================================= -->

        <h2 align="center">
            Estado de cartera
        </h2>


        <p align="center">
            Consulta de obligaciones, pagos y saldos pendientes.
        </p>


        <br>


        <!-- ======================================================
             RESUMEN
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Resumen
                </h3>

                <br>

                <div class="tabla-responsive">

                    <table class="tabla">

                        <thead>

                            <tr>

                                <th>
                                    Registros
                                </th>

                                <th>
                                    Valor original
                                </th>

                                <th>
                                    Pagado
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
                                    <?= (int)($resumen['total_registros'] ?? 0) ?>
                                </td>

                                <td>
                                    <?= dinero($resumen['total_original'] ?? 0) ?>
                                </td>

                                <td>
                                    <?= dinero($resumen['total_pagado'] ?? 0) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= dinero($resumen['total_saldo'] ?? 0) ?>
                                    </strong>
                                </td>

                                <td>

                                    <?php if (
                                        (float)($resumen['total_vencido'] ?? 0) > 0
                                    ): ?>

                                        <span class="inactivo">
                                            <?= dinero($resumen['total_vencido']) ?>
                                        </span>

                                    <?php else: ?>

                                        <?= dinero(0) ?>

                                    <?php endif; ?>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <br>


        <!-- ======================================================
             FILTROS
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Filtros
                </h3>

                <br>

                <form
                    method="GET"
                    action=""
                >

                    <div
                        style="
                            display:grid;
                            grid-template-columns:
                                repeat(
                                    auto-fit,
                                    minmax(210px, 1fr)
                                );
                            gap:15px;
                        "
                    >

                        <div>

                            <label>
                                Buscar
                            </label>

                            <input
                                type="text"
                                name="buscar"
                                value="<?= e($buscar) ?>"
                                placeholder="Unidad, factura, concepto..."
                            >

                        </div>


                        <div>

                            <label>
                                Estado
                            </label>

                            <select name="estado">

                                <option value="">
                                    Todos
                                </option>

                                <option
                                    value="PENDIENTE"
                                    <?= $estado === 'PENDIENTE'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Pendiente
                                </option>

                                <option
                                    value="PAGADA"
                                    <?= $estado === 'PAGADA'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Pagada
                                </option>

                                <option
                                    value="ANULADA"
                                    <?= $estado === 'ANULADA'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Anulada
                                </option>

                            </select>

                        </div>


                        <div>

                            <label>
                                Período
                            </label>

                            <input
                                type="month"
                                name="periodo"
                                value="<?= e($periodo) ?>"
                            >

                        </div>

                    </div>


                    <br>


                    <div class="form-actions">

                        <button
                            type="submit"
                            class="btn-filtrar"
                        >
                            Filtrar
                        </button>


                        <a
                            href="<?= BASE_URL ?>configuracion/cartera.php"
                            class="btn-limpiar"
                        >
                            Limpiar
                        </a>

                    </div>

                </form>

            </div>

        </div>


        <br>


        <!-- ======================================================
             TABLA CARTERA
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Obligaciones
                </h3>

                <br>

                <div class="tabla-responsive">

                    <table class="tabla">

                        <thead>

                            <tr>

                                <th>
                                    Unidad
                                </th>

                                <th>
                                    Grupo
                                </th>

                                <th>
                                    Factura
                                </th>

                                <th>
                                    Período
                                </th>

                                <th>
                                    Conceptos
                                </th>

                                <th>
                                    Valor original
                                </th>

                                <th>
                                    Pagado
                                </th>

                                <th>
                                    Saldo
                                </th>

                                <th>
                                    Vencimiento
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


                        <?php if (empty($registros)): ?>

                            <tr>

                                <td
                                    colspan="11"
                                    align="center"
                                >
                                    No existen registros de cartera.
                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach ($registros as $fila): ?>

                                <tr>


                                    <td>
                                        <strong>
                                            <?= e($fila['unidad_codigo']) ?>
                                        </strong>
                                    </td>


                                    <td>
                                        <?= e($fila['nombre_grupo'] ?? '') ?>
                                    </td>


                                    <td>

                                        <?php if (
                                            !empty(
                                                $fila['numero_factura']
                                            )
                                        ): ?>

                                            <?= e(
                                                $fila['numero_factura']
                                            ) ?>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </td>


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

                                        <strong>
                                            <?= (int)$fila['cantidad_conceptos'] ?>
                                            concepto<?= (int)$fila['cantidad_conceptos'] === 1 ? '' : 's' ?>
                                        </strong>

                                        <br>

                                        <small>
                                            <?= e($fila['conceptos'] ?? '') ?>
                                        </small>

                                        <?php if (
                                            (int)($fila['cantidad_intereses'] ?? 0) > 0
                                        ): ?>

                                            <br>

                                            <span class="mora-badge">
                                                MORA
                                            </span>

                                            <span class="mora-resumen">
                                                Interés facturado:
                                                <?= dinero($fila['valor_intereses'] ?? 0) ?>
                                                · Saldo de mora:
                                                <?= dinero($fila['saldo_intereses'] ?? 0) ?>
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td class="numero">

                                        <?= dinero(
                                            $fila['valor_original']
                                        ) ?>

                                    </td>


                                    <td class="numero">

                                        <?= dinero(
                                            $fila['valor_pagado']
                                        ) ?>

                                    </td>


                                    <td class="numero">

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

                                        <?php else: ?>

                                            <strong>
                                                PENDIENTE
                                            </strong>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <div
                                            style="
                                                display:flex;
                                                gap:6px;
                                                flex-wrap:wrap;
                                            "
                                        >

                                            <a
                                                href="<?= BASE_URL ?>configuracion/cartera_detalle.php?id_unidad=<?= (int)$fila['id_unidad'] ?>"
                                                class="btn-secondary"
                                            >
                                                Ver cartera
                                            </a>

                                            <?php if (
                                                !empty(
                                                    $fila['id_factura']
                                                )
                                            ): ?>

                                                <a
                                                    href="<?= BASE_URL ?>configuracion/factura_detalle.php?id=<?= (int)$fila['id_factura'] ?>"
                                                    class="btn-secondary"
                                                >
                                                    Ver factura
                                                </a>

                                            <?php endif; ?>

                                        </div>

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
