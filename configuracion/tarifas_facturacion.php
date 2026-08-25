<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// CARGAR TARIFAS DE FACTURACIÓN
// ==========================================================

$sql = "
    SELECT
        tf.id_tarifa,
        tf.id_concepto,
        tf.nombre,
        tf.valor,
        tf.fecha_inicio,
        tf.fecha_fin,
        tf.estado,
        tf.observaciones,

        cf.nombre AS concepto,
        cf.tipo_calculo

    FROM tarifas_facturacion tf

    INNER JOIN conceptos_facturacion cf
        ON cf.id_concepto = tf.id_concepto

    ORDER BY tf.fecha_inicio DESC, cf.nombre ASC
";

$stmt = $conexion->query($sql);

$tarifas = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// CARGAR CONCEPTOS ACTIVOS
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

$stmtConceptos = $conexion->query($sqlConceptos);

$conceptos = $stmtConceptos->fetchAll(PDO::FETCH_ASSOC);

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

        <h2 align="center">
            Tarifas de facturación
        </h2>

        <br>

        <p>
            Configura los valores que se utilizarán para calcular
            los cobros de la copropiedad.
        </p>

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


                <table class="tabla">

                    <thead>

                        <tr>

                            <th>Concepto</th>

                            <th>Tipo de cálculo</th>

                            <th>Valor</th>

                            <th>Fecha inicio</th>

                            <th>Fecha fin</th>

                            <th>Estado</th>

                            <th>Acciones</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($tarifas)): ?>

                        <tr>

                            <td colspan="7" align="center">

                                No hay tarifas de facturación
                                configuradas.

                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($tarifas as $tarifa): ?>

                            <tr>


                                <!-- CONCEPTO -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $tarifa['concepto']
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


                                <!-- TIPO DE CÁLCULO -->

                                <td>

                                    <?php

                                    switch (
                                        $tarifa['tipo_calculo']
                                    ) {

                                        case 'FIJO':
                                            echo 'Valor fijo';
                                            break;

                                        case 'METRO_CUADRADO':
                                            echo 'Por metro cuadrado';
                                            break;

                                        case 'COEFICIENTE':
                                            echo 'Por coeficiente';
                                            break;

                                        case 'PORCENTAJE':
                                            echo 'Porcentaje';
                                            break;

                                        default:
                                            echo htmlspecialchars(
                                                $tarifa['tipo_calculo']
                                            );

                                    }

                                    ?>

                                </td>


                                <!-- VALOR -->

                                <td>

                                    <?php

                                    if (
                                        $tarifa['tipo_calculo']
                                        === 'PORCENTAJE'
                                    ) {

                                        echo number_format(
                                            $tarifa['valor'],
                                            2,
                                            ',',
                                            '.'
                                        ) . ' %';

                                    } else {

                                        echo '$ ' .
                                            number_format(
                                                $tarifa['valor'],
                                                2,
                                                ',',
                                                '.'
                                            );

                                    }

                                    ?>

                                </td>


                                <!-- FECHA INICIO -->

                                <td>

                                    <?= date(
                                        'd/m/Y',
                                        strtotime(
                                            $tarifa['fecha_inicio']
                                        )
                                    ) ?>

                                </td>


                                <!-- FECHA FIN -->

                                <td>

                                    <?php if (
                                        !empty($tarifa['fecha_fin'])
                                    ): ?>

                                        <?= date(
                                            'd/m/Y',
                                            strtotime(
                                                $tarifa['fecha_fin']
                                            )
                                        ) ?>

                                    <?php else: ?>

                                        <span>
                                            Sin fecha de finalización
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ESTADO -->

                                <td>

                                    <?php if (
                                        $tarifa['estado'] == 1
                                    ): ?>

                                        <span class="activo">
                                            Activo
                                        </span>

                                    <?php else: ?>

                                        <span class="inactivo">
                                            Inactivo
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACCIONES -->

                                <td>

                                    <button
                                        type="button"
                                        class="btn-secondary btnEditarTarifa"
                                        data-id="<?= $tarifa['id_tarifa'] ?>">

                                        ✏ Editar

                                    </button>

                                </td>


                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>


                <br>


                <button
                    type="button"
                    id="btnNuevaTarifa"
                    class="btn-filtrar">

                    + Agregar tarifa

                </button>


            </div>

        </div>

    </main>

</div>


<!-- ==========================================================
     MODAL NUEVA TARIFA
========================================================== -->

