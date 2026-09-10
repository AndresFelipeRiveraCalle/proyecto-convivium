<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// FILTROS
// ==========================================================

$buscar = isset($_GET['buscar'])
    ? trim($_GET['buscar'])
    : '';

$estado = isset($_GET['estado'])
    ? trim($_GET['estado'])
    : '';

$periodo = isset($_GET['periodo'])
    ? trim($_GET['periodo'])
    : '';


// ==========================================================
// CONSULTA BASE
// ==========================================================

$sql = "
    SELECT
        f.id_factura,
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

        u.codigo AS unidad_codigo,
        u.nombre AS unidad_nombre,

        dtu.nombre_grupo

    FROM facturas f

    INNER JOIN unidades u
        ON u.id_unidad =
           f.id_unidad

    INNER JOIN detalle_tipos_unidad dtu
        ON dtu.id_tipo_config =
           u.id_tipo_config

    WHERE 1 = 1
";


$params = [];


// ==========================================================
// FILTRO BUSCAR
// ==========================================================

if ($buscar !== '') {

    $sql .= "
        AND (
            f.numero_factura LIKE :buscar
            OR u.codigo LIKE :buscar
            OR u.nombre LIKE :buscar
            OR dtu.nombre_grupo LIKE :buscar
        )
    ";

    $params[':buscar'] =
        '%' . $buscar . '%';
}


// ==========================================================
// FILTRO ESTADO
// ==========================================================

if ($estado !== '') {

    $sql .= "
        AND f.estado =
            :estado
    ";

    $params[':estado'] =
        $estado;
}


// ==========================================================
// FILTRO PERÍODO
// ==========================================================

if ($periodo !== '') {

    $partes =
        explode(
            '-',
            $periodo
        );

    if (
        count($partes) === 2
    ) {

        $anio =
            (int)$partes[0];

        $mes =
            (int)$partes[1];

        if (
            $anio > 0 &&
            $mes >= 1 &&
            $mes <= 12
        ) {

            $sql .= "
                AND f.periodo =
                    :anio

                AND f.mes =
                    :mes
            ";

            $params[':anio'] =
                $anio;

            $params[':mes'] =
                $mes;
        }
    }
}


// ==========================================================
// ORDEN
// ==========================================================

$sql .= "
    ORDER BY
        f.periodo DESC,
        f.mes DESC,
        f.id_factura DESC
";


$stmt =
    $conexion->prepare(
        $sql
    );


$stmt->execute(
    $params
);


$facturas =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// FUNCIÓN PERÍODO
// ==========================================================

function formatoPeriodoFactura(
    $anio,
    $mes
) {

    return
        str_pad(
            $mes,
            2,
            '0',
            STR_PAD_LEFT
        )
        . '/'
        . $anio;
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
             ENCABEZADO
        ======================================================= -->

        <h2 align="center">
            Facturas
        </h2>


        <br>


        <p>
            Consulte las facturas generadas por período,
            unidad y estado.
        </p>


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


                    <div class="form-group">


                        <label for="buscar">
                            Buscar
                        </label>


                        <input
                            type="text"
                            name="buscar"
                            id="buscar"
                            value="<?= htmlspecialchars($buscar) ?>"
                            placeholder="Factura, unidad o grupo"
                        >


                    </div>


                    <div class="form-group">


                        <label for="periodo">
                            Período
                        </label>


                        <input
                            type="month"
                            name="periodo"
                            id="periodo"
                            value="<?= htmlspecialchars($periodo) ?>"
                        >


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


                            <?php
                            $estados = [
                                'BORRADOR',
                                'GENERADA',
                                'PARCIAL',
                                'PAGADA',
                                'VENCIDA',
                                'ANULADA'
                            ];
                            ?>


                            <?php foreach ($estados as $estadoItem): ?>


                                <option
                                    value="<?= $estadoItem ?>"
                                    <?= (
                                        $estado === $estadoItem
                                            ? 'selected'
                                            : ''
                                    ) ?>
                                >

                                    <?= $estadoItem ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                    </div>


                    <div class="form-actions">


                        <a
                            href="<?= BASE_URL ?>configuracion/facturas_genneradas.php"
                            class="btn-limpiar"
                        >

                            Limpiar

                        </a>


                        <button
                            type="submit"
                            class="btn-filtrar"
                        >

                            Filtrar

                        </button>


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
                    Facturas generadas
                </h3>


                <br>


                <div class="tabla-responsive">


                    <table class="tabla">


                        <thead>


                            <tr>

                                <th>
                                    Número
                                </th>

                                <th>
                                    Unidad
                                </th>

                                <th>
                                    Grupo
                                </th>

                                <th>
                                    Período
                                </th>

                                <th>
                                    Generación
                                </th>

                                <th>
                                    Vencimiento
                                </th>

                                <th>
                                    Subtotal
                                </th>

                                <th>
                                    Intereses
                                </th>

                                <th>
                                    Saldo anterior
                                </th>

                                <th>
                                    Total
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th>
                                    Acciones
                                </th>

                            </tr>


                        </thead>


                        <tbody>


                        <?php if (empty($facturas)): ?>


                            <tr>

                                <td
                                    colspan="12"
                                    align="center"
                                >

                                    No existen facturas para mostrar.

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach ($facturas as $factura): ?>


                                <tr>


                                    <td>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $factura['numero_factura']
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $factura['unidad_codigo']
                                        ) ?>

                                        <?php if (
                                            !empty(
                                                $factura['unidad_nombre']
                                            )
                                        ): ?>

                                            <br>

                                            <small>

                                                <?= htmlspecialchars(
                                                    $factura['unidad_nombre']
                                                ) ?>

                                            </small>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $factura['nombre_grupo']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= formatoPeriodoFactura(
                                            $factura['periodo'],
                                            $factura['mes']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= date(
                                            'd/m/Y',
                                            strtotime(
                                                $factura['fecha_generacion']
                                            )
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= date(
                                            'd/m/Y',
                                            strtotime(
                                                $factura['fecha_vencimiento']
                                            )
                                        ) ?>

                                    </td>


                                    <td>

                                        $<?= number_format(
                                            $factura['subtotal'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>

                                    </td>


                                    <td>

                                        $<?= number_format(
                                            $factura['intereses'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>

                                    </td>


                                    <td>

                                        $<?= number_format(
                                            $factura['saldos_anteriores'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>

                                    </td>


                                    <td>

                                        <strong>

                                            $<?= number_format(
                                                $factura['total'],
                                                2,
                                                ',',
                                                '.'
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?php if (
                                            $factura['estado'] === 'GENERADA'
                                        ): ?>

                                            <span class="activo">
                                                GENERADA
                                            </span>

                                        <?php elseif (
                                            $factura['estado'] === 'ANULADA'
                                        ): ?>

                                            <span class="inactivo">
                                                ANULADA
                                            </span>

                                        <?php else: ?>

                                            <?= htmlspecialchars(
                                                $factura['estado']
                                            ) ?>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <a
                                            href="<?= BASE_URL ?>configuracion/factura_detalle.php?id=<?= (int)$factura['id_factura'] ?>"
                                            class="btn-secondary"
                                        >

                                            Ver detalle

                                        </a>

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