<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// CONFIGURACIÓN FINANCIERA ACTIVA
// ==========================================================

$sqlConfig = "
    SELECT
        *
    FROM configuracion_financiera
    WHERE activo = 1
    ORDER BY id_configuracion DESC
    LIMIT 1
";

$stmtConfig = $conexion->prepare($sqlConfig);
$stmtConfig->execute();

$configuracion = $stmtConfig->fetch(PDO::FETCH_ASSOC);


// ==========================================================
// CONSULTAR CALENDARIO
// ==========================================================

$sql = "
    SELECT
        id_calendario,
        periodo,
        fecha_inicio_cierre,
        fecha_fin_cierre,
        fecha_facturacion,
        fecha_generacion_intereses,
        fecha_vencimiento,
        estado,
        observaciones
    FROM calendario_financiero
    ORDER BY periodo DESC
";

$stmt = $conexion->prepare($sql);
$stmt->execute();

$calendarios = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// FORMATEAR FECHA
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
// FORMATEAR PERÍODO
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

    $mes = (int)date('n', $fecha);
    $anio = date('Y', $fecha);

    return $meses[$mes] . ' ' . $anio;
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


        <div class="calendario-financiero-container">


            <!-- ==================================================
                ENCABEZADO
            =================================================== -->

            <div class="calendario-header">

                <div>

                    <h1>
                        Calendario financiero
                    </h1>

                    <p>
                        Administra las fechas de facturación,
                        cierre e intereses de cada período.
                    </p>

                </div>


                <div>

                    <a
                        href="<?= BASE_URL ?>configuracion/crear_calendario_financiero.php"
                        class="btn-primary"
                    >
                        + Nuevo período
                    </a>

                </div>

            </div>


            <!-- ==================================================
                CONFIGURACIÓN ACTUAL
            =================================================== -->

            <?php if ($configuracion): ?>

                <div class="info-configuracion">

                    <div>

                        <strong>
                            Configuración general
                        </strong>

                    </div>


                    <div>

                        Facturación:
                        día
                        <?= (int)$configuracion['dia_facturacion'] ?>

                    </div>


                    <div>

                        Vencimiento:
                        día
                        <?= (int)$configuracion['dia_vencimiento'] ?>

                    </div>


                    <div>

                        Cierre habitual:
                        <?= (int)$configuracion['dia_inicio_cierre'] ?>
                        -
                        <?= (int)$configuracion['dia_fin_cierre'] ?>

                    </div>


                    <div>

                        Intereses:
                        <?= $configuracion['generar_intereses']
                            ? 'Automáticos'
                            : 'Desactivados'
                        ?>

                    </div>

                </div>

            <?php else: ?>

                <div class="alerta-info">

                    No existe una configuración financiera activa.

                    Antes de crear períodos debe configurar
                    las reglas financieras generales.

                </div>

            <?php endif; ?>


            <!-- ==================================================
                TABLA
            =================================================== -->

            <div class="tabla-card">

                <div class="tabla-header">

                    <h2>
                        Períodos financieros
                    </h2>

                </div>


                <?php if (empty($calendarios)): ?>

                    <div class="sin-datos">

                        No existen períodos financieros configurados.

                        <br><br>

                        Utilice el botón
                        <strong>+ Nuevo período</strong>
                        para crear el primero.

                    </div>

                <?php else: ?>


                    <div class="tabla-responsive">

                        <table class="tabla-calendario">

                            <thead>

                                <tr>

                                    <th>
                                        Período
                                    </th>

                                    <th>
                                        Facturación
                                    </th>

                                    <th>
                                        Vencimiento
                                    </th>

                                    <th>
                                        Inicio cierre
                                    </th>

                                    <th>
                                        Fin cierre
                                    </th>

                                    <th>
                                        Intereses
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

                                <?php foreach (
                                    $calendarios
                                    as $calendario
                                ): ?>


                                    <tr>


                                        <!-- PERÍODO -->

                                        <td>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    formatoPeriodo(
                                                        $calendario['periodo']
                                                    )
                                                ) ?>

                                            </strong>

                                        </td>


                                        <!-- FACTURACIÓN -->

                                        <td>

                                            <?= formatoFecha(
                                                $calendario[
                                                    'fecha_facturacion'
                                                ]
                                            ) ?>

                                        </td>


                                        <!-- VENCIMIENTO -->

                                        <td>

                                            <?= formatoFecha(
                                                $calendario[
                                                    'fecha_vencimiento'
                                                ]
                                            ) ?>

                                        </td>


                                        <!-- INICIO CIERRE -->

                                        <td>

                                            <?= formatoFecha(
                                                $calendario[
                                                    'fecha_inicio_cierre'
                                                ]
                                            ) ?>

                                        </td>


                                        <!-- FIN CIERRE -->

                                        <td>

                                            <?= formatoFecha(
                                                $calendario[
                                                    'fecha_fin_cierre'
                                                ]
                                            ) ?>

                                        </td>


                                        <!-- INTERESES -->

                                        <td>

                                            <?= formatoFecha(
                                                $calendario[
                                                    'fecha_generacion_intereses'
                                                ]
                                            ) ?>

                                        </td>


                                        <!-- ESTADO -->

                                        <td>

                                            <?php

                                            $claseEstado = '';

                                            switch (
                                                $calendario['estado']
                                            ) {

                                                case 'ABIERTO':

                                                    $claseEstado =
                                                        'estado-abierto';

                                                    break;


                                                case 'EN_CIERRE':

                                                    $claseEstado =
                                                        'estado-cierre';

                                                    break;


                                                case 'CERRADO':

                                                    $claseEstado =
                                                        'estado-cerrado';

                                                    break;

                                            }

                                            ?>


                                            <span
                                                class="estado-calendario <?= $claseEstado ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    $calendario['estado']
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- ACCIONES -->

                                        <td>

                                            <div
                                                class="acciones-tabla"
                                            >

                                                <a
                                                    href="<?= BASE_URL ?>configuracion/editar_calendario_financiero.php?id=<?= (int)$calendario['id_calendario'] ?>"
                                                    class="btn-secondary"
                                                >
                                                    ✏ Editar
                                                </a>

                                            </div>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>


                <?php endif; ?>


            </div>


        </div>


    </main>

</div>


</body>

</html>