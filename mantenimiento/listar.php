<?php
require_once "../config/conexion.php";

// todos los filtros
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
    $map = ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta'];
    return $map[$p] ?? $p;
}

// filtro de zonas
$zonas = $conexion->query("SELECT id, nombre FROM zona_comun ORDER BY nombre ")->fetchAll();

// consulta principal
$sql = "SELECT m.*, 
                z.nombre  AS nombre_zona,
                u.nombre  AS nombre_usuario,
                u.apellido AS apellido_usuario
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

// ordenamos por prioridades 
// $sql .= " ORDER BY m.id DESC";
$sql .= " ORDER BY 
            CASE m.prioridad 
                WHEN 'alta'  THEN 1 
                WHEN 'media' THEN 2 
                WHEN 'baja'  THEN 3 
            END, m.id DESC";

$stmt = $conexion->prepare($sql);
$stmt->execute($params);
$mantenimientos = $stmt->fetchAll();


?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Lista de mantenimientos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/css/script.js" defer></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>

<?php include "../includes/sidebar.php"; ?>

        <!-- CONTENIDO -->
        <main class="contenido">

            <h2 align="center">Lista de mantenimientos</h2>            

            <!-- BOTÓN NUEVO -->
            
            <button type="submit" class="btn-filtrar">
                <a href="crear.php"  >
                    <h3>Crear nuevo mantenimiento</h3>
                </a>

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
                        <option value="pendiente" <?= $estado == "pendiente" ? "selected" : "" ?>>
                            Pendiente
                        </option>
                        <option value="en_proceso" <?= $estado == "en_proceso" ? "selected" : "" ?>>
                            En Proceso
                        </option>
                        <option value="solucionado" <?= $estado == "solucionado" ? "selected" : "" ?>>
                            Solucionado
                        </option>
                    </select>

                    <select name="prioridad">
                        <option value="">Todas las prioridades</option>
                        <option value="alta" <?= $prioridad == "alta" ? "selected" : "" ?>>
                            Alta
                        </option>
                        <option value="media" <?= $prioridad == "media" ? "selected" : "" ?>>
                            Media
                        </option>
                        <option value="baja" <?= $prioridad == "baja" ? "selected" : "" ?>>
                            Baja
                        </option>
                    </select>

                    
                    <button type="submit" class="btn-filtrar">
                        Filtrar
                    </button>

                    <?php if ($buscar || $estado || $prioridad): ?>
                        <a href="listar.php" class="btn-limpiar">
                            Limpiar filtros
                        </a>
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
                                        <td>
                                            <?= etiquetaPrioridad($fila['prioridad']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($fila['descripcion']) ?>
                                        </td>

                                        <td>
                                            <?= etiquetaEstado($fila['estado']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($fila['nombre_zona'] ?? '-') ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                ($fila['nombre_usuario'] ?? '') .
                                                    ' ' .
                                                    ($fila['apellido_usuario'] ?? '')
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= $fila['fecha_reporte'] ?>
                                        </td>

                                        <td>
                                            <?= $fila['fecha_solucion'] ?: '-' ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($fila['evidencia'])): ?>
                                                <a href="<?= $fila['evidencia'] ?>" target="_blank">
                                                    Ver archivo
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>

                                        </td>
                                        <td>
                                            <a href="editar.php?id=<?= $fila['id'] ?>">
                                                Editar
                                            </a>
                                            |
                                            <a
                                                href="eliminar.php?id=<?= $fila['id'] ?>"
                                                onclick="return confirm('¿Seguro que deseas eliminar este mantenimiento?')">

                                                Eliminar
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="10">
                                        No hay mantenimientos registrados.
                                    </td>
                                </tr>

                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>

        </main>
</body>

</html>