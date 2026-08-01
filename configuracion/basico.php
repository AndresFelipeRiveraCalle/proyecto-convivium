<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

$stmtPais = $conexion->query("SELECT id_pais AS id, nombre FROM paises ORDER BY nombre");
$paises = $stmtPais->fetchAll(PDO::FETCH_ASSOC);

$stmtDepartamentos = $conexion->query("SELECT codigo, id_departamento AS id, nombre FROM departamentos ORDER BY nombre");
$departamentos = $stmtDepartamentos->fetchAll(PDO::FETCH_ASSOC);

$stmtCiudades = $conexion->query("SELECT id_ciudad AS id, nombre , codigo_dane FROM ciudades ORDER BY nombre");
$ciudades = $stmtCiudades->fetchAll(PDO::FETCH_ASSOC);


// ===========================================
// GRUPO SELECCIONADO
// ===========================================

$idGrupoSeleccionado = isset($_GET['id'])
    ? intval($_GET['id'])
    : 0;

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

$usos = $conexion->query("SELECT id_uso as id, nombre FROM usos_vivienda ORDER BY nombre");
$usosVivienda = $usos->fetchAll(PDO::FETCH_ASSOC);

$tipoCopropiedad = $conexion->query("SELECT id, nombre FROM tipos_copropiedad ORDER BY nombre");
$tiposCopropiedad = $tipoCopropiedad->fetchAll(PDO::FETCH_ASSOC);

$stmtTipos = $conexion->query("SELECT * FROM detalle_tipos_unidad WHERE activo = 1 ORDER BY id_tipo_config");
$tiposUnidad = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include ROOT_PATH . "/includes/head.php"; ?>
</head>

<!-- ==========================================
        MODAL DE MENSAJES DEL SISTEMA
        ========================================== -->

<div id="modalMensaje" class="modal">
    <div class="modal-contenido modal-mensaje">
        <h2 id="tituloMensaje"></h2>
        <br>
        <p id="textoMensaje"></p>
        <br><br>
        <div class="acciones-modal">
            <button
                type="button"
                id="btnCerrarMensaje"
                class="btn-filtrar">
                Aceptar
            </button>
        </div>
    </div>
</div>

