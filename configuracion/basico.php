<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

// ==========================================================
// TIPOS DE AGRUPACIÓN
// ==========================================================

$stmtTiposAgrupacion = $conexion->query(" SELECT id_tipo_agrupacion, nombre FROM tipos_agrupacion
    WHERE activo = 1 ORDER BY nombre");

$tiposAgrupacion = $stmtTiposAgrupacion->fetchAll(PDO::FETCH_ASSOC);


// ===========================================
// GRUPO SELECCIONADO
// ===========================================

$idGrupoSeleccionado = isset($_GET['id'])
    ? intval($_GET['id'])
    : 0;

$idAgrupacionSeleccionada = isset($_GET['agrupacion'])
? intval($_GET['agrupacion'])
: 0;
// ==========================================================
// AGRUPACIONES
// ==========================================================

$stmtAgrupaciones = $conexion->query(" SELECT  a.id_agrupacion,a.id_tipo_agrupacion,
        a.nombre,a.descripcion, ta.nombre AS tipo_agrupacion
    FROM agrupaciones a
    INNER JOIN tipos_agrupacion ta  ON ta.id_tipo_agrupacion = a.id_tipo_agrupacion
    WHERE a.activo = 1 ORDER BY a.nombre
");

$agrupaciones = $stmtAgrupaciones->fetchAll(PDO::FETCH_ASSOC);

if ($idAgrupacionSeleccionada == 0 && !empty($agrupaciones)) {
    $idAgrupacionSeleccionada = $agrupaciones[0]['id_agrupacion'];
}

$agrupacionSeleccionada = null;

foreach ($agrupaciones as $agrupacion) {
    if ($agrupacion['id_agrupacion'] == $idAgrupacionSeleccionada) {
        $agrupacionSeleccionada = $agrupacion;
        break;
    }
}
// ==========================================================
// DISTRIBUCIÓN DE UNIDADES
// ==========================================================

$stmtDistribucion = $conexion->query(" SELECT id_agrupacion, id_tipo_config, cantidad
    FROM agrupacion_tipos_unidad
    WHERE activo = 1
");

$distribuciones = $stmtDistribucion->fetchAll(PDO::FETCH_ASSOC);



$tiposDeAgrupacion = [];

if ($idAgrupacionSeleccionada > 0) {

    $stmtTiposAgrupacion = $conexion->prepare("SELECT at.id_agrupacion_tipo, at.id_tipo_config, at.cantidad,
            dt.nombre_grupo, dt.cantidad_unidades,dt.area_total,dt.coeficiente_total, tv.nombre AS tipo_vivienda
        FROM agrupacion_tipos_unidad at
        INNER JOIN detalle_tipos_unidad dt ON dt.id_tipo_config = at.id_tipo_config
        INNER JOIN tipos_vivienda tv ON tv.id_tipo_vivienda = dt.id_tipo_vivienda
        WHERE at.id_agrupacion = :id_agrupacion AND at.activo = 1
        ORDER BY dt.nombre_grupo
    ");

    $stmtTiposAgrupacion->execute([
        ':id_agrupacion' => $idAgrupacionSeleccionada
    ]);

    $tiposDeAgrupacion = $stmtTiposAgrupacion->fetchAll(PDO::FETCH_ASSOC);
}

// ==========================================================
// AGRUPACIONES DE DISTRIBUCIONES
// ==========================================================

$stmtDistribucion = $conexion->query(" SELECT atu.id_agrupacion_tipo, atu.id_agrupacion, atu.id_tipo_config, atu.cantidad
    FROM agrupacion_tipos_unidad atu
    WHERE atu.activo = 1");

$distribuciones = $stmtDistribucion->fetchAll(PDO::FETCH_ASSOC);


// ===========================================
// CARGAR GRUPOS CONFIGURADOS
// ===========================================

$sql = "SELECT dt.id_tipo_config, dt.id_tipo_vivienda, dt.nombre_grupo, tv.nombre AS tipo,
    dt.cantidad_unidades, dt.area_total, dt.coeficiente_total, dt.observaciones
FROM detalle_tipos_unidad dt
INNER JOIN tipos_vivienda tv ON tv.id_tipo_vivienda = dt.id_tipo_vivienda
WHERE dt.activo = 1
ORDER BY dt.id_tipo_config";

$stmt = $conexion->query($sql);

$tiposVivienda = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ===========================================
// SI NO HAY GRUPO SELECCIONADO, TOMAR EL PRIMERO
// ===========================================

if ($idGrupoSeleccionado == 0 && !empty($tiposVivienda)) {
    $idGrupoSeleccionado = $tiposVivienda[0]['id_tipo_config'];
}

// ===========================================
// BUSCAR EL GRUPO SELECCIONADO
// ===========================================

$grupoSeleccionado = null;

foreach ($tiposVivienda as $tipo) {
    if ($tipo['id_tipo_config'] == $idGrupoSeleccionado) {
        $grupoSeleccionado = $tipo;
        break;
    }
}

// ===========================================
// SI NO HAY GRUPO SELECCIONADO, TOMAR EL PRIMERO
// ===========================================

$mapaDistribucion = [];

foreach ($distribuciones as $distribucion) {

    $idAgrupacion = $distribucion["id_agrupacion"];
    $idTipoConfig = $distribucion["id_tipo_config"];

    $mapaDistribucion[$idAgrupacion][$idTipoConfig] =
        $distribucion["cantidad"];
}




$usos = $conexion->query("SELECT id_uso as id, nombre FROM usos_vivienda ORDER BY nombre");
$usosVivienda = $usos->fetchAll(PDO::FETCH_ASSOC);


$stmtTipos = $conexion->query("SELECT * FROM detalle_tipos_unidad WHERE activo = 1 ORDER BY id_tipo_config");
$tiposUnidad = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

$bloqueadoTipoUnidad = true;

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



            <h2 align="center">Configuracion básica</h2>
            <br>
            <p>En esta seccion podras configurar las áreas de la copropiedad como cantidad de apartamentos, zonas comunes y distrinbuciones generales</p>
            <br>


            <h3>Configuración de tipos de unidades</h3>
            <div class="bloque filtros">
                <div class="tabs-container">
                    <?php foreach ($tiposVivienda as $tipo): ?>
                        <a
                            href="basico.php?id=<?= $tipo['id_tipo_config'] ?>"
                            class="tab-button <?= ($tipo['id_tipo_config'] == $idGrupoSeleccionado) ? 'active' : '' ?>">
                            <?= htmlspecialchars($tipo['nombre_grupo']) ?>
                        </a>
                    <?php endforeach; ?>

                    <button
                        type="button" class="tab-button tab-add" id="btnNuevoTipo">
                        +
                    </button>
                </div>
            </div>  


            <div class="bloque filtros">
                           
                <div id="contenidoTipo" class="tab-content">

                    <?php if ($grupoSeleccionado): ?>

                        <h3><?= htmlspecialchars($grupoSeleccionado['nombre_grupo']) ?></h3>
                        <form id="formTipoUnidad" action="../actions/guardar_tipo_unidad.php" method="POST">
                            <input type="hidden" name="id_tipo_config" value="<?= $grupoSeleccionado['id_tipo_config'] ?>">

                            <div class="bloque filtros">
                        
                                <div class="card">
                                    <label>Cantidad de unidades</label>

                                    <input
                                        type="number"
                                        name="cantidad_unidades"
                                        min="1"
                                        value="<?= htmlspecialchars($grupoSeleccionado['cantidad_unidades'] ?? '') ?>"
                                        readonly>
                                </div>

                                <div class="card">
                                    <label>Área total (m²)</label>

                                    <input type="number" step="0.01" name="area_total"
                                        value="<?= htmlspecialchars($grupoSeleccionado['area_total'] ?? '') ?>"
                                        <?= $bloqueadoTipoUnidad ? 'readonly' : '' ?>>
                                </div>

                                <div class="card">
                                    <label>Coeficiente total</label>

                                    <input type="number" step="0.00001"
                                        name="coeficiente_total"
                                        value="<?= htmlspecialchars($grupoSeleccionado['coeficiente_total'] ?? '') ?>"
                                        <?= $bloqueadoTipoUnidad ? 'readonly' : '' ?>>
                                </div>
                            </div>

                            <div class="form-group textarea">
                                <label>Observaciones</label>

                                <textarea
                                    name="observaciones"
                                    rows="2"
                                    <?= $bloqueadoTipoUnidad ? 'readonly' : '' ?>>
                                    <?= htmlspecialchars($grupoSeleccionado['observaciones'] ?? '') ?></textarea>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="form-actions">

                        <button
                            type="button"
                            id="btnEditarTipoUnidad"
                            class="btn-filtrar">

                            Editar
                        </button>


                        <button
                            type="submit"
                            form="formTipoUnidad"
                            id="btnGuardarTipoUnidad"
                            class="btn-filtrar"
                            style="display: none;">

                            Guardar
                        </button>


                        <button
                            type="button"
                            id="btnCancelarTipoUnidad"
                            class="btn-limpiar"
                            style="display: none;">

                            Cancelar
                        </button>

                    </div>
                </div>
                
            </div>
            <div class="form-actions">
                <button
                    type="button"
                    class="btn-filtrar btn-derecha"
                    onclick="window.location.href='../configuracion/unidades.php'">
                    Configuración de unidades
                </button>
            </div>
        </main>
        
    </div>

    <!-- ==========================================================
        MODAL NUEVA AGRUPACIÓN
        ========================================================== -->
    <div id="modalNuevaAgrupacion" class="modal">
        <div class="modal-contenido">
            <!-- ENCABEZADO -->
            <div class="modal-header">
                <h3>Nueva agrupación</h3>
                <button type="button" class="modal-cerrar" id="cerrarModalAgrupacion">
                    &times;
                </button>
            </div>

            <!-- FORMULARIO -->
            <form action="<?= BASE_URL ?>actions/guardar_agrupacion.php" method="POST">

                <!-- TIPO DE AGRUPACIÓN -->
                <div class="form-group">

                    <label for="id_tipo_agrupacion">
                        Tipo de agrupación
                    </label>

                    <select
                        name="id_tipo_agrupacion" id="editar_id_tipo_agrupacion" class="form-control" required>
                        <option value="">
                            Seleccione un tipo
                        </option>
                        <?php foreach ($tiposAgrupacion as $tipo): ?>

                            <option value="<?= $tipo['id_tipo_agrupacion'] ?>">
                                <?= htmlspecialchars($tipo['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- NOMBRE -->
                <div class="form-group">
                    <label for="nombre_agrupacion">
                        Nombre
                    </label>

                    <input type="text" name="nombre" id="nombre_agrupacion" class="form-control" placeholder="Ej. Torre 1"
                        maxlength="100" required>
                </div>

                <!-- DESCRIPCIÓN -->
                <div class="form-group">
                    <label for="descripcion_agrupacion">
                        Descripción
                    </label>

                    <textarea name="descripcion" id="descripcion_agrupacion" class="form-control" rows="2"
                        maxlength="200" placeholder="Descripción de la agrupación"></textarea>
                </div>

                <!-- BOTONES -->
                <div class="form-actions">
                    <button
                        type="button" class="btn-limpiar" id="cancelarModalAgrupacion">
                        Cancelar
                    </button>

                    <button type="submit" class="btn-filtrar">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    
        <!-- ==========================================================
            MODAL EDITAR AGRUPACIÓN
            ========================================================== -->

        <div id="modalEditarAgrupacion" class="modal">
            <div class="modal-contenido">
                <div class="modal-header">
                    <h3>Editar agrupación</h3>
                    <button type="button" class="modal-cerrar" id="cerrarModalEditarAgrupacion">
                        &times;
                    </button>
                </div>

                <form action="<?= BASE_URL ?>actions/editar_agrupacion.php" method="POST">
                    
                <!-- ID DE LA AGRUPACIÓN -->
                    <input type="hidden" name="id_agrupacion" id="editar_id_agrupacion">

                    <!-- TIPO DE AGRUPACIÓN -->
                    <div class="form-group">
                        <label for="editar_id_tipo_agrupacion">
                            Tipo de agrupación
                        </label>
                        <select name="id_tipo_agrupacion" id="editar_id_tipo_agrupacion" class="form-control" required>
                            <option value="">
                                Seleccione un tipo
                            </option>

                            <?php foreach ($tiposAgrupacion as $tipo): ?>
                                <option
                                    value="<?= $tipo['id_tipo_agrupacion'] ?>">
                                    <?= htmlspecialchars($tipo['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- NOMBRE -->
                    <div class="form-group">
                        <label for="editar_nombre_agrupacion">
                            Nombre
                        </label>

                        <input type="text" name="nombre" id="editar_nombre_agrupacion" class="form-control"
                            maxlength="100" required>
                    </div>

                    <!-- DESCRIPCIÓN -->
                    <div class="form-group">
                        <label for="editar_descripcion_agrupacion">
                            Descripción
                        </label>

                        <textarea name="descripcion" id="editar_descripcion_agrupacion" class="form-control"
                            rows="2"  maxlength="150"></textarea>
                    </div>

                    <!-- BOTONES -->
                    <div class="form-actions">

                        <button type="button" class="btn-limpiar" id="cancelarModalEditarAgrupacion">
                            Cancelar
                        </button>

                        <button type="submit" class="btn-filtrar">
                            Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>


    <!-- ==========================================
     MODAL NUEVO GRUPO
    ========================================== -->

    <div id="modalGrupo" class="modal">
        <div class="modal-contenido">
            <div class="modal-header">
                <h3>Editar agrupación</h3>
                <button type="button"id="cerrarGrupo" class="modal-cerrar">
                    &times;
                </button>
            </div>


            <h2>Nuevo grupo de unidades</h2>
            <br>
            <form action="../actions/guardar_grupo.php" method="POST">
                <label>Tipo de unidad</label>
                <select
                    name="id_tipo_vivienda" required>
                    <option value="">
                        Seleccione...
                    </option>   

                    <?php
                    $stmtTipos = $conexion->query("SELECT id_tipo_vivienda, nombre FROM tipos_vivienda WHERE activo = 1 ORDER BY orden ");
                    while ($fila = $stmtTipos->fetch(PDO::FETCH_ASSOC)):
                    ?>
                        <option value="<?= $fila['id_tipo_vivienda'] ?>">
                            <?= htmlspecialchars($fila['nombre']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <br><br>

                <label>Nombre del grupo</label>
                <input
                    type="text" name="nombre_grupo" maxlength="100" required>
                <br><br>

                <label>Cantidad de unidades</label>
                <input
                    type="number" name="cantidad_unidades" min="1" required>
                <br><br>

                <label>Área total (m²)</label>
                <input
                    type="number" step="0.01" name="area_total">
                <br><br>

                <label>Coeficiente total</label>
                <input
                    type="number" step="0.00001" name="coeficiente_total">
                <br><br>

                <label>Observaciones</label>
                <textarea
                    name="observaciones" rows="2"></textarea>
                <br><br>

                <button
                    type="reset" class="btn-limpiar" id="cancelarGrupo">
                    Cancelar
                </button>

                <button
                    type="submit" class="btn-filtrar">
                    Guardar
                </button>
            </form>
        </div>

    </div>


    </div>
<script src="<?= BASE_URL ?>assets/js/modal_agrupacion.js"></script>

<script>
    const BASE_URL = "<?= BASE_URL ?>";
</script>

<script src="<?= BASE_URL ?>assets/js/editar_agrupacion.js"></script>
<script src="<?= BASE_URL ?>assets/js/distribucion_unidades.js"></script>
<script src="<?= BASE_URL ?>assets/js/editar_tipo_unidad.js"></script>
</html>
