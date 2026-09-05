<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// TIPOS DE AGRUPACIÓN
// ==========================================================

$stmtTiposAgrupacion = $conexion->query("
    SELECT
        id_tipo_agrupacion,
        nombre
    FROM tipos_agrupacion
    WHERE activo = 1
    ORDER BY nombre
");

$tiposAgrupacion =
    $stmtTiposAgrupacion->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// GRUPO SELECCIONADO
// ==========================================================

$idGrupoSeleccionado = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


// ==========================================================
// AGRUPACIÓN SELECCIONADA
// ==========================================================

$idAgrupacionSeleccionada =
    isset($_GET['agrupacion'])
        ? (int)$_GET['agrupacion']
        : 0;


// ==========================================================
// AGRUPACIONES
// ==========================================================

$stmtAgrupaciones = $conexion->query("
    SELECT
        a.id_agrupacion,
        a.id_tipo_agrupacion,
        a.nombre,
        a.descripcion,
        ta.nombre AS tipo_agrupacion

    FROM agrupaciones a

    INNER JOIN tipos_agrupacion ta
        ON ta.id_tipo_agrupacion =
           a.id_tipo_agrupacion

    WHERE a.activo = 1

    ORDER BY a.nombre
");

$agrupaciones =
    $stmtAgrupaciones->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// TOMAR PRIMERA AGRUPACIÓN
// ==========================================================

if (
    $idAgrupacionSeleccionada === 0 &&
    !empty($agrupaciones)
) {

    $idAgrupacionSeleccionada =
        (int)$agrupaciones[0]['id_agrupacion'];
}


// ==========================================================
// BUSCAR AGRUPACIÓN SELECCIONADA
// ==========================================================

$agrupacionSeleccionada = null;

foreach ($agrupaciones as $agrupacion) {

    if (
        (int)$agrupacion['id_agrupacion'] ===
        $idAgrupacionSeleccionada
    ) {

        $agrupacionSeleccionada =
            $agrupacion;

        break;
    }
}


// ==========================================================
// DISTRIBUCIÓN DE UNIDADES
// ==========================================================

$stmtDistribucion = $conexion->query("
    SELECT
        id_agrupacion,
        id_tipo_config,
        cantidad

    FROM agrupacion_tipos_unidad

    WHERE activo = 1
");

$distribuciones =
    $stmtDistribucion->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// MAPA DE DISTRIBUCIÓN
// ==========================================================

$mapaDistribucion = [];

foreach ($distribuciones as $distribucion) {

    $idAgrupacion =
        (int)$distribucion["id_agrupacion"];

    $idTipoConfig =
        (int)$distribucion["id_tipo_config"];

    $mapaDistribucion
        [$idAgrupacion]
        [$idTipoConfig]
        =
        (int)$distribucion["cantidad"];
}


// ==========================================================
// CARGAR GRUPOS CONFIGURADOS
// ==========================================================

$sqlGrupos = "
    SELECT
        dt.id_tipo_config,
        dt.id_tipo_vivienda,
        dt.nombre_grupo,
        dt.cantidad_unidades,
        dt.area_total,
        dt.coeficiente_total,
        dt.observaciones,

        tv.nombre AS tipo

    FROM detalle_tipos_unidad dt

    INNER JOIN tipos_vivienda tv
        ON tv.id_tipo_vivienda =
           dt.id_tipo_vivienda

    WHERE dt.activo = 1

    ORDER BY dt.id_tipo_config
";

$stmtGrupos =
    $conexion->query($sqlGrupos);

$tiposVivienda =
    $stmtGrupos->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// TOMAR PRIMER GRUPO
// ==========================================================

if (
    $idGrupoSeleccionado === 0 &&
    !empty($tiposVivienda)
) {

    $idGrupoSeleccionado =
        (int)$tiposVivienda[0]['id_tipo_config'];
}


// ==========================================================
// BUSCAR GRUPO SELECCIONADO
// ==========================================================

$grupoSeleccionado = null;

foreach ($tiposVivienda as $tipo) {

    if (
        (int)$tipo['id_tipo_config'] ===
        $idGrupoSeleccionado
    ) {

        $grupoSeleccionado =
            $tipo;

        break;
    }
}


// ==========================================================
// RESUMEN DE UNIDADES REALES
// ==========================================================

$resumenUnidades = [

    'cantidad_creadas' => 0,
    'area_real' => 0,
    'coeficiente_real' => 0

];


if ($idGrupoSeleccionado > 0) {

    $sqlResumenUnidades = "
        SELECT
            COUNT(*) AS cantidad_creadas,

            COALESCE(
                SUM(area),
                0
            ) AS area_real,

            COALESCE(
                SUM(coeficiente),
                0
            ) AS coeficiente_real

        FROM unidades

        WHERE
            id_tipo_config = :id_tipo_config
            AND activo = 1
    ";

    $stmtResumenUnidades =
        $conexion->prepare(
            $sqlResumenUnidades
        );

    $stmtResumenUnidades->execute([

        ':id_tipo_config'
            => $idGrupoSeleccionado

    ]);

    $resultadoResumen =
        $stmtResumenUnidades->fetch(
            PDO::FETCH_ASSOC
        );

    if ($resultadoResumen) {

        $resumenUnidades = [

            'cantidad_creadas'
                => (int)$resultadoResumen['cantidad_creadas'],

            'area_real'
                => (float)$resultadoResumen['area_real'],

            'coeficiente_real'
                => (float)$resultadoResumen['coeficiente_real']

        ];
    }
}


// ==========================================================
// CONTROLES DEL GRUPO
// ==========================================================

$cantidadConfigurada =
    $grupoSeleccionado
        ? (int)$grupoSeleccionado['cantidad_unidades']
        : 0;


$cantidadCreada =
    (int)$resumenUnidades['cantidad_creadas'];


$unidadesPendientes =
    max(
        0,
        $cantidadConfigurada -
        $cantidadCreada
    );


$areaConfigurada =
    $grupoSeleccionado
        ? (float)($grupoSeleccionado['area_total'] ?? 0)
        : 0;


$areaReal =
    (float)$resumenUnidades['area_real'];


$diferenciaArea =
    $areaConfigurada -
    $areaReal;


$coeficienteConfigurado =
    $grupoSeleccionado
        ? (float)($grupoSeleccionado['coeficiente_total'] ?? 0)
        : 0;


$coeficienteReal =
    (float)$resumenUnidades['coeficiente_real'];


$diferenciaCoeficiente =
    $coeficienteConfigurado -
    $coeficienteReal;


// ==========================================================
// TIPOS DE VIVIENDA
// ==========================================================

$stmtTiposUnidad = $conexion->query("
    SELECT
        id_tipo_vivienda,
        nombre

    FROM tipos_vivienda

    WHERE activo = 1

    ORDER BY orden, nombre
");

$tiposUnidad =
    $stmtTiposUnidad->fetchAll(
        PDO::FETCH_ASSOC
    );

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
            Configuración básica
        </h2>


        <br>


        <p>

            Configura la estructura física de la copropiedad,
            los tipos de unidades, grupos, cantidades,
            áreas y coeficientes.

        </p>


        <br>


        <!-- ======================================================
             GRUPOS
        ======================================================= -->

        <h3>
            Configuración de tipos de unidades
        </h3>


        <div class="bloque filtros">

            <div class="tabs-container">


                <?php foreach ($tiposVivienda as $tipo): ?>


                    <a
                        href="basico.php?id=<?= (int)$tipo['id_tipo_config'] ?>"
                        class="tab-button <?= (
                            (int)$tipo['id_tipo_config'] ===
                            $idGrupoSeleccionado
                        )
                            ? 'active'
                            : ''
                        ?>"
                    >

                        <?= htmlspecialchars(
                            $tipo['nombre_grupo']
                        ) ?>

                    </a>


                <?php endforeach; ?>


                <button
                    type="button"
                    class="tab-button tab-add"
                    id="btnNuevoTipo"
                    title="Crear nuevo grupo"
                >

                    +

                </button>


            </div>

        </div>


        <br>


        <!-- ======================================================
             DETALLE DEL GRUPO
        ======================================================= -->

        <?php if ($grupoSeleccionado): ?>


            <div class="bloque filtros">


                <div class="form-card">


                    <div class="acciones-superior">


                        <div>

                            <h3>

                                <?= htmlspecialchars(
                                    $grupoSeleccionado['nombre_grupo']
                                ) ?>

                            </h3>

                            <p>

                                Tipo:
                                <strong>

                                    <?= htmlspecialchars(
                                        $grupoSeleccionado['tipo']
                                    ) ?>

                                </strong>

                            </p>

                        </div>


                        <button
                            type="button"
                            class="btn-filtrar"
                            id="btnEditarTipoUnidad"
                        >

                            Editar grupo

                        </button>


                    </div>


                    <br>


                    <!-- ==================================================
                         RESUMEN DE CANTIDADES
                    =================================================== -->

                    <div class="bloque filtros">


                        <div class="card">

                            <label>
                                Cantidad configurada
                            </label>

                            <input
                                type="number"
                                value="<?= $cantidadConfigurada ?>"
                                readonly
                            >

                        </div>


                        <div class="card">

                            <label>
                                Unidades creadas
                            </label>

                            <input
                                type="number"
                                value="<?= $cantidadCreada ?>"
                                readonly
                            >

                        </div>


                        <div class="card">

                            <label>
                                Pendientes por crear
                            </label>

                            <input
                                type="number"
                                value="<?= $unidadesPendientes ?>"
                                readonly
                            >


                            <?php if (
                                $cantidadCreada <
                                $cantidadConfigurada
                            ): ?>

                                <small>

                                    Faltan
                                    <?= $unidadesPendientes ?>
                                    unidades por crear.

                                </small>

                            <?php elseif (
                                $cantidadCreada ===
                                $cantidadConfigurada
                            ): ?>

                                <small>
                                    Configuración completa.
                                </small>

                            <?php else: ?>

                                <small>

                                    Existen
                                    <?= $cantidadCreada - $cantidadConfigurada ?>
                                    unidades adicionales.

                                </small>

                            <?php endif; ?>


                        </div>

                    </div>


                    <br>


                    <!-- ==================================================
                         ÁREAS
                    =================================================== -->

                    <h3>
                        Control de áreas
                    </h3>


                    <div class="bloque filtros">


                        <div class="card">

                            <label>
                                Área total configurada
                            </label>

                            <input
                                type="text"
                                value="<?= number_format(
                                    $areaConfigurada,
                                    2,
                                    ',',
                                    '.'
                                ) ?> m²"
                                readonly
                            >

                        </div>


                        <div class="card">

                            <label>
                                Área de unidades creadas
                            </label>

                            <input
                                type="text"
                                value="<?= number_format(
                                    $areaReal,
                                    2,
                                    ',',
                                    '.'
                                ) ?> m²"
                                readonly
                            >

                        </div>


                        <div class="card">

                            <label>
                                Diferencia
                            </label>

                            <input
                                type="text"
                                value="<?= number_format(
                                    $diferenciaArea,
                                    2,
                                    ',',
                                    '.'
                                ) ?> m²"
                                readonly
                            >

                        </div>

                    </div>


                    <br>


                    <!-- ==================================================
                         COEFICIENTES
                    =================================================== -->

                    <h3>
                        Control de coeficientes
                    </h3>


                    <div class="bloque filtros">


                        <div class="card">

                            <label>
                                Coeficiente total configurado
                            </label>

                            <input
                                type="text"
                                value="<?= number_format(
                                    $coeficienteConfigurado,
                                    8,
                                    '.',
                                    ''
                                ) ?>"
                                readonly
                            >

                        </div>


                        <div class="card">

                            <label>
                                Coeficiente de unidades creadas
                            </label>

                            <input
                                type="text"
                                value="<?= number_format(
                                    $coeficienteReal,
                                    8,
                                    '.',
                                    ''
                                ) ?>"
                                readonly
                            >

                        </div>


                        <div class="card">

                            <label>
                                Diferencia
                            </label>

                            <input
                                type="text"
                                value="<?= number_format(
                                    $diferenciaCoeficiente,
                                    8,
                                    '.',
                                    ''
                                ) ?>"
                                readonly
                            >

                        </div>

                    </div>


                    <br>


                    <!-- ==================================================
                         OBSERVACIONES
                    =================================================== -->

                    <div class="form-group textarea">

                        <label>
                            Observaciones
                        </label>

                        <textarea
                            rows="3"
                            readonly
                        ><?= htmlspecialchars(
                            $grupoSeleccionado['observaciones']
                            ?? ''
                        ) ?></textarea>

                    </div>


                    <br>


                    <!-- ==================================================
                         ACCIONES
                    =================================================== -->

                    <div class="form-actions">


                        <button
                            type="button"
                            class="btn-secondary"
                            onclick="window.location.href='<?= BASE_URL ?>configuracion/unidades.php?id=<?= (int)$idGrupoSeleccionado ?>'"
                        >

                            Ver unidades

                        </button>


                    </div>


                </div>

            </div>


        <?php else: ?>


            <div class="bloque filtros">

                <p>

                    No existen grupos de unidades configurados.

                </p>

            </div>


        <?php endif; ?>


    </main>

