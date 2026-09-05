<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// CARGAR GRUPOS DE UNIDADES ACTIVOS
// ==========================================================

$sqlGrupos = "
    SELECT
        id_tipo_config,
        nombre_grupo,
        cantidad_unidades

    FROM detalle_tipos_unidad

    WHERE activo = 1

    ORDER BY id_tipo_config
";


$stmtGrupos = $conexion->query($sqlGrupos);

$grupos = $stmtGrupos->fetchAll(
    PDO::FETCH_ASSOC
);


// ==========================================================
// GRUPO SELECCIONADO
// ==========================================================

$idGrupoSeleccionado = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


// ==========================================================
// SI NO HAY GRUPO SELECCIONADO,
// TOMAR EL PRIMERO
// ==========================================================

if (
    $idGrupoSeleccionado === 0 &&
    !empty($grupos)
) {

    $idGrupoSeleccionado =
        (int)$grupos[0]['id_tipo_config'];
}


// ==========================================================
// CARGAR UNIDADES DEL GRUPO
// ==========================================================

$unidades = [];


if ($idGrupoSeleccionado > 0) {

    $sqlUnidades = "
        SELECT
            id_unidad,
            id_tipo_config,
            codigo,
            nombre,
            piso,
            area,
            coeficiente,
            estado,
            observaciones,
            activo

        FROM unidades

        WHERE id_tipo_config = :id_tipo_config

        ORDER BY codigo
    ";


    $stmtUnidades = $conexion->prepare(
        $sqlUnidades
    );


    $stmtUnidades->execute([

        ':id_tipo_config'
            => $idGrupoSeleccionado

    ]);


    $unidades = $stmtUnidades->fetchAll(
        PDO::FETCH_ASSOC
    );
}


