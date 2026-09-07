<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// CARGAR GRUPOS DE UNIDADES
// ==========================================================

$sqlGrupos = "
    SELECT
        dt.id_tipo_config,
        dt.id_tipo_vivienda,
        dt.nombre_grupo,
        dt.cantidad_unidades,
        tv.nombre AS tipo_unidad

    FROM detalle_tipos_unidad dt

    INNER JOIN tipos_vivienda tv
        ON tv.id_tipo_vivienda = dt.id_tipo_vivienda

    WHERE dt.activo = 1

    ORDER BY dt.id_tipo_config
";


$stmtGrupos = $conexion->query($sqlGrupos);

$grupos = $stmtGrupos->fetchAll(
    PDO::FETCH_ASSOC
);


// ==========================================================
// GRUPO SELECCIONADO
// ==========================================================

$idGrupoSeleccionado =
    isset($_GET['id'])
        ? (int)$_GET['id']
        : 0;


// ==========================================================
// SI NO HAY GRUPO, TOMAR EL PRIMERO
// ==========================================================

if (
    $idGrupoSeleccionado === 0 &&
    !empty($grupos)
) {

    $idGrupoSeleccionado =
        (int)$grupos[0]['id_tipo_config'];
}


// ==========================================================
// BUSCAR GRUPO SELECCIONADO
// ==========================================================

$grupoSeleccionado = null;


foreach ($grupos as $grupo) {

    if (
        (int)$grupo['id_tipo_config'] ===
        $idGrupoSeleccionado
    ) {

        $grupoSeleccionado = $grupo;

        break;
    }
}


// ==========================================================
// CARGAR UNIDADES
// ==========================================================
//
// También calculamos:
//
// - personas relacionadas actualmente
// - espacios asociados actualmente
//
// ==========================================================

$unidades = [];


if ($idGrupoSeleccionado > 0) {

    $sqlUnidades = "
        SELECT

            u.id_unidad,
            u.id_tipo_config,
            u.codigo,
            u.nombre,
            u.piso,
            u.area,
            u.coeficiente,
            u.estado,
            u.observaciones,
            u.activo,

            (
                SELECT COUNT(*)

                FROM residente r

                WHERE
                    r.unidad_id = u.id_unidad
                    AND r.activo = 1
                    AND r.fecha_hasta IS NULL

            ) AS cantidad_personas,

            (
                SELECT COUNT(*)

                FROM espacios_unidad eu

                WHERE
                    eu.id_unidad = u.id_unidad
                    AND eu.activo = 1
                    AND eu.fecha_hasta IS NULL

            ) AS cantidad_espacios

        FROM unidades u

        WHERE
            u.id_tipo_config = :id_tipo_config

        ORDER BY u.codigo
    ";


    $stmtUnidades =
        $conexion->prepare(
            $sqlUnidades
        );


    $stmtUnidades->execute([

        ':id_tipo_config'
            => $idGrupoSeleccionado

    ]);


    $unidades =
        $stmtUnidades->fetchAll(
            PDO::FETCH_ASSOC
        );
}


// ==========================================================
// CANTIDADES DEL GRUPO
// ==========================================================

$cantidadConfigurada =
    $grupoSeleccionado
        ? (int)$grupoSeleccionado['cantidad_unidades']
        : 0;


$cantidadCreadas =
    count($unidades);


$cantidadPendientes =
    max(
        0,
        $cantidadConfigurada -
        $cantidadCreadas
    );


