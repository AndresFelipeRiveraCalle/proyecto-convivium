<?php
require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/auth/requerir_sesion.php";
require_once ROOT_PATH . "/config/conexion.php";

// Todos los filtros
$buscar    = isset($_GET["buscar"])    ? trim($_GET["buscar"])    : "";
$estado    = isset($_GET["estado"])    ? $_GET["estado"]          : "";
$prioridad = isset($_GET["prioridad"]) ? $_GET["prioridad"]       : "";

function etiquetaEstado($e)
{
    $map = ['pendiente' => 'Pendiente', 'en_proceso' => 'En Proceso', 'solucionado' => 'Solucionado'];
    return $map[$e] ?? $e;
}

function etiquetaPrioridad($p)
{
    $map = ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta', 'critica' => 'Crítica'];
    return $map[$p] ?? $p;
}

// Filtro de zonas y usuarios para los selectores del modal
$zonas = $conexion->query("SELECT id, nombre FROM zona_comun ORDER BY nombre")->fetchAll();
$usuarios = $conexion->query("SELECT id, nombres, apellidos FROM usuario ORDER BY nombres")->fetchAll();

// Consulta principal
$sql = "SELECT m.*, 
                z.nombre  AS nombre_zona,
                u.nombres  AS nombre_usuario,
                u.apellidos AS apellido_usuario
        FROM mantenimiento m
        LEFT JOIN zona_comun z ON m.zona_id = z.id
        LEFT JOIN usuario    u ON m.usuario_reporta_id = u.id
        WHERE 1=1";

$params = [];

if ($buscar !== "") {
    $sql .= " AND m.descripcion LIKE :buscar";
    $params[':buscar'] = "%$buscar%";
}
if ($estado !== "") {
    $sql .= " AND m.estado = :estado";
    $params[':estado'] = $estado;
}
if ($prioridad !== "") {
    $sql .= " AND m.prioridad = :prioridad";
    $params[':prioridad'] = $prioridad;
}

// Ordenamos por prioridades 
$sql .= " ORDER BY 
            CASE m.prioridad 
                WHEN 'critica' THEN 1
                WHEN 'alta'  THEN 2 
                WHEN 'media' THEN 3 
                WHEN 'baja'  THEN 4 
            END, m.id DESC";