// ==========================================================
// BUSCAR DATOS DEL GRUPO SELECCIONADO
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

            Configura las unidades de la copropiedad,
            su área, coeficiente y estado actual.

        </p>


        <br>


        <!-- ======================================================
             ACCIONES
        ======================================================= -->

        <div class="acciones-superior">


            <?php if ($idGrupoSeleccionado > 0): ?>


                <button
                    type="button"
                    class="btn-filtrar btn-derecha"
                    id="btnNuevaUnidad"
                >

                    + Nueva unidad

                </button>


            <?php endif; ?>


        </div>


        <!-- ======================================================
             TABS DE GRUPOS
        ======================================================= -->

        <div class="tabs-container">


            <?php foreach ($grupos as $grupo): ?>


                <a
                    href="unidades.php?id=<?= (int)$grupo['id_tipo_config'] ?>"
                    class="tab-button
                    <?= (
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


        <!-- ======================================================
             INFORMACIÓN DEL GRUPO
        ======================================================= -->

        <?php if ($grupoSeleccionado): ?>


            <div class="info-box">


                <strong>

                    <?= htmlspecialchars(
                        $grupoSeleccionado['nombre_grupo']
                    ) ?>

                </strong>


                <p>

                    Unidades configuradas para este grupo:

                    <strong>

                        <?= (int)$grupoSeleccionado['cantidad_unidades'] ?>

                    </strong>

                </p>


            </div>


            <br>


        <?php endif; ?>


        <!-- ======================================================
             TABLA
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
                                    colspan="8"
                                    style="text-align:center;"
                                >

                                    No existen unidades registradas
                                    para este grupo.

                                </td>


                            </tr>


                        <?php else: ?>


                            <?php foreach ($unidades as $u): ?>


                                <tr>


                                    <!-- CÓDIGO -->

                                    <td>


                                        <strong>

                                            <?= htmlspecialchars(
                                                $u['codigo']
                                            ) ?>

                                        </strong>


                                    </td>


                                    <!-- NOMBRE -->

                                    <td>


                                        <?= htmlspecialchars(
                                            $u['nombre'] ?? ''
                                        ) ?>


                                    </td>


                                    <!-- PISO -->

                                    <td>


                                        <?= htmlspecialchars(
                                            $u['piso'] ?? ''
                                        ) ?>


                                    </td>


                                    <!-- ÁREA -->

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


                                    <!-- COEFICIENTE -->

                                    <td>


                                        <?php if (
                                            $u['coeficiente'] !== null &&
                                            $u['coeficiente'] !== ''
                                        ): ?>


                                            <?= number_format(
                                                (float)$u['coeficiente'],
                                                5,
                                                ',',
                                                '.'
                                            ) ?>


                                        <?php else: ?>


                                            -


                                        <?php endif; ?>


                                    </td>


                                    <!-- ESTADO DE LA UNIDAD -->

                                    <td>


                                        <?= htmlspecialchars(
                                            $u['estado']
                                        ) ?>


                                    </td>


                                    <!-- ACTIVO / INACTIVO -->

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


                                    <!-- ACCIONES -->

                                    <td>


                                        <button
                                            type="button"
                                            class="btn-secondary btnEditarUnidad"
                                            data-id="<?= (int)$u['id_unidad'] ?>"
                                        >

                                            Editar

                                        </button>


                                        <a
                                            href="personas_unidad.php?id_unidad=<?= (int)$u['id_unidad'] ?>"
                                            class="btn-secondary"
                                        >

                                            Residentes

                                        </a>


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


                <!-- ==========================================
                     GRUPO
                =========================================== -->

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
                            $grupoSeleccionado['nombre_grupo'] ?? ''
                        ) ?>"
                        readonly
                    >


                </div>


                <!-- ==========================================
                     CÓDIGO
                =========================================== -->

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


                    <small>

                        Ejemplo: 101, A-101, Local 01.

                    </small>


                </div>


                <!-- ==========================================
                     NOMBRE
                =========================================== -->

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


                <!-- ==========================================
                     PISO
                =========================================== -->

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


                <!-- ==========================================
                     ESTADO
                =========================================== -->

                <div class="form-group">


                    <label for="nuevo_estado">
                        Estado de la unidad *
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


                    <small>

                        Este estado describe la situación actual
                        de la unidad y no determina por sí solo
                        si debe facturarse.

                    </small>


                </div>


                <!-- ==========================================
                     ÁREA
                =========================================== -->

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


                    <small>

                        Se utiliza cuando un concepto se calcula
                        por metro cuadrado.

                    </small>


                </div>


                <!-- ==========================================
                     COEFICIENTE
                =========================================== -->

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


                    <small>

                        Se utiliza para conceptos calculados
                        por coeficiente.

                    </small>


                </div>


                <!-- ==========================================
                     OBSERVACIONES
                =========================================== -->

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


                <!-- ==========================================
                     BOTONES
                =========================================== -->

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


</div>


<!-- ==========================================================
     JAVASCRIPT
========================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

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


        // ==================================================
        // ABRIR
        // ==================================================

        if (
            btnNuevaUnidad &&
            modalUnidad
        ) {

            btnNuevaUnidad.addEventListener(
                "click",
                function () {

                    modalUnidad.style.display =
                        "flex";

                }
            );
        }


        // ==================================================
        // CERRAR
        // ==================================================

        function cerrarModalUnidad()
        {
            if (modalUnidad) {

                modalUnidad.style.display =
                    "none";
            }
        }


        if (cerrarUnidad) {

            cerrarUnidad.addEventListener(
                "click",
                cerrarModalUnidad
            );
        }


        if (cancelarUnidad) {

            cancelarUnidad.addEventListener(
                "click",
                cerrarModalUnidad
            );
        }


        // ==================================================
        // CLIC FUERA
        // ==================================================

        if (modalUnidad) {

            modalUnidad.addEventListener(
                "click",
                function (event) {

                    if (
                        event.target ===
                        modalUnidad
                    ) {

                        cerrarModalUnidad();
                    }
                }
            );
        }

    }
);

</script>


</body>

</html>