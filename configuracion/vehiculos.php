<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

try {

    // ==========================================================
    // LISTAR VEHÍCULOS
    // ==========================================================

    $sql = "
    SELECT
        v.id_vehiculo,
        v.placa,
        v.tipo,
        v.marca,
        v.modelo,
        v.color,
        v.id_residente,
        v.id_unidad,
        v.estado,
        v.fecha_desde,
        v.fecha_hasta,
        v.observaciones,

        u.nombres AS nombres_residente,
        u.apellidos AS apellidos_residente,

        un.codigo AS codigo_unidad,
        un.nombre AS nombre_unidad

    FROM vehiculos v

    LEFT JOIN residente r
        ON r.id = v.id_residente

    LEFT JOIN usuario u
        ON u.id = r.usuario_id

    LEFT JOIN unidades un
        ON un.id_unidad = v.id_unidad

    ORDER BY v.id_vehiculo DESC";

        $stmt = $conexion->prepare($sql);
        $stmt->execute();

        $vehiculos = $stmt->fetchAll();
    } catch (PDOException $e) {

    error_log($e->getMessage());
    $vehiculos = [];
}


// ==========================================================
// CARGAR UNIDADES
// ==========================================================

try {

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
    $unidades = [];
}


    // ==========================================================
    // CARGAR RESIDENTES
    // ==========================================================

    try {

        $stmtResidentes = $conexion->query("
            SELECT
                r.id AS id_residente,
                r.unidad_id,
                r.usuario_id,
                r.tipo,
                u.nombres,
                u.apellidos
            FROM residente r
            INNER JOIN usuario u
                ON u.id = r.usuario_id
            WHERE r.activo = 1
            AND u.estado = 1
            ORDER BY u.nombres ASC, u.apellidos ASC
        ");

        $residentes = $stmtResidentes->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {

        error_log($e->getMessage());
        $residentes = [];
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

            <div class="contenido-header">

                <div>

                    <h2>Vehículos</h2>

                    <p>
                        Administración de vehículos de la copropiedad
                    </p>

                </div>


                <button
                    type="button"
                    class="btn-primary"
                    id="btnNuevoVehiculo">

                    Nuevo vehículo

                </button>

            </div>



            <!-- ======================================================
            TABLA
        ======================================================= -->

            <div class="table-container">

                <table class="tabla-datos">

                    <thead>

                        <tr>

                            <th>Placa</th>
                            <th>Tipo</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Color</th>
                            <th>Unidad</th>
                            <th>Residente</th>
                            <th>Estado</th>
                            <th>Acciones</th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php if (empty($vehiculos)): ?>

                            <tr>

                                <td
                                    colspan="9"
                                    class="text-center">

                                    No hay vehículos registrados.

                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach ($vehiculos as $v): ?>

                                <tr>


                                    <!-- PLACA -->

                                    <td>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $v['placa']
                                            ) ?>

                                        </strong>

                                    </td>


                                    <!-- TIPO -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $v['tipo']
                                        ) ?>

                                    </td>


                                    <!-- MARCA -->

                                    <td>

                                        <?= !empty($v['marca'])

                                            ? htmlspecialchars(
                                                $v['marca']
                                            )

                                            : '<span class="texto-muted">
                                        Sin especificar
                                    </span>'
                                        ?>

                                    </td>


                                    <!-- MODELO -->

                                    <td>

                                        <?= !empty($v['modelo'])

                                            ? htmlspecialchars(
                                                $v['modelo']
                                            )

                                            : '<span class="texto-muted">
                                        Sin especificar
                                    </span>'
                                        ?>

                                    </td>


                                    <!-- COLOR -->

                                    <td>

                                        <?= !empty($v['color'])

                                            ? htmlspecialchars(
                                                $v['color']
                                            )

                                            : '<span class="texto-muted">
                                        Sin especificar
                                    </span>'
                                        ?>

                                    </td>


                                    <!-- UNIDAD -->

                                    <td>

                                        <?php if (!empty($v['id_unidad'])): ?>

                                            <?= htmlspecialchars(
                                                $v['codigo_unidad'] ?? ''
                                            ) ?>

                                            <?php if (
                                                !empty($v['nombre_unidad'])
                                            ): ?>

                                                -
                                                <?= htmlspecialchars(
                                                    $v['nombre_unidad']
                                                ) ?>

                                            <?php endif; ?>

                                        <?php else: ?>

                                            <span class="texto-muted">

                                                Sin unidad

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- RESIDENTE -->

                                    <td>

                                        <?php

                                        $nombreResidente = trim(
                                            ($v['nombres_residente'] ?? '') .
                                                ' ' .
                                                ($v['apellidos_residente'] ?? '')
                                        );

                                        ?>

                                        <?php if ($nombreResidente !== ''): ?>

                                            <?= htmlspecialchars(
                                                $nombreResidente
                                            ) ?>

                                        <?php else: ?>

                                            <span class="texto-muted">

                                                Sin residente

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- ESTADO -->

                                    <td>

                                        <?php if ((int)$v['estado'] === 1): ?>

                                            <span class="estado estado-activo">

                                                Activo

                                            </span>

                                        <?php else: ?>

                                            <span class="estado estado-inactivo">

                                                Inactivo

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- ACCIONES -->

                                    <td>

                                        <button
                                            type="button"
                                            class="btn-secondary btnEditarVehiculo"
                                            data-id="<?= (int)$v['id_vehiculo'] ?>">

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
    </div>


    <!-- ==========================================================
     MODAL NUEVO VEHÍCULO
=========================================================== -->

    <div
        id="modalVehiculo"
        class="modal"
        style="display:none;">


        <div class="modal-contenido">

            <div class="modal-header">

                <h3>
                    Nuevo vehículo
                </h3>

                <button
                    type="button"
                    class="modal-cerrar"
                    id="cerrarVehiculo">
                    &times;
                </button>

            </div>


            <form
                id="formVehiculo"
                method="POST"
                action="<?= BASE_URL ?>actions/guardar_vehiculo.php">


        <!-- ==================================================
             PLACA
        =================================================== -->

                <div class="form-group">

                    <label for="placa">
                        Placa
                    </label>

                    <input
                        type="text"
                        name="placa"
                        id="placa"
                        maxlength="10"
                        required>

                </div>


        <!-- ==================================================
            TIPO
        =================================================== -->

                <div class="form-group">

                    <label for="tipo">
                        Tipo
                    </label>

                    <select
                        name="tipo"
                        id="tipo"
                        required>

                        <option value="AUTOMOVIL">
                            Automóvil
                        </option>

                        <option value="MOTOCICLETA">
                            Motocicleta
                        </option>

                        <option value="BICICLETA">
                            Bicicleta
                        </option>

                        <option value="OTRO">
                            Otro
                        </option>

                    </select>

                </div>


                <!-- ==================================================
             MARCA
        =================================================== -->

                <div class="form-group">

                    <label for="marca">
                        Marca
                    </label>

                    <input
                        type="text"
                        name="marca"
                        id="marca"
                        maxlength="50">

                </div>


                <!-- ==================================================
             MODELO
        =================================================== -->

                <div class="form-group">

                    <label for="modelo">
                        Modelo
                    </label>

                    <input
                        type="text"
                        name="modelo"
                        id="modelo"
                        maxlength="50">

                </div>


                <!-- ==================================================
             COLOR
        =================================================== -->

                <div class="form-group">

                    <label for="color">
                        Color
                    </label>

                    <input
                        type="text"
                        name="color"
                        id="color"
                        maxlength="30">

                </div>


                <!-- ==================================================
             UNIDAD
        =================================================== -->

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

                                    -
                                    <?= htmlspecialchars($unidad['nombre']) ?>

                                <?php endif; ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- ==================================================
             RESIDENTE
        =================================================== -->

                <div class="form-group">

                    <label for="id_residente">
                        Residente
                    </label>

                    <select
                        name="id_residente"
                        id="id_residente">

                        <option value="">
                            -- Sin residente --
                        </option>
                        <?php foreach ($residentes as $residente): ?>

                            <option
                                value="<?= (int)$residente['id_residente'] ?>"
                                data-unidad="<?= (int)$residente['unidad_id'] ?>">

                                <?= htmlspecialchars(
                                    trim(
                                        ($residente['nombres'] ?? '') . ' ' .
                                        ($residente['apellidos'] ?? '')
                                    )
                                ) ?>
                                -
                                <?= htmlspecialchars(
                                    ucfirst($residente['tipo'])
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- ==================================================
             FECHA DESDE
        =================================================== -->

                <div class="form-group">

                    <label for="fecha_desde">
                        Desde
                    </label>

                    <input
                        type="datetime-local"
                        name="fecha_desde"
                        id="fecha_desde">

                </div>


                <!-- ==================================================
             FECHA HASTA
        =================================================== -->

                <div class="form-group">

                    <label for="fecha_hasta">
                        Hasta
                    </label>

                    <input
                        type="datetime-local"
                        name="fecha_hasta"
                        id="fecha_hasta">

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
                        rows="3"
                        maxlength="255"></textarea>

                </div>


                <!-- ==================================================
             ESTADO
        =================================================== -->

                <div class="form-group">

                    <label for="estado">
                        Estado del registro
                    </label>

                    <select
                        name="estado"
                        id="estado">

                        <option value="1">
                            Activo
                        </option>

                        <option value="0">
                            Inactivo
                        </option>

                    </select>

                </div>


                <!-- ==================================================
             BOTONES
        =================================================== -->

                <div class="form-actions">

                    <button
                        type="button"
                        class="btn-secondary"
                        id="cancelarVehiculo">

                        Cancelar

                    </button>


                    <button
                        type="submit"
                        class="btn-primary">

                        Guardar

                    </button>

                </div>

            </form>

        </div>


    </div>


    <!-- ==========================================================
        MODAL EDITAR VEHÍCULO
    =========================================================== -->

    <div
        id="modalEditarVehiculo"
        class="modal"
        style="display:none;">

        <div class="modal-contenido">

            <div class="modal-header">

                <h3>
                    Editar vehículo
                </h3>

                <button
                    type="button"
                    class="modal-cerrar"
                    id="cerrarEditarVehiculo">

                    &times;

                </button>

            </div>


            <form
                id="formEditarVehiculo"
                method="POST"
                action="<?= BASE_URL ?>actions/editar_vehiculo.php">


                <!-- ID -->

                <input
                    type="hidden"
                    name="id_vehiculo"
                    id="editar_id_vehiculo">


                <!-- ==================================================
                    PLACA
                =================================================== -->

                <div class="form-group">

                    <label for="editar_placa">
                        Placa
                    </label>

                    <input
                        type="text"
                        name="placa"
                        id="editar_placa"
                        maxlength="10"
                        required>

                </div>


                <!-- ==================================================
                    TIPO
                =================================================== -->

                <div class="form-group">

                    <label for="editar_tipo">
                        Tipo
                    </label>

                    <select
                        name="tipo"
                        id="editar_tipo"
                        required>

                        <option value="AUTOMOVIL">
                            Automóvil
                        </option>

                        <option value="MOTOCICLETA">
                            Motocicleta
                        </option>

                        <option value="BICICLETA">
                            Bicicleta
                        </option>

                        <option value="OTRO">
                            Otro
                        </option>

                    </select>

                </div>


                <!-- ==================================================
                    MARCA
                =================================================== -->

                <div class="form-group">

                    <label for="editar_marca">
                        Marca
                    </label>

                    <input
                        type="text"
                        name="marca"
                        id="editar_marca"
                        maxlength="50">

                </div>


                <!-- ==================================================
                    MODELO
                =================================================== -->

                <div class="form-group">

                    <label for="editar_modelo">
                        Modelo
                    </label>

                    <input
                        type="text"
                        name="modelo"
                        id="editar_modelo"
                        maxlength="50">

                </div>


                <!-- ==================================================
                    COLOR
                =================================================== -->

                <div class="form-group">

                    <label for="editar_color">
                        Color
                    </label>

                    <input
                        type="text"
                        name="color"
                        id="editar_color"
                        maxlength="30">

                </div>


                <!-- ==================================================
                    UNIDAD
                =================================================== -->

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

                                <?= htmlspecialchars(
                                    $unidad['codigo']
                                ) ?>

                                <?php if (!empty($unidad['nombre'])): ?>

                                    -
                                    <?= htmlspecialchars(
                                        $unidad['nombre']
                                    ) ?>

                                <?php endif; ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- ==================================================
                    RESIDENTE
                =================================================== -->

                <div class="form-group">

                    <label for="editar_id_residente">
                        Residente
                    </label>

                    <select
                        name="id_residente"
                        id="editar_id_residente">

                        <option value="">
                            -- Sin residente --
                        </option>

                        <?php foreach ($residentes as $residente): ?>

                            <option
                                value="<?= (int)$residente['id_residente'] ?>"
                                data-unidad="<?= (int)$residente['unidad_id'] ?>">

                                <?= htmlspecialchars(
                                    trim(
                                        ($residente['nombres'] ?? '') . ' ' .
                                        ($residente['apellidos'] ?? '')
                                    )
                                ) ?>

                                -
                                <?= htmlspecialchars(
                                    ucfirst($residente['tipo'])
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- ==================================================
                    FECHA DESDE
                =================================================== -->

                <div class="form-group">

                    <label for="editar_fecha_desde">
                        Desde
                    </label>

                    <input
                        type="datetime-local"
                        name="fecha_desde"
                        id="editar_fecha_desde">

                </div>


                <!-- ==================================================
                    FECHA HASTA
                =================================================== -->

                <div class="form-group">

                    <label for="editar_fecha_hasta">
                        Hasta
                    </label>

                    <input
                        type="datetime-local"
                        name="fecha_hasta"
                        id="editar_fecha_hasta">

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
                        rows="3"
                        maxlength="255"></textarea>

                </div>


                <!-- ==================================================
                    ESTADO
                =================================================== -->

                <div class="form-group">

                    <label for="editar_estado">
                        Estado del registro
                    </label>

                    <select
                        name="estado"
                        id="editar_estado">

                        <option value="1">
                            Activo
                        </option>

                        <option value="0">
                            Inactivo
                        </option>

                    </select>

                </div>


                <!-- ==================================================
                    BOTONES
                =================================================== -->

                <div class="form-actions">

                    <button
                        type="button"
                        class="btn-secondary"
                        id="cancelarEditarVehiculo">

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


    
    <!-- ==========================================================
     JAVASCRIPT MODAL
=========================================================== -->

    <script>
        document.addEventListener("DOMContentLoaded",
            function() {

                const btnNuevo =
                    document.getElementById(
                        "btnNuevoVehiculo"
                    );

                const modal =
                    document.getElementById(
                        "modalVehiculo"
                    );

                const cerrar =
                    document.getElementById(
                        "cerrarVehiculo"
                    );

                const cancelar =
                    document.getElementById(
                        "cancelarVehiculo"
                    );

                const formulario =
                    document.getElementById(
                        "formVehiculo"
                    );


                if (!btnNuevo || !modal) {
                    return;
                }


                // ======================================================
                // ABRIR
                // ======================================================

                btnNuevo.addEventListener(
                    "click",
                    function() {

                        formulario.reset();

                        modal.style.display = "flex";

                    }
                );


                // ======================================================
                // CERRAR
                // ======================================================

                function cerrarModal() {

                    modal.style.display = "none";

                }


                if (cerrar) {

                    cerrar.addEventListener(
                        "click",
                        cerrarModal
                    );

                }


                if (cancelar) {

                    cancelar.addEventListener(
                        "click",
                        cerrarModal
                    );

                }


                // ======================================================
                // CLIC FUERA
                // ======================================================

                modal.addEventListener(
                    "click",
                    function(event) {

                        if (event.target === modal) {

                            cerrarModal();

                        }

                    }
                );


                // ======================================================
                // ESC
                // ======================================================

                document.addEventListener(
                    "keydown",
                    function(event) {

                        if (
                            event.key === "Escape" &&
                            modal.style.display === "flex"
                        ) {

                            cerrarModal();

                        }

                    }
                );

            }
        );
    </script>

    
    <script>

    document.addEventListener("DOMContentLoaded", function () {

        // ==========================================================
        // ELEMENTOS DEL MODAL
        // ==========================================================

        const modalEditar = document.getElementById("modalEditarVehiculo");
        const cerrarEditar = document.getElementById("cerrarEditarVehiculo");
        const cancelarEditar = document.getElementById("cancelarEditarVehiculo");

        const selectUnidadEditar = document.getElementById("editar_id_unidad");
        const selectResidenteEditar = document.getElementById("editar_id_residente");


        // ==========================================================
        // VERIFICAR QUE EXISTE EL MODAL
        // ==========================================================

        if (!modalEditar) {
            console.error("No existe #modalEditarVehiculo");
            return;
        }


        // ==========================================================
        // CONVERTIR FECHA MYSQL → DATETIME-LOCAL
        // ==========================================================

        function convertirFecha(fecha) {

            if (!fecha) {
                return "";
            }

            return String(fecha)
                .replace(" ", "T")
                .substring(0, 16);
        }


        // ==========================================================
        // FILTRAR RESIDENTES POR UNIDAD
        // ==========================================================

        function filtrarResidentesEditar(
            unidadSeleccionada,
            residenteSeleccionado = ""
        ) {

            if (!selectResidenteEditar) {
                console.error("No existe #editar_id_residente");
                return;
            }

            unidadSeleccionada = String(unidadSeleccionada || "");
            residenteSeleccionado = String(residenteSeleccionado || "");

            // ------------------------------------------------------
            // RECORRER TODAS LAS OPCIONES
            // ------------------------------------------------------

            const opciones = selectResidenteEditar.querySelectorAll("option");

            opciones.forEach(function (opcion) {

                // La opción vacía siempre debe estar disponible
                if (opcion.value === "") {

                    opcion.hidden = false;
                    return;
                }

                const unidadResidente =
                    String(opcion.dataset.unidad || "");

                if (
                    unidadSeleccionada !== "" &&
                    unidadResidente === unidadSeleccionada
                ) {

                    opcion.hidden = false;

                } else {

                    opcion.hidden = true;
                }

            });


            // ------------------------------------------------------
            // SELECCIONAR RESIDENTE ACTUAL
            // ------------------------------------------------------

            if (residenteSeleccionado !== "") {

                selectResidenteEditar.value =
                    residenteSeleccionado;

            } else {

                selectResidenteEditar.value = "";
            }

        }


        // ==========================================================
        // CAMBIO DE UNIDAD
        // ==========================================================

        if (selectUnidadEditar) {

            selectUnidadEditar.addEventListener("change", function () {

                filtrarResidentesEditar(
                    this.value,
                    ""
                );

            });

        }


        // ==========================================================
        // BOTONES EDITAR
        // ==========================================================

        const botonesEditar =
            document.querySelectorAll(".btnEditarVehiculo");


        console.log(
            "Botones editar encontrados:",
            botonesEditar.length
        );


        botonesEditar.forEach(function (boton) {

            boton.addEventListener("click", function () {

                const id = this.dataset.id;


                // --------------------------------------------------
                // VALIDAR ID
                // --------------------------------------------------

                if (!id) {

                    alert(
                        "No se encontró el ID del vehículo."
                    );

                    return;
                }


                // --------------------------------------------------
                // CONSULTAR VEHÍCULO
                // --------------------------------------------------

                fetch(
                    "<?= BASE_URL ?>actions/obtener_vehiculo.php?id=" +
                    encodeURIComponent(id)
                )

                .then(function (respuesta) {

                    if (!respuesta.ok) {

                        throw new Error(
                            "Error HTTP: " + respuesta.status
                        );
                    }

                    return respuesta.json();

                })


                // --------------------------------------------------
                // PROCESAR RESPUESTA
                // --------------------------------------------------

                .then(function (resultado) {

                    console.log(
                        "Respuesta obtener vehículo:",
                        resultado
                    );


                    if (!resultado.success) {

                        alert(
                            resultado.mensaje ||
                            "No fue posible obtener los datos del vehículo."
                        );

                        return;
                    }


                    const v = resultado.data;


                    // --------------------------------------------------
                    // ID
                    // --------------------------------------------------

                    const campoId =
                        document.getElementById("editar_id_vehiculo");

                    if (campoId) {
                        campoId.value =
                            v.id_vehiculo ?? "";
                    }


                    // --------------------------------------------------
                    // PLACA
                    // --------------------------------------------------

                    const campoPlaca =
                        document.getElementById("editar_placa");

                    if (campoPlaca) {
                        campoPlaca.value =
                            v.placa ?? "";
                    }


                    // --------------------------------------------------
                    // TIPO
                    // --------------------------------------------------

                    const campoTipo =
                        document.getElementById("editar_tipo");

                    if (campoTipo) {
                        campoTipo.value =
                            v.tipo ?? "";
                    }


                    // --------------------------------------------------
                    // MARCA
                    // --------------------------------------------------

                    const campoMarca =
                        document.getElementById("editar_marca");

                    if (campoMarca) {
                        campoMarca.value =
                            v.marca ?? "";
                    }


                    // --------------------------------------------------
                    // MODELO
                    // --------------------------------------------------

                    const campoModelo =
                        document.getElementById("editar_modelo");

                    if (campoModelo) {
                        campoModelo.value =
                            v.modelo ?? "";
                    }


                    // --------------------------------------------------
                    // COLOR
                    // --------------------------------------------------

                    const campoColor =
                        document.getElementById("editar_color");

                    if (campoColor) {
                        campoColor.value =
                            v.color ?? "";
                    }


                    // --------------------------------------------------
                    // FECHA DESDE
                    // --------------------------------------------------

                    const campoFechaDesde =
                        document.getElementById("editar_fecha_desde");

                    if (campoFechaDesde) {

                        campoFechaDesde.value =
                            convertirFecha(v.fecha_desde);
                    }


                    // --------------------------------------------------
                    // FECHA HASTA
                    // --------------------------------------------------

                    const campoFechaHasta =
                        document.getElementById("editar_fecha_hasta");

                    if (campoFechaHasta) {

                        campoFechaHasta.value =
                            convertirFecha(v.fecha_hasta);
                    }


                    // --------------------------------------------------
                    // OBSERVACIONES
                    // --------------------------------------------------

                    const campoObservaciones =
                        document.getElementById(
                            "editar_observaciones"
                        );

                    if (campoObservaciones) {

                        campoObservaciones.value =
                            v.observaciones ?? "";
                    }


                    // --------------------------------------------------
                    // ESTADO
                    // --------------------------------------------------

                    const campoEstado =
                        document.getElementById("editar_estado");

                    if (campoEstado) {

                        campoEstado.value =
                            v.estado ?? "1";
                    }


                    // --------------------------------------------------
                    // UNIDAD
                    // --------------------------------------------------

                    if (selectUnidadEditar) {

                        selectUnidadEditar.value =
                            v.id_unidad ?? "";
                    }


                    // --------------------------------------------------
                    // RESIDENTES
                    // --------------------------------------------------

                    filtrarResidentesEditar(
                        v.id_unidad ?? "",
                        v.id_residente ?? ""
                    );


                    // --------------------------------------------------
                    // ABRIR MODAL
                    // --------------------------------------------------

                    modalEditar.style.display = "flex";


                    // --------------------------------------------------
                    // COLOCAR FOCO EN PLACA
                    // --------------------------------------------------

                    if (campoPlaca) {

                        setTimeout(function () {

                            campoPlaca.focus();

                        }, 100);
                    }

                })


                // --------------------------------------------------
                // ERROR
                // --------------------------------------------------

                .catch(function (error) {

                    console.error(
                        "Error obteniendo vehículo:",
                        error
                    );

                    alert(
                        "No fue posible obtener los datos del vehículo."
                    );

                });

            });

        });


        // ==========================================================
        // CERRAR MODAL
        // ==========================================================

        function cerrarModalEditar() {

            modalEditar.style.display = "none";
        }


        // ==========================================================
        // BOTÓN X
        // ==========================================================

        if (cerrarEditar) {

            cerrarEditar.addEventListener(
                "click",
                cerrarModalEditar
            );

        }


        // ==========================================================
        // BOTÓN CANCELAR
        // ==========================================================

        if (cancelarEditar) {

            cancelarEditar.addEventListener(
                "click",
                cerrarModalEditar
            );

        }


        // ==========================================================
        // CERRAR AL HACER CLIC FUERA
        // ==========================================================

        modalEditar.addEventListener(
            "click",
            function (event) {

                if (event.target === modalEditar) {

                    cerrarModalEditar();

                }

            }
        );


        // ==========================================================
        // CERRAR CON ESC
        // ==========================================================

        document.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key === "Escape" &&
                    modalEditar.style.display === "flex"
                ) {

                    cerrarModalEditar();

                }

            }
        );

    });

    </script>




</body>

</html>