<?php
require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

$sql = "SELECT id_tipo_config, nombre_grupo, cantidad_unidades FROM detalle_tipos_unidad
WHERE activo = 1
ORDER BY id_tipo_config";

$stmt = $conexion->query($sql);

$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$idGrupoSeleccionado = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($idGrupoSeleccionado == 0 && !empty($grupos)) {
    $idGrupoSeleccionado = $grupos[0]['id_tipo_config'];
}

$sql = "SELECT * FROM unidades WHERE id_tipo_config = :id ORDER BY codigo";
$stmt = $conexion->prepare($sql);
$stmt->execute([
    ':id' => $idGrupoSeleccionado
]);

$unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <h2 align="center">Unidades</h2>    
            <div class="acciones-superior">
                
                <button type="button" class="btn-filtrar btn-derecha" id="btnNuevaUnidad">
                    + Nueva unidad
                </button>
            </div>

            <div class="tabs-container">
                <?php foreach ($grupos as $grupo): ?>
                    <a
                        href="unidades.php?id=<?= $grupo['id_tipo_config'] ?>"
                        class="tab-button <?= $grupo['id_tipo_config'] == $idGrupoSeleccionado ? 'active' : '' ?>">
                        <?= htmlspecialchars($grupo['nombre_grupo']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Piso</th>
                        <th>Área</th>
                        <th>Coeficiente</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>

                </thead>
                    <tbody>

                        <?php if (empty($unidades)): ?>
                            <tr>
                                <td colspan="7" style="text-align:center;">
                                    No existen unidades registradas para este grupo.
                                </td>
                            </tr>
                        <?php else: ?>

                            <?php foreach ($unidades as $u): ?>
                                <tr>
                                    <td><?= htmlspecialchars($u['codigo']) ?></td>
                                    <td><?= htmlspecialchars($u['nombre']) ?></td>
                                    <td><?= htmlspecialchars($u['piso']) ?></td>
                                    <td><?= htmlspecialchars($u['area'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($u['coeficiente'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($u['estado']) ?></td>
                                    <td>
                                        <button class="btn-secondary btnEditarUnidad"
                                            data-id="<?= $u['id_unidad'] ?>">
                                            Editar
                                        </button>

                                        <a href="personas_unidad.php?id_unidad=<?= $u['id_unidad'] ?>"
                                            class="btn-secondary">
                                            Residentes
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
            </table>

        </main>
        <!-- ==========================================
            MODAL NUEVA UNIDAD
        ========================================== -->

        <div id="modalUnidad" class="modal">
            <div class="modal-contenido">
                <span id="cerrarUnidad" class="cerrar">&times;</span>
                <h2>Nueva unidad</h2>
                <br>
                <form action="<?= BASE_URL ?>actions/guardar_unidad.php" method="POST">
                    <!-- Grupo al que pertenece -->
                    <input
                        type="hidden"
                        name="id_tipo_config"
                        value="<?= $idGrupoSeleccionado ?>">
                    <label>Código *</label>

                    <input
                        type="text" name="codigo" maxlength="20" required>
                    <br><br>
                    <label>Nombre</label>

                    <input
                        type="text" name="nombre" maxlength="100">
                    <br><br>

                    <div class="form-group">
                        <div>
                            <label>Piso</label>
                            <input
                                type="number" name="piso" min="0">
                        </div>

                        <div>
                            <label>Estado</label>
                            <select name="estado">
                                <option value="Activa" selected>
                                    Activa
                                </option>

                                <option value="Inactiva">
                                    Inactiva
                                </option>
                            </select>
                        </div>
                    </div>

                    <br>

                    <div class="form-group">
                        <div>
                            <label>Área (m²)</label>
                            <input type="number" name="area"  step="0.01" min="0">
                        </div>

                        <div>
                            <label>Coeficiente</label>
                            <input type="number" name="coeficiente" step="0.00001" min="0">
                        </div>

                    </div>
                    <br>

                    <label>Observaciones</label>
                    <textarea
                        name="observaciones" rows="2"></textarea>
                    <br><br>

                    <button
                        type="reset" class="btn-limpiar" id="cancelarUnidad">
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
</body>
</html>