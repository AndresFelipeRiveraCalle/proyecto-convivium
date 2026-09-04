<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// CONSULTAR TASAS DE INTERÉS
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
        fecha_creacion
    FROM tasas_interes
    ORDER BY fecha_inicio DESC, id_tasa_interes DESC
";

$stmt = $conexion->prepare($sql);
$stmt->execute();

$tasas = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// FORMATO
// ==========================================================

function formatoTasa($valor)
{
    return number_format(
        (float)$valor,
        6,
        ',',
        '.'
    );
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <?php include ROOT_PATH . "/includes/head.php"; ?>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>assets/css/tasas_interes.css"
    >

</head>


<body>

<?php include ROOT_PATH . "/includes/header.php"; ?>

<?php require_once ROOT_PATH . "/includes/mensajes.php"; ?>


<div class="contenedor">

    <?php include ROOT_PATH . "/includes/sidebar.php"; ?>


    <main class="contenido">

        <div class="tasas-container">


            <!-- ==================================================
                 ENCABEZADO
            =================================================== -->

            <div class="tasas-header">

                <div>

                    <h1>
                        Tasas de interés
                    </h1>

                    <p>
                        Administración de las tasas utilizadas
                        para el cálculo de intereses de mora.
                    </p>

                </div>


                <div>

                    <button
                        type="button"
                        class="btn-primary"
                        id="btnNuevaTasa"
                    >
                        + Nueva tasa
                    </button>

                </div>

            </div>


            <!-- ==================================================
                 INFORMACIÓN
            =================================================== -->

            <div class="tasas-info">

                <strong>
                    Información importante:
                </strong>

                La tasa de interés moratorio debe configurarse de acuerdo
                con la normatividad vigente, el reglamento de propiedad
                horizontal y las decisiones de la asamblea.

                Las tasas utilizadas para generar intereses quedan
                registradas históricamente y no deben eliminarse.

            </div>
            <div class="tasas-info-secundaria">

                La aplicación permite registrar tasas legales, tasas
                aprobadas por asamblea o tasas ingresadas manualmente.
                Verifique siempre su porcentaje, vigencia y método de
                cálculo antes de generar intereses.

            </div>

            <!-- ==================================================
                 TABLA
            =================================================== -->

            <div class="tabla-card">

                <div class="tabla-header">

                    <h2>
                        Historial de tasas
                    </h2>

                </div>


                <?php if (empty($tasas)): ?>

                    <div class="sin-datos">

                        No existen tasas de interés
                        configuradas.

                    </div>

                <?php else: ?>


                    <div class="tabla-responsive">

                        <table class="tabla-tasas">

                            <thead>

                                <tr>

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
                                        Vigencia
                                    </th>

                                    <th>
                                        Fuente
                                    </th>

                                    <th>
                                        Estado
                                    </th>

                                    <th>
                                        Acción
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach ($tasas as $tasa): ?>

                                    <tr>

                                        <!-- NOMBRE -->

                                        <td>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $tasa['nombre']
                                                ) ?>

                                            </strong>


                                            <?php if (
                                                !empty(
                                                    $tasa['observaciones']
                                                )
                                            ): ?>

                                                <small class="texto-secundario">

                                                    <?= htmlspecialchars(
                                                        $tasa['observaciones']
                                                    ) ?>

                                                </small>

                                            <?php endif; ?>

                                        </td>

                                        <div class="campo">

                                            <label for="tipo_tasa">
                                                Tipo de tasa
                                            </label>

                                            <select
                                                name="tipo_tasa"
                                                id="tipo_tasa"
                                                required
                                            >

                                                <option value="LEGAL">
                                                    Legal / referencia normativa
                                                </option>

                                                <option value="ASAMBLEA">
                                                    Definida por asamblea
                                                </option>

                                                <option value="MANUAL">
                                                    Manual
                                                </option>

                                            </select>

                                        </div>

                                        <!-- TASA ANUAL -->

                                        <td>

                                            <?= formatoTasa(
                                                $tasa['tasa_anual']
                                            ) ?>

                                            %

                                        </td>


                                        <!-- TASA MENSUAL -->

                                        <td>

                                            <?= formatoTasa(
                                                $tasa['tasa_mensual']
                                            ) ?>

                                            %

                                        </td>


                                        <!-- VIGENCIA -->

                                        <td>

                                            <div class="vigencia">

                                                <span>

                                                    <?= date(
                                                        'd/m/Y',
                                                        strtotime(
                                                            $tasa['fecha_inicio']
                                                        )
                                                    ) ?>

                                                </span>


                                                <span class="separador">
                                                    →
                                                </span>


                                                <span>

                                                    <?php if (
                                                        !empty(
                                                            $tasa['fecha_fin']
                                                        )
                                                    ): ?>

                                                        <?= date(
                                                            'd/m/Y',
                                                            strtotime(
                                                                $tasa['fecha_fin']
                                                            )
                                                        ) ?>

                                                    <?php else: ?>

                                                        Vigente

                                                    <?php endif; ?>

                                                </span>

                                            </div>

                                        </td>


                                        <!-- FUENTE -->

                                        <td>

                                            <?= !empty(
                                                $tasa['fuente']
                                            )
                                                ? htmlspecialchars(
                                                    $tasa['fuente']
                                                )
                                                : '—'
                                            ?>

                                        </td>


                                        <!-- ESTADO -->

                                        <td>

                                            <?php if (
                                                (int)$tasa['activo'] === 1
                                            ): ?>

                                                <span
                                                    class="estado activo"
                                                >
                                                    Activa
                                                </span>

                                            <?php else: ?>

                                                <span
                                                    class="estado inactivo"
                                                >
                                                    Inactiva
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- ACCIÓN -->

                                        <td>

                                            <button
                                                type="button"
                                                class="btn-secondary btnEditarTasa"
                                                data-id="<?= (int)$tasa['id_tasa_interes'] ?>"
                                            >
                                                ✏ Editar
                                            </button>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>


                <?php endif; ?>


            </div>


        </div>

    </main>