</div>


<!-- ==========================================================
     MODAL EDITAR GRUPO
========================================================== -->

<?php if ($grupoSeleccionado): ?>

<div
    id="modalEditarTipoUnidad"
    class="modal"
>

    <div class="modal-contenido">


        <div class="modal-header">


            <h3>
                Editar grupo de unidades
            </h3>


            <button
                type="button"
                id="cerrarEditarTipoUnidad"
                class="modal-cerrar"
            >

                &times;

            </button>


        </div>


        <form
            id="formEditarTipoUnidad"
            action="<?= BASE_URL ?>actions/guardar_tipo_unidad.php"
            method="POST"
        >


            <!-- ID -->

            <input
                type="hidden"
                name="id_tipo_config"
                value="<?= (int)$grupoSeleccionado['id_tipo_config'] ?>"
            >


            <!-- ==================================================
                 TIPO DE UNIDAD
            =================================================== -->

            <div class="form-group">

                <label for="editar_id_tipo_vivienda">

                    Tipo de unidad *

                </label>


                <select
                    name="id_tipo_vivienda"
                    id="editar_id_tipo_vivienda"
                    required
                >

                    <option value="">
                        Seleccione...
                    </option>


                    <?php foreach ($tiposUnidad as $tipoUnidad): ?>

                        <option
                            value="<?= (int)$tipoUnidad['id_tipo_vivienda'] ?>"
                            <?= (
                                (int)$grupoSeleccionado['id_tipo_vivienda'] ===
                                (int)$tipoUnidad['id_tipo_vivienda']
                            )
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= htmlspecialchars(
                                $tipoUnidad['nombre']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- ==================================================
                 NOMBRE
            =================================================== -->

            <div class="form-group">

                <label for="editar_nombre_grupo">

                    Nombre del grupo *

                </label>


                <input
                    type="text"
                    name="nombre_grupo"
                    id="editar_nombre_grupo"
                    maxlength="100"
                    value="<?= htmlspecialchars(
                        $grupoSeleccionado['nombre_grupo']
                    ) ?>"
                    required
                >

            </div>


            <!-- ==================================================
                 CANTIDAD
            =================================================== -->

            <div class="form-group">

                <label for="editar_cantidad_unidades">

                    Cantidad configurada *

                </label>


                <input
                    type="number"
                    name="cantidad_unidades"
                    id="editar_cantidad_unidades"
                    min="1"
                    value="<?= $cantidadConfigurada ?>"
                    required
                >


                <small>

                    Actualmente existen
                    <?= $cantidadCreada ?>
                    unidades activas creadas.

                </small>

            </div>


            <!-- ==================================================
                 ÁREA TOTAL
            =================================================== -->

            <div class="form-group">

                <label for="editar_area_total">

                    Área total esperada (m²)

                </label>


                <input
                    type="number"
                    name="area_total"
                    id="editar_area_total"
                    step="0.01"
                    min="0"
                    value="<?= htmlspecialchars(
                        $grupoSeleccionado['area_total']
                        ?? ''
                    ) ?>"
                >


                <small>

                    Suma esperada del área
                    de todas las unidades del grupo.

                </small>

            </div>


            <!-- ==================================================
                 COEFICIENTE
            =================================================== -->

            <div class="form-group">

                <label for="editar_coeficiente_total">

                    Coeficiente total esperado

                </label>


                <input
                    type="number"
                    name="coeficiente_total"
                    id="editar_coeficiente_total"
                    step="0.00000001"
                    min="0"
                    value="<?= htmlspecialchars(
                        $grupoSeleccionado['coeficiente_total']
                        ?? ''
                    ) ?>"
                >


                <small>

                    Suma esperada de los coeficientes
                    individuales del grupo.

                </small>

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
                    maxlength="255"
                ><?= htmlspecialchars(
                    $grupoSeleccionado['observaciones']
                    ?? ''
                ) ?></textarea>

            </div>


            <!-- ==================================================
                 BOTONES
            =================================================== -->

            <div class="form-actions">


                <button
                    type="button"
                    class="btn-limpiar"
                    id="cancelarEditarTipoUnidad"
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

