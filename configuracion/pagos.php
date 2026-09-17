<?php
/**
 * ==============================================================
 * CONVIVIUM - LISTADO GENERAL DE PAGOS
 * ==============================================================
 * Archivo: configuracion/pagos.php
 *
 * Objetivo:
 * - Consultar todos los pagos registrados en el sistema.
 * - Filtrar por unidad/referencia, estado de conciliación,
 *   estado del pago y rango de fechas.
 * - Mostrar cuánto de cada pago ya fue aplicado y cuánto queda
 *   disponible para futuras aplicaciones.
 * - Permitir registrar un nuevo pago o aplicar uno que aún tenga saldo.
 *
 * Flujo relacionado:
 *   pagos.php
 *      -> registrar_pago.php
 *          -> actions/guardar_pago.php
 *              -> aplicar_pago.php
 *                  -> actions/aplicar_pago_manual.php
 *
 * IMPORTANTE:
 * Un registro de la tabla `pagos` representa dinero recibido.
 * No debe confundirse con una aplicación a cartera. Un mismo pago puede
 * distribuirse entre varias obligaciones mientras conserve saldo disponible.
 * ==============================================================
 */


require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// FUNCIONES DE PRESENTACIÓN
// ==========================================================
// e(): protege las salidas HTML.
// dinero(): unifica el formato monetario colombiano usado en la vista.

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
// FILTROS RECIBIDOS POR GET
// ==========================================================
// Todos son opcionales. Se usan más abajo para construir el WHERE dinámico.

$buscar =
    trim(
        $_GET['buscar'] ?? ''
    );

$estadoConciliacion =
    trim(
        $_GET['estado_conciliacion'] ?? ''
    );

$estado =
    trim(
        $_GET['estado'] ?? ''
    );

$fechaDesde =
    trim(
        $_GET['fecha_desde'] ?? ''
    );

$fechaHasta =
    trim(
        $_GET['fecha_hasta'] ?? ''
    );


// ==========================================================
// CONSTRUCCIÓN DEL WHERE DINÁMICO
// ==========================================================
// Se agregan condiciones únicamente cuando el usuario diligencia un filtro.
// Los valores siempre viajan como parámetros preparados para evitar inyección SQL.

$where = [
    "1 = 1"
];

$params = [];


if ($buscar !== '') {

    $where[] = "
        (
            u.codigo LIKE :buscar
            OR p.referencia LIKE :buscar
            OR p.referencia_externa LIKE :buscar
            OR p.id_externo LIKE :buscar
        )
    ";

    $params[':buscar'] =
        '%' . $buscar . '%';
}


