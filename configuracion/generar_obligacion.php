<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// CONSULTAR CALENDARIOS DISPONIBLES
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
// PERÍODO SELECCIONADO
// ==========================================================

$idCalendarioSeleccionado = $_GET['id_calendario'] ?? '';


// ==========================================================
// BUSCAR CALENDARIO SELECCIONADO
// ==========================================================

$calendarioSeleccionado = null;

if (!empty($idCalendarioSeleccionado)) {

    foreach ($calendarios as $calendario) {

        if (
            (int)$calendario['id_calendario']
            ===
            (int)$idCalendarioSeleccionado
        ) {

            $calendarioSeleccionado = $calendario;

            break;
        }
    }
}


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


        <div class="form-card">


            <!-- ==================================================
                 ENCABEZADO
            =================================================== -->

            <div class="form-header">

                <div>

                    <h1>
                        Generar obligaciones
                    </h1>

                    <p>
                        Genera las obligaciones financieras de las
                        unidades para un período determinado.
                    </p>

                </div>

                <br>
                <div>

                    <a
                        href="<?= BASE_URL ?>configuracion/calendario_financiero.php"
                        class="btn-secondary"
                    >
                        ← Calendario financiero
                    </a>

                </div>
                <br>
            </div>


            <!-- ==================================================
                 SELECCIÓN DE PERÍODO
            =================================================== -->

            <div class="form-group">

                <label for="id_calendario">

                    Período financiero

                </label>


                <select
                    id="id_calendario"
                    name="id_calendario"
                    onchange="cambiarPeriodo(this.value)"
                >

                    <option value="">

                        Seleccione un período

                    </option>


                    <?php foreach (
                        $calendarios
                        as $calendario
                    ): ?>

                        <option
                            value="<?= (int)$calendario['id_calendario'] ?>"
                            <?= (
                                (int)$idCalendarioSeleccionado
                                ===
                                (int)$calendario['id_calendario']
                            )
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= htmlspecialchars(
                                formatoPeriodo(
                                    $calendario['periodo']
                                )
                            ) ?>

                            -
                            <?= htmlspecialchars(
                                $calendario['estado']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>


                <small>

                    Seleccione el período para el cual desea
                    generar las obligaciones.

                </small>

            </div>


            <?php if ($calendarioSeleccionado): ?>


                <!-- ==================================================
                     INFORMACIÓN DEL PERÍODO
                =================================================== -->

                <div class="info-box">

                    <strong>
                        Información del período
                    </strong>


                    <p>

                        <strong>
                            Período:
                        </strong>

                        <?= htmlspecialchars(
                            formatoPeriodo(
                                $calendarioSeleccionado['periodo']
                            )
                        ) ?>

                        <br>


                        <strong>
                            Facturación:
                        </strong>

                        <?= formatoFecha(
                            $calendarioSeleccionado[
                                'fecha_facturacion'
                            ]
                        ) ?>

                        <br>


                        <strong>
                            Vencimiento:
                        </strong>

                        <?= formatoFecha(
                            $calendarioSeleccionado[
                                'fecha_vencimiento'
                            ]
                        ) ?>

                        <br>


                        <strong>
                            Inicio del cierre:
                        </strong>

                        <?= formatoFecha(
                            $calendarioSeleccionado[
                                'fecha_inicio_cierre'
                            ]
                        ) ?>

                        <br>


                        <strong>
                            Fin del cierre:
                        </strong>

                        <?= formatoFecha(
                            $calendarioSeleccionado[
                                'fecha_fin_cierre'
                            ]
                        ) ?>

                        <br>


                        <strong>
                            Generación de intereses:
                        </strong>

                        <?= formatoFecha(
                            $calendarioSeleccionado[
                                'fecha_generacion_intereses'
                            ]
                        ) ?>

                        <br>


                        <strong>
                            Estado:
                        </strong>

                        <?= htmlspecialchars(
                            $calendarioSeleccionado['estado']
                        ) ?>

                    </p>


                    <?php if (
                        !empty(
                            $calendarioSeleccionado[
                                'observaciones'
                            ]
                        )
                    ): ?>

                        <hr>

                        <strong>
                            Observaciones:
                        </strong>

                        <p>

                            <?= nl2br(
                                htmlspecialchars(
                                    $calendarioSeleccionado[
                                        'observaciones'
                                    ]
                                )
                            ) ?>

                        </p>

                    <?php endif; ?>

                </div>


                <!-- ==================================================
                     EXPLICACIÓN DEL PROCESO
                =================================================== -->

                <div class="info-box">

                    <strong>
                        ¿Qué hará el proceso?
                    </strong>


                    <p>

                        Para este período el sistema revisará las
                        unidades y los conceptos configurados para
                        determinar qué obligaciones deben generarse.

                    </p>


                    <p>

                        Las obligaciones generadas serán registradas
                        en la tabla <strong>cartera</strong>.

                    </p>


                    <p>

                        Posteriormente estas obligaciones podrán ser
                        utilizadas para construir las facturas del
                        período.

                    </p>

                </div>


                <!-- ==================================================
                     BOTÓN GENERAR
                =================================================== -->


                <form
                    action="<?= BASE_URL ?>actions/generar_obligaciones.php"
                    method="POST"
                    onsubmit="return confirmarGeneracion();"
                >

                    <input
                        type="hidden"
                        name="id_calendario"
                        value="<?= (int)$calendarioSeleccionado['id_calendario'] ?>"
                    >

                    <div class="form-actions">

                        <a
                            href="<?= BASE_URL ?>configuracion/generar_obligacion.php"
                            class="btn-secondary"
                        >
                            Cancelar
                        </a>

                        <button
                            type="submit"
                            class="btn-primary"
                        >
                            ⚙ Generar obligaciones
                        </button>

                    </div>

                </form>



            <?php else: ?>


                <!-- ==================================================
                     SIN PERÍODO
                =================================================== -->

                <div class="alerta-info">

                    Seleccione un período financiero para continuar.

                </div>

            <?php endif; ?>


        </div>


    </main>

</div>


<script>

function cambiarPeriodo(idCalendario)
{
    if (!idCalendario) {

        window.location.href =
            "<?= BASE_URL ?>actions/generar_obligacion.php";

        return;
    }


    window.location.href =
        "<?= BASE_URL ?>configuracion/generar_obligacion.php?id_calendario="
        + encodeURIComponent(idCalendario);
}


function confirmarGeneracion()
{
    return confirm(
        "¿Está seguro de generar las obligaciones para este período?"
    );
}

</script>


</body>

</html>