<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// OBTENER CONFIGURACIÓN FINANCIERA ACTIVA
// ==========================================================

$sql = "
    SELECT
        cf.*,
        ti.nombre AS nombre_tasa

    FROM configuracion_financiera cf

    LEFT JOIN tasas_interes ti
        ON ti.id_tasa_interes = cf.id_tasa_interes

    WHERE cf.activo = 1

    ORDER BY cf.id_configuracion DESC

    LIMIT 1
";

$stmt = $conexion->prepare($sql);
$stmt->execute();

$configuracion = $stmt->fetch(PDO::FETCH_ASSOC);


// ==========================================================
// VERIFICAR CONFIGURACIÓN
// ==========================================================

if (!$configuracion) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/calendario_financiero.php" .
        "?tipo=error&mensaje=" .
        urlencode(
            "No existe una configuración financiera activa."
        )
    );

    exit;
}


// ==========================================================
// FUNCIÓN PARA OBTENER FECHA VÁLIDA DEL MES
// ==========================================================
//
// Si el día configurado supera el último día real del mes,
// utilizamos el último día disponible.
//
// Ejemplo:
//
// Día configurado: 31
// Febrero 2027:    28
//

function generarFechaPeriodo($anio, $mes, $dia)
{
    $ultimoDiaMes = (int)date(
        't',
        strtotime(
            sprintf(
                '%04d-%02d-01',
                $anio,
                $mes
            )
        )
    );

    $diaReal = min(
        (int)$dia,
        $ultimoDiaMes
    );

    return sprintf(
        '%04d-%02d-%02d',
        $anio,
        $mes,
        $diaReal
    );
}


// ==========================================================
// PERÍODO POR DEFECTO
// ==========================================================

$periodo = date('Y-m');

$anio = (int)date('Y');
$mes  = (int)date('m');


// ==========================================================
// DÍAS CONFIGURADOS
// ==========================================================

$diaInicioCierre = (int)$configuracion[
    'dia_inicio_cierre'
];

$diaFinCierre = (int)$configuracion[
    'dia_fin_cierre'
];

$diaFacturacion = (int)$configuracion[
    'dia_facturacion'
];

$diaVencimiento = (int)$configuracion[
    'dia_vencimiento'
];


// ==========================================================
// GENERAR FECHAS PROPUESTAS
// ==========================================================

$fechaInicioCierre = generarFechaPeriodo(
    $anio,
    $mes,
    $diaInicioCierre
);

$fechaFinCierre = generarFechaPeriodo(
    $anio,
    $mes,
    $diaFinCierre
);

$fechaFacturacion = generarFechaPeriodo(
    $anio,
    $mes,
    $diaFacturacion
);

$fechaVencimiento = generarFechaPeriodo(
    $anio,
    $mes,
    $diaVencimiento
);


// ==========================================================
// FECHA GENERACIÓN INTERESES
// ==========================================================
//
// Por ahora se propone el día siguiente al cierre.
//
// Esta fecha sigue siendo editable por el administrador.
//

