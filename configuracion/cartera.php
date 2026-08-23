<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// FILTROS
// ==========================================================

$idUnidad = filter_input(
    INPUT_GET,
    'id_unidad',
    FILTER_VALIDATE_INT
);

$idTipoObligacion = filter_input(
    INPUT_GET,
    'id_tipo_obligacion',
    FILTER_VALIDATE_INT
);

$estado = trim($_GET['estado'] ?? '');


// ==========================================================
// CONSULTAR UNIDADES
// ==========================================================

$sqlUnidades = "
    SELECT
        u.id_unidad,
        u.codigo,
        u.nombre
    FROM unidades u
    WHERE u.activo = 1
    ORDER BY u.codigo ASC
";

$stmtUnidades = $conexion->prepare($sqlUnidades);
$stmtUnidades->execute();

$unidades = $stmtUnidades->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// CONSULTAR TIPOS DE OBLIGACIÓN
// ==========================================================

$sqlTipos = "
    SELECT
        id_tipo_obligacion,
        nombre
    FROM tipos_obligacion
    WHERE activo = 1
    ORDER BY orden_defecto ASC, nombre ASC
";

$stmtTipos = $conexion->prepare($sqlTipos);
$stmtTipos->execute();

$tiposObligacion = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// CONSTRUIR FILTROS
// ==========================================================

$where = [];
$params = [];

$where[] = "c.estado <> 'ANULADA'";


// ----------------------------------------------------------
// FILTRO UNIDAD
// ----------------------------------------------------------

if ($idUnidad) {

    $where[] = "c.id_unidad = :id_unidad";

    $params[':id_unidad'] = $idUnidad;
}


// ----------------------------------------------------------
// FILTRO TIPO OBLIGACIÓN
// ----------------------------------------------------------

if ($idTipoObligacion) {

    $where[] = "
        c.id_tipo_obligacion = :id_tipo_obligacion
    ";

    $params[':id_tipo_obligacion'] = $idTipoObligacion;
}


// ----------------------------------------------------------
// FILTRO ESTADO
// ----------------------------------------------------------

if (
    in_array(
        $estado,
        ['PENDIENTE', 'PAGADA'],
        true
    )
) {

    $where[] = "c.estado = :estado";

    $params[':estado'] = $estado;
}


// ==========================================================
// WHERE FINAL
// ==========================================================

$whereSQL = implode(
    " AND ",
    $where
);


// ==========================================================
// RESUMEN GENERAL
// ==========================================================

$sqlResumen = "
    SELECT

        COALESCE(
            SUM(c.saldo),
            0
        ) AS cartera_total,

        COALESCE(
            SUM(
                CASE
                    WHEN c.fecha_vencimiento < CURDATE()
                    THEN c.saldo
                    ELSE 0
                END
            ),
            0
        ) AS cartera_vencida,

        COUNT(
            CASE
                WHEN c.saldo > 0
                THEN 1
            END
        ) AS obligaciones_pendientes,

        COUNT(
            CASE
                WHEN c.estado = 'PAGADA'
                THEN 1
            END
        ) AS obligaciones_pagadas

    FROM cartera c

    WHERE
        $whereSQL
";

$stmtResumen = $conexion->prepare($sqlResumen);
$stmtResumen->execute($params);

$resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC);


// ==========================================================
// CARTERA AGRUPADA POR UNIDAD
// ==========================================================

$sqlCartera = "
    SELECT

        u.id_unidad,
        u.codigo AS codigo_unidad,
        u.nombre AS nombre_unidad,

        COALESCE(
            SUM(c.valor_original),
            0
        ) AS valor_original,

        COALESCE(
            SUM(c.valor_pagado),
            0
        ) AS valor_pagado,

        COALESCE(
            SUM(c.saldo),
            0
        ) AS saldo,

        COALESCE(
            SUM(
                CASE
                    WHEN c.fecha_vencimiento < CURDATE()
                    THEN c.saldo
                    ELSE 0
                END
            ),
            0
        ) AS saldo_vencido,

        COUNT(
            CASE
                WHEN c.saldo > 0
                THEN 1
            END
        ) AS obligaciones_pendientes

    FROM cartera c

    INNER JOIN unidades u
        ON u.id_unidad = c.id_unidad

    WHERE
        $whereSQL

    GROUP BY
        u.id_unidad,
        u.codigo,
        u.nombre

    ORDER BY
        u.codigo ASC
";

$stmtCartera = $conexion->prepare($sqlCartera);
$stmtCartera->execute($params);

$cartera = $stmtCartera->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// FORMATO MONEDA
// ==========================================================

