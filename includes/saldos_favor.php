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

$fechaDesde =
    trim(
        $_GET['fecha_desde'] ?? ''
    );

$fechaHasta =
    trim(
        $_GET['fecha_hasta'] ?? ''
    );


// ==========================================================
// WHERE DINÁMICO
// ==========================================================

$where = [
    "1 = 1"
];

$params = [];


if ($buscar !== '') {

    $where[] = "
        (
            u.codigo LIKE :buscar
            OR p.referencia LIKE :buscar
            OR sf.observaciones LIKE :buscar
        )
    ";

    $params[':buscar'] =
        '%' . $buscar . '%';
}


if (
    in_array(
        $estado,
        [
            'DISPONIBLE',
            'UTILIZADO',
            'ANULADO'
        ],
        true
    )
) {

    $where[] = "
        sf.estado = :estado
    ";

    $params[':estado'] =
        $estado;
}


if ($fechaDesde !== '') {

    $where[] = "
        DATE(sf.fecha_generacion) >= :fecha_desde
    ";

    $params[':fecha_desde'] =
        $fechaDesde;
}


if ($fechaHasta !== '') {

    $where[] = "
        DATE(sf.fecha_generacion) <= :fecha_hasta
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
// RESUMEN
// ==========================================================

$sqlResumen = "
    SELECT
        COUNT(*) AS total_registros,

        COALESCE(
            SUM(sf.valor_original),
            0
        ) AS total_original,

        COALESCE(
            SUM(sf.valor_utilizado),
            0
        ) AS total_utilizado,

        COALESCE(
            SUM(sf.saldo_disponible),
            0
        ) AS total_disponible,

        COALESCE(
            SUM(
                CASE
                    WHEN sf.estado = 'DISPONIBLE'
                    THEN sf.saldo_disponible
                    ELSE 0
                END
            ),
            0
        ) AS disponible_activo

    FROM saldo_favor sf

    INNER JOIN unidades u
        ON u.id_unidad =
           sf.id_unidad

    INNER JOIN pagos p
        ON p.id_pago =
           sf.id_pago

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
        sf.id_saldo_favor,
        sf.id_unidad,
        sf.id_pago,
        sf.valor_original,
        sf.valor_utilizado,
        sf.saldo_disponible,
        sf.estado,
        sf.fecha_generacion,
        sf.fecha_ultimo_uso,
        sf.observaciones,

        u.codigo AS unidad_codigo,
        u.nombre AS unidad_nombre,

        dtu.nombre_grupo,

        p.fecha_pago,
        p.valor AS valor_pago,
        p.referencia,
        p.medio_pago,
        p.origen_pago,
        p.estado AS estado_pago,

        COALESCE(
            COUNT(asf.id_aplicacion_saldo),
            0
        ) AS cantidad_aplicaciones,

        COALESCE(
            SUM(asf.valor_aplicado),
            0
        ) AS total_aplicado_registrado

    FROM saldo_favor sf

    INNER JOIN unidades u
        ON u.id_unidad =
           sf.id_unidad

    LEFT JOIN detalle_tipos_unidad dtu
        ON dtu.id_tipo_config =
           u.id_tipo_config

    INNER JOIN pagos p
        ON p.id_pago =
           sf.id_pago

    LEFT JOIN aplicaciones_saldo_favor asf
        ON asf.id_saldo_favor =
           sf.id_saldo_favor

    WHERE
        $whereSql

    GROUP BY
        sf.id_saldo_favor,
        sf.id_unidad,
        sf.id_pago,
        sf.valor_original,
        sf.valor_utilizado,
        sf.saldo_disponible,
        sf.estado,
        sf.fecha_generacion,
        sf.fecha_ultimo_uso,
        sf.observaciones,
        u.codigo,
        u.nombre,
        dtu.nombre_grupo,
        p.fecha_pago,
        p.valor,
        p.referencia,
        p.medio_pago,
        p.origen_pago,
        p.estado

    ORDER BY
        CASE
            WHEN sf.estado = 'DISPONIBLE'
            THEN 0
            ELSE 1
        END,
        sf.fecha_generacion DESC,
        sf.id_saldo_favor DESC
";


$stmt =
    $conexion->prepare(
        $sql
    );


$stmt->execute(
    $params
);


$saldosFavor =
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
                    Saldos a favor
                </h2>

                <p>
                    Consulta y seguimiento de excedentes disponibles por unidad.
                </p>

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

                                <th>Registros</th>
                                <th>Valor original</th>
                                <th>Utilizado</th>
                                <th>Saldo disponible</th>
                                <th>Disponible activo</th>

                            </tr>

                        </thead>

                        <tbody>

                            <tr>

                                <td>
                                    <?= (int)($resumen['total_registros'] ?? 0) ?>
                                </td>

                                <td>
                                    <?= dinero(
                                        $resumen['total_original'] ?? 0
                                    ) ?>
                                </td>

                                <td>
                                    <?= dinero(
                                        $resumen['total_utilizado'] ?? 0
                                    ) ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= dinero(
                                            $resumen['total_disponible'] ?? 0
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <strong>
                                        <?= dinero(
                                            $resumen['disponible_activo'] ?? 0
                                        ) ?>
                                    </strong>
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
                                placeholder="Unidad, referencia..."
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
                                    value="DISPONIBLE"
                                    <?= $estado === 'DISPONIBLE'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Disponible
                                </option>

                                <option
                                    value="UTILIZADO"
                                    <?= $estado === 'UTILIZADO'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Utilizado
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
                            href="<?= BASE_URL ?>configuracion/saldos_favor.php"
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
                    Saldos registrados
                </h3>

                <br>

                <div class="tabla-responsive">

                    <table class="tabla">

                        <thead>

                            <tr>

                                <th>ID</th>
                                <th>Unidad</th>
                                <th>Pago origen</th>
                                <th>Fecha generación</th>
                                <th>Valor original</th>
                                <th>Utilizado</th>
                                <th>Disponible</th>
                                <th>Aplicaciones</th>
                                <th>Estado</th>
                                <th>Acciones</th>

                            </tr>

                        </thead>

                        <tbody>


                        <?php if (empty($saldosFavor)): ?>

                            <tr>

                                <td
                                    colspan="10"
                                    align="center"
                                >
                                    No existen saldos a favor registrados.
                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach ($saldosFavor as $fila): ?>

                                <tr>


                                    <td>
                                        #<?= (int)$fila['id_saldo_favor'] ?>
                                    </td>


                                    <td>

                                        <strong>
                                            <?= e($fila['unidad_codigo']) ?>
                                        </strong>

                                        <?php if (
                                            !empty(
                                                $fila['nombre_grupo']
                                            )
                                        ): ?>

                                            <br>

                                            <small>
                                                <?= e(
                                                    $fila['nombre_grupo']
                                                ) ?>
                                            </small>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <strong>
                                            Pago #<?= (int)$fila['id_pago'] ?>
                                        </strong>

                                        <?php if (
                                            !empty(
                                                $fila['referencia']
                                            )
                                        ): ?>

                                            <br>

                                            <small>
                                                <?= e(
                                                    $fila['referencia']
                                                ) ?>
                                            </small>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            date(
                                                'd/m/Y H:i',
                                                strtotime(
                                                    $fila['fecha_generacion']
                                                )
                                            )
                                        ) ?>

                                    </td>


                                    <td>
                                        <?= dinero(
                                            $fila['valor_original']
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= dinero(
                                            $fila['valor_utilizado']
                                        ) ?>
                                    </td>


                                    <td>

                                        <strong>
                                            <?= dinero(
                                                $fila['saldo_disponible']
                                            ) ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?= (int)$fila['cantidad_aplicaciones'] ?>

                                        <?php if (
                                            (int)$fila['cantidad_aplicaciones'] > 0
                                        ): ?>

                                            <br>

                                            <small>
                                                <?= dinero(
                                                    $fila['total_aplicado_registrado']
                                                ) ?>
                                            </small>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            $fila['estado'] === 'DISPONIBLE'
                                        ): ?>

                                            <span class="activo">
                                                DISPONIBLE
                                            </span>

                                        <?php elseif (
                                            $fila['estado'] === 'ANULADO'
                                        ): ?>

                                            <span class="inactivo">
                                                ANULADO
                                            </span>

                                        <?php else: ?>

                                            UTILIZADO

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
                                                $fila['estado'] === 'DISPONIBLE' &&
                                                (float)$fila['saldo_disponible'] > 0
                                            ): ?>

                                                <a
                                                    href="<?= BASE_URL ?>configuracion/aplicar_saldo_favor.php?id_saldo_favor=<?= (int)$fila['id_saldo_favor'] ?>"
                                                    class="btn-secondary"
                                                >
                                                    Aplicar saldo
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