$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$mantenimientos = $stmt->fetchAll();
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
        
        <!-- CONTENIDO -->
        <main class="contenido">

            <h2 align="center">Lista de mantenimientos</h2>            

            <!-- BOTÓN NUEVO -->
            <button type="button" id="btnAbrirModalMantenimiento" class="btn-filtrar" style="margin-bottom: 20px; cursor: pointer;">
                <h3>Crear nuevo mantenimiento</h3>
            </button>

            <!-- FILTROS -->
            <div class="bloque filtros">
                <form method="GET">
                    <input
                        type="text"
                        name="buscar"
                        placeholder="Buscar por descripción"
                        value="<?= htmlspecialchars($buscar) ?>">

                    <select name="estado">
                        <option value="">Todos los estados</option>
                        <option value="pendiente" <?= $estado == "pendiente" ? "selected" : "" ?>>Pendiente</option>
                        <option value="en_proceso" <?= $estado == "en_proceso" ? "selected" : "" ?>>En Proceso</option>
                        <option value="solucionado" <?= $estado == "solucionado" ? "selected" : "" ?>>Solucionado</option>
                    </select>

                    <select name="prioridad">
                        <option value="">Todas las prioridades</option>
                        <option value="critica" <?= $prioridad == "critica" ? "selected" : "" ?>>Crítica</option>
                        <option value="alta" <?= $prioridad == "alta" ? "selected" : "" ?>>Alta</option>
                        <option value="media" <?= $prioridad == "media" ? "selected" : "" ?>>Media</option>
                        <option value="baja" <?= $prioridad == "baja" ? "selected" : "" ?>>Baja</option>
                    </select>

                    <button type="submit" class="btn-filtrar">Filtrar</button>

                    <?php if ($buscar || $estado || $prioridad): ?>
                        <a href="listar.php" class="btn-limpiar">Limpiar filtros</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- TABLA -->
            <h3>Historial de Mantenimientos</h3>
            
            <div class="bloque historial-grid">
                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Prioridad</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Zona</th>
                                <th>Solicitante</th>
                                <th>Fecha Reporte</th>
                                <th>Fecha Solución</th>
                                <th>Evidencia</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (count($mantenimientos) > 0): ?>
                                <?php foreach ($mantenimientos as $fila): ?>
                                    <tr>
                                        <td><?= $fila['id'] ?></td>
                                        <td><?= etiquetaPrioridad($fila['prioridad']) ?></td>
                                        <td><?= htmlspecialchars($fila['descripcion']) ?></td>
                                        <td><?= etiquetaEstado($fila['estado']) ?></td>
                                        <td><?= htmlspecialchars($fila['nombre_zona'] ?? '-') ?></td>
                                        <td>
                                            <?= htmlspecialchars(
                                                ($fila['nombre_usuario'] ?? '') . ' ' . ($fila['apellido_usuario'] ?? '')
                                            ) ?>
                                        </td>
                                        <td><?= $fila['fecha_reporte'] ?></td>
                                        <td><?= $fila['fecha_solucion'] ?: '-' ?></td>
                                        <td>
                                            <?php if (!empty($fila['evidencia'])): ?>
                                                <a href="<?= rtrim(BASE_URL, '/') . '/' . ltrim($fila['evidencia'], '/') ?>" target="_blank">Ver archivo</a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="#" 
                                                class="btn-abrir-editar"
                                                data-id="<?= $fila['id'] ?>"
                                                data-zona="<?= $fila['zona_id'] ?>"
                                                data-solicitante="<?= $fila['usuario_reporta_id'] ?>"
                                                data-desc="<?= htmlspecialchars($fila['descripcion']) ?>"
                                                data-prioridad="<?= $fila['prioridad'] ?>"
                                                data-estado="<?= $fila['estado'] ?>"
                                                data-fecha="<?= $fila['fecha_solucion'] ?>">
                                                Editar
                                            </a>
                                            |
                                            <a href="eliminar.php?id=<?= $fila['id'] ?>" onclick="return confirm('¿Seguro que deseas eliminar este mantenimiento?')">Eliminar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10">No hay mantenimientos registrados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- ESTRUCTURA DEL MODAL: CREAR -->
    <div id="modalMantenimiento" class="modal-custom">
        <div class="modal-content-custom">
            <span class="close-modal">&times;</span>
            <h2>Crear Nuevo Mantenimiento</h2>
            
            <form action="guardar.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="zona_id">Zona Común:</label>
                    <select name="zona_id" id="zona_id" required>
                        <option value="">Seleccione una zona...</option>
                        <?php foreach ($zonas as $zona): ?>
                            <option value="<?= $zona['id'] ?>"><?= htmlspecialchars($zona['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="usuario_reporta_id">Solicitante:</label>
                    <select name="usuario_reporta_id" id="usuario_reporta_id" required>
                        <option value="">Seleccione un usuario...</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?= $usuario['id'] ?>"><?= htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="prioridad">Prioridad:</label>
                    <select name="prioridad" id="prioridad" required>
                        <option value="baja">Baja</option>
                        <option value="media" selected>Media</option>
                        <option value="alta">Alta</option>
                        <option value="critica">Crítica</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea name="descripcion" id="descripcion" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label for="estado">Estado actual:</label>
                    <select name="estado" id="estado" required>
                        <option value="pendiente" selected>Pendiente</option>
                        <option value="en_proceso">En Proceso</option>
                        <option value="solucionado">Solucionado</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="fecha_solucion">Fecha de solución (Fecha tentativa):</label>
                    <input type="date" name="fecha_solucion" id="fecha_solucion">
                </div>

                <div class="form-group">
                    <label for="evidencia">Evidencia Fotográfica o Documento (Foto o PDF):</label>
                    <input type="file" name="evidencia" id="evidencia">
                </div>

                <button type="submit" class="btn-submit">Guardar Mantenimiento</button>
            </form>
        </div>
    </div>

    <!-- ESTRUCTURA DEL MODAL: EDITAR -->
    <div id="modalEditarMantenimiento" class="modal-custom">
        <div class="modal-content-custom">
            <span class="close-modal-editar">&times;</span>
            <h2>Editar Mantenimiento</h2>
            
            <form action="actualizar.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-group">
                    <label for="edit_zona_id">Zona Común:</label>
                    <select name="zona_id" id="edit_zona_id" required>
                        <option value="">Seleccione una zona...</option>
                        <?php foreach ($zonas as $zona): ?>
                            <option value="<?= $zona['id'] ?>"><?= htmlspecialchars($zona['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_usuario_reporta_id">Solicitante:</label>
                    <select name="usuario_reporta_id" id="edit_usuario_reporta_id" required>
                        <option value="">Seleccione un usuario...</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?= $usuario['id'] ?>"><?= htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_prioridad">Prioridad:</label>
                    <select name="prioridad" id="edit_prioridad" required>
                        <option value="baja">Baja</option>
                        <option value="media">Media</option>
                        <option value="alta">Alta</option>
                        <option value="critica">Crítica</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_descripcion">Descripción:</label>
                    <textarea name="descripcion" id="edit_descripcion" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_estado">Estado:</label>
                    <select name="estado" id="edit_estado" required>
                        <option value="pendiente">Pendiente</option>
                        <option value="en_proceso">En Proceso</option>
                        <option value="solucionado">Solucionado</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_fecha_solucion">Fecha de solución:</label>
                    <input type="date" name="fecha_solucion" id="edit_fecha_solucion">
                </div>

                <div class="form-group">
                    <label for="edit_evidencia">Actualizar Evidencia (Opcional):</label>
                    <input type="file" name="evidencia" id="edit_evidencia">
                </div>

                <button type="submit" class="btn-submit">Actualizar Mantenimiento</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/modal_mantenimiento.js"></script>
</body>
</html>