function formatoMoneda($valor)
{
    return '$ ' . number_format(
        (float)$valor,
        0,
        ',',
        '.'
    );
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

        <div class="cartera-container">


            <!-- =====================================================
                ENCABEZADO
            ====================================================== -->

            <div class="cartera-header">

                <div>

                    <h1>
                        Cartera y Resumen General
                    </h1>

                    <p>
                        Consulta general de obligaciones pendientes,
                        pagos y cartera de las unidades.
                    </p>

                </div>

                <div>

                    <a
                        href="<?= BASE_URL ?>configuracion/facturacion.php"
                        class="btn-primary"
                    >
                        Facturación mensual
                    </a>

                </div>

            </div>


            <!-- =====================================================
                RESUMEN
            ====================================================== -->

            <div class="resumen-grid">


                <!-- CARTERA -->

                <div class="resumen-card">

                    <div class="titulo">
                        Cartera total
                    </div>

                    <div class="valor">
                        <?= formatoMoneda(
                            $resumen['cartera_total'] ?? 0
                        ) ?>
                    </div>

                </div>


                <!-- VENCIDA -->

                <div class="resumen-card">

                    <div class="titulo">
                        Cartera vencida
                    </div>

                    <div class="valor">
                        <?= formatoMoneda(
                            $resumen['cartera_vencida'] ?? 0
                        ) ?>
                    </div>

                </div>


                <!-- PENDIENTES -->

                <div class="resumen-card">

                    <div class="titulo">
                        Obligaciones pendientes
                    </div>

                    <div class="valor">

                        <?= number_format(
                            (int)(
                                $resumen['obligaciones_pendientes']
                                ?? 0
                            )
                        ) ?>

                    </div>

                </div>


                <!-- PAGADAS -->

                <div class="resumen-card">

                    <div class="titulo">
                        Obligaciones pagadas
                    </div>

                    <div class="valor">

                        <?= number_format(
                            (int)(
                                $resumen['obligaciones_pagadas']
                                ?? 0
                            )
                        ) ?>

                    </div>

                </div>


            </div>


            <!-- =====================================================
                FILTROS
            ====================================================== -->

            <div class="filtros-card">

                <form
                    method="GET"
                    action=""
                >

                    <div class="filtros-grid">


                        <!-- UNIDAD -->

                        <div class="campo">

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
                                        <?= (
                                            $idUnidad ==
                                            $unidad['id_unidad']
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        <?= htmlspecialchars(
                                            $unidad['codigo']
                                        ) ?>

                                        <?php if (!empty($unidad['nombre'])): ?>

                                            -
                                            <?= htmlspecialchars(
                                                $unidad['nombre']
                                            ) ?>

                                        <?php endif; ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- TIPO OBLIGACIÓN -->

                        <div class="campo">

                            <label for="id_tipo_obligacion">
                                Tipo de obligación
                            </label>

                            <select
                                name="id_tipo_obligacion"
                                id="id_tipo_obligacion"
                            >

                                <option value="">
                                    Todos
                                </option>

                                <?php foreach (
                                    $tiposObligacion
                                    as $tipo
                                ): ?>

                                    <option
                                        value="<?= (int)$tipo['id_tipo_obligacion'] ?>"
                                        <?= (
                                            $idTipoObligacion ==
                                            $tipo['id_tipo_obligacion']
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        <?= htmlspecialchars(
                                            $tipo['nombre']
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- ESTADO -->

                        <div class="campo">

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

                            </select>

                        </div>


                        <!-- BOTONES -->

                        <div class="botones-filtro">

                            <button
                                type="submit"
                                class="btn-primary"
                            >
                                Buscar
                            </button>

                            <a
                                href="<?= BASE_URL ?>configuracion/cartera.php"
                                class="btn-secondary"
                            >
                                Limpiar
                            </a>

                        </div>


                    </div>

                </form>

            </div>


            <!-- =====================================================
                TABLA
            ====================================================== -->

            <div class="tabla-card">

                <h2>
                    Cartera por unidad
                </h2>


                <?php if (empty($cartera)): ?>

                    <div class="sin-datos">

                        No existen registros de cartera
                        para los filtros seleccionados.

                    </div>

                <?php else: ?>


                    <table class="tabla-cartera">

                        <thead>

                            <tr>

                                <th>
                                    Unidad
                                </th>

                                <th>
                                    Obligaciones
                                </th>

                                <th class="text-right">
                                    Valor original
                                </th>

                                <th class="text-right">
                                    Pagado
                                </th>

                                <th class="text-right">
                                    Saldo
                                </th>

                                <th class="text-right">
                                    Vencido
                                </th>

                                <th>
                                    Acción
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach (
                                $cartera
                                as $fila
                            ): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?= htmlspecialchars(
                                                $fila['codigo_unidad']
                                            ) ?>
                                        </strong>

                                        <?php if (
                                            !empty(
                                                $fila['nombre_unidad']
                                            )
                                        ): ?>

                                            <br>

                                            <small>
                                                <?= htmlspecialchars(
                                                    $fila['nombre_unidad']
                                                ) ?>
                                            </small>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?= number_format(
                                            (int)$fila[
                                                'obligaciones_pendientes'
                                            ]
                                        ) ?>

                                    </td>


                                    <td class="text-right">

                                        <?= formatoMoneda(
                                            $fila['valor_original']
                                        ) ?>

                                    </td>


                                    <td class="text-right">

                                        <?= formatoMoneda(
                                            $fila['valor_pagado']
                                        ) ?>

                                    </td>


                                    <td class="text-right saldo">

                                        <?= formatoMoneda(
                                            $fila['saldo']
                                        ) ?>

                                    </td>


                                    <td class="text-right saldo-vencido">

                                        <?= formatoMoneda(
                                            $fila['saldo_vencido']
                                        ) ?>

                                    </td>


                                    <td>

                                        <a
                                            href="<?= BASE_URL ?>configuracion/detalle_cartera.php?id_unidad=<?= (int)$fila['id_unidad'] ?>"
                                            class="btn-secondary btn-ver"
                                        >
                                            Ver detalle
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>


                <?php endif; ?>


            </div>
        </div>
    </main>

</div>


</body>

</html>