<?php


require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

try {

    // ==========================================================
    // LISTAR PARQUEADEROS
    // ==========================================================

    $sql = "
        SELECT
            p.id_parqueadero,
            p.codigo,
            p.id_unidad,
            p.tipo,
            p.ubicacion,
            p.estado,
            p.observaciones,
            p.activo,
            p.fecha_creacion,
            u.codigo AS codigo_unidad,
            u.nombre AS nombre_unidad
        FROM parqueaderos p
        LEFT JOIN unidades u
            ON u.id_unidad = p.id_unidad
        ORDER BY p.codigo ASC
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->execute();

    $parqueaderos = $stmt->fetchAll();

    // ==========================================================
    // LISTAR UNIDADES ACTIVAS
    // ==========================================================

    $stmtUnidades = $conexion->query("
        SELECT
            id_unidad,
            codigo,
            nombre
        FROM unidades
        WHERE activo = 1
        ORDER BY codigo ASC
    ");

    $unidades = $stmtUnidades->fetchAll();
    
} catch (PDOException $e) {

    error_log($e->getMessage());
    $parqueaderos = [];

}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include ROOT_PATH . "/includes/head.php"; ?>
</head>


<body>
    <?php include ROOT_PATH . "/includes/header.php"; ?>
    <?php require_once  ROOT_PATH . "/includes/mensajes.php"; ?>
    
    <div class="contenedor">
        <?php include ROOT_PATH . "/includes/sidebar.php"; ?>

        <main class="contenido">

        <!-- ======================================================
            ENCABEZADO
        ======================================================= -->

            <div class="contenido-header">

                <div>
                    <h2>Parqueaderos</h2>
                    <p>Administración de parqueaderos de la copropiedad</p>
                </div>

                <button
                    type="button"
                    class="btn-primary"
                    id="btnNuevoParqueadero">
                    Nuevo parqueadero
                </button>

            </div>


            <!-- ======================================================
                TABLA
            ======================================================= -->

            <div class="table-container">

                <table class="tabla-datos">

                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Unidad</th>
                            <th>Tipo</th>
                            <th>Ubicación</th>
                            <th>Estado</th>
                            <th>Activo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($parqueaderos)): ?>

                            <tr>
                                <td colspan="7" class="text-center">
                                    No hay parqueaderos registrados.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($parqueaderos as $p): ?>

                                <tr>

                                    <!-- Código -->
                                    <td>
                                        <strong>
                                            <?= htmlspecialchars($p['codigo']) ?>
                                        </strong>
                                    </td>


                                    <!-- Unidad -->
                                    <td>
                                        <?php if (!empty($p['id_unidad'])): ?>
                                            <?= htmlspecialchars($p['codigo_unidad'] ?? '') ?>
                                            <?php if (!empty($p['nombre_unidad'])): ?>
                                                - <?= htmlspecialchars($p['nombre_unidad']) ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="texto-muted">
                                                Sin unidad
                                            </span>
                                        <?php endif; ?>

                                    </td>

                                    <!-- Tipo -->
                                    <td>
                                        <?= htmlspecialchars($p['tipo']) ?>
                                    </td>

                                    <!-- Ubicación -->
                                    <td>
                                        <?= !empty($p['ubicacion'])
                                            ? htmlspecialchars($p['ubicacion'])
                                            : '<span class="texto-muted">Sin especificar</span>'
                                        ?>
                                    </td>


                                    <!-- Estado -->
                                    <td>

                                        <?php $claseEstado = '';

                                        switch ($p['estado']) {

                                            case 'DISPONIBLE':
                                                $claseEstado = 'estado-disponible';
                                                break;

                                            case 'OCUPADO':
                                                $claseEstado = 'estado-ocupado';
                                                break;

                                            case 'MANTENIMIENTO':
                                                $claseEstado = 'estado-mantenimiento';
                                                break;

                                        }
                                        ?>

                                        <span class="estado <?= $claseEstado ?>">
                                            <?= htmlspecialchars($p['estado']) ?>
                                        </span>
                                    </td>

                                    <!-- Activo -->
                                    <td>
                                        <?php if ((int)$p['activo'] === 1): ?>
                                            <span class="estado estado-activo">
                                                Activo
                                            </span>
                                        <?php else: ?>
                                            <span class="estado estado-inactivo">
                                                Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Acciones -->
                                    <td>
                                        <button
                                            type="button"
                                            class="btn-secondary btnEditarParqueadero"
                                            data-id="<?= (int)$p['id_parqueadero'] ?>">
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


    <!-- ==========================================================
        MODAL NUEVO / EDITAR PARQUEADERO
    =========================================================== -->

    <div id="modalParqueadero" class="modal-parqueadero">


        <div class="modal-parqueadero-contenido">

            <!-- ENCABEZADO -->

            <div class="modal-parqueadero-header">

                <div>
                    <h3 id="tituloModalParqueadero">
                        Nuevo parqueadero
                    </h3>

                    <p>
                        Registre la información del parqueadero
                    </p>
                </div>

                <button
                    type="button"
                    class="modal-parqueadero-cerrar"
                    id="cerrarModalParqueadero">
                    &times;
                </button>

            </div>


            <!-- FORMULARIO -->

            <form
                id="formParqueadero"
                method="POST"
                action="<?= BASE_URL ?>actions/guardar_parqueadero.php">

                <input
                    type="hidden"
                    name="id_parqueadero"
                    id="id_parqueadero"
                    value="">


                <div class="modal-parqueadero-body">

                    <!-- FILA 1 -->

                    <div class="form-row">

                        <div class="form-group">

                            <label for="codigo">
                                Código del parqueadero
                            </label>

                            <input
                                type="text"
                                name="codigo"
                                id="codigo"
                                maxlength="20"
                                required
                                autocomplete="off">

                        </div>


                        <div class="form-group">

                            <label for="tipo">
                                Tipo
                            </label>

                            <select
                                name="tipo"
                                id="tipo"
                                required>

                                <option value="PRIVADO">
                                    Privado
                                </option>

                                <option value="VISITANTES">
                                    Visitantes
                                </option>

                                <option value="MOTOS">
                                    Motos
                                </option>

                                <option value="BICICLETAS">
                                    Bicicletas
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- UNIDAD -->

                    <div class="form-group">

                        <label for="id_unidad">
                            Unidad
                        </label>

                        <select
                            name="id_unidad"
                            id="id_unidad">

                            <option value="">
                                -- Sin unidad --
                            </option>

                            <?php foreach ($unidades as $unidad): ?>

                                <option
                                    value="<?= (int)$unidad['id_unidad'] ?>">
                                    <?= htmlspecialchars($unidad['codigo']) ?>
                                    <?php if (!empty($unidad['nombre'])): ?>
                                        - <?= htmlspecialchars($unidad['nombre']) ?>
                                    <?php endif; ?>
                                </option>
                            
                            <?php endforeach; ?>
                            

                        </select>

                    </div>


                    <!-- UBICACIÓN -->

                    <div class="form-group">

                        <label for="ubicacion">
                            Ubicación
                        </label>

                        <input
                            type="text"
                            name="ubicacion"
                            id="ubicacion"
                            maxlength="100"
                            placeholder="Ej. Sótano 1, zona norte">

                    </div>


                    <!-- FILA 2 -->

                    <div class="form-row">

                        <div class="form-group">

                            <label for="estado">
                                Estado
                            </label>

                            <select
                                name="estado"
                                id="estado"
                                required>

                                <option value="DISPONIBLE">
                                    Disponible
                                </option>

                                <option value="OCUPADO">
                                    Ocupado
                                </option>

                                <option value="MANTENIMIENTO">
                                    Mantenimiento
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="activo">
                                Estado del registro
                            </label>

                            <select
                                name="activo"
                                id="activo">

                                <option value="1">
                                    Activo
                                </option>

                                <option value="0">
                                    Inactivo
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- OBSERVACIONES -->

                    <div class="form-group">

                        <label for="observaciones">
                            Observaciones
                        </label>

                        <textarea
                            name="observaciones"
                            id="observaciones"
                            rows="3"
                            maxlength="255"
                            placeholder="Observaciones adicionales"></textarea>

                    </div>

                </div>


                <!-- PIE DEL MODAL -->

                <div class="modal-parqueadero-footer">

                    <button
                        type="button"
                        class="btn-secondary"
                        id="cancelarParqueadero">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="btn-primary">
                        Guardar parqueadero
                    </button>

                </div>

            </form>

        </div>
    </div>

    <!-- ==========================================================
        MODAL EDITAR PARQUEADERO
    =========================================================== -->

    <div id="modalEditarParqueadero"
        class="modal"
        style="display:none;">

    
        <div class="modal-contenido">

            <!-- ENCABEZADO -->

            <div class="modal-header">

                <h3>
                    Editar parqueadero
                </h3>

                <button
                    type="button"
                    class="modal-cerrar"
                    id="cerrarEditarParqueadero">
                    &times;
                </button>

            </div>


            <!-- FORMULARIO -->

            <form
                id="formEditarParqueadero"
                method="POST"
                action="<?= BASE_URL ?>actions/editar_parqueadero.php">

                <input
                    type="hidden"
                    name="id_parqueadero"
                    id="editar_id_parqueadero">


                <!-- CÓDIGO -->

                <div class="form-group">

                    <label for="editar_codigo">
                        Código del parqueadero
                    </label>

                    <input
                        type="text"
                        name="codigo"
                        id="editar_codigo"
                        maxlength="20"
                        required>

                </div>


                <!-- TIPO -->

                <div class="form-group">

                    <label for="editar_tipo">
                        Tipo
                    </label>

                    <select
                        name="tipo"
                        id="editar_tipo"
                        required>

                        <option value="PRIVADO">
                            Privado
                        </option>

                        <option value="VISITANTES">
                            Visitantes
                        </option>

                        <option value="MOTOS">
                            Motos
                        </option>

                        <option value="BICICLETAS">
                            Bicicletas
                        </option>

                    </select>

                </div>


                <!-- UNIDAD -->

                <div class="form-group">

                    <label for="editar_id_unidad">
                        Unidad
                    </label>

                    <select
                        name="id_unidad"
                        id="editar_id_unidad">

                        <option value="">
                            -- Sin unidad --
                        </option>

                        <?php foreach ($unidades as $unidad): ?>

                            <option
                                value="<?= (int)$unidad['id_unidad'] ?>">

                                <?= htmlspecialchars($unidad['codigo']) ?>

                                <?php if (!empty($unidad['nombre'])): ?>
                                    - <?= htmlspecialchars($unidad['nombre']) ?>
                                <?php endif; ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- UBICACIÓN -->

                <div class="form-group">

                    <label for="editar_ubicacion">
                        Ubicación
                    </label>

                    <input
                        type="text"
                        name="ubicacion"
                        id="editar_ubicacion"
                        maxlength="100">

                </div>


                <!-- ESTADO -->

                <div class="form-group">

                    <label for="editar_estado">
                        Estado
                    </label>

                    <select
                        name="estado"
                        id="editar_estado"
                        required>

                        <option value="DISPONIBLE">
                            Disponible
                        </option>

                        <option value="OCUPADO">
                            Ocupado
                        </option>

                        <option value="MANTENIMIENTO">
                            Mantenimiento
                        </option>

                    </select>

                </div>


                <!-- OBSERVACIONES -->

                <div class="form-group">

                    <label for="editar_observaciones">
                        Observaciones
                    </label>

                    <textarea
                        name="observaciones"
                        id="editar_observaciones"
                        rows="3"
                        maxlength="255"></textarea>

                </div>


                <!-- ACTIVO -->

                <div class="form-group">

                    <label for="editar_activo">
                        Estado del registro
                    </label>

                    <select
                        name="activo"
                        id="editar_activo">

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
                        class="btn-secondary"
                        id="cancelarEditarParqueadero">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="btn-primary">
                        Guardar cambios
                    </button>

                </div>

            </form>

        </div>

    </div>


    <script> document.addEventListener("DOMContentLoaded", function () {

        const btnNuevo = document.getElementById("btnNuevoParqueadero");
        const modal = document.getElementById("modalParqueadero");
        const btnCerrar = document.getElementById("cerrarModalParqueadero");
        const btnCancelar = document.getElementById("cancelarParqueadero");

        const formulario = document.getElementById("formParqueadero");
        const titulo = document.getElementById("tituloModalParqueadero");


        // ==========================================================
        // ABRIR MODAL - NUEVO
        // ==========================================================

        btnNuevo.addEventListener("click", function () {

            formulario.reset();

            document.getElementById("id_parqueadero").value = "";

            titulo.textContent = "Nuevo parqueadero";

            modal.style.display = "flex";

            document.getElementById("codigo").focus();

        });


        // ==========================================================
        // CERRAR MODAL
        // ==========================================================

        function cerrarModal() {

            modal.style.display = "none";

        }


        btnCerrar.addEventListener("click", cerrarModal);

        btnCancelar.addEventListener("click", cerrarModal);


        // ==========================================================
        // CERRAR AL HACER CLICK FUERA
        // ==========================================================

        modal.addEventListener("click", function (e) {

            if (e.target === modal) {

                cerrarModal();

            }

        });


        // ==========================================================
        // CERRAR CON ESC
        // ==========================================================

        document.addEventListener("keydown", function (e) {

            if (e.key === "Escape" &&
                modal.style.display === "flex") {

                cerrarModal();

            }

        });

    });
    </script>




</body>

</html>