<?php endif; ?>


<!-- ==========================================================
     MODAL NUEVO GRUPO
========================================================== -->

<div
    id="modalGrupo"
    class="modal"
>

    <div class="modal-contenido">


        <div class="modal-header">


            <h3>
                Nuevo grupo de unidades
            </h3>


            <button
                type="button"
                id="cerrarGrupo"
                class="modal-cerrar"
            >

                &times;

            </button>


        </div>


        <form
            action="<?= BASE_URL ?>actions/guardar_grupo.php"
            method="POST"
        >


            <!-- TIPO -->

            <div class="form-group">

                <label>
                    Tipo de unidad *
                </label>


                <select
                    name="id_tipo_vivienda"
                    required
                >

                    <option value="">
                        Seleccione...
                    </option>


                    <?php foreach ($tiposUnidad as $tipoUnidad): ?>

                        <option
                            value="<?= (int)$tipoUnidad['id_tipo_vivienda'] ?>"
                        >

                            <?= htmlspecialchars(
                                $tipoUnidad['nombre']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- NOMBRE -->

            <div class="form-group">

                <label>
                    Nombre del grupo *
                </label>


                <input
                    type="text"
                    name="nombre_grupo"
                    maxlength="100"
                    placeholder="Ej. Torre A"
                    required
                >

            </div>


            <!-- CANTIDAD -->

            <div class="form-group">

                <label>
                    Cantidad esperada de unidades *
                </label>


                <input
                    type="number"
                    name="cantidad_unidades"
                    min="1"
                    required
                >

            </div>


            <!-- ÁREA -->

            <div class="form-group">

                <label>
                    Área total esperada (m²)
                </label>


                <input
                    type="number"
                    name="area_total"
                    step="0.01"
                    min="0"
                >

            </div>


            <!-- COEFICIENTE -->

            <div class="form-group">

                <label>
                    Coeficiente total esperado
                </label>


                <input
                    type="number"
                    name="coeficiente_total"
                    step="0.00000001"
                    min="0"
                >

            </div>


            <!-- OBSERVACIONES -->

            <div class="form-group">

                <label>
                    Observaciones
                </label>


                <textarea
                    name="observaciones"
                    rows="3"
                    maxlength="255"
                ></textarea>

            </div>


            <!-- BOTONES -->

            <div class="form-actions">


                <button
                    type="reset"
                    class="btn-limpiar"
                    id="cancelarGrupo"
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


<!-- ==========================================================
     MODAL NUEVA AGRUPACIÓN
========================================================== -->

<div
    id="modalNuevaAgrupacion"
    class="modal"
>

    <div class="modal-contenido">


        <div class="modal-header">


            <h3>
                Nueva agrupación
            </h3>


            <button
                type="button"
                class="modal-cerrar"
                id="cerrarModalAgrupacion"
            >

                &times;

            </button>


        </div>


        <form
            action="<?= BASE_URL ?>actions/guardar_agrupacion.php"
            method="POST"
        >


            <div class="form-group">

                <label>
                    Tipo de agrupación
                </label>


                <select
                    name="id_tipo_agrupacion"
                    class="form-control"
                    required
                >

                    <option value="">
                        Seleccione un tipo
                    </option>


                    <?php foreach ($tiposAgrupacion as $tipo): ?>

                        <option
                            value="<?= (int)$tipo['id_tipo_agrupacion'] ?>"
                        >

                            <?= htmlspecialchars(
                                $tipo['nombre']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Nombre
                </label>


                <input
                    type="text"
                    name="nombre"
                    maxlength="100"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Descripción
                </label>


                <textarea
                    name="descripcion"
                    rows="2"
                    maxlength="200"
                ></textarea>

            </div>


            <div class="form-actions">


                <button
                    type="button"
                    class="btn-limpiar"
                    id="cancelarModalAgrupacion"
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


<!-- ==========================================================
     MODAL EDITAR AGRUPACIÓN
========================================================== -->

<div
    id="modalEditarAgrupacion"
    class="modal"
>

    <div class="modal-contenido">


        <div class="modal-header">


            <h3>
                Editar agrupación
            </h3>


            <button
                type="button"
                class="modal-cerrar"
                id="cerrarModalEditarAgrupacion"
            >

                &times;

            </button>


        </div>


        <form
            action="<?= BASE_URL ?>actions/editar_agrupacion.php"
            method="POST"
        >


            <input
                type="hidden"
                name="id_agrupacion"
                id="editar_id_agrupacion"
            >


            <div class="form-group">

                <label>
                    Tipo de agrupación
                </label>


                <select
                    name="id_tipo_agrupacion"
                    id="editar_id_tipo_agrupacion"
                    class="form-control"
                    required
                >

                    <option value="">
                        Seleccione un tipo
                    </option>


                    <?php foreach ($tiposAgrupacion as $tipo): ?>

                        <option
                            value="<?= (int)$tipo['id_tipo_agrupacion'] ?>"
                        >

                            <?= htmlspecialchars(
                                $tipo['nombre']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Nombre
                </label>


                <input
                    type="text"
                    name="nombre"
                    id="editar_nombre_agrupacion"
                    maxlength="100"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Descripción
                </label>


                <textarea
                    name="descripcion"
                    id="editar_descripcion_agrupacion"
                    rows="2"
                    maxlength="200"
                ></textarea>

            </div>


            <div class="form-actions">


                <button
                    type="button"
                    class="btn-limpiar"
                    id="cancelarModalEditarAgrupacion"
                >

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="btn-filtrar"
                >

                    Actualizar

                </button>


            </div>


        </form>

    </div>

</div>


<!-- ==========================================================
     JAVASCRIPT
========================================================== -->

<script>

const BASE_URL = "<?= BASE_URL ?>";

</script>


<!-- ==========================================================
     MODAL EDITAR GRUPO
========================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const btnEditar =
            document.getElementById(
                "btnEditarTipoUnidad"
            );

        const modal =
            document.getElementById(
                "modalEditarTipoUnidad"
            );

        const btnCerrar =
            document.getElementById(
                "cerrarEditarTipoUnidad"
            );

        const btnCancelar =
            document.getElementById(
                "cancelarEditarTipoUnidad"
            );


        // ==================================================
        // ABRIR
        // ==================================================

        if (
            btnEditar &&
            modal
        ) {

            btnEditar.addEventListener(
                "click",
                function () {

                    modal.style.display =
                        "flex";

                }
            );

        }


        // ==================================================
        // CERRAR
        // ==================================================

        function cerrarModal()
        {

            if (modal) {

                modal.style.display =
                    "none";

            }

        }


        if (btnCerrar) {

            btnCerrar.addEventListener(
                "click",
                cerrarModal
            );

        }


        if (btnCancelar) {

            btnCancelar.addEventListener(
                "click",
                cerrarModal
            );

        }


        // ==================================================
        // CLIC FUERA DEL MODAL
        // ==================================================

        if (modal) {

            modal.addEventListener(
                "click",
                function (event) {

                    if (
                        event.target === modal
                    ) {

                        cerrarModal();

                    }

                }
            );

        }

    }
);

</script>


<script src="<?= BASE_URL ?>assets/js/modal_agrupacion.js"></script>

<script src="<?= BASE_URL ?>assets/js/editar_agrupacion.js"></script>

<script src="<?= BASE_URL ?>assets/js/distribucion_unidades.js"></script>


</body>

</html>