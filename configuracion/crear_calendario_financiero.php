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
        "configuracion/calendario_financiero.php?tipo=error&mensaje=" .
        urlencode(
            "No existe una configuración financiera activa."
        )
    );

    exit;
}


// ==========================================================
// PERÍODO POR DEFECTO
// ==========================================================

$periodo = date('Y-m');


// ==========================================================
// GENERAR FECHAS PROPUESTAS
// ==========================================================

$anio = (int)date('Y');
$mes  = (int)date('m');


// ----------------------------------------------------------
// FECHA DE INICIO DE CIERRE
// ----------------------------------------------------------

$diaInicioCierre = (int)$configuracion['dia_inicio_cierre'];

$fechaInicioCierre = sprintf(
    '%04d-%02d-%02d',
    $anio,
    $mes,
    $diaInicioCierre
);


// ----------------------------------------------------------
// FECHA DE FIN DE CIERRE
// ----------------------------------------------------------

$diaFinCierre = (int)$configuracion['dia_fin_cierre'];

$fechaFinCierre = sprintf(
    '%04d-%02d-%02d',
    $anio,
    $mes,
    $diaFinCierre
);


// ----------------------------------------------------------
// FECHA DE FACTURACIÓN
// ----------------------------------------------------------

$diaFacturacion = (int)$configuracion['dia_facturacion'];

$fechaFacturacion = sprintf(
    '%04d-%02d-%02d',
    $anio,
    $mes,
    $diaFacturacion
);


// ----------------------------------------------------------
// FECHA DE VENCIMIENTO
// ----------------------------------------------------------

$diaVencimiento = (int)$configuracion['dia_vencimiento'];

$fechaVencimiento = sprintf(
    '%04d-%02d-%02d',
    $anio,
    $mes,
    $diaVencimiento
);


// ----------------------------------------------------------
// FECHA GENERACIÓN INTERESES
// ----------------------------------------------------------
//
// Inicialmente proponemos el día siguiente al cierre.
//
// Posteriormente podremos hacer esta fecha totalmente
// independiente si queremos una configuración más avanzada.
//

$fechaGeneracionIntereses = date(
    'Y-m-d',
    strtotime($fechaFinCierre . ' +1 day')
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
                 FORMULARIO
            =================================================== -->

            <form
                action="<?= BASE_URL ?>actions/guardar_calendario_financiero.php"
                method="POST"
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
                        Corresponde al período financiero que se
                        administrará.
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
                            value="<?= htmlspecialchars($fechaFacturacion) ?>"
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
                            value="<?= htmlspecialchars($fechaVencimiento) ?>"
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
                            value="<?= htmlspecialchars($fechaInicioCierre) ?>"
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
                            value="<?= htmlspecialchars($fechaFinCierre) ?>"
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
                            value="<?= htmlspecialchars($fechaGeneracionIntereses) ?>"
                            required
                        >

                        <?php if (
                            (int)$configuracion['generar_intereses'] === 1
                        ): ?>

                            <small>
                                Intereses configurados como automáticos.
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

                        <option value="ABIERTO">
                            Abierto
                        </option>

                        <option value="EN_CIERRE">
                            En cierre
                        </option>

                        <option value="CERRADO">
                            Cerrado
                        </option>

                    </select>

                </div>


                <!-- ==================================================
                     INFORMACIÓN DE CONFIGURACIÓN
                =================================================== -->

                <div class="info-box">

                    <strong>
                        Configuración financiera utilizada
                    </strong>

                    <p>

                        Cierre:
                        <?= (int)$configuracion['dia_inicio_cierre'] ?>
                        -
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

                        <?php if (!empty($configuracion['nombre_tasa'])): ?>

                            <br>

                            Tasa:
                            <?= htmlspecialchars(
                                $configuracion['nombre_tasa']
                            ) ?>

                        <?php endif; ?>

                    </p>

                    <small>
                        Las fechas propuestas se pueden modificar
                        para atender situaciones especiales del período.
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

</body>

</html>