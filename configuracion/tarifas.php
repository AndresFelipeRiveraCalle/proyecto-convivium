<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// CARGAR TARIFAS
// ==========================================================
//
// Para el listado NO filtramos por estado del concepto ni
// por estado del tipo de unidad.
//
// Esto permite conservar y visualizar correctamente el
// histórico aunque posteriormente un concepto o grupo
// sea inactivado.
// ==========================================================

$sql = "
    SELECT
        t.id_tarifa,
        t.id_concepto,
        t.id_tipo_config,
        t.nombre,
        t.valor,
        t.fecha_inicio,
        t.fecha_fin,
        t.estado,
        t.observaciones,

        cf.nombre AS concepto_nombre,
        cf.tipo_calculo,

        dtu.nombre_grupo AS tipo_unidad

    FROM tarifas_facturacion t

    INNER JOIN conceptos_facturacion cf
        ON cf.id_concepto = t.id_concepto

    INNER JOIN detalle_tipos_unidad dtu
        ON dtu.id_tipo_config = t.id_tipo_config

    ORDER BY
        cf.nombre,
        dtu.nombre_grupo,
        t.fecha_inicio DESC,
        t.id_tarifa DESC
";


$stmt = $conexion->query($sql);

$tarifas = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);


// ==========================================================
// CARGAR CONCEPTOS DE FACTURACIÓN ACTIVOS
// ==========================================================

$sqlConceptos = "
    SELECT
        id_concepto,
        nombre,
        tipo_calculo

    FROM conceptos_facturacion

    WHERE estado = 1

    ORDER BY nombre
";


$stmtConceptos = $conexion->query(
    $sqlConceptos
);

$conceptos = $stmtConceptos->fetchAll(
    PDO::FETCH_ASSOC
);


// ==========================================================
// CARGAR TIPOS DE UNIDAD ACTIVOS
// ==========================================================

$sqlTiposUnidad = "
    SELECT
        id_tipo_config,
        nombre_grupo,
        cantidad_unidades

    FROM detalle_tipos_unidad

    WHERE activo = 1

    ORDER BY nombre_grupo
";


$stmtTiposUnidad = $conexion->query(
    $sqlTiposUnidad
);

$tiposUnidad = $stmtTiposUnidad->fetchAll(
    PDO::FETCH_ASSOC
);


// ==========================================================
// FUNCIÓN PARA MOSTRAR TIPO DE CÁLCULO
// ==========================================================

function nombreTipoCalculo($tipo)
{
    switch ($tipo) {

        case 'FIJO':
            return 'Valor fijo';

        case 'METRO_CUADRADO':
            return 'Por metro cuadrado';

        case 'COEFICIENTE':
            return 'Por coeficiente';

        case 'PORCENTAJE':
            return 'Porcentaje';

        default:
            return $tipo;
    }
}


// ==========================================================
// FUNCIÓN PARA MOSTRAR VALOR
// ==========================================================

