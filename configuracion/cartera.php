<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// FUNCIONES
// Permite escapar texto y formatear valores monetarios.
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
// Obtiene los filtros enviados desde la pantalla.
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
// FECHA DE CORTE
// Define la fecha usada para evaluar la mora.
// ==========================================================

if ($periodo !== '') {

    $fechaCorte =
        date(
            'Y-m-t',
            strtotime(
                $periodo . '-01'
            )
        );

} else {

    $fechaCorte =
        date('Y-m-d');
}

// ==========================================================
// WHERE DINÁMICO
// Construye las condiciones de búsqueda del listado.
// ==========================================================

$where = [
    "1 = 1"
];


$params = [];


// ==========================================================
// FILTRO DE BÚSQUEDA
// Permite buscar por unidad, factura, descripción o concepto.
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


    $valorBuscar =
        '%' . $buscar . '%';


    $params[':buscar_unidad'] =
        $valorBuscar;


    $params[':buscar_factura'] =
        $valorBuscar;


    $params[':buscar_descripcion'] =
        $valorBuscar;


    $params[':buscar_concepto'] =
        $valorBuscar;
}


// ==========================================================
// FILTRO POR ESTADO
// Diferencia pendiente, mora, pagada y anulada.
// ==========================================================

if ($estado === 'EN_MORA') {

    $where[] = "
        c.estado = 'PENDIENTE'
        AND c.saldo > 0.009
        AND c.fecha_vencimiento < :fecha_corte_estado_mora
    ";

    $params[':fecha_corte_estado_mora'] =
        $fechaCorte;

} elseif ($estado === 'PENDIENTE') {

    $where[] = "
        c.estado = 'PENDIENTE'
        AND c.saldo > 0.009
        AND c.fecha_vencimiento >= :fecha_corte_estado_pendiente
    ";

    $params[':fecha_corte_estado_pendiente'] =
        $fechaCorte;

} elseif (
    in_array(
        $estado,
        ['PAGADA', 'ANULADA'],
        true
    )
) {

    $where[] = "
        c.estado = :estado
    ";

    $params[':estado'] =
        $estado;
}


// ==========================================================
// FILTRO POR PERÍODO
// Permite consultar obligaciones de un mes específico.
// ==========================================================

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


// ==========================================================
// ARMAR WHERE
// Une todas las condiciones dinámicas.
// ==========================================================

$whereSql =
    implode(
        ' AND ',
        $where
    );