<div id="modalNuevaTarifa" class="modal">

    <div class="modal-contenido">

        <div class="modal-header">

            <h3>
                Nueva tarifa de facturación
            </h3>

            <button
                type="button"
                class="modal-cerrar"
                id="cerrarNuevaTarifa">

                &times;

            </button>

        </div>


        <form
            action="<?= BASE_URL ?>actions/guardar_tarifa_facturacion.php"
            method="POST">


            <!-- CONCEPTO -->

            <div class="form-group">

                <label>
                    Concepto *
                </label>

                <select
                    name="id_concepto"
                    id="id_concepto_tarifa"
                    required>

                    <option value="">
                        Seleccione un concepto...
                    </option>


                    <?php foreach ($conceptos as $concepto): ?>

                        <option
                            value="<?= $concepto['id_concepto'] ?>"
                            data-tipo="<?= $concepto['tipo_calculo'] ?>">

                            <?= htmlspecialchars(
                                $concepto['nombre']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- TIPO DE CÁLCULO -->

            <div class="form-group">

                <label>
                    Tipo de cálculo
                </label>

                <input
                    type="text"
                    id="tipo_calculo_tarifa"
                    readonly
                    placeholder="Se determina según el concepto">

            </div>


            <!-- NOMBRE -->

            <div class="form-group">

                <label>
                    Nombre de la tarifa
                </label>

                <input
                    type="text"
                    name="nombre"
                    maxlength="150"
                    placeholder="Ej. Administración 2026">

            </div>


            <!-- VALOR -->

            <div class="form-group">

                <label>
                    Valor *
                </label>

                <input
                    type="number"
                    name="valor"
                    id="valor_tarifa"
                    min="0"
                    step="0.01"
                    required
                    placeholder="0.00">

            </div>


            <!-- FECHA INICIO -->

            <div class="form-group">

                <label>
                    Fecha de inicio *
                </label>

                <input
                    type="date"
                    name="fecha_inicio"
                    required>

            </div>


            <!-- FECHA FIN -->

            <div class="form-group">

                <label>
                    Fecha de finalización
                </label>

                <input
                    type="date"
                    name="fecha_fin">

                <small>
                    Déjala vacía si la tarifa no tiene fecha
                    de finalización.
                </small>

            </div>


            <!-- OBSERVACIONES -->

            <div class="form-group">

                <label>
                    Observaciones
                </label>

                <textarea
                    name="observaciones"
                    maxlength="255"
                    rows="3"
                    placeholder="Observaciones de la tarifa"></textarea>

            </div>


            <!-- ESTADO -->

            <div class="form-group">

                <label>
                    Estado
                </label>

                <select
                    name="estado">

                    <option value="1">
                        Activo
                    </option>

                    <option value="0">
                        Inactivo
                    </option>

                </select>

            </div>


            <!-- BOTONES -->

            <div class="form-actions">

                <button
                    type="button"
                    class="btn-limpiar"
                    id="cancelarNuevaTarifa">

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="btn-filtrar">

                    Guardar

                </button>

            </div>

        </form>

    </div>

</div>


<!-- ==========================================================
     MODAL EDITAR TARIFA
========================================================== -->

<div id="modalEditarTarifa" class="modal">

    <div class="modal-contenido">

        <div class="modal-header">

            <h3>
                Editar tarifa de facturación
            </h3>

            <button
                type="button"
                class="modal-cerrar"
                id="cerrarModalEditarTarifa">

                &times;

            </button>

        </div>


        <form
            id="formEditarTarifa"
            action="<?= BASE_URL ?>actions/actualizar_tarifa_facturacion.php"
            method="POST">


            <!-- ID -->

            <input
                type="hidden"
                name="id_tarifa"
                id="editar_id_tarifa">


            <!-- CONCEPTO -->

            <div class="form-group">

                <label>
                    Concepto
                </label>

                <select
                    name="id_concepto"
                    id="editar_id_concepto_tarifa"
                    required>

                    <?php foreach ($conceptos as $concepto): ?>

                        <option
                            value="<?= $concepto['id_concepto'] ?>"
                            data-tipo="<?= $concepto['tipo_calculo'] ?>">

                            <?= htmlspecialchars(
                                $concepto['nombre']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- TIPO DE CÁLCULO -->

            <div class="form-group">

                <label>
                    Tipo de cálculo
                </label>

                <input
                    type="text"
                    id="editar_tipo_calculo_tarifa"
                    readonly>

            </div>


            <!-- NOMBRE -->

            <div class="form-group">

                <label>
                    Nombre de la tarifa
                </label>

                <input
                    type="text"
                    name="nombre"
                    id="editar_nombre_tarifa"
                    maxlength="150">

            </div>


            <!-- VALOR -->

            <div class="form-group">

                <label>
                    Valor
                </label>

                <input
                    type="number"
                    name="valor"
                    id="editar_valor_tarifa"
                    min="0"
                    step="0.01"
                    required>

            </div>


            <!-- FECHA INICIO -->

            <div class="form-group">

                <label>
                    Fecha de inicio
                </label>

                <input
                    type="date"
                    name="fecha_inicio"
                    id="editar_fecha_inicio"
                    required>

            </div>


            <!-- FECHA FIN -->

            <div class="form-group">

                <label>
                    Fecha de finalización
                </label>

                <input
                    type="date"
                    name="fecha_fin"
                    id="editar_fecha_fin">

            </div>


            <!-- OBSERVACIONES -->

            <div class="form-group">

                <label>
                    Observaciones
                </label>

                <textarea
                    name="observaciones"
                    id="editar_observaciones"
                    maxlength="255"
                    rows="3"></textarea>

            </div>


            <!-- ESTADO -->

            <div class="form-group">

                <label>
                    Estado
                </label>

                <select
                    name="estado"
                    id="editar_estado_tarifa">

                    <option value="1">
                        Activo
                    </option>

                    <option value="0">
                        Inactivo
                    </option>

                </select>

            </div>


            <!-- BOTONES -->

            <div class="form-actions">

                <button
                    type="button"
                    class="btn-limpiar"
                    id="cancelarModalEditarTarifa">

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="btn-filtrar">

                    Guardar cambios

                </button>

            </div>

        </form>

    </div>

</div>


<!-- ==========================================================
     MODAL MENSAJE
========================================================== -->

<div id="modalMensaje" class="modal">

    <div class="modal-contenido modal-mensaje">

        <h2 id="tituloMensaje"></h2>

        <br>

        <p id="textoMensaje"></p>

        <br><br>

        <div class="acciones-modal">

            <button
                type="button"
                id="btnCerrarMensaje"
                class="btn-filtrar">

                Aceptar

            </button>

        </div>

    </div>

</div>


<script>
    const BASE_URL = "<?= BASE_URL ?>";
</script>

<script src="<?= BASE_URL ?>assets/js/tarifas_facturacion.js"></script>

<script src="<?= BASE_URL ?>assets/js/editar_tarifa_facturacion.js"></script>


</body>

</html>