$puedeCrearUnidad =
    $grupoSeleccionado &&
    $cantidadCreadas < $cantidadConfigurada;

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
            Unidades
        </h2>


        <br>


        <p>

            Administra las unidades de la copropiedad
            y las personas y espacios asociados a cada una.

        </p>


        <br>


        <!-- ======================================================
             TABS
        ======================================================= -->

        <?php if (!empty($grupos)): ?>


            <div class="tabs-container">


                <?php foreach ($grupos as $grupo): ?>


                    <a
                        href="unidades.php?id=<?= (int)$grupo['id_tipo_config'] ?>"
                        class="tab-button <?= (
                            (int)$grupo['id_tipo_config'] ===
                            $idGrupoSeleccionado
                        )
                            ? 'active'
                            : ''
                        ?>"
                    >

                        <?= htmlspecialchars(
                            $grupo['nombre_grupo']
                        ) ?>

                    </a>


                <?php endforeach; ?>


            </div>


            <br>


        <?php endif; ?>


        <!-- ======================================================
             INFORMACIÓN SIMPLE DEL GRUPO
        ======================================================= -->

        <?php if ($grupoSeleccionado): ?>


            <div class="bloque filtros">


                <div class="card">


                    <label>
                        Grupo
                    </label>


                    <strong>

                        <?= htmlspecialchars(
                            $grupoSeleccionado['nombre_grupo']
                        ) ?>

                    </strong>


                </div>


                <div class="card">


                    <label>
                        Tipo
                    </label>


                    <strong>

                        <?= htmlspecialchars(
                            $grupoSeleccionado['tipo_unidad']
                        ) ?>

                    </strong>


                </div>


                <div class="card">


                    <label>
                        Configuradas
                    </label>


                    <strong>
                        <?= $cantidadConfigurada ?>
                    </strong>


                </div>


                <div class="card">


                    <label>
                        Creadas
                    </label>


                    <strong>
                        <?= $cantidadCreadas ?>
                    </strong>


                </div>


                <div class="card">


                    <label>
                        Pendientes
                    </label>


                    <strong>
                        <?= $cantidadPendientes ?>
                    </strong>


                </div>


            </div>


            <br>


        <?php endif; ?>


        <!-- ======================================================
             ACCIONES
        ======================================================= -->

        <div class="acciones-superior">


            <?php if ($grupoSeleccionado): ?>


                <button
                    type="button"
                    class="btn-filtrar btn-derecha"
                    id="btnNuevaUnidad"
                    <?= !$puedeCrearUnidad
                        ? 'disabled'
                        : ''
                    ?>
                >

                    + Nueva unidad

                </button>


            <?php endif; ?>


        </div>


        <br>


        <!-- ======================================================
             TABLA DE UNIDADES
        ======================================================= -->

        <div class="bloque filtros">


            <div class="form-card">


                <h3>
                    Unidades registradas
                </h3>


                <br>


                <div class="tabla-responsive">


                    <table class="tabla">


                        <thead>


                            <tr>


                                <th>
                                    Código
                                </th>


                                <th>
                                    Nombre
                                </th>


                                <th>
                                    Piso
                                </th>


                                <th>
                                    Área
                                </th>


                                <th>
                                    Coeficiente
                                </th>


                                <th>
                                    Estado
                                </th>


                                <th>
                                    Personas
                                </th>


                                <th>
                                    Espacios
                                </th>


                                <th>
                                    Registro
                                </th>


                                <th>
                                    Acciones
                                </th>


                            </tr>


                        </thead>


                        <tbody>


                        <?php if (empty($unidades)): ?>


                            <tr>


                                <td
                                    colspan="10"
                                    style="text-align:center;"
                                >

                                    No existen unidades registradas
                                    para este grupo.

                                </td>


                            </tr>


                        <?php else: ?>


                            <?php foreach ($unidades as $u): ?>


                                <tr>


                                    <!-- ==========================
                                         CÓDIGO
                                    =========================== -->

                                    <td>


                                        <strong>

                                            <?= htmlspecialchars(
                                                $u['codigo']
                                            ) ?>

                                        </strong>


                                    </td>


                                    <!-- ==========================
                                         NOMBRE
                                    =========================== -->

                                    <td>


                                        <?= htmlspecialchars(
                                            $u['nombre'] ?? ''
                                        ) ?>


                                    </td>


                                    <!-- ==========================
                                         PISO
                                    =========================== -->

                                    <td>


                                        <?= $u['piso'] !== null
                                            ? htmlspecialchars(
                                                $u['piso']
                                            )
                                            : '-'
                                        ?>


                                    </td>


                                    <!-- ==========================
                                         ÁREA
                                    =========================== -->

                                    <td>


                                        <?php if (
                                            $u['area'] !== null &&
                                            $u['area'] !== ''
                                        ): ?>


                                            <?= number_format(
                                                (float)$u['area'],
                                                2,
                                                ',',
                                                '.'
                                            ) ?>

                                            m²


                                        <?php else: ?>


                                            -


                                        <?php endif; ?>


                                    </td>


                                    <!-- ==========================
                                         COEFICIENTE
                                    =========================== -->

                                    <td>


                                        <?php if (
                                            $u['coeficiente'] !== null &&
                                            $u['coeficiente'] !== ''
                                        ): ?>


                                            <?= number_format(
                                                (float)$u['coeficiente'],
                                                8,
                                                '.',
                                                ''
                                            ) ?>


                                        <?php else: ?>


                                            -


                                        <?php endif; ?>


                                    </td>


                                    <!-- ==========================
                                         ESTADO
                                    =========================== -->

                                    <td>


                                        <?= htmlspecialchars(
                                            $u['estado']
                                        ) ?>


                                    </td>


                                    <!-- ==========================
                                         PERSONAS
                                    =========================== -->

                                    <td style="text-align:center;">


                                        <strong>

                                            <?= (int)$u['cantidad_personas'] ?>

                                        </strong>


                                    </td>


                                    <!-- ==========================
                                         ESPACIOS
                                    =========================== -->

                                    <td style="text-align:center;">


                                        <strong>

                                            <?= (int)$u['cantidad_espacios'] ?>

                                        </strong>


                                    </td>


                                    <!-- ==========================
                                         REGISTRO
                                    =========================== -->

                                    <td>


                                        <?php if (
                                            (int)$u['activo'] === 1
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


                                        <!-- PERSONAS -->

                                        <a
                                            href="<?= BASE_URL ?>configuracion/personas_unidad.php?id_unidad=<?= (int)$u['id_unidad'] ?>"
                                            class="btn-secondary"
                                        >

                                            Personas

                                        </a>


                                        <!-- ESPACIOS -->

                                        <a
                                            href="<?= BASE_URL ?>configuracion/espacios_unidad.php?id_unidad=<?= (int)$u['id_unidad'] ?>"
                                            class="btn-secondary"
                                        >

                                            Espacios

                                        </a>


                                        <!-- EDITAR -->

                                        <button
                                            type="button"
                                            class="btn-secondary btnEditarUnidad"

                                            data-id="<?= (int)$u['id_unidad'] ?>"

                                            data-codigo="<?= htmlspecialchars(
                                                $u['codigo'],
                                                ENT_QUOTES
                                            ) ?>"

                                            data-nombre="<?= htmlspecialchars(
                                                $u['nombre'] ?? '',
                                                ENT_QUOTES
                                            ) ?>"

                                            data-piso="<?= htmlspecialchars(
                                                $u['piso'] ?? '',
                                                ENT_QUOTES
                                            ) ?>"

                                            data-area="<?= htmlspecialchars(
                                                $u['area'] ?? '',
                                                ENT_QUOTES
                                            ) ?>"

                                            data-coeficiente="<?= htmlspecialchars(
                                                $u['coeficiente'] ?? '',
                                                ENT_QUOTES
                                            ) ?>"

                                            data-estado="<?= htmlspecialchars(
                                                $u['estado'],
                                                ENT_QUOTES
                                            ) ?>"

                                            data-activo="<?= (int)$u['activo'] ?>"

                                            data-observaciones="<?= htmlspecialchars(
                                                $u['observaciones'] ?? '',
                                                ENT_QUOTES
                                            ) ?>"
                                        >

                                            Editar

                                        </button>


                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            </div>


        </div>


    </main>


    <!-- ==========================================================
         MODAL NUEVA UNIDAD
    ========================================================== -->

    <?php if ($grupoSeleccionado): ?>


        <div
            id="modalUnidad"
            class="modal"
        >


            <div class="modal-contenido">


                <div class="modal-header">


                    <h3>
                        Nueva unidad
                    </h3>


                    <button
                        type="button"
                        id="cerrarUnidad"
                        class="modal-cerrar"
                    >

                        &times;

                    </button>


                </div>


                <form
                    id="formNuevaUnidad"
                    action="<?= BASE_URL ?>actions/guardar_unidad.php"
                    method="POST"
                >


                    <!-- ==============================
                         GRUPO
                    =============================== -->

                    <input
                        type="hidden"
                        name="id_tipo_config"
                        value="<?= (int)$idGrupoSeleccionado ?>"
                    >


                    <div class="form-group">


                        <label>
                            Grupo
                        </label>


                        <input
                            type="text"
                            value="<?= htmlspecialchars(
                                $grupoSeleccionado['nombre_grupo']
                            ) ?>"
                            readonly
                        >


                    </div>


                    <!-- ==============================
                         CÓDIGO
                    =============================== -->

                    <div class="form-group">


                        <label for="nuevo_codigo">
                            Código *
                        </label>


                        <input
                            type="text"
                            name="codigo"
                            id="nuevo_codigo"
                            maxlength="20"
                            required
                        >


                    </div>


                    <!-- ==============================
                         NOMBRE
                    =============================== -->

                    <div class="form-group">


                        <label for="nuevo_nombre">
                            Nombre
                        </label>


                        <input
                            type="text"
                            name="nombre"
                            id="nuevo_nombre"
                            maxlength="100"
                        >


                    </div>


                    <!-- ==============================
                         PISO
                    =============================== -->

                    <div class="form-group">


                        <label for="nuevo_piso">
                            Piso
                        </label>


                        <input
                            type="number"
                            name="piso"
                            id="nuevo_piso"
                            min="0"
                        >


                    </div>


                    <!-- ==============================
                         ÁREA
                    =============================== -->

                    <div class="form-group">


                        <label for="nuevo_area">
                            Área (m²)
                        </label>


                        <input
                            type="number"
                            name="area"
                            id="nuevo_area"
                            step="0.01"
                            min="0"
                        >


                    </div>


                    <!-- ==============================
                         COEFICIENTE
                    =============================== -->

                    <div class="form-group">


                        <label for="nuevo_coeficiente">
                            Coeficiente
                        </label>


                        <input
                            type="number"
                            name="coeficiente"
                            id="nuevo_coeficiente"
                            step="0.00000001"
                            min="0"
                        >


                    </div>


                    <!-- ==============================
                         ESTADO
                    =============================== -->

                    <div class="form-group">


                        <label for="nuevo_estado">
                            Estado *
                        </label>


                        <select
                            name="estado"
                            id="nuevo_estado"
                            required
                        >


                            <option value="Disponible" selected>
                                Disponible
                            </option>


                            <option value="Habitada">
                                Habitada
                            </option>


                            <option value="Desocupada">
                                Desocupada
                            </option>


                            <option value="En mantenimiento">
                                En mantenimiento
                            </option>


                        </select>


                    </div>


                    <!-- ==============================
                         OBSERVACIONES
                    =============================== -->

                    <div class="form-group">


                        <label for="nuevo_observaciones">
                            Observaciones
                        </label>


                        <textarea
                            name="observaciones"
                            id="nuevo_observaciones"
                            rows="3"
                            maxlength="255"
                        ></textarea>


                    </div>


                    <!-- ==============================
                         BOTONES
                    =============================== -->

                    <div class="form-actions">


                        <button
                            type="reset"
                            class="btn-limpiar"
                            id="cancelarUnidad"
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


    <?php endif; ?>


    <!-- ==========================================================
         MODAL EDITAR UNIDAD
    ========================================================== -->

    <div
        id="modalEditarUnidad"
        class="modal"
        style="display:none;"
    >


        <div class="modal-contenido">


            <div class="modal-header">


                <h3>
                    Editar unidad
                </h3>


                <button
                    type="button"
                    id="cerrarEditarUnidad"
                    class="modal-cerrar"
                >

                    &times;

                </button>


            </div>


            <form
                id="formEditarUnidad"
                action="<?= BASE_URL ?>actions/editar_unidad.php"
                method="POST"
            >


                <!-- ID -->

                <input
                    type="hidden"
                    name="id_unidad"
                    id="editar_id_unidad"
                >


                <!-- ==============================
                     CÓDIGO
                =============================== -->

                <div class="form-group">


                    <label for="editar_codigo">
                        Código *
                    </label>


                    <input
                        type="text"
                        name="codigo"
                        id="editar_codigo"
                        maxlength="20"
                        required
                    >


                </div>


                <!-- ==============================
                     NOMBRE
                =============================== -->

                <div class="form-group">


                    <label for="editar_nombre">
                        Nombre
                    </label>


                    <input
                        type="text"
                        name="nombre"
                        id="editar_nombre"
                        maxlength="100"
                    >


                </div>


                <!-- ==============================
                     PISO
                =============================== -->

                <div class="form-group">


                    <label for="editar_piso">
                        Piso
                    </label>


                    <input
                        type="number"
                        name="piso"
                        id="editar_piso"
                        min="0"
                    >


                </div>


                <!-- ==============================
                     ÁREA
                =============================== -->

                <div class="form-group">


                    <label for="editar_area">
                        Área (m²)
                    </label>


                    <input
                        type="number"
                        name="area"
                        id="editar_area"
                        step="0.01"
                        min="0"
                    >


                </div>


                <!-- ==============================
                     COEFICIENTE
                =============================== -->

                <div class="form-group">


                    <label for="editar_coeficiente">
                        Coeficiente
                    </label>


                    <input
                        type="number"
                        name="coeficiente"
                        id="editar_coeficiente"
                        step="0.00000001"
                        min="0"
                    >


                </div>


                <!-- ==============================
                     ESTADO
                =============================== -->

                <div class="form-group">


                    <label for="editar_estado">
                        Estado
                    </label>


                    <select
                        name="estado"
                        id="editar_estado"
                        required
                    >


                        <option value="Disponible">
                            Disponible
                        </option>


                        <option value="Habitada">
                            Habitada
                        </option>


                        <option value="Desocupada">
                            Desocupada
                        </option>


                        <option value="En mantenimiento">
                            En mantenimiento
                        </option>


                    </select>


                </div>


                <!-- ==============================
                     REGISTRO
                =============================== -->

                <div class="form-group">


                    <label for="editar_activo">
                        Registro
                    </label>


                    <select
                        name="activo"
                        id="editar_activo"
                        required
                    >


                        <option value="1">
                            Activa
                        </option>


                        <option value="0">
                            Inactiva
                        </option>


                    </select>


                </div>


                <!-- ==============================
                     OBSERVACIONES
                =============================== -->

                <div class="form-group">


                    <label for="editar_observaciones">
                        Observaciones
                    </label>


                    <textarea
                        name="observaciones"
                        id="editar_observaciones"
                        rows="3"
                        maxlength="255"
                    ></textarea>


                </div>


                <!-- ==============================
                     BOTONES
                =============================== -->

                <div class="form-actions">


                    <button
                        type="button"
                        class="btn-limpiar"
                        id="cancelarEditarUnidad"
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