// ==========================================================
// RESUMEN GENERAL
// Calcula los principales valores de la cartera filtrada.
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
                        AND c.saldo > 0.009
                        AND c.fecha_vencimiento < :fecha_corte
                    THEN c.saldo
                    ELSE 0
                END
            ),
            0
        ) AS total_mora,

        COALESCE(
            SUM(
                CASE
                    WHEN
                        c.estado = 'PENDIENTE'
                        AND c.saldo > 0.009
                        AND c.fecha_vencimiento < :fecha_corte
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS obligaciones_mora

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


// ==========================================================
// EJECUTAR RESUMEN
// Obtiene los totales de la cartera.
// ==========================================================

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
// Obtiene las obligaciones y calcula su estado visual.
// ==========================================================

$sql = "
    SELECT
        c.id_cartera,
        c.id_factura,
        c.id_detalle,
        c.id_unidad,
        c.id_tipo_obligacion,
        c.periodo,
        c.descripcion,
        c.valor_original,
        c.valor_pagado,
        c.saldo,
        c.fecha_vencimiento,
        c.estado,
        c.observaciones,

        u.codigo AS unidad_codigo,
        u.nombre AS unidad_nombre,

        dtu.nombre_grupo,

        f.numero_factura,
        f.estado AS estado_factura,

        cf.nombre AS concepto,
        cf.aplica_interes_mora,

        tobl.nombre AS tipo_obligacion,

        CASE
            WHEN
                c.estado = 'ANULADA'
            THEN 'ANULADA'

            WHEN
                c.estado = 'PAGADA'
                OR c.saldo <= 0.009
            THEN 'PAGADA'

            WHEN
                c.estado = 'PENDIENTE'
                AND c.saldo > 0.009
                AND c.fecha_vencimiento < :fecha_corte_visual
            THEN 'EN_MORA'

            ELSE 'PENDIENTE'
        END AS estado_visual,

        CASE
            WHEN
                c.estado = 'PENDIENTE'
                AND c.saldo > 0.009
                AND c.fecha_vencimiento < :fecha_corte_meses
            THEN
                TIMESTAMPDIFF(
                    MONTH,
                    DATE_FORMAT(
                        c.fecha_vencimiento,
                        '%Y-%m-01'
                    ),
                    DATE_FORMAT(
                        :fecha_corte_calculo,
                        '%Y-%m-01'
                    )
                )

            ELSE 0
        END AS meses_mora

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

    ORDER BY
        CASE
            WHEN
                c.estado = 'PENDIENTE'
                AND c.saldo > 0.009
                AND c.fecha_vencimiento < :fecha_corte
            THEN 0

            ELSE 1
        END,

        c.fecha_vencimiento,
        u.codigo,
        c.id_cartera
";


// ==========================================================
// EJECUTAR LISTADO
// Obtiene los registros de cartera.
// ==========================================================

$stmt =
    $conexion->prepare(
        $sql
    );


$paramsListado =
    $params;


$paramsListado[':fecha_corte_visual'] =
    $fechaCorte;


$paramsListado[':fecha_corte_meses'] =
    $fechaCorte;


$paramsListado[':fecha_corte_calculo'] =
    $fechaCorte;


$stmt->execute(
    $paramsListado
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
            Consulta de obligaciones, pagos, saldos pendientes y mora.
        </p>
        <p align="center">

            <small>

                Fecha de corte:
                <strong>
                    <?= e(
                        date(
                            'd/m/Y',
                            strtotime($fechaCorte)
                        )
                    ) ?>
                </strong>

            </small>

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
                                    Obligaciones en mora
                                </th>

                                <th>
                                    Saldo en mora
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <tr>

                                <td>
                                    <?= (int)($resumen['total_registros'] ?? 0) ?>
                                </td>


                                <td>
                                    <?= dinero(
                                        $resumen['total_original']
                                        ?? 0
                                    ) ?>
                                </td>


                                <td>
                                    <?= dinero(
                                        $resumen['total_pagado']
                                        ?? 0
                                    ) ?>
                                </td>


                                <td>

                                    <strong>

                                        <?= dinero(
                                            $resumen['total_saldo']
                                            ?? 0
                                        ) ?>

                                    </strong>

                                </td>


                                <td>

                                    <?php if (
                                        (int)($resumen['obligaciones_mora'] ?? 0) > 0
                                    ): ?>

                                        <span class="inactivo">

                                            <?= (int)$resumen[
                                                'obligaciones_mora'
                                            ] ?>

                                        </span>

                                    <?php else: ?>

                                        0

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php if (
                                        (float)($resumen['total_mora'] ?? 0) > 0
                                    ): ?>

                                        <span class="inactivo">

                                            <?= dinero(
                                                $resumen[
                                                    'total_mora'
                                                ]
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
                                    value="EN_MORA"
                                    <?= $estado === 'EN_MORA'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    En mora
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
                                    Concepto
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
                                    Mora
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
                                    colspan="12"
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
                                            <?= e(
                                                $fila[
                                                    'unidad_codigo'
                                                ]
                                            ) ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?= e(
                                            $fila[
                                                'nombre_grupo'
                                            ]
                                            ?? ''
                                        ) ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            !empty(
                                                $fila[
                                                    'numero_factura'
                                                ]
                                            )
                                        ): ?>

                                            <?= e(
                                                $fila[
                                                    'numero_factura'
                                                ]
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
                                                    $fila[
                                                        'periodo'
                                                    ]
                                                )
                                            )
                                        ) ?>

                                    </td>


                                    <td>

                                        <strong>

                                            <?= e(
                                                $fila[
                                                    'concepto'
                                                ]
                                                ?? $fila[
                                                    'tipo_obligacion'
                                                ]
                                                ?? 'Obligación'
                                            ) ?>

                                        </strong>


                                        <?php if (
                                            !empty(
                                                $fila[
                                                    'descripcion'
                                                ]
                                            )
                                        ): ?>

                                            <br>

                                            <small>

                                                <?= e(
                                                    $fila[
                                                        'descripcion'
                                                    ]
                                                ) ?>

                                            </small>

                                        <?php endif; ?>

                                    </td>


                                    <td class="numero">

                                        <?= dinero(
                                            $fila[
                                                'valor_original'
                                            ]
                                        ) ?>

                                    </td>


                                    <td class="numero">

                                        <?= dinero(
                                            $fila[
                                                'valor_pagado'
                                            ]
                                        ) ?>

                                    </td>


                                    <td class="numero">

                                        <strong>

                                            <?= dinero(
                                                $fila[
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
                                                    $fila[
                                                        'fecha_vencimiento'
                                                    ]
                                                )
                                            )
                                        ) ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            $fila[
                                                'estado_visual'
                                            ] === 'EN_MORA'
                                        ): ?>

                                            <span class="inactivo">
                                                EN MORA
                                            </span>

                                            <br>

                                            <small>

                                                <?= (int)$fila[
                                                    'meses_mora'
                                                ] ?>

                                                <?= (int)$fila[
                                                    'meses_mora'
                                                ] === 1
                                                    ? 'mes'
                                                    : 'meses'
                                                ?>

                                            </small>


                                            <?php if (
                                                (int)(
                                                    $fila[
                                                        'aplica_interes_mora'
                                                    ]
                                                    ?? 0
                                                ) === 1
                                            ): ?>

                                                <br>

                                                <small>
                                                    Aplica interés mensual
                                                </small>

                                            <?php else: ?>

                                                <br>

                                                <small>
                                                    Sin interés de mora
                                                </small>

                                            <?php endif; ?>


                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            $fila[
                                                'estado_visual'
                                            ] === 'PAGADA'
                                        ): ?>

                                            <span class="activo">
                                                PAGADA
                                            </span>


                                        <?php elseif (
                                            $fila[
                                                'estado_visual'
                                            ] === 'ANULADA'
                                        ): ?>

                                            <span class="inactivo">
                                                ANULADA
                                            </span>


                                        <?php elseif (
                                            $fila[
                                                'estado_visual'
                                            ] === 'EN_MORA'
                                        ): ?>

                                            <span class="inactivo">
                                                EN MORA
                                            </span>


                                        <?php else: ?>

                                            <strong>
                                                PENDIENTE
                                            </strong>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            !empty(
                                                $fila[
                                                    'id_factura'
                                                ]
                                            )
                                        ): ?>

                                            <a
                                                href="<?= BASE_URL ?>configuracion/factura_detalle.php?id=<?= (int)$fila['id_factura'] ?>"
                                                class="btn-secondary"
                                            >
                                                Ver factura
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


    </main>


</div>


</body>

</html>