function formatoValorTarifa(
    $valor,
    $tipoCalculo
) {

    $valor = (float)$valor;


    switch ($tipoCalculo) {

        case 'PORCENTAJE':

            return number_format(
                $valor,
                4,
                ',',
                '.'
            ) . ' %';


        case 'METRO_CUADRADO':

            return '$' .
                number_format(
                    $valor,
                    2,
                    ',',
                    '.'
                ) .
                ' / m²';


        case 'COEFICIENTE':

            return '$' .
                number_format(
                    $valor,
                    2,
                    ',',
                    '.'
                );


        case 'FIJO':
        default:

            return '$' .
                number_format(
                    $valor,
                    2,
                    ',',
                    '.'
                );
    }
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
            Tarifas de facturación
        </h2>


        <br>


        <p>

            Configura los valores que se utilizarán para generar
            los cobros de la copropiedad según el concepto y el
            tipo de unidad.

        </p>


        <br>


        <!-- ======================================================
             INFORMACIÓN
        ======================================================= -->

        <div class="info-box">


            <strong>
                Información sobre las tarifas
            </strong>


            <p>

                Las tarifas se manejan por concepto, tipo de unidad
                y período de vigencia.

            </p>


            <small>

                Cuando un valor cambie, se recomienda finalizar la
                vigencia de la tarifa anterior y crear una nueva,
                conservando así el histórico de facturación.

            </small>


        </div>


        <br>


        <!-- ======================================================
             LISTADO
        ======================================================= -->

        <div class="bloque filtros">


            <div class="form-card">


                <h3>
                    Tarifas configuradas
                </h3>


                <br>


                <div class="tabla-responsive">


                    <table class="tabla">


                        <thead>


                            <tr>


                                <th>
                                    Concepto
                                </th>


                                <th>
                                    Tipo de unidad
                                </th>


                                <th>
                                    Tipo de cálculo
                                </th>


                                <th>
                                    Valor
                                </th>


                                <th>
                                    Vigencia
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


                        <?php if (empty($tarifas)): ?>


                            <tr>


                                <td
                                    colspan="7"
                                    align="center"
                                >

                                    No hay tarifas configuradas.

                                </td>


                            </tr>


                        <?php else: ?>


                            <?php foreach ($tarifas as $tarifa): ?>


                                <tr>


                                    <!-- ==========================
                                         CONCEPTO
                                    =========================== -->

                                    <td>


                                        <strong>

                                            <?= htmlspecialchars(
                                                $tarifa['concepto_nombre']
                                            ) ?>

                                        </strong>


                                        <?php if (
                                            !empty($tarifa['nombre'])
                                        ): ?>


                                            <br>


                                            <small>

                                                <?= htmlspecialchars(
                                                    $tarifa['nombre']
                                                ) ?>

                                            </small>


                                        <?php endif; ?>


                                    </td>


                                    <!-- ==========================
                                         TIPO DE UNIDAD
                                    =========================== -->

                                    <td>


                                        <?= htmlspecialchars(
                                            $tarifa['tipo_unidad']
                                        ) ?>


                                    </td>


                                    <!-- ==========================
                                         TIPO DE CÁLCULO
                                    =========================== -->

                                    <td>


                                        <?= htmlspecialchars(
                                            nombreTipoCalculo(
                                                $tarifa['tipo_calculo']
                                            )
                                        ) ?>


                                    </td>


                                    <!-- ==========================
                                         VALOR
                                    =========================== -->

                                    <td>


                                        <strong>

                                            <?= htmlspecialchars(
                                                formatoValorTarifa(
                                                    $tarifa['valor'],
                                                    $tarifa['tipo_calculo']
                                                )
                                            ) ?>

                                        </strong>


                                    </td>


                                    <!-- ==========================
                                         VIGENCIA
                                    =========================== -->

                                    <td>


                                        Desde


                                        <?= date(
                                            'd/m/Y',
                                            strtotime(
                                                $tarifa['fecha_inicio']
                                            )
                                        ) ?>


                                        <?php if (
                                            !empty($tarifa['fecha_fin'])
                                        ): ?>


                                            <br>


                                            Hasta


                                            <?= date(
                                                'd/m/Y',
                                                strtotime(
                                                    $tarifa['fecha_fin']
                                                )
                                            ) ?>


                                        <?php else: ?>


                                            <br>


                                            <span class="activo">
                                                Vigente
                                            </span>


                                        <?php endif; ?>


                                    </td>


                                    <!-- ==========================
                                         ESTADO
                                    =========================== -->

                                    <td>


                                        <?php if (
                                            (int)$tarifa['estado'] === 1
                                        ): ?>


                                            <span class="activo">
                                                Activa
                                            </span>


                                        <?php else: ?>


                                            <span class="inactivo">
                                                Inactiva
                                            </span>


                                        <?php endif; ?>


                                    </td>


                                    <!-- ==========================
                                         ACCIONES
                                    =========================== -->

                                    <td>


                                        <button
                                            type="button"
                                            class="btn-secondary btnEditarTarifa"
                                            data-id="<?= (int)$tarifa['id_tarifa'] ?>"
                                        >

                                            ✏ Editar

                                        </button>


                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>


                    </table>


                </div>


                <br>


                <button
                    type="button"
                    id="btnNuevaTarifa"
                    class="btn-filtrar"
                >

                    + Agregar tarifa

                </button>


            </div>


        </div>


    </main>


</div>


<!-- ==========================================================
     MODAL NUEVA TARIFA
========================================================== -->

<div
    id="modalNuevaTarifa"
    class="modal"
>


    <div class="modal-contenido">


        <div class="modal-header">


            <h3>
                Nueva tarifa
            </h3>


            <button
                type="button"
                class="modal-cerrar"
                id="cerrarNuevaTarifa"
            >

                &times;

            </button>


        </div>


        <form
            id="formNuevaTarifa"
            action="<?= BASE_URL ?>actions/guardar_tarifa.php"
            method="POST"
        >


            <!-- ==================================================
                 CONCEPTO
            =================================================== -->

            <div class="form-group">


                <label for="nuevo_id_concepto">
                    Concepto *
                </label>


                <select
                    name="id_concepto"
                    id="nuevo_id_concepto"
                    required
                >


                    <option value="">
                        Seleccione un concepto...
                    </option>


                    <?php foreach ($conceptos as $concepto): ?>


                        <option
                            value="<?= (int)$concepto['id_concepto'] ?>"
                            data-tipo-calculo="<?= htmlspecialchars(
                                $concepto['tipo_calculo']
                            ) ?>"
                        >

                            <?= htmlspecialchars(
                                $concepto['nombre']
                            ) ?>

                        </option>


                    <?php endforeach; ?>


                </select>


            </div>


            <!-- ==================================================
                 TIPO DE CÁLCULO
            =================================================== -->

            <div class="form-group">


                <label for="nuevo_tipo_calculo">
                    Tipo de cálculo
                </label>


                <select
                    id="nuevo_tipo_calculo"
                    disabled
                >


                    <option value="">
                        Seleccione un concepto...
                    </option>


                    <option value="FIJO">
                        Valor fijo
                    </option>


                    <option value="METRO_CUADRADO">
                        Por metro cuadrado
                    </option>


                    <option value="COEFICIENTE">
                        Por coeficiente
                    </option>


                    <option value="PORCENTAJE">
                        Porcentaje
                    </option>


                </select>


                <small id="nuevo_ayuda_tipo_calculo">

                    El tipo de cálculo se define automáticamente
                    según el concepto seleccionado.

                </small>


            </div>


            <!-- ==================================================
                 TIPO DE UNIDAD
            =================================================== -->

            <div class="form-group">


                <label for="nuevo_id_tipo_config">
                    Tipo de unidad *
                </label>


                <select
                    name="id_tipo_config"
                    id="nuevo_id_tipo_config"
                    required
                >


                    <option value="">
                        Seleccione...
                    </option>


                    <?php foreach ($tiposUnidad as $tipo): ?>


                        <option
                            value="<?= (int)$tipo['id_tipo_config'] ?>"
                        >


                            <?= htmlspecialchars(
                                $tipo['nombre_grupo']
                            ) ?>


                            <?php if (
                                isset($tipo['cantidad_unidades'])
                            ): ?>

                                (<?= (int)$tipo['cantidad_unidades'] ?>)

                            <?php endif; ?>


                        </option>


                    <?php endforeach; ?>


                </select>


            </div>


            <!-- ==================================================
                 NOMBRE
            =================================================== -->

            <div class="form-group">


                <label for="nuevo_nombre">
                    Nombre de la tarifa
                </label>


                <input
                    type="text"
                    name="nombre"
                    id="nuevo_nombre"
                    maxlength="150"
                    placeholder="Ej. Administración Torre A"
                >


                <small>

                    Campo opcional para identificar esta tarifa
                    dentro del histórico.

                </small>


            </div>


            <!-- ==================================================
                 VALOR
            =================================================== -->

            <div class="form-group">


                <label
                    for="nuevo_valor"
                    id="nuevo_label_valor"
                >

                    Valor *

                </label>


                <input
                    type="number"
                    name="valor"
                    id="nuevo_valor"
                    step="0.01"
                    min="0"
                    required
                    placeholder="0.00"
                >


                <small id="nuevo_ayuda_valor">

                    Ingrese el valor correspondiente al concepto.

                </small>


            </div>


            <!-- ==================================================
                 FECHA INICIO
            =================================================== -->

            <div class="form-group">


                <label for="nuevo_fecha_inicio">
                    Fecha de inicio *
                </label>


                <input
                    type="date"
                    name="fecha_inicio"
                    id="nuevo_fecha_inicio"
                    required
                >


                <small>

                    Fecha a partir de la cual esta tarifa podrá
                    utilizarse en la facturación.

                </small>


            </div>


            <!-- ==================================================
                 FECHA FIN
            =================================================== -->

            <div class="form-group">


                <label for="nuevo_fecha_fin">
                    Fecha de finalización
                </label>


                <input
                    type="date"
                    name="fecha_fin"
                    id="nuevo_fecha_fin"
                >


                <small>

                    Deje este campo vacío si la tarifa continúa vigente.

                </small>


            </div>


            <!-- ==================================================
                 OBSERVACIONES
            =================================================== -->

            <div class="form-group">


                <label for="nuevo_observaciones">
                    Observaciones
                </label>


                <textarea
                    name="observaciones"
                    id="nuevo_observaciones"
                    maxlength="255"
                    rows="3"
                    placeholder="Observaciones de la tarifa..."
                ></textarea>


            </div>


            <!-- ==================================================
                 ESTADO
            =================================================== -->

            <div class="form-group">


                <label for="nuevo_estado">
                    Estado
                </label>


                <select
                    name="estado"
                    id="nuevo_estado"
                >


                    <option value="1">
                        Activa
                    </option>


                    <option value="0">
                        Inactiva
                    </option>


                </select>


            </div>


            <!-- ==================================================
                 BOTONES
            =================================================== -->

            <div class="form-actions">


                <button
                    type="button"
                    class="btn-limpiar"
                    id="cancelarNuevaTarifa"
                >

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="btn-filtrar"
                >

                    Guardar

                </button>


            </div>


        </form>


    </div>


