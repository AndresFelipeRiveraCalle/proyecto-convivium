<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


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


// ==========================================================
// TASAS DISPONIBLES
// ==========================================================

$sqlTasas = "
    SELECT
        id_tasa_interes,
        nombre,
        tasa_anual,
        tasa_mensual,
        fecha_inicio,
        fecha_fin,
        activo

    FROM tasas_interes

    WHERE activo = 1

    ORDER BY
        fecha_inicio DESC,
        id_tasa_interes DESC
";

$stmtTasas =
    $conexion->query($sqlTasas);

$tasas =
    $stmtTasas->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// CONCEPTOS DISPONIBLES
// ==========================================================

$sqlConceptos = "
    SELECT
        id_concepto,
        nombre,
        tipo_calculo,
        estado

    FROM conceptos_facturacion

    WHERE estado = 1

    ORDER BY nombre
";

$stmtConceptos =
    $conexion->query($sqlConceptos);

$conceptos =
    $stmtConceptos->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// CONFIGURACIONES DE MORA
// ==========================================================

$sql = "
    SELECT
        cm.id_configuracion_mora,
        cm.nombre,
        cm.tipo_tasa,
        cm.tasa,
        cm.periodicidad,
        cm.dias_gracia,
        cm.aplicar_desde,
        cm.id_concepto,
        cm.id_tasa_interes,
        cm.fecha_inicio,
        cm.fecha_fin,
        cm.estado,
        cm.observaciones,
        cm.fecha_creacion,
        cm.fecha_actualizacion,

        ti.nombre AS tasa_nombre,
        ti.tasa_anual,
        ti.tasa_mensual,

        cf.nombre AS concepto_nombre

    FROM configuracion_mora cm

    LEFT JOIN tasas_interes ti
        ON ti.id_tasa_interes =
           cm.id_tasa_interes

    LEFT JOIN conceptos_facturacion cf
        ON cf.id_concepto =
           cm.id_concepto

    ORDER BY
        cm.fecha_inicio DESC,
        cm.id_configuracion_mora DESC
";

$stmt =
    $conexion->query($sql);

$configuraciones =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            Configuración de mora
        </h2>


        <p align="center">
            Defina la tasa y vigencia utilizadas para generar intereses mensuales sobre cartera vencida.
        </p>


        <br>


        <?php
            require_once ROOT_PATH .
                "/includes/mensajes.php";
        ?>


        <!-- ======================================================
             REGLA DEL SISTEMA
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Regla de cálculo
                </h3>

                <br>

                <p>
                    La mora se calcula una sola vez por mes sobre el saldo pendiente de la obligación.
                    No importa si la obligación lleva 1 o 30 días vencida dentro del mes.
                </p>

                <p>
                    Cuando inicia un nuevo mes y la obligación continúa vencida,
                    se genera un nuevo interés mensual sobre el saldo principal pendiente.
                </p>

                <p>
                    <strong>
                        No se calcula interés sobre interés.
                    </strong>
                </p>

            </div>

        </div>


        <br>


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
                            Configuraciones registradas
                        </h3>

                        <small>
                            Cada cambio de tasa debe conservar una vigencia histórica.
                        </small>

                    </div>


                    <button
                        type="button"
                        class="btn-filtrar"
                        id="btnNuevaConfiguracion"
                    >
                        + Nueva configuración
                    </button>

                </div>

            </div>

        </div>


        <br>


        <!-- ======================================================
             TABLA
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Histórico de configuración
                </h3>

                <br>


                <div class="tabla-responsive">

                    <table class="tabla">

                        <thead>

                            <tr>

                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Concepto</th>
                                <th>Tasa</th>
                                <th>Mensual</th>
                                <th>Inicio</th>
                                <th>Fin</th>
                                <th>Estado</th>
                                <th>Vigencia</th>
                                <th>Acción</th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (empty($configuraciones)): ?>

                            <tr>

                                <td
                                    colspan="10"
                                    align="center"
                                >
                                    No existen configuraciones de mora.
                                </td>

                            </tr>

                        <?php else: ?>


                            <?php foreach ($configuraciones as $fila): ?>

                                <?php
                                    $vigenciaActual =
                                        estadoVigencia(
                                            $fila['fecha_inicio'],
                                            $fila['fecha_fin']
                                        );
                                ?>

                                <tr>

                                    <td>
                                        <?= (int)$fila['id_configuracion_mora'] ?>
                                    </td>


                                    <td>
                                        <strong>
                                            <?= e($fila['nombre']) ?>
                                        </strong>
                                    </td>


                                    <td>
                                        <?= e($fila['concepto_nombre'] ?? '-') ?>
                                    </td>


                                    <td>

                                        <?php if (
                                            !empty($fila['tasa_nombre'])
                                        ): ?>

                                            <strong>
                                                <?= e($fila['tasa_nombre']) ?>
                                            </strong>

                                            <br>

                                            <small>
                                                ID tasa:
                                                <?= (int)$fila['id_tasa_interes'] ?>
                                            </small>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </td>


                                    <td class="numero">
                                        <?= porcentaje(
                                            $fila['tasa_mensual']
                                            ?? $fila['tasa']
                                            ?? 0
                                        ) ?>
                                    </td>


                                    <td>
                                        <?= e(
                                            date(
                                                'd/m/Y',
                                                strtotime(
                                                    $fila['fecha_inicio']
                                                )
                                            )
                                        ) ?>
                                    </td>


                                    <td>

                                        <?php if (
                                            !empty($fila['fecha_fin'])
                                        ): ?>

                                            <?= e(
                                                date(
                                                    'd/m/Y',
                                                    strtotime(
                                                        $fila['fecha_fin']
                                                    )
                                                )
                                            ) ?>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            (int)$fila['estado'] === 1
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

                                        <button
                                            type="button"
                                            class="btn-secondary btnEditarConfiguracion"
                                            data-id="<?= (int)$fila['id_configuracion_mora'] ?>"
                                            data-nombre="<?= e($fila['nombre']) ?>"
                                            data-concepto="<?= (int)($fila['id_concepto'] ?? 0) ?>"
                                            data-tasa="<?= (int)($fila['id_tasa_interes'] ?? 0) ?>"
                                            data-fecha-inicio="<?= e($fila['fecha_inicio']) ?>"
                                            data-fecha-fin="<?= e($fila['fecha_fin'] ?? '') ?>"
                                            data-estado="<?= (int)$fila['estado'] ?>"
                                            data-observaciones="<?= e($fila['observaciones'] ?? '') ?>"
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