if (
    in_array(
        $estadoConciliacion,
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
        p.estado_conciliacion =
            :estado_conciliacion
    ";

    $params[':estado_conciliacion'] =
        $estadoConciliacion;
}


if (
    in_array(
        $estado,
        [
            'REGISTRADO',
            'ANULADO'
        ],
        true
    )
) {

    $where[] = "
        p.estado =
            :estado
    ";

    $params[':estado'] =
        $estado;
}


if ($fechaDesde !== '') {

    $where[] = "
        p.fecha_pago >= :fecha_desde
    ";

    $params[':fecha_desde'] =
        $fechaDesde;
}


if ($fechaHasta !== '') {

    $where[] = "
        p.fecha_pago <= :fecha_hasta
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
// RESUMEN GENERAL DE PAGOS SEGÚN LOS FILTROS
// ==========================================================
// Estos totales alimentan las tarjetas/resumen de la parte superior.

$sqlResumen = "
    SELECT
        COUNT(*) AS total_pagos,

        COALESCE(
            SUM(p.valor),
            0
        ) AS total_recibido,

        COALESCE(
            SUM(
                CASE
                    WHEN p.estado = 'REGISTRADO'
                    THEN p.valor
                    ELSE 0
                END
            ),
            0
        ) AS total_registrado,

        COALESCE(
            SUM(
                CASE
                    WHEN p.estado = 'ANULADO'
                    THEN p.valor
                    ELSE 0
                END
            ),
            0
        ) AS total_anulado,

        COALESCE(
            SUM(
                CASE
                    WHEN p.estado_conciliacion = 'CONCILIADO'
                    THEN p.valor
                    ELSE 0
                END
            ),
            0
        ) AS total_conciliado

    FROM pagos p

    INNER JOIN unidades u
        ON u.id_unidad =
           p.id_unidad

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
// LISTADO DETALLADO DE PAGOS DE PAGOS
// ==========================================================

$sql = "
    SELECT
        p.id_pago,
        p.id_unidad,
        p.id_extracto,
        p.fecha_pago,
        p.valor,
        p.medio_pago,
        p.origen_pago,
        p.estado_conciliacion,
        p.fecha_conciliacion,
        p.referencia,
        p.referencia_externa,
        p.id_externo,
        p.observaciones,
        p.estado,
        p.fecha_creacion,

        u.codigo AS unidad_codigo,
        u.nombre AS unidad_nombre,

        dtu.nombre_grupo,

        COALESCE(
            SUM(ap.valor_aplicado),
            0
        ) AS valor_aplicado,

        COALESCE(
            (
                SELECT SUM(sf.valor_original)
                FROM saldo_favor sf
                WHERE
                    sf.id_pago = p.id_pago
                    AND sf.estado <> 'ANULADO'
            ),
            0
        ) AS valor_saldo_favor,

        (
            p.valor -
            COALESCE(
                SUM(ap.valor_aplicado),
                0
            ) -
            COALESCE(
                (
                    SELECT SUM(sf.valor_original)
                    FROM saldo_favor sf
                    WHERE
                        sf.id_pago = p.id_pago
                        AND sf.estado <> 'ANULADO'
                ),
                0
            )
        ) AS valor_disponible,

        COUNT(ap.id_aplicacion) AS cantidad_aplicaciones

    FROM pagos p

    INNER JOIN unidades u
        ON u.id_unidad =
           p.id_unidad

    LEFT JOIN detalle_tipos_unidad dtu
        ON dtu.id_tipo_config =
           u.id_tipo_config

    LEFT JOIN aplicaciones_pagos ap
        ON ap.id_pago =
           p.id_pago

    WHERE
        $whereSql

    GROUP BY
        p.id_pago,
        p.id_unidad,
        p.id_extracto,
        p.fecha_pago,
        p.valor,
        p.medio_pago,
        p.origen_pago,
        p.estado_conciliacion,
        p.fecha_conciliacion,
        p.referencia,
        p.referencia_externa,
        p.id_externo,
        p.observaciones,
        p.estado,
        p.fecha_creacion,
        u.codigo,
        u.nombre,
        dtu.nombre_grupo

    ORDER BY
        p.fecha_pago DESC,
        p.id_pago DESC
";


$stmt =
    $conexion->prepare(
        $sql
    );


$stmt->execute(
    $params
);


$pagos =
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
                    Pagos registrados
                </h2>

                <p>
                    Consulta y seguimiento de pagos recibidos.
                </p>

            </div>

            <div>
                <a
                    href="<?= BASE_URL ?>configuracion/registrar_pago.php"
                    class="btn-filtrar"
                    style="text-decoration:none; display:inline-block;"
                >
                    + Registrar pago
                </a>
            </div>

        </div>


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
                                    Pagos
                                </th>

                                <th>
                                    Total recibido
                                </th>

                                <th>
                                    Registrado
                                </th>

                                <th>
                                    Conciliado
                                </th>

                                <th>
                                    Anulado
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <tr>

                                <td>
                                    <?= (int)($resumen['total_pagos'] ?? 0) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= dinero(
                                            $resumen['total_recibido'] ?? 0
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= dinero(
                                        $resumen['total_registrado'] ?? 0
                                    ) ?>
                                </td>

                                <td>
                                    <?= dinero(
                                        $resumen['total_conciliado'] ?? 0
                                    ) ?>
                                </td>

                                <td>
                                    <?= dinero(
                                        $resumen['total_anulado'] ?? 0
                                    ) ?>
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
                                    minmax(200px, 1fr)
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
                                placeholder="Unidad, referencia..."
                            >

                        </div>


                        <div>

                            <label>
                                Conciliación
                            </label>

                            <select
                                name="estado_conciliacion"
                            >

                                <option value="">
                                    Todos
                                </option>

                                <option
                                    value="PENDIENTE"
                                    <?= $estadoConciliacion === 'PENDIENTE'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Pendiente
                                </option>

                                <option
                                    value="CONCILIADO"
                                    <?= $estadoConciliacion === 'CONCILIADO'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Conciliado
                                </option>

                                <option
                                    value="RECHAZADO"
                                    <?= $estadoConciliacion === 'RECHAZADO'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Rechazado
                                </option>

                                <option
                                    value="CON_DIFERENCIA"
                                    <?= $estadoConciliacion === 'CON_DIFERENCIA'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Con diferencia
                                </option>

                            </select>

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
                                    value="REGISTRADO"
                                    <?= $estado === 'REGISTRADO'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Registrado
                                </option>

                                <option
                                    value="ANULADO"
                                    <?= $estado === 'ANULADO'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Anulado
                                </option>

                            </select>

                        </div>


                        <div>

                            <label>
                                Desde
                            </label>

                            <input
                                type="date"
                                name="fecha_desde"
                                value="<?= e($fechaDesde) ?>"
                            >

                        </div>


                        <div>

                            <label>
                                Hasta
                            </label>

                            <input
                                type="date"
                                name="fecha_hasta"
                                value="<?= e($fechaHasta) ?>"
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
                            href="<?= BASE_URL ?>configuracion/pagos.php"
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
             LISTADO
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Pagos
                </h3>

                <br>

                <div class="tabla-responsive">

                    <table class="tabla">

                        <thead>

                            <tr>

                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Unidad</th>
                                <th>Referencia</th>
                                <th>Medio</th>
                                <th>Origen</th>
                                <th>Valor</th>
                                <th>Aplicado</th>
                                <th>Disponible</th>
                                <th>Conciliación</th>
                                <th>Estado</th>
                                <th>Acciones</th>

                            </tr>

                        </thead>

                        <tbody>


                        <?php if (empty($pagos)): ?>

                            <tr>

                                <td
                                    colspan="12"
                                    align="center"
                                >
                                    No existen pagos registrados.
                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach ($pagos as $pago): ?>

                                <tr>


                                    <td>
                                        #<?= (int)$pago['id_pago'] ?>
                                    </td>


                                    <td>

                                        <?= e(
                                            date(
                                                'd/m/Y',
                                                strtotime(
                                                    $pago['fecha_pago']
                                                )
                                            )
                                        ) ?>

                                    </td>


                                    <td>

                                        <strong>
                                            <?= e($pago['unidad_codigo']) ?>
                                        </strong>

                                        <?php if (
                                            !empty(
                                                $pago['nombre_grupo']
                                            )
                                        ): ?>

                                            <br>

                                            <small>
                                                <?= e(
                                                    $pago['nombre_grupo']
                                                ) ?>
                                            </small>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $pago['referencia']
                                            ?? '-'
                                        ) ?>

                                        <?php if (
                                            !empty(
                                                $pago['referencia_externa']
                                            )
                                        ): ?>

                                            <br>

                                            <small>
                                                <?= e(
                                                    $pago['referencia_externa']
                                                ) ?>
                                            </small>

                                        <?php endif; ?>

                                    </td>


                                    <td>
                                        <?= e(
                                            $pago['medio_pago']
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= e(
                                            $pago['origen_pago']
                                        ) ?>
                                    </td>


                                    <td>
                                        <strong>
                                            <?= dinero(
                                                $pago['valor']
                                            ) ?>
                                        </strong>
                                    </td>


                                    <td>

                                        <?= dinero(
                                            $pago['valor_aplicado']
                                        ) ?>

                                        <?php if (
                                            (int)$pago['cantidad_aplicaciones'] > 0
                                        ): ?>

                                            <br>

                                            <small>
                                                <?= (int)$pago['cantidad_aplicaciones'] ?>
                                                aplicación<?= (int)$pago['cantidad_aplicaciones'] === 1 ? '' : 'es' ?>
                                            </small>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <strong>
                                            <?= dinero(
                                                $pago['valor_disponible']
                                            ) ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?= e(
                                            $pago['estado_conciliacion']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            $pago['estado'] === 'ANULADO'
                                        ): ?>

                                            <span class="inactivo">
                                                ANULADO
                                            </span>

                                        <?php else: ?>

                                            <span class="activo">
                                                REGISTRADO
                                            </span>

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
                                                href="<?= BASE_URL ?>configuracion/cartera_detalle.php?id_unidad=<?= (int)$pago['id_unidad'] ?>"
                                                class="btn-secondary"
                                            >
                                                Ver cartera
                                            </a>


                                            <?php if (
                                                $pago['estado'] === 'REGISTRADO' &&
                                                (float)$pago['valor_disponible'] > 0
                                            ): ?>

                                                <a
                                                    href="<?= BASE_URL ?>configuracion/aplicar_pago.php?id_pago=<?= (int)$pago['id_pago'] ?>"
                                                    class="btn-secondary"
                                                >
                                                    Aplicar pago
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