<body>
    <?php include ROOT_PATH . "/includes/header.php"; ?>
    <?php require_once  ROOT_PATH . "/includes/mensajes.php"; ?>

    <div class="contenedor">
        <?php include ROOT_PATH . "/includes/sidebar.php"; ?>
        <main class="contenido">

            <div class="form-actions">
                <button
                        type="button"
                        class="btn-filtrar btn-derecha"
                        onclick="window.location.href='../configuracion/datos.php'">
                        Datos de la copropiedad
                    </button>
            </div>

            <h2 align="center">Configuracion básica</h2>
            <br>
            <p>En esta seccion podras configurar las áreas de la copropiedad como cantidad de apartamentos, zonas comunes y distrinbuciones generales</p>
            <br>

            <h3>Ubicación de la copropiedad</h3>

            <div class="bloque filtros">

                <div class="card">
                    <h4>País</h4>
                    <select name="id_pais" class="form-control">
                        <?php foreach ($paises as $pais): ?>
                            <option value="<?= $pais['id'] ?>">
                                <?= htmlspecialchars($pais['id']) ?> - <?= htmlspecialchars($pais['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="card">
                    <h4>Departamento</h4>
                    <select name="id_departamento" class="form-control">
                        <option value="">Seleccione un departamento</option>
                        <?php foreach ($departamentos as $departamento): ?>
                            <option value="<?= $departamento['id'] ?>">
                                <?= htmlspecialchars($departamento['codigo']) ?> - <?= htmlspecialchars($departamento['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>


                <div class="card">
                    <h4>Ciudad</h4>
                    <select name="id_ciudad" class="form-control">
                        <option value="">Seleccione una ciudad</option>
                        <?php foreach ($ciudades as $c): ?>
                            <option value="<?= $c['id'] ?>">
                                <?= htmlspecialchars($c['codigo_dane']) ?> - <?= htmlspecialchars($c['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <br>

                    <button type="button" class="btn-secondary" id="btnNuevaCiudad">
                        + Nueva ciudad
                    </button>

                </div>


                <div class="form-group label">
                    <span for="direccion">Dirección:</span>
                    <input type="text" id="direccion" name="direccion" placeholder="Ej. Vía Las Palmas Km 4" required>
                </div>
                <div class="form-group label">
                    <span for="sector">Sector:</span>
                    <input type="text" id="sector" name="sector" placeholder="Comuna - Barrio - Zona">
                </div>

            </div>

            <h3>Unidades de vivienda</h3>

            <div class="bloque filtros">


                    <!--h3>Tipo de unidad</h3-->
                    <div class="card">
                        <h4>Tipo de unidad</h4>
                        <select name="tipo_copropiedad" class="form-control" required>
                            <option value="">Seleccione un tipo</option>
                            <?php foreach ($tiposCopropiedad as $propiedad): ?>
                                <option value="<?= $propiedad['id'] ?>">
                                    <?= htmlspecialchars($propiedad['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="card">
                        <span class="step active"><h4>Cantidad total de unidades</h4></span>
                        <input type="text" id="cantidad_unidades" name="cantidad_unidades" placeholder="Cantidad total de unidades" required>
                    </div>               

            </div>

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
                        <form action="../actions/guardar_tipo_unidad.php" method="POST">
                            <input
                                type="hidden" name="id_tipo_config" value="<?= $grupoSeleccionado['id_tipo_config'] ?>">
                            <div class="bloque filtros">
                                <div class="form-group">
                                    <label>Cantidad de unidades</label>
                                    <input
                                        type="number" name="cantidad_unidades" value="<?= $grupoSeleccionado['cantidad_unidades'] ?>">
                                </div>

                                <div class="form-group">
                                    <label>Área total (m²)</label>
                                    <input
                                        type="number" step="0.01" name="area_total" value="<?= $grupoSeleccionado['area_total'] ?>">
                                </div>

                                <div class="form-group">
                                    <label>Coeficiente total</label>
                                    <input
                                        type="number" step="0.00001" name="coeficiente_total" value="<?= $grupoSeleccionado['coeficiente_total'] ?>">
                                </div>
                            </div>
                            <br>

                            <div class="form-group textarea"  >
                                <label>Observaciones</label>
                                <textarea
                                    name="observaciones" rows="4"><?= htmlspecialchars($grupoSeleccionado['observaciones']) ?></textarea>
                            </div>
                            <br>

                            <button
                                type="submit"
                                class="btn-filtrar">
                                Guardar configuración
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>


            <div class="form-actions">
                <button type="submit" class="btn-limpiar">Cancelar</button>
                <button type="submit" class="btn-filtrar">Guardar</button>
            </div>
        </main>
    </div>

    <div id="modalPais" class="modal">
        <div class="modal-contenido">
            <span id="cerrarPais" class="cerrar">&times;</span>

            <h2>Nuevo País</h2>
            <br><br>
            <form action="../actions/guardar_pais.php" method="POST">
                <label>Nombre del país</label>
                <br>
                <input
                    type="text" id="nombrePais" name="nombreP" required maxlength="100">
                <br><br>
                <label>Codigo del país</label>
                <input
                    type="text" id="codigoPais" name="codigoP" maxlength="10">
                <br><br>
                <button type="reset" class="btn-limpiar" id="cancelarPais">Cancelar</button>
                <button type="submit" class="btn-filtrar">Guardar</button>
            </form>

        </div>
    </div>


    <div id="modalDepartamento" class="modal">
        <div class="modal-contenido">
            <span id="cerrarDepartamento" class="cerrar">&times;</span>

            <h2>Nuevo Departamento</h2>

            <form action="../actions/guardar_Departamento.php" method="POST">
                <label>Nombre del departamento</label>
                <input
                    type="text" id="nombreDepartamento" name="nombreD" required maxlength="100">
                <br><br>
                <label>Código del departamento</label>
                <input
                    type="text" id="codigoDepartamento" name="codigo" maxlength="10">
                <br><br>
                <button type="reset" class="btn-limpiar" id="cancelarDepartamento">Cancelar</button>
                <button type="submit" class="btn-filtrar">Guardar</button>

            </form>
        </div>
    </div>

    <div id="modalCiudad" class="modal">
        <div class="modal-contenido">
            <span id="cerrarCiudad" class="cerrar">&times;</span>

            <h2>Nueva Ciudad</h2>

            <form action="../actions/guardar_ciudad.php" method="POST">
                
            
                <label>País</label>
                <select name="id_pais" class="form-control">
                    <?php foreach ($paises as $pais): ?>
                        <option value="<?= $pais['id'] ?>">
                            <?= htmlspecialchars($pais['id']) ?> - <?= htmlspecialchars($pais['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <br><br>
                <label>Departamento</label>
                <select name="id_departamento" class="form-control">
                    <option value="">Seleccione un departamento</option>
                    <?php foreach ($departamentos as $departamento): ?>
                        <option value="<?= $departamento['id'] ?>">
                            <?= htmlspecialchars($departamento['codigo']) ?> - <?= htmlspecialchars($departamento['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <br><br>
            
                <label>Nombre de la ciudad</label>
                <input
                    type="text" id="nombreCiudad" name="nombreC" required maxlength="100">
                <br><br>
                <label>Código de la ciudad</label>
                <input
                    type="text" id="codigoCiudad" name="codigo" maxlength="10">
                <br><br>
                <button type="reset" class="btn-limpiar" id="cancelarCiudad">Cancelar</button>
                <button type="submit" class="btn-filtrar">Guardar</button>

            </form>
        </div>
    </div>

    <!-- ==========================================
     MODAL NUEVO GRUPO
    ========================================== -->

    <div id="modalGrupo" class="modal">
        <div class="modal-contenido">
            <span id="cerrarGrupo" class="cerrar">&times;</span>
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
                    name="observaciones" rows="3"></textarea>
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

</body>

</html>