</div>


<!-- ==========================================================
     MODAL EDITAR TARIFA
========================================================== -->

<div
    id="modalEditarTarifa"
    class="modal"
>


    <div class="modal-contenido">


        <div class="modal-header">


            <h3>
                Editar tarifa
            </h3>


            <button
                type="button"
                class="modal-cerrar"
                id="cerrarModalEditarTarifa"
            >

                &times;

            </button>


        </div>


        <form
            id="formEditarTarifa"
            action="<?= BASE_URL ?>actions/actualizar_tarifa.php"
            method="POST"
        >


            <!-- ==================================================
                 ID
            =================================================== -->

            <input
                type="hidden"
                name="id_tarifa"
                id="editar_id_tarifa"
            >


            <!-- ==================================================
                 CONCEPTO
            =================================================== -->

            <div class="form-group">


                <label for="editar_id_concepto">
                    Concepto *
                </label>


                <select
                    name="id_concepto"
                    id="editar_id_concepto"
                    required
                >


                    <option value="">
                        Seleccione...
                    </option>


                    <?php foreach ($conceptos as $concepto): ?>


                        <option
                            value="<?= (int)$concepto['id_concepto'] ?>"
                            data-tipo-calculo="<?= htmlspecialchars(
                                $concepto['tipo_calculo']
                            ) ?>"
                        >

                            <?= htmlspecialchars(
                                $concepto['nombre']
                            ) ?>

                        </option>


                    <?php endforeach; ?>


                </select>


            </div>


            <!-- ==================================================
                 TIPO DE CÁLCULO
            =================================================== -->

            <div class="form-group">


                <label for="editar_tipo_calculo">
                    Tipo de cálculo
                </label>


                <select
                    id="editar_tipo_calculo"
                    disabled
                >


                    <option value="">
                        Seleccione un concepto...
                    </option>


                    <option value="FIJO">
                        Valor fijo
                    </option>


                    <option value="METRO_CUADRADO">
                        Por metro cuadrado
                    </option>


                    <option value="COEFICIENTE">
                        Por coeficiente
                    </option>


                    <option value="PORCENTAJE">
                        Porcentaje
                    </option>


                </select>


                <small id="editar_ayuda_tipo_calculo">

                    El tipo de cálculo se define automáticamente
                    según el concepto seleccionado.

                </small>


            </div>


            <!-- ==================================================
                 TIPO DE UNIDAD
            =================================================== -->

            <div class="form-group">


                <label for="editar_id_tipo_config">
                    Tipo de unidad *
                </label>


                <select
                    name="id_tipo_config"
                    id="editar_id_tipo_config"
                    required
                >


                    <option value="">
                        Seleccione...
                    </option>


                    <?php foreach ($tiposUnidad as $tipo): ?>


                        <option
                            value="<?= (int)$tipo['id_tipo_config'] ?>"
                        >

                            <?= htmlspecialchars(
                                $tipo['nombre_grupo']
                            ) ?>

                        </option>


                    <?php endforeach; ?>


                </select>


            </div>


            <!-- ==================================================
                 NOMBRE
            =================================================== -->

            <div class="form-group">


                <label for="editar_nombre">
                    Nombre de la tarifa
                </label>


                <input
                    type="text"
                    name="nombre"
                    id="editar_nombre"
                    maxlength="150"
                >


            </div>


            <!-- ==================================================
                 VALOR
            =================================================== -->

            <div class="form-group">


                <label
                    for="editar_valor"
                    id="editar_label_valor"
                >

                    Valor *

                </label>


                <input
                    type="number"
                    name="valor"
                    id="editar_valor"
                    step="0.01"
                    min="0"
                    required
                >


                <small id="editar_ayuda_valor">

                    Ingrese el valor correspondiente al concepto.

                </small>


            </div>


            <!-- ==================================================
                 FECHA INICIO
            =================================================== -->

            <div class="form-group">


                <label for="editar_fecha_inicio">
                    Fecha de inicio *
                </label>


                <input
                    type="date"
                    name="fecha_inicio"
                    id="editar_fecha_inicio"
                    required
                >


            </div>


            <!-- ==================================================
                 FECHA FIN
            =================================================== -->

            <div class="form-group">


                <label for="editar_fecha_fin">
                    Fecha de finalización
                </label>


                <input
                    type="date"
                    name="fecha_fin"
                    id="editar_fecha_fin"
                >


                <small>
                    Deje vacío si la tarifa continúa vigente.
                </small>


            </div>


            <!-- ==================================================
                 OBSERVACIONES
            =================================================== -->

            <div class="form-group">


                <label for="editar_observaciones">
                    Observaciones
                </label>


                <textarea
                    name="observaciones"
                    id="editar_observaciones"
                    maxlength="255"
                    rows="3"
                ></textarea>


            </div>


            <!-- ==================================================
                 ESTADO
            =================================================== -->

            <div class="form-group">


                <label for="editar_estado">
                    Estado
                </label>


                <select
                    name="estado"
                    id="editar_estado"
                >


                    <option value="1">
                        Activa
                    </option>


                    <option value="0">
                        Inactiva
                    </option>


                </select>


            </div>


            <!-- ==================================================
                 BOTONES
            =================================================== -->

            <div class="form-actions">


                <button
                    type="button"
                    class="btn-limpiar"
                    id="cancelarModalEditarTarifa"
                >

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="btn-filtrar"
                >

                    Guardar cambios

                </button>


            </div>


        </form>


    </div>


</div>


<!-- ==========================================================
     MODAL MENSAJE
========================================================== -->

<div
    id="modalMensaje"
    class="modal"
>


    <div class="modal-contenido modal-mensaje">


        <h2 id="tituloMensaje"></h2>


        <br>


        <p id="textoMensaje"></p>


        <br><br>


        <div class="acciones-modal">


            <button
                type="button"
                id="btnCerrarMensaje"
                class="btn-filtrar"
            >

                Aceptar

            </button>


        </div>


    </div>


</div>


<!-- ==========================================================
     JAVASCRIPT
========================================================== -->

<script>

    const BASE_URL = "<?= BASE_URL ?>";

</script>


<script src="<?= BASE_URL ?>assets/js/modal_popup.js"></script>

<script src="<?= BASE_URL ?>assets/js/tarifas.js"></script>

<script src="<?= BASE_URL ?>assets/js/editar_tarifa.js"></script>


</body>

</html>