</div>


<!-- ==========================================================
     JAVASCRIPT
========================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        // ======================================================
        // NUEVA UNIDAD
        // ======================================================

        const btnNuevaUnidad =
            document.getElementById(
                "btnNuevaUnidad"
            );


        const modalUnidad =
            document.getElementById(
                "modalUnidad"
            );


        const cerrarUnidad =
            document.getElementById(
                "cerrarUnidad"
            );


        const cancelarUnidad =
            document.getElementById(
                "cancelarUnidad"
            );


        function cerrarModalNuevaUnidad()
        {

            if (modalUnidad) {

                modalUnidad.style.display =
                    "none";

            }

        }


        if (
            btnNuevaUnidad &&
            modalUnidad &&
            !btnNuevaUnidad.disabled
        ) {

            btnNuevaUnidad.addEventListener(
                "click",
                function () {

                    modalUnidad.style.display =
                        "flex";

                }
            );

        }


        if (cerrarUnidad) {

            cerrarUnidad.addEventListener(
                "click",
                cerrarModalNuevaUnidad
            );

        }


        if (cancelarUnidad) {

            cancelarUnidad.addEventListener(
                "click",
                cerrarModalNuevaUnidad
            );

        }


        if (modalUnidad) {

            modalUnidad.addEventListener(
                "click",
                function (event) {

                    if (
                        event.target ===
                        modalUnidad
                    ) {

                        cerrarModalNuevaUnidad();

                    }

                }
            );

        }


        // ======================================================
        // EDITAR UNIDAD
        // ======================================================

        const modalEditar =
            document.getElementById(
                "modalEditarUnidad"
            );


        const cerrarEditar =
            document.getElementById(
                "cerrarEditarUnidad"
            );


        const cancelarEditar =
            document.getElementById(
                "cancelarEditarUnidad"
            );


        function cerrarModalEditarUnidad()
        {

            if (modalEditar) {

                modalEditar.style.display =
                    "none";

            }

        }


        document
            .querySelectorAll(
                ".btnEditarUnidad"
            )
            .forEach(
                function (boton) {

                    boton.addEventListener(
                        "click",
                        function () {

                            // ==============================
                            // ID
                            // ==============================

                            document.getElementById(
                                "editar_id_unidad"
                            ).value =
                                this.dataset.id || "";


                            // ==============================
                            // CÓDIGO
                            // ==============================

                            document.getElementById(
                                "editar_codigo"
                            ).value =
                                this.dataset.codigo || "";


                            // ==============================
                            // NOMBRE
                            // ==============================

                            document.getElementById(
                                "editar_nombre"
                            ).value =
                                this.dataset.nombre || "";


                            // ==============================
                            // PISO
                            // ==============================

                            document.getElementById(
                                "editar_piso"
                            ).value =
                                this.dataset.piso || "";


                            // ==============================
                            // ÁREA
                            // ==============================

                            document.getElementById(
                                "editar_area"
                            ).value =
                                this.dataset.area || "";


                            // ==============================
                            // COEFICIENTE
                            // ==============================

                            document.getElementById(
                                "editar_coeficiente"
                            ).value =
                                this.dataset.coeficiente || "";


                            // ==============================
                            // ESTADO
                            // ==============================

                            document.getElementById(
                                "editar_estado"
                            ).value =
                                this.dataset.estado ||
                                "Disponible";


                            // ==============================
                            // ACTIVO
                            // ==============================

                            document.getElementById(
                                "editar_activo"
                            ).value =
                                this.dataset.activo ||
                                "1";


                            // ==============================
                            // OBSERVACIONES
                            // ==============================

                            document.getElementById(
                                "editar_observaciones"
                            ).value =
                                this.dataset.observaciones ||
                                "";


                            // ==============================
                            // MOSTRAR
                            // ==============================

                            if (modalEditar) {

                                modalEditar.style.display =
                                    "flex";

                            }

                        }
                    );

                }
            );


        if (cerrarEditar) {

            cerrarEditar.addEventListener(
                "click",
                cerrarModalEditarUnidad
            );

        }


        if (cancelarEditar) {

            cancelarEditar.addEventListener(
                "click",
                cerrarModalEditarUnidad
            );

        }


        if (modalEditar) {

            modalEditar.addEventListener(
                "click",
                function (event) {

                    if (
                        event.target ===
                        modalEditar
                    ) {

                        cerrarModalEditarUnidad();

                    }

                }
            );

        }

    }
);

</script>


</body>

</html>