</div>


<!-- ==========================================================
     MODAL NUEVA CONFIGURACIÓN
=========================================================== -->

<div
    id="modalNuevaConfiguracion"
    class="modal"
    style="display:none;"
>

    <div class="modal-contenido">

        <div class="modal-header">

            <h3>
                Nueva configuración de mora
            </h3>

            <button
                type="button"
                class="modal-cerrar"
                id="cerrarNuevaConfiguracion"
            >
                &times;
            </button>

        </div>


        <form
            method="POST"
            action="<?= BASE_URL ?>actions/guardar_configuracion_mora.php"
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
                        placeholder="Ej. Mora mensual"
                    >

                </div>


                <div>

                    <label>
                        Concepto de facturación *
                    </label>

                    <select
                        name="id_concepto"
                        required
                    >

                        <option value="">
                            Seleccione...
                        </option>

                        <?php foreach ($conceptos as $concepto): ?>

                            <option
                                value="<?= (int)$concepto['id_concepto'] ?>"
                                <?= (int)$concepto['id_concepto'] === 4
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= e($concepto['nombre']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div>

                    <label>
                        Tasa de interés *
                    </label>

                    <select
                        name="id_tasa_interes"
                        required
                    >

                        <option value="">
                            Seleccione...
                        </option>

                        <?php foreach ($tasas as $tasa): ?>

                            <option
                                value="<?= (int)$tasa['id_tasa_interes'] ?>"
                            >
                                <?= e($tasa['nombre']) ?>
                                -
                                <?= porcentaje($tasa['tasa_mensual']) ?>
                                mensual
                                (
                                <?= e($tasa['fecha_inicio']) ?>
                                <?= !empty($tasa['fecha_fin'])
                                    ? ' a ' . e($tasa['fecha_fin'])
                                    : ' en adelante'
                                ?>
                                )
                            </option>

                        <?php endforeach; ?>

                    </select>

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
                        Estado *
                    </label>

                    <select
                        name="estado"
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


            <input
                type="hidden"
                name="tipo_tasa"
                value="PORCENTAJE"
            >

            <input
                type="hidden"
                name="periodicidad"
                value="MENSUAL"
            >

            <input
                type="hidden"
                name="dias_gracia"
                value="0"
            >

            <input
                type="hidden"
                name="aplicar_desde"
                value="DIA_SIGUIENTE_VENCIMIENTO"
            >


            <br>


            <div class="form-actions">

                <button
                    type="button"
                    class="btn-limpiar"
                    id="cancelarNuevaConfiguracion"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="btn-filtrar"
                >
                    Guardar configuración
                </button>

            </div>

        </form>

    </div>

</div>


<!-- ==========================================================
     MODAL EDITAR CONFIGURACIÓN
=========================================================== -->

<div
    id="modalEditarConfiguracion"
    class="modal"
    style="display:none;"
>

    <div class="modal-contenido">

        <div class="modal-header">

            <h3>
                Editar configuración de mora
            </h3>

            <button
                type="button"
                class="modal-cerrar"
                id="cerrarEditarConfiguracion"
            >
                &times;
            </button>

        </div>


        <form
            method="POST"
            action="<?= BASE_URL ?>actions/editar_configuracion_mora.php"
        >

            <input
                type="hidden"
                name="id_configuracion_mora"
                id="editar_id_configuracion_mora"
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
                        Concepto de facturación *
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
                            >
                                <?= e($concepto['nombre']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div>

                    <label>
                        Tasa de interés *
                    </label>

                    <select
                        name="id_tasa_interes"
                        id="editar_id_tasa_interes"
                        required
                    >

                        <option value="">
                            Seleccione...
                        </option>

                        <?php foreach ($tasas as $tasa): ?>

                            <option
                                value="<?= (int)$tasa['id_tasa_interes'] ?>"
                            >
                                <?= e($tasa['nombre']) ?>
                                -
                                <?= porcentaje($tasa['tasa_mensual']) ?>
                                mensual
                            </option>

                        <?php endforeach; ?>

                    </select>

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


                <div>

                    <label>
                        Estado *
                    </label>

                    <select
                        name="estado"
                        id="editar_estado"
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
                    id="cancelarEditarConfiguracion"
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


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const modalNueva =
            document.getElementById(
                'modalNuevaConfiguracion'
            );

        const modalEditar =
            document.getElementById(
                'modalEditarConfiguracion'
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
            .getElementById(
                'btnNuevaConfiguracion'
            )
            ?.addEventListener(
                'click',
                function () {
                    abrirModal(modalNueva);
                }
            );


        document
            .getElementById(
                'cerrarNuevaConfiguracion'
            )
            ?.addEventListener(
                'click',
                function () {
                    cerrarModal(modalNueva);
                }
            );


        document
            .getElementById(
                'cancelarNuevaConfiguracion'
            )
            ?.addEventListener(
                'click',
                function () {
                    cerrarModal(modalNueva);
                }
            );


        document
            .getElementById(
                'cerrarEditarConfiguracion'
            )
            ?.addEventListener(
                'click',
                function () {
                    cerrarModal(modalEditar);
                }
            );


        document
            .getElementById(
                'cancelarEditarConfiguracion'
            )
            ?.addEventListener(
                'click',
                function () {
                    cerrarModal(modalEditar);
                }
            );


        document
            .querySelectorAll(
                '.btnEditarConfiguracion'
            )
            .forEach(
                function (boton) {

                    boton.addEventListener(
                        'click',
                        function () {

                            document
                                .getElementById(
                                    'editar_id_configuracion_mora'
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
                                    'editar_id_concepto'
                                )
                                .value =
                                    boton.dataset.concepto
                                    || '';

                            document
                                .getElementById(
                                    'editar_id_tasa_interes'
                                )
                                .value =
                                    boton.dataset.tasa
                                    || '';

                            document
                                .getElementById(
                                    'editar_fecha_inicio'
                                )
                                .value =
                                    boton.dataset.fechaInicio
                                    || '';

                            document
                                .getElementById(
                                    'editar_fecha_fin'
                                )
                                .value =
                                    boton.dataset.fechaFin
                                    || '';

                            document
                                .getElementById(
                                    'editar_estado'
                                )
                                .value =
                                    boton.dataset.estado
                                    || '1';

                            document
                                .getElementById(
                                    'editar_observaciones'
                                )
                                .value =
                                    boton.dataset.observaciones
                                    || '';

                            abrirModal(modalEditar);
                        }
                    );
                }
            );


        [
            modalNueva,
            modalEditar
        ].forEach(
            function (modal) {

                modal?.addEventListener(
                    'click',
                    function (event) {

                        if (event.target === modal) {
                            cerrarModal(modal);
                        }
                    }
                );
            }
        );


        document.addEventListener(
            'keydown',
            function (event) {

                if (event.key === 'Escape') {
                    cerrarModal(modalNueva);
                    cerrarModal(modalEditar);
                }
            }
        );

    }
);

</script>


</body>

</html>
