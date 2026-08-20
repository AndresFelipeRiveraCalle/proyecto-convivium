<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// CARGAR TARIFAS
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
        t.fecha_inicio DESC
";

$stmt = $conexion->query($sql);

$tarifas = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// CARGAR CONCEPTOS DE FACTURACIÓN
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


// ==========================================================
// CARGAR TIPOS DE UNIDAD
// ==========================================================

$sqlTiposUnidad = "
    SELECT
        id_tipo_config,
        nombre_grupo,
        cantidad_unidades
    FROM detalle_tipos_unidad
    ORDER BY nombre_grupo
";

$stmtTiposUnidad = $conexion->query($sqlTiposUnidad);

$tiposUnidad = $stmtTiposUnidad->fetchAll(PDO::FETCH_ASSOC);

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
            Configura los valores que se utilizarán para generar
            los cobros de la copropiedad según el concepto y el
            tipo de unidad.
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
                                align="center">

                                No hay tarifas configuradas.

                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($tarifas as $tarifa): ?>

                            <tr>


                                <!-- CONCEPTO -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $tarifa['concepto_nombre']
                                        ) ?>

                                    </strong>

                                    <?php if (!empty($tarifa['nombre'])): ?>

                                        <br>

                                        <small>

                                            <?= htmlspecialchars(
                                                $tarifa['nombre']
                                            ) ?>

                                        </small>

                                    <?php endif; ?>

                                </td>


                                <!-- TIPO UNIDAD -->

                                <td>

                                    <?= htmlspecialchars(
                                        $tarifa['tipo_unidad']
                                    ) ?>

                                </td>


                                <!-- TIPO CALCULO -->

                                <td>

                                    <?php

                                    switch ($tarifa['tipo_calculo']) {

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

                                    $<?= number_format(
                                        $tarifa['valor'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>

                                </td>


                                <!-- VIGENCIA -->

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


                <button type="button" id="btnNuevaTarifa" class="btn-filtrar">
                    Agregar tarifa
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

            <h3>Nueva tarifa</h3>

            <button
                type="button"
                class="modal-cerrar"
                id="cerrarNuevaTarifa">
                &times;
            </button>

        </div>

        <form
            action="<?= BASE_URL ?>actions/guardar_tarifa.php"
            method="POST">

            <div class="form-group">

                <label>Concepto *</label>

                <select name="id_concepto" required>

                    <option value="">
                        Seleccione un concepto...
                    </option>

                    <?php foreach ($conceptos as $concepto): ?>

                        <option value="<?= $concepto['id_concepto'] ?>">
                            <?= htmlspecialchars($concepto['nombre']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-group">

                <label>Tipo de unidad *</label>

                <select name="id_tipo_config" required>

                    <option value="">
                        Seleccione...
                    </option>

                    <?php foreach ($tiposUnidad as $tipo): ?>

                        <option value="<?= $tipo['id_tipo_config'] ?>">
                            <?= htmlspecialchars($tipo['nombre_grupo']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-group">

                <label>Nombre de la tarifa</label>

                <input
                    type="text"
                    name="nombre"
                    maxlength="150"
                    placeholder="Ej. Administración Torre A">

            </div>


            <div class="form-group">

                <label>Valor *</label>

                <input
                    type="number"
                    name="valor"
                    step="0.01"
                    min="0"
                    required
                    placeholder="0.00">

            </div>


            <div class="form-group">

                <label>Fecha de inicio *</label>

                <input
                    type="date"
                    name="fecha_inicio"
                    required>

            </div>


            <div class="form-group">

                <label>Fecha de finalización</label>

                <input
                    type="date"
                    name="fecha_fin">

            </div>


            <div class="form-group">

                <label>Observaciones</label>

                <textarea
                    name="observaciones"
                    maxlength="255"
                    rows="3"></textarea>

            </div>


            <div class="form-group">

                <label>Estado</label>

                <select name="estado">

                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>

                </select>

            </div>


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

            <h3>Editar tarifa</h3>

            <button
                type="button"
                class="modal-cerrar"
                id="cerrarModalEditarTarifa">
                &times;
            </button>

        </div>


        <form
            id="formEditarTarifa"
            action="<?= BASE_URL ?>actions/actualizar_tarifa.php"
            method="POST">

            <input
                type="hidden"
                name="id_tarifa"
                id="editar_id_tarifa">


            <!-- CONCEPTO -->

            <div class="form-group">

                <label>
                    Concepto *
                </label>

                <select
                    name="id_concepto"
                    id="editar_id_concepto"
                    required>

                    <option value="">
                        Seleccione...
                    </option>

                    <?php foreach ($conceptos as $concepto): ?>

                        <option
                            value="<?= $concepto['id_concepto'] ?>">

                            <?= htmlspecialchars(
                                $concepto['nombre']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- TIPO DE UNIDAD -->

            <div class="form-group">

                <label>
                    Tipo de unidad *
                </label>

                <select
                    name="id_tipo_config"
                    id="editar_id_tipo_config"
                    required>

                    <option value="">
                        Seleccione...
                    </option>

                    <?php foreach ($tiposUnidad as $tipo): ?>

                        <option
                            value="<?= $tipo['id_tipo_config'] ?>">

                            <?= htmlspecialchars(
                                $tipo['nombre_grupo']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- NOMBRE -->

            <div class="form-group">

                <label>
                    Nombre de la tarifa
                </label>

                <input
                    type="text"
                    name="nombre"
                    id="editar_nombre"
                    maxlength="150">

            </div>


            <!-- VALOR -->

            <div class="form-group">

                <label>
                    Valor *
                </label>

                <input
                    type="number"
                    name="valor"
                    id="editar_valor"
                    step="0.01"
                    min="0"
                    required>

            </div>


            <!-- FECHA INICIO -->

            <div class="form-group">

                <label>
                    Fecha de inicio *
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
                    id="editar_estado">

                    <option value="1">
                        Activa
                    </option>

                    <option value="0">
                        Inactiva
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

<div
    id="modalMensaje"
    class="modal">

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


<script src="<?= BASE_URL ?>assets/js/tarifas.js"></script>

<script>
    const BASE_URL = "<?= BASE_URL ?>";
</script>

<script src="<?= BASE_URL ?>assets/js/editar_tarifa.js"></script>

</body>
</html>