$fechaGeneracionIntereses = date(
    'Y-m-d',
    strtotime(
        $fechaFinCierre . ' +1 day'
    )
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
                        Nuevo período financiero
                    </h1>

                    <p>
                        Configure las fechas de facturación,
                        vencimiento, cierre y generación de intereses
                        para este período.
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
                 INFORMACIÓN
            =================================================== -->

            <div class="info-box">

                <strong>
                    Creación de período financiero
                </strong>

                <p>
                    Las fechas se proponen automáticamente según
                    la configuración financiera general.
                </p>

                <small>
                    Puede modificar manualmente cualquier fecha antes
                    de guardar el período.
                </small>

            </div>


            <!-- ==================================================
                 FORMULARIO
            =================================================== -->

            <form
                action="<?= BASE_URL ?>actions/guardar_calendario_financiero.php"
                method="POST"
                id="formCalendarioFinanciero"
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
                        name="periodo"
                        id="periodo"
                        value="<?= htmlspecialchars($periodo) ?>"
                        required
                    >

                    <small>
                        Al cambiar el período, las fechas propuestas
                        se recalcularán automáticamente.
                    </small>

                </div>


                <!-- ==================================================
                     FECHAS
                =================================================== -->

                <div class="form-grid">


                    <!-- FACTURACIÓN -->

                    <div class="form-group">

                        <label for="fecha_facturacion">
                            Fecha de facturación
                        </label>

                        <input
                            type="date"
                            name="fecha_facturacion"
                            id="fecha_facturacion"
                            value="<?= htmlspecialchars(
                                $fechaFacturacion
                            ) ?>"
                            required
                        >

                    </div>


                    <!-- VENCIMIENTO -->

                    <div class="form-group">

                        <label for="fecha_vencimiento">
                            Fecha de vencimiento
                        </label>

                        <input
                            type="date"
                            name="fecha_vencimiento"
                            id="fecha_vencimiento"
                            value="<?= htmlspecialchars(
                                $fechaVencimiento
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
                                $fechaInicioCierre
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
                                $fechaFinCierre
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
                                $fechaGeneracionIntereses
                            ) ?>"
                            required
                        >

                        <?php if (
                            (int)$configuracion['generar_intereses'] === 1
                        ): ?>

                            <small>
                                La generación automática de intereses
                                está habilitada.
                            </small>

                        <?php else: ?>

                            <small>
                                La generación automática de intereses
                                está desactivada.
                            </small>

                        <?php endif; ?>

                    </div>


                </div>


                <!-- ==================================================
                     ESTADO INICIAL
                =================================================== -->

                <div class="form-group">

                    <label>
                        Estado inicial
                    </label>

                    <input
                        type="text"
                        value="ABIERTO"
                        disabled
                    >

                    <small>
                        Todo nuevo período financiero se crea
                        inicialmente en estado abierto.
                    </small>

                </div>


                <!-- ==================================================
                     INFORMACIÓN DE CONFIGURACIÓN
                =================================================== -->

                <div class="info-box">

                    <strong>
                        Configuración financiera utilizada
                    </strong>

                    <p>

                        Cierre habitual:
                        día
                        <?= (int)$configuracion['dia_inicio_cierre'] ?>
                        -
                        día
                        <?= (int)$configuracion['dia_fin_cierre'] ?>

                        <br>

                        Facturación:
                        día
                        <?= (int)$configuracion['dia_facturacion'] ?>

                        <br>

                        Vencimiento:
                        día
                        <?= (int)$configuracion['dia_vencimiento'] ?>

                        <br>

                        Intereses:
                        <?= (int)$configuracion['generar_intereses'] === 1
                            ? 'Automáticos'
                            : 'Desactivados'
                        ?>

                        <?php if (
                            !empty($configuracion['nombre_tasa'])
                        ): ?>

                            <br>

                            Tasa configurada:
                            <?= htmlspecialchars(
                                $configuracion['nombre_tasa']
                            ) ?>

                        <?php endif; ?>

                    </p>

                    <small>
                        Las fechas propuestas pueden modificarse
                        para atender situaciones especiales
                        del período.
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
                    ></textarea>

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
                        Guardar período
                    </button>

                </div>


            </form>

        </div>


    </main>

</div>


<!-- ==========================================================
     DATOS DE CONFIGURACIÓN PARA JAVASCRIPT
=========================================================== -->

<script>

    window.configuracionCalendario = {

        diaFacturacion:
            <?= (int)$configuracion['dia_facturacion'] ?>,

        diaVencimiento:
            <?= (int)$configuracion['dia_vencimiento'] ?>,

        diaInicioCierre:
            <?= (int)$configuracion['dia_inicio_cierre'] ?>,

        diaFinCierre:
            <?= (int)$configuracion['dia_fin_cierre'] ?>

    };

</script>


<script src="<?= BASE_URL ?>assets/js/crear_calendario_financiero.js"></script>


</body>

</html>