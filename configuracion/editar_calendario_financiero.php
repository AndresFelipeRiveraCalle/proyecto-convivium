<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// OBTENER ID
// ==========================================================

$idCalendario = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);


if (!$idCalendario) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/calendario_financiero.php?tipo=error&mensaje=" .
        urlencode(
            "El período financiero no es válido."
        )
    );

    exit;
}


// ==========================================================
// CONSULTAR PERÍODO
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

    WHERE id_calendario = :id_calendario

    LIMIT 1
";


$stmt = $conexion->prepare($sql);

$stmt->execute([
    ':id_calendario' => $idCalendario
]);


$calendario = $stmt->fetch(
    PDO::FETCH_ASSOC
);


// ==========================================================
// VERIFICAR EXISTENCIA
// ==========================================================

if (!$calendario) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/calendario_financiero.php?tipo=error&mensaje=" .
        urlencode(
            "El período financiero no existe."
        )
    );

    exit;
}


// ==========================================================
// FORMATEAR PERÍODO
// ==========================================================

function formatoPeriodoEditar($periodo)
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


// ==========================================================
// VALORES
// ==========================================================

$periodoTexto = formatoPeriodoEditar(
    $calendario['periodo']
);

$periodoInput = date(
    'Y-m',
    strtotime($calendario['periodo'])
);

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
                        Editar período financiero
                    </h1>

                    <p>
                        Modifique las fechas y configuración
                        operativa del período financiero.
                    </p>

                </div>


                <div>

                    <a
                        href="<?= BASE_URL ?>configuracion/calendario_financiero.php"
                        class="btn-secondary"
                    >
                        ← Volver
                    </a>

                </div>

            </div>


            <!-- ==================================================
                 FORMULARIO
            =================================================== -->

            <form
                action="<?= BASE_URL ?>actions/actualizar_calendario_financiero.php"
                method="POST"
            >


                <!-- ==================================================
                     ID
                =================================================== -->

                <input
                    type="hidden"
                    name="id_calendario"
                    value="<?= (int)$calendario['id_calendario'] ?>"
                >


                <!-- ==================================================
                     PERÍODO
                =================================================== -->

                <div class="form-group">

                    <label for="periodo">
                        Período
                    </label>

                    <input
                        type="month"
                        id="periodo"
                        value="<?= htmlspecialchars($periodoInput) ?>"
                        disabled
                    >

                    <small>
                        <?= htmlspecialchars($periodoTexto) ?>.
                        El período no puede modificarse.
                    </small>

                </div>


                <!-- ==================================================
                     FECHAS
                =================================================== -->

                <div class="form-grid">


                    <!-- FECHA FACTURACIÓN -->

                    <div class="form-group">

                        <label for="fecha_facturacion">
                            Fecha de facturación
                        </label>

                        <input
                            type="date"
                            name="fecha_facturacion"
                            id="fecha_facturacion"
                            value="<?= htmlspecialchars(
                                $calendario['fecha_facturacion']
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- FECHA VENCIMIENTO -->

                    <div class="form-group">

                        <label for="fecha_vencimiento">
                            Fecha de vencimiento
                        </label>

                        <input
                            type="date"
                            name="fecha_vencimiento"
                            id="fecha_vencimiento"
                            value="<?= htmlspecialchars(
                                $calendario['fecha_vencimiento']
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- INICIO CIERRE -->

                    <div class="form-group">

                        <label for="fecha_inicio_cierre">
                            Inicio del cierre
                        </label>

                        <input
                            type="date"
                            name="fecha_inicio_cierre"
                            id="fecha_inicio_cierre"
                            value="<?= htmlspecialchars(
                                $calendario['fecha_inicio_cierre']
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- FIN CIERRE -->

                    <div class="form-group">

                        <label for="fecha_fin_cierre">
                            Fin del cierre
                        </label>

                        <input
                            type="date"
                            name="fecha_fin_cierre"
                            id="fecha_fin_cierre"
                            value="<?= htmlspecialchars(
                                $calendario['fecha_fin_cierre']
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- GENERACIÓN INTERESES -->

                    <div class="form-group">

                        <label for="fecha_generacion_intereses">
                            Generación de intereses
                        </label>

                        <input
                            type="date"
                            name="fecha_generacion_intereses"
                            id="fecha_generacion_intereses"
                            value="<?= htmlspecialchars(
                                $calendario[
                                    'fecha_generacion_intereses'
                                ]
                            ) ?>"
                            required
                        >

                        <small>
                            Fecha en la que el sistema realizará
                            el proceso automático de intereses.
                        </small>

                    </div>


                </div>


                <!-- ==================================================
                     ESTADO
                =================================================== -->

                <div class="form-group">

                    <label for="estado">
                        Estado
                    </label>

                    <select
                        name="estado"
                        id="estado"
                        required
                    >

                        <option
                            value="ABIERTO"
                            <?= $calendario['estado'] === 'ABIERTO'
                                ? 'selected'
                                : '' ?>
                        >
                            Abierto
                        </option>

                        <option
                            value="EN_CIERRE"
                            <?= $calendario['estado'] === 'EN_CIERRE'
                                ? 'selected'
                                : '' ?>
                        >
                            En cierre
                        </option>

                        <option
                            value="CERRADO"
                            <?= $calendario['estado'] === 'CERRADO'
                                ? 'selected'
                                : '' ?>
                        >
                            Cerrado
                        </option>

                    </select>

                </div>


                <!-- ==================================================
                     INFORMACIÓN
                =================================================== -->

                <div class="info-box">

                    <strong>
                        Información del período
                    </strong>

                    <p>

                        Período:
                        <?= htmlspecialchars(
                            $periodoTexto
                        ) ?>

                        <br>

                        Estado actual:
                        <?= htmlspecialchars(
                            $calendario['estado']
                        ) ?>

                    </p>

                    <small>
                        Las modificaciones realizadas aquí
                        afectarán el calendario operativo
                        de este período.
                    </small>

                </div>


                <!-- ==================================================
                     OBSERVACIONES
                =================================================== -->

                <div class="form-group">

                    <label for="observaciones">
                        Observaciones
                    </label>

                    <textarea
                        name="observaciones"
                        id="observaciones"
                        rows="4"
                        maxlength="255"
                        placeholder="Observaciones del período..."
                    ><?= htmlspecialchars(
                        $calendario['observaciones'] ?? ''
                    ) ?></textarea>

                </div>


                <!-- ==================================================
                     BOTONES
                =================================================== -->

                <div class="form-actions">

                    <a
                        href="<?= BASE_URL ?>configuracion/calendario_financiero.php"
                        class="btn-secondary"
                    >
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        Guardar cambios
                    </button>

                </div>


            </form>

        </div>


    </main>

</div>


</body>

</html>