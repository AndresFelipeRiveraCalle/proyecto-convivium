<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// CONSULTA DE TASAS
// ==========================================================

$sql = "
    SELECT
        id_tasa_interes,
        nombre,
        tasa_anual,
        tasa_mensual,
        fecha_inicio,
        fecha_fin,
        fuente,
        activo,
        observaciones,
        fecha_creacion,
        fecha_actualizacion,

        (
            SELECT COUNT(*)
            FROM intereses_cartera ic
            WHERE ic.id_tasa_interes =
                  tasas_interes.id_tasa_interes
        ) AS cantidad_usos

    FROM tasas_interes

    ORDER BY
        fecha_inicio DESC,
        id_tasa_interes DESC
";

$stmt = $conexion->query($sql);

$tasas = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// FUNCIONES
// ==========================================================

function e($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

function porcentaje($valor)
{
    return number_format(
        round((float)$valor, 2),
        2,
        ',',
        '.'
    ) . ' %';
}


function estadoVigencia($fechaInicio, $fechaFin)
{
    $hoy = date('Y-m-d');

    if ($fechaInicio > $hoy) {
        return 'PROGRAMADA';
    }

    if (
        empty($fechaFin)
        ||
        $fechaFin >= $hoy
    ) {
        return 'VIGENTE';
    }

    return 'HISTÓRICA';
}

?>


<!DOCTYPE html>

<html lang="es">

<head>

    <?php include ROOT_PATH . "/includes/head.php"; ?>

</head>


<body>


<?php include ROOT_PATH . "/includes/header.php"; ?>


<div class="contenedor">


    <?php include ROOT_PATH . "/includes/sidebar.php"; ?>


    <main class="contenido">


        <!-- ======================================================
             TÍTULO
        ======================================================= -->

        <h2 align="center">
            Tasas de interés
        </h2>


        <p align="center">
            Configuración e histórico de tasas utilizadas para el cálculo de mora.
        </p>


        <br>


        <?php
            require_once ROOT_PATH .
                "/includes/mensajes.php";
        ?>


        <!-- ======================================================
             ACCIONES
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                        gap:15px;
                        flex-wrap:wrap;
                    "
                >

                    <div>

                        <h3>
                            Administración de tasas
                        </h3>

                        <small>
                            Las nuevas tasas deben registrarse por vigencia para conservar el histórico.
                        </small>

                    </div>


                    <button
                        type="button"
                        class="btn-filtrar"
                        id="btnNuevaTasa"
                    >
                        + Nueva tasa
                    </button>

                </div>

            </div>

        </div>


        <br>


        <!-- ======================================================
             HISTÓRICO DE TASAS
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Histórico de tasas
                </h3>

                <br>


                <div class="tabla-responsive">

                    <table class="tabla">

                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    Nombre
                                </th>

                                <th>
                                    Tasa anual
                                </th>

                                <th>
                                    Tasa mensual
                                </th>

                                <th>
                                    Inicio
                                </th>

                                <th>
                                    Fin
                                </th>

                                <th>
                                    Fuente
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th>
                                    Vigencia
                                </th>

                                <th>
                                    Acción
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (empty($tasas)): ?>

                            <tr>

                                <td
                                    colspan="10"
                                    align="center"
                                >
                                    No existen tasas registradas.
                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach ($tasas as $tasa): ?>

                                <tr>


                                    <td>
                                        <?= (int)$tasa['id_tasa_interes'] ?>
                                    </td>


                                    <td>
                                        <strong>
                                            <?= e($tasa['nombre']) ?>
                                        </strong>
                                    </td>


                                    <td class="numero">
                                        <?= porcentaje($tasa['tasa_anual']) ?>
                                    </td>


                                    <td class="numero">
                                        <?= porcentaje($tasa['tasa_mensual']) ?>
                                    </td>


                                    <td>
                                        <?= e(
                                            date(
                                                'd/m/Y',
                                                strtotime(
                                                    $tasa['fecha_inicio']
                                                )
                                            )
                                        ) ?>
                                    </td>


                                    <td>

                                        <?php if (
                                            !empty(
                                                $tasa['fecha_fin']
                                            )
                                        ): ?>

                                            <?= e(
                                                date(
                                                    'd/m/Y',
                                                    strtotime(
                                                        $tasa['fecha_fin']
                                                    )
                                                )
                                            ) ?>

                                        <?php else: ?>

                                            <span class="activo">
                                                VIGENTE
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>
                                        <?= e($tasa['fuente'] ?? '-') ?>
                                    </td>


                                    <td>

                                        <?php if (
                                            (int)$tasa['activo'] === 1
                                        ): ?>

                                            <span class="activo">
                                                ACTIVA
                                            </span>

                                        <?php else: ?>

                                            <span class="inactivo">
                                                INACTIVA
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php
                                            $vigenciaActual =
                                                estadoVigencia(
                                                    $tasa['fecha_inicio'],
                                                    $tasa['fecha_fin']
                                                );
                                        ?>

                                        <?php if (
                                            $vigenciaActual === 'VIGENTE'
                                        ): ?>

                                            <span class="activo">
                                                VIGENTE
                                            </span>

                                        <?php elseif (
                                            $vigenciaActual === 'PROGRAMADA'
                                        ): ?>

                                            <strong>
                                                PROGRAMADA
                                            </strong>

                                        <?php else: ?>

                                            <span class="inactivo">
                                                HISTÓRICA
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <div
                                            style="
                                                display:flex;
                                                gap:6px;
                                                flex-wrap:wrap;
                                            "
                                        >

                                            <button
                                                type="button"
                                                class="btn-secondary btnEditarTasa"
                                                data-id="<?= (int)$tasa['id_tasa_interes'] ?>"
                                                data-nombre="<?= e($tasa['nombre']) ?>"
                                                data-fuente="<?= e($tasa['fuente'] ?? '') ?>"
                                                data-observaciones="<?= e($tasa['observaciones'] ?? '') ?>"
                                                data-activo="<?= (int)$tasa['activo'] ?>"
                                                data-fecha-inicio="<?= e($tasa['fecha_inicio']) ?>"
                                                data-fecha-fin="<?= e($tasa['fecha_fin'] ?? '') ?>"
                                                data-usada="<?= (int)($tasa['cantidad_usos'] ?? 0) > 0 ? '1' : '0' ?>"
                                            >
                                                Editar
                                            </button>

                                        <?php if (
                                            (int)$tasa['activo'] === 1
                                            &&
                                            $vigenciaActual === 'VIGENTE'
                                        ): ?>

                                            <button
                                                type="button"
                                                class="btn-secondary btnNuevaVigencia"
                                                data-id="<?= (int)$tasa['id_tasa_interes'] ?>"
                                                data-nombre="<?= e($tasa['nombre']) ?>"
                                                data-tasa-anual="<?= e($tasa['tasa_anual']) ?>"
                                                data-tasa-mensual="<?= e($tasa['tasa_mensual']) ?>"
                                                data-fuente="<?= e($tasa['fuente'] ?? '') ?>"
                                                data-observaciones="<?= e($tasa['observaciones'] ?? '') ?>"
                                            >
                                                Nueva vigencia
                                            </button>

                                        <?php endif; ?>

                                        </div>

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


</div>


<!-- ==========================================================
     MODAL NUEVA TASA
=========================================================== -->

<div
    id="modalNuevaTasa"
    class="modal"
    style="display:none;"
>

    <div class="modal-contenido">

        <div class="modal-header">

            <h3>
                Nueva tasa de interés
            </h3>

            <button
                type="button"
                class="modal-cerrar"
                id="cerrarNuevaTasa"
            >
                &times;
            </button>

        </div>


        <form
            method="POST"
            action="<?= BASE_URL ?>actions/guardar_tasa_interes.php"
        >

            <div
                style="
                    display:grid;
                    grid-template-columns:
                        repeat(
                            auto-fit,
                            minmax(210px, 1fr)
                        );
                    gap:15px;
                "
            >

                <div>

                    <label>
                        Nombre *
                    </label>

                    <input
                        type="text"
                        name="nombre"
                        maxlength="150"
                        required
                    >

                </div>


                <div>

                    <label>
                        Tasa anual (%) *
                    </label>

                    <input
                        type="number"
                        name="tasa_anual"
                        step="0.01"
                        min="0"
                        required
                    >

                </div>


                <div>

                    <label>
                        Tasa mensual (%) *
                    </label>

                    <input
                        type="number"
                        name="tasa_mensual"
                        step="0.01"
                        min="0"
                        required
                    >

                </div>


                <div>

                    <label>
                        Fecha inicio *
                    </label>

                    <input
                        type="date"
                        name="fecha_inicio"
                        required
                    >

                </div>


                <div>

                    <label>
                        Fecha fin
                    </label>

                    <input
                        type="date"
                        name="fecha_fin"
                    >

                </div>


                <div>

                    <label>
                        Fuente
                    </label>

                    <input
                        type="text"
                        name="fuente"
                        maxlength="255"
                    >

                </div>

                <div
                    style="
                        grid-column:1 / -1;
                    "
                >

                    <label>
                        Observaciones
                    </label>

                    <textarea
                        name="observaciones"
                        rows="3"
                        maxlength="255"
                    ></textarea>

                </div>

            </div>


            <br>


            <div class="form-actions">

                <button
                    type="button"
                    class="btn-limpiar"
                    id="cancelarNuevaTasa"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="btn-filtrar"
                >
                    Guardar tasa
                </button>

            </div>

        </form>

    </div>

</div>


<!-- ==========================================================
     MODAL EDITAR TASA
=========================================================== -->

<div
    id="modalEditarTasa"
    class="modal"
    style="display:none;"
>

    <div class="modal-contenido">

        <div class="modal-header">

            <h3>
                Editar datos de la tasa
            </h3>

            <button
                type="button"
                class="modal-cerrar"
                id="cerrarEditarTasa"
            >
                &times;
            </button>

        </div>


        <form
            method="POST"
            action="<?= BASE_URL ?>actions/actualizar_tasa_interes.php"
        >

            <input
                type="hidden"
                name="id_tasa_interes"
                id="editar_id_tasa"
            >


            <div
                style="
                    display:grid;
                    grid-template-columns:
                        repeat(
                            auto-fit,
                            minmax(210px, 1fr)
                        );
                    gap:15px;
                "
            >

                <div>

                    <label>
                        Nombre *
                    </label>

                    <input
                        type="text"
                        name="nombre"
                        id="editar_nombre"
                        maxlength="150"
                        required
                    >

                </div>


                <div>

                    <label>
                        Fuente
                    </label>

                    <input
                        type="text"
                        name="fuente"
                        id="editar_fuente"
                        maxlength="255"
                    >

                </div>


                <div>

                    <label>
                        Estado *
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

                    <small>
                        Al desactivar una tasa no se elimina su histórico.
                    </small>

                </div>


                <div>

                    <label>
                        Fecha inicio *
                    </label>

                    <input
                        type="date"
                        name="fecha_inicio"
                        id="editar_fecha_inicio"
                        required
                    >

                </div>


                <div>

                    <label>
                        Fecha fin
                    </label>

                    <input
                        type="date"
                        name="fecha_fin"
                        id="editar_fecha_fin"
                    >

                </div>


                <div
                    id="avisoFechasBloqueadas"
                    style="
                        grid-column:1 / -1;
                        display:none;
                    "
                >

                    <small>
                        Esta tasa ya fue utilizada en cálculos de intereses.
                        Las fechas de vigencia no pueden modificarse.
                        Para cambios futuros use Nueva vigencia.
                    </small>

                </div>



                <div
                    style="
                        grid-column:1 / -1;
                    "
                >

                    <label>
                        Observaciones
                    </label>

                    <textarea
                        name="observaciones"
                        id="editar_observaciones"
                        rows="3"
                        maxlength="255"
                    ></textarea>

                </div>

            </div>


            <br>


            <div class="form-actions">

                <button
                    type="button"
                    class="btn-limpiar"
                    id="cancelarEditarTasa"
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
     MODAL NUEVA VIGENCIA
=========================================================== -->

<div
    id="modalNuevaVigencia"
    class="modal"
    style="display:none;"
>

    <div class="modal-contenido">

        <div class="modal-header">

            <h3>
                Nueva vigencia de tasa
            </h3>

            <button
                type="button"
                class="modal-cerrar"
                id="cerrarNuevaVigencia"
            >
                &times;
            </button>

        </div>


        <form
            method="POST"
            action="<?= BASE_URL ?>actions/editar_tasa_interes.php"
        >

            <input
                type="hidden"
                name="id_tasa_interes"
                id="vigencia_id_tasa"
            >


            <div
                style="
                    display:grid;
                    grid-template-columns:
                        repeat(
                            auto-fit,
                            minmax(210px, 1fr)
                        );
                    gap:15px;
                "
            >

                <div>

                    <label>
                        Nombre *
                    </label>

                    <input
                        type="text"
                        name="nombre"
                        id="vigencia_nombre"
                        maxlength="150"
                        required
                    >

                </div>


                <div>

                    <label>
                        Nueva tasa anual (%) *
                    </label>

                    <input
                        type="number"
                        name="tasa_anual"
                        id="vigencia_tasa_anual"
                        step="0.01"
                        min="0"
                        required
                    >

                </div>


                <div>

                    <label>
                        Nueva tasa mensual (%) *
                    </label>

                    <input
                        type="number"
                        name="tasa_mensual"
                        id="vigencia_tasa_mensual"
                        step="0.01"
                        min="0"
                        required
                    >

                </div>


                <div>

                    <label>
                        Inicio nueva vigencia *
                    </label>

                    <input
                        type="date"
                        name="fecha_inicio"
                        id="vigencia_fecha_inicio"
                        required
                    >

                    <small>
                        La vigencia anterior se cerrará el día anterior.
                    </small>

                </div>


                <div>

                    <label>
                        Fuente
                    </label>

                    <input
                        type="text"
                        name="fuente"
                        id="vigencia_fuente"
                        maxlength="255"
                    >

                </div>


                <div
                    style="
                        grid-column:1 / -1;
                    "
                >

                    <label>
                        Observaciones
                    </label>

                    <textarea
                        name="observaciones"
                        id="vigencia_observaciones"
                        rows="3"
                        maxlength="255"
                    ></textarea>

                </div>

            </div>


            <br>


            <div class="form-actions">

                <button
                    type="button"
                    class="btn-limpiar"
                    id="cancelarNuevaVigencia"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="btn-filtrar"
                >
                    Crear nueva vigencia
                </button>

            </div>

        </form>

    </div>

</div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const modalNueva =
            document.getElementById(
                'modalNuevaTasa'
            );

        const modalVigencia =
            document.getElementById(
                'modalNuevaVigencia'
            );

        const modalEditar =
            document.getElementById(
                'modalEditarTasa'
            );


        function abrirModal(modal)
        {
            if (modal) {
                modal.style.display = 'flex';
            }
        }


        function cerrarModal(modal)
        {
            if (modal) {
                modal.style.display = 'none';
            }
        }


        document
            .getElementById('btnNuevaTasa')
            ?.addEventListener(
                'click',
                function () {
                    abrirModal(modalNueva);
                }
            );


        document
            .getElementById('cerrarNuevaTasa')
            ?.addEventListener(
                'click',
                function () {
                    cerrarModal(modalNueva);
                }
            );


        document
            .getElementById('cancelarNuevaTasa')
            ?.addEventListener(
                'click',
                function () {
                    cerrarModal(modalNueva);
                }
            );


        document
            .getElementById('cerrarEditarTasa')
            ?.addEventListener(
                'click',
                function () {
                    cerrarModal(modalEditar);
                }
            );


        document
            .getElementById('cancelarEditarTasa')
            ?.addEventListener(
                'click',
                function () {
                    cerrarModal(modalEditar);
                }
            );


        document
            .querySelectorAll(
                '.btnEditarTasa'
            )
            .forEach(
                function (boton) {

                    boton.addEventListener(
                        'click',
                        function () {

                            document
                                .getElementById(
                                    'editar_id_tasa'
                                )
                                .value =
                                    boton.dataset.id
                                    || '';

                            document
                                .getElementById(
                                    'editar_nombre'
                                )
                                .value =
                                    boton.dataset.nombre
                                    || '';

                            document
                                .getElementById(
                                    'editar_fuente'
                                )
                                .value =
                                    boton.dataset.fuente
                                    || '';

                            document
                                .getElementById(
                                    'editar_observaciones'
                                )
                                .value =
                                    boton.dataset.observaciones
                                    || '';

                            document
                                .getElementById(
                                    'editar_activo'
                                )
                                .value =
                                    boton.dataset.activo
                                    || '1';

                            const fechaInicio =
                                document.getElementById(
                                    'editar_fecha_inicio'
                                );

                            const fechaFin =
                                document.getElementById(
                                    'editar_fecha_fin'
                                );

                            const avisoFechas =
                                document.getElementById(
                                    'avisoFechasBloqueadas'
                                );

                            fechaInicio.value =
                                boton.dataset.fechaInicio
                                || '';

                            fechaFin.value =
                                boton.dataset.fechaFin
                                || '';

                            const tasaUsada =
                                boton.dataset.usada === '1';

                            fechaInicio.disabled =
                                tasaUsada;

                            fechaFin.disabled =
                                tasaUsada;

                            if (avisoFechas) {
                                avisoFechas.style.display =
                                    tasaUsada
                                        ? 'block'
                                        : 'none';
                            }

                            abrirModal(
                                modalEditar
                            );
                        }
                    );
                }
            );


        document
            .getElementById('cerrarNuevaVigencia')
            ?.addEventListener(
                'click',
                function () {
                    cerrarModal(modalVigencia);
                }
            );


        document
            .getElementById('cancelarNuevaVigencia')
            ?.addEventListener(
                'click',
                function () {
                    cerrarModal(modalVigencia);
                }
            );


        document
            .querySelectorAll(
                '.btnNuevaVigencia'
            )
            .forEach(
                function (boton) {

                    boton.addEventListener(
                        'click',
                        function () {

                            document
                                .getElementById(
                                    'vigencia_id_tasa'
                                )
                                .value =
                                    boton.dataset.id
                                    || '';

                            document
                                .getElementById(
                                    'vigencia_nombre'
                                )
                                .value =
                                    boton.dataset.nombre
                                    || '';

                            document
                                .getElementById(
                                    'vigencia_tasa_anual'
                                )
                                .value =
                                    boton.dataset.tasaAnual
                                    || '';

                            document
                                .getElementById(
                                    'vigencia_tasa_mensual'
                                )
                                .value =
                                    boton.dataset.tasaMensual
                                    || '';

                            document
                                .getElementById(
                                    'vigencia_fuente'
                                )
                                .value =
                                    boton.dataset.fuente
                                    || '';

                            document
                                .getElementById(
                                    'vigencia_observaciones'
                                )
                                .value =
                                    boton.dataset.observaciones
                                    || '';

                            document
                                .getElementById(
                                    'vigencia_fecha_inicio'
                                )
                                .value = '';

                            abrirModal(
                                modalVigencia
                            );
                        }
                    );
                }
            );


        [
            modalNueva,
            modalVigencia,
            modalEditar
        ].forEach(
            function (modal) {

                modal?.addEventListener(
                    'click',
                    function (event) {

                        if (
                            event.target === modal
                        ) {
                            cerrarModal(modal);
                        }
                    }
                );
            }
        );


        document.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Escape'
                ) {
                    cerrarModal(modalNueva);
                    cerrarModal(modalVigencia);
                    cerrarModal(modalEditar);
                }
            }
        );

    }
);

</script>


</body>

</html>