</div>


<!-- ==========================================================
     MODAL
=========================================================== -->

<div
    id="modalTasaInteres"
    class="modal"
    style="display:none;"
>

    <div class="modal-contenido">


        <div class="modal-header">

            <h2 id="tituloModalTasa">
                Nueva tasa de interés
            </h2>

            <button
                type="button"
                class="modal-cerrar"
                id="cerrarModalTasa"
            >
                ×
            </button>

        </div>


        <form
            id="formTasaInteres"
            method="POST"
            action="<?= BASE_URL ?>actions/guardar_tasa_interes.php"
        >

            <input
                type="hidden"
                name="id_tasa_interes"
                id="id_tasa_interes"
                value=""
            >


            <!-- NOMBRE -->

            <div class="campo">

                <label for="nombre">
                    Nombre
                </label>

                <input
                    type="text"
                    name="nombre"
                    id="nombre"
                    maxlength="150"
                    required
                >

            </div>


            <!-- TASAS -->

            <div class="campos-dos">


                <div class="campo">

                    <label for="tasa_anual">
                        Tasa anual (%)
                    </label>

                    <input
                        type="number"
                        name="tasa_anual"
                        id="tasa_anual"
                        step="0.000001"
                        min="0"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="tasa_mensual">
                        Tasa mensual equivalente (%)
                    </label>

                    <input
                        type="number"
                        name="tasa_mensual"
                        id="tasa_mensual"
                        step="0.000001"
                        min="0"
                        readonly
                    >

                    <small>
                        Calculada automáticamente a partir de la tasa anual.
                    </small>

                </div>


            </div>

            <div class="campos-dos">

                <div class="campo">

                    <label for="periodicidad">
                        Periodicidad
                    </label>

                    <select
                        name="periodicidad"
                        id="periodicidad"
                        required
                    >

                        <option value="MENSUAL">
                            Mensual
                        </option>

                    </select>

                </div>


                <div class="campo">

                    <label for="metodo_calculo">
                        Método de cálculo
                    </label>

                    <select
                        name="metodo_calculo"
                        id="metodo_calculo"
                        required
                    >

                        <option value="MES_VENCIDO">
                            Mes vencido
                        </option>

                        <option value="DIARIO">
                            Diario
                        </option>

                        <option value="OTRO">
                            Otro
                        </option>

                    </select>

                </div>

            </div>


            <!-- FECHAS -->

            <div class="campos-dos">


                <div class="campo">

                    <label for="fecha_inicio">
                        Fecha de inicio
                    </label>

                    <input
                        type="date"
                        name="fecha_inicio"
                        id="fecha_inicio"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="fecha_fin">
                        Fecha de finalización
                    </label>

                    <input
                        type="date"
                        name="fecha_fin"
                        id="fecha_fin"
                    >

                    <small>
                        Dejar vacío si la tasa continúa vigente.
                    </small>

                </div>


            </div>


            <!-- FUENTE -->

            <div class="campo">

                <label for="fuente">
                    Fuente
                </label>

                <input
                    type="text"
                    name="fuente"
                    id="fuente"
                    maxlength="255"
                    placeholder="Ej. Superintendencia Financiera"
                >

            </div>


            <!-- OBSERVACIONES -->

            <div class="campo">

                <label for="observaciones">
                    Observaciones
                </label>

                <textarea
                    name="observaciones"
                    id="observaciones"
                    rows="3"
                    maxlength="255"
                ></textarea>

            </div>


            <!-- ESTADO -->

            <div class="campo campo-checkbox">

                <label>

                    <input
                        type="checkbox"
                        name="activo"
                        id="activo"
                        value="1"
                        checked
                    >

                    Tasa activa

                </label>

            </div>


            <!-- BOTONES -->

            <div class="modal-botones">

                <button
                    type="button"
                    class="btn-secondary"
                    id="cancelarTasa"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="btn-primary"
                >
                    Guardar
                </button>

            </div>


        </form>

    </div>

</div>


<script src="<?= BASE_URL ?>assets/js/tasas_interes.js"></script>

</body>

</html>
