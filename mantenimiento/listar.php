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
    <title>Lista de Mantenimientos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/script.js">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>

    <!-- HEADER -->
    <header class="header">
        <div class="logo">
            <img src="../assets/css/Imagenes/Logo_2.png" alt="Logo">
        </div>
        <div class="usuario">
            <img src="..Imagenes/user.png" alt="Usuario">
            <span>Usuario</span>
        </div>
    </header>

    <!-- CONTENEDOR -->
    <div class="contenedor">

        <!-- MENU -->
        <aside class="menu">
            <ul>
                <li>Inicio</li>
                <li>Recaudos</li>
                <li>Cartera</li>
                <li class="activo">Mantenimiento</li>
                <li>Gastos</li>
                <li>Multas</li>
            </ul>

            <!-- CALENDARIO -->
            <div class="calendar">

                <!-- HEADER CALENDARIO -->
                <div class="calendar-header">

                    <!-- MES -->
                    <div class="month-control">
                        <span class="month-change" id="prev-month">
                            <
                                </span>
                                <span class="month-picker" id="month-picker">
                                    Mayo
                                </span>
                                <span class="month-change" id="next-month">
                                    >
                                </span>
                    </div>

                    <!-- AÑO -->
                    <div class="year-control">
                        <span class="year-change" id="prev-year">
                            <
                        </span>
                        <span id="year">
                            2026
                        </span>
                        <span class="year-change" id="next-year">
                            >
                        </span>
                    </div>
                </div>

                <!-- CUERPO -->
                <div class="calendar-body">

                    <!-- DIAS -->
                    <div class="calendar-week-days">
                        <div>Dom</div>
                        <div>Lun</div>
                        <div>Mar</div>
                        <div>Mie</div>
                        <div>Jue</div>
                        <div>Vie</div>
                        <div>Sab</div>
                    </div>

                    <!-- NUMEROS -->
                    <div class="calendar-days"></div>
                </div>

                <!-- FECHA -->
                <div class="date-time-formate">
                    <div class="time-formate"></div>
                    <div class="date-formate"></div>
                </div>
            </div>
        </aside>


                <!-- CONTENIDO -->
        <main class="contenido">

                <!-- CARD 1 -->
                <div class="bloque"></div>
                    <h1>Lista de Mantenimientos</h1>
                    <a href="crear.php">+ Crear Nuevo Mantenimiento</a>
                    <br><br>


                    <form method="GET">
                        <input type="text" name="buscar" placeholder="Buscar por descripcion"
                            value="<?= htmlspecialchars($buscar) ?>">

                        <select name="estado">
                            <option value="">Todos los estados</option>
                            <option value="pendiente" <?= $estado == "pendiente"  ? "selected" : "" ?>>Pendiente</option>
                            <option value="en_proceso" <?= $estado == "en_proceso" ? "selected" : "" ?>>En Proceso</option>
                            <option value="solucionado" <?= $estado == "solucionado" ? "selected" : "" ?>>Solucionado</option>
                        </select>

                        <select name="prioridad">
                            <option value="">Todas las prioridades</option>
                            <option value="alta" <?= $prioridad == "alta"  ? "selected" : "" ?>>Alta</option>
                            <option value="media" <?= $prioridad == "media" ? "selected" : "" ?>>Media</option>
                            <option value="baja" <?= $prioridad == "baja"  ? "selected" : "" ?>>Baja</option>
                        </select>

                        <button type="submit">Filtrar</button>
                        <?php if ($buscar || $estado || $prioridad): ?>
                            <a href="listar.php">Limpiar filtros</a>
                        <?php endif; ?>
                    </form>
                    <br>
                </div>


                <table border="1" cellpadding="6">
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
                                    <td><?= htmlspecialchars(($fila['nombre_usuario'] ?? '') . ' ' . ($fila['apellido_usuario'] ?? '')) ?></td>
                                    <td><?= $fila['fecha_reporte'] ?></td>
                                    <td><?= $fila['fecha_solucion'] ?? '-' ?></td>
                                    <td>
                                        <?php if (!empty($fila['evidencia']) && file_exists($fila['evidencia'])): ?>
                                            <a href="<?= $fila['evidencia'] ?>" target="_blank">Ver archivo</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="editar.php?id=<?= $fila['id'] ?>">Editar</a>
                                        |
                                        <a href="eliminar.php?id=<?= $fila['id'] ?>"
                                            onclick="return confirm('¿Seguro que deseas eliminar este mantenimiento?')">Eliminar</a>
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
            </body>
        </main>

</body>

</html>