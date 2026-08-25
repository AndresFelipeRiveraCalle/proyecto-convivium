<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

try {

    // ==========================================================
    // CONSULTAR PAGOS
    // ==========================================================

    $sql = " SELECT p.id_pago,p.id_unidad,p.fecha_pago,p.valor,p.medio_pago,p.origen_pago,p.estado_conciliacion,
            p.referencia,p.observaciones,p.estado,u.codigo AS codigo_unidad
        FROM pagos p
        INNER JOIN unidades u ON u.id_unidad = p.id_unidad
        WHERE p.estado = 'REGISTRADO'
        ORDER BY p.fecha_pago DESC, p.id_pago DESC
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->execute();

    $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ==========================================================
    // CONSULTAR UNIDADES
    // ==========================================================

    $sqlUnidades = " SELECT id_unidad, codigo FROM unidades
        WHERE activo = 1 ORDER BY codigo ASC
    ";

    $stmtUnidades = $conexion->prepare($sqlUnidades);
    $stmtUnidades->execute();

    $unidades = $stmtUnidades->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    die(
        "Error al consultar pagos: " .
        $e->getMessage()
    );
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
        <!-- ======================================================
             SIDEBAR
        ======================================================= -->
        <?php include ROOT_PATH . "/includes/sidebar.php"; ?>

        <!-- ======================================================
             CONTENIDO
        ======================================================= -->

        <main class="contenido">
            <!-- =================================================
                 ENCABEZADO
            =================================================== -->

            <div class="contenido-header">
                <div>
                    <h1>Pagos</h1>
                    <p>Registro de ingresos recibidos por las unidades </p>
                </div>

                <button type="button" class="btn-filtrar" id="btnNuevoPago">
                    Nuevo pago
                </button>
            </div>

            <!-- ==================================================
                 TABLA DE PAGOS
            =================================================== -->
            <div class="form-card">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Unidad</th>
                            <th>Fecha</th>
                            <th>Valor</th>
                            <th>Medio</th>
                            <th>Origen</th>
                            <th>Conciliación</th>
                            <th>Referencia</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($pagos)): ?>
                            <tr>
                                <td colspan="8" style="text-align:center;">
                                    No existen pagos registrados.
                                </td>
                            </tr>

                        <?php else: ?>
                            <?php foreach ($pagos as $p): ?>
                                <tr>
                                    <!-- UNIDAD -->
                                    <td>
                                        <?= htmlspecialchars(
                                            $p['codigo_unidad']
                                        ) ?>
                                    </td>

                                    <!-- FECHA -->
                                    <td>
                                        <?= htmlspecialchars(
                                            $p['fecha_pago']
                                        ) ?>
                                    </td>

                                    <!-- VALOR -->
                                    <td>
                                        $<?= number_format(
                                            $p['valor'],
                                            0,
                                            ',',
                                            '.'
                                        ) ?>
                                    </td>

                                    <!-- MEDIO -->
                                    <td>
                                        <?= htmlspecialchars(
                                            $p['medio_pago']
                                        ) ?>
                                    </td>

                                    <!-- ORIGEN -->
                                    <td>
                                        <?= htmlspecialchars(
                                            $p['origen_pago']
                                        ) ?>
                                    </td>

                                    <!-- CONCILIACIÓN -->
                                    <td>
                                        <?= htmlspecialchars(
                                            $p['estado_conciliacion']
                                        ) ?>
                                    </td>

                                    <!-- REFERENCIA -->
                                    <td>
                                        <?= htmlspecialchars(
                                            $p['referencia'] ?? ''
                                        ) ?>
                                    </td>

                                    <!-- ACCIONES -->
                                    <td>
                                        <button
                                            type="button"
                                            class="btn-secondary btnEditarPago"
                                            data-id="<?= $p['id_pago'] ?>">
                                            Editar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>

        <!-- ======================================================
             MODAL PAGO
        ======================================================= -->
        <div class="modal" id="modalPago">
            <div class="modal-contenido modal-mensaje">
                <!-- ==================================================
                     HEADER MODAL
                =================================================== -->

                <div class="modal-header">


                    <h2>
                        Registrar pago
                    </h2>


                    <button
                        type="button"
                        class="modal-close"
                        id="cerrarModalPago">
                        &times;
                    </button>


                </div>


                <!-- ==================================================
                     FORMULARIO
                =================================================== -->

                <form
                    id="formPago"
                    method="POST"
                    action="<?= BASE_URL ?>actions/guardar_pago.php">


                    <!-- ID DEL PAGO -->

                    <input
                        type="hidden"
                        name="id_pago"
                        id="id_pago"
                        value="">


                    <div class="form-grid">


                        <!-- ==================================================
                             UNIDAD
                        =================================================== -->

                        <div class="form-group">


                            <label for="id_unidad">

                                Unidad

                            </label>


                            <select
                                name="id_unidad"
                                id="id_unidad"
                                required>


                                <option value="">

                                    Seleccione una unidad

                                </option>


                                <?php foreach ($unidades as $u): ?>


                                    <option
                                        value="<?= $u['id_unidad'] ?>">

                                        <?= htmlspecialchars(
                                            $u['codigo']
                                        ) ?>

                                    </option>


                                <?php endforeach; ?>


                            </select>


                        </div>


                        <!-- ==================================================
                             FECHA
                        =================================================== -->

                        <div class="form-group">


                            <label for="fecha_pago">

                                Fecha de pago

                            </label>


                            <input
                                type="date"
                                name="fecha_pago"
                                id="fecha_pago"
                                value="<?= date('Y-m-d') ?>"
                                required>


                        </div>


                        <!-- ==================================================
                             VALOR
                        =================================================== -->

                        <div class="form-group">


                            <label for="valor">

                                Valor

                            </label>


                            <input
                                type="number"
                                name="valor"
                                id="valor"
                                min="0"
                                step="0.01"
                                required>


                        </div>


                        <!-- ==================================================
                             MEDIO DE PAGO
                        =================================================== -->

                        <div class="form-group">


                            <label for="medio_pago">

                                Medio de pago

                            </label>


                            <select
                                name="medio_pago"
                                id="medio_pago"
                                required>


                                <option value="TRANSFERENCIA">

                                    Transferencia

                                </option>


                                <option value="EFECTIVO">

                                    Efectivo

                                </option>


                                <option value="CONSIGNACION">

                                    Consignación

                                </option>


                                <option value="PSE">

                                    PSE

                                </option>


                                <option value="TARJETA">

                                    Tarjeta

                                </option>


                                <option value="OTRO">

                                    Otro

                                </option>


                            </select>


                        </div>


                        <!-- ==================================================
                             ORIGEN
                        =================================================== -->

                        <div class="form-group">


                            <label for="origen_pago">

                                Origen del pago

                            </label>


                            <select
                                name="origen_pago"
                                id="origen_pago"
                                required>


                                <option value="MANUAL">

                                    Manual

                                </option>


                                <option value="BANCO">

                                    Banco

                                </option>


                                <option value="PASARELA">

                                    Pasarela

                                </option>


                            </select>


                        </div>


                        <!-- ==================================================
                             REFERENCIA
                        =================================================== -->

                        <div class="form-group">


                            <label for="referencia">

                                Referencia

                            </label>


                            <input
                                type="text"
                                name="referencia"
                                id="referencia"
                                maxlength="100">


                        </div>


                        <!-- ==================================================
                             OBSERVACIONES
                        =================================================== -->

                        <div
                            class="form-group form-group-full">


                            <label for="observaciones">

                                Observaciones

                            </label>


                            <textarea
                                name="observaciones"
                                id="observaciones"
                                maxlength="255"
                                rows="3"></textarea>


                        </div>


                    </div>


                    <!-- ==================================================
                         FOOTER MODAL
                    =================================================== -->

                    <div class="modal-footer">


                        <button
                            type="button"
                            class="btn-secondary"
                            id="cancelarPago">

                            Cancelar

                        </button>


                        <button
                            type="submit"
                            class="btn-primary">

                            Guardar pago

                        </button>


                    </div>


                </form>


            </div>

        </div>


    </div>


    <!-- ==========================================================
         JAVASCRIPT
    =========================================================== -->

<script src="<?= BASE_URL ?>assets/js/modal_popup.js"></script>
<script src="<?= BASE_URL ?>assets/js/editar_pago.js"></script>


</body>

</html>