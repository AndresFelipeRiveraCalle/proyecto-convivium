<?php
// Conectar a la base de datos
require_once "conexion.php";

// ========== FILTROS DE BÚSQUEDA ==========
$palabraBuscar = isset($_GET["buscar"]) ? $_GET["buscar"] : "";
$torreFiltro = isset($_GET["torre"]) ? $_GET["torre"] : "";
$estadoFiltro = isset($_GET["estado"]) ? $_GET["estado"] : "";
$prioridadFiltro = isset($_GET["prioridad"]) ? $_GET["prioridad"] : "";

// ========== FUNCIONES PARA MOSTRAR TEXTO BONITO ==========

// Función para mostrar el estado con emojis
function mostrarEstado($estado) {
    if($estado == "pendiente") {
        return "Pendiente";
    } elseif($estado == "en_proceso") {
        return "En Proceso";
    } elseif($estado == "finalizado") {
        return "Finalizado";
    } else {
        return $estado;
    }
}

// Función para mostrar la prioridad con emojis
function mostrarPrioridad($prioridad) {
    if($prioridad == "baja") {
        return "Baja";
    } elseif($prioridad == "media") {
        return "Media";
    } elseif($prioridad == "alta") {
        return "Alta";
    } elseif($prioridad == "critica") {
        return "Crítica";
    } else {
        return $prioridad;
    }
}

// Función para mostrar el costo con formato de pesos
function mostrarCosto($costo) {
    if($costo > 0) {
        return "$ " . number_format($costo, 0, ',', '.');
    } else {
        return "-";
    }
}

// ========== CONSULTAR TORRES PARA EL FILTRO ==========
$sqlTorres = "SELECT DISTINCT nombre FROM conjunto ORDER BY nombre";
$resultadoTorres = $conn->query($sqlTorres);

// ========== CONSULTAR MANTENIMIENTOS CON FILTROS ==========
$sql = "SELECT m.*, c.nombre as nombre_torre, u.nombre as nombre_usuario 
        FROM mantenimiento m
        LEFT JOIN conjunto c ON m.conjunto_id = c.id
        LEFT JOIN usuario u ON m.usuario_id = u.id
        WHERE 1=1";

// Agregar filtro por palabra
if($palabraBuscar != "") {
    $sql = $sql . " AND m.descripcion LIKE '%$palabraBuscar%'";
}

// Agregar filtro por torre
if($torreFiltro != "") {
    $sql = $sql . " AND c.nombre = '$torreFiltro'";
}

// Agregar filtro por estado (NUEVO)
if($estadoFiltro != "") {
    $sql = $sql . " AND m.estado = '$estadoFiltro'";
}

// Agregar filtro por prioridad (NUEVO)
if($prioridadFiltro != "") {
    $sql = $sql . " AND m.prioridad = '$prioridadFiltro'";
}

// Ordenar: primero los más urgentes (crítica, alta, media, baja)
$sql = $sql . " ORDER BY 
                CASE m.prioridad 
                    WHEN 'critica' THEN 1 
                    WHEN 'alta' THEN 2 
                    WHEN 'media' THEN 3 
                    WHEN 'baja' THEN 4 
                END, m.id DESC";

// Ejecutar la consulta
$resultado = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Lista de Mantenimientos</title>
</head>
<body>

<h1>Lista de Mantenimientos</h1>

<!-- Botón para crear nuevo -->
<a href="crear.php">Crear Nuevo Mantenimiento</a>

<br><br>

<!-- ========== FORMULARIO DE FILTROS ========== -->
<form method="GET">
    <!-- Filtro por palabra -->
    <input type="text" name="buscar" placeholder="Buscar por descripción..." 
            value="<?php echo $palabraBuscar; ?>">
    
    <!-- Filtro por torre -->
    <select name="torre">
        <option value="">Todas las torres</option>
        <?php while($torre = $resultadoTorres->fetch_assoc()): ?>
            <option value="<?php echo $torre['nombre']; ?>" 
                <?php if($torreFiltro == $torre['nombre']) echo "selected"; ?>>
                <?php echo $torre['nombre']; ?>
            </option>
        <?php endwhile; ?>
    </select>
    
    <!-- Filtro por estado (NUEVO) -->
    <select name="estado">
        <option value="">Todos los estados</option>
        <option value="pendiente" <?php if($estadoFiltro == "pendiente") echo "selected"; ?>>Pendiente</option>
        <option value="en_proceso" <?php if($estadoFiltro == "en_proceso") echo "selected"; ?>>En Proceso</option>
        <option value="finalizado" <?php if($estadoFiltro == "finalizado") echo "selected"; ?>>Finalizado</option>
    </select>
    
    <!-- Filtro por prioridad (NUEVO) -->
    <select name="prioridad">
        <option value="">Todas las prioridades</option>
        <option value="critica" <?php if($prioridadFiltro == "critica") echo "selected"; ?>>Crítica</option>
        <option value="alta" <?php if($prioridadFiltro == "alta") echo "selected"; ?>>Alta</option>
        <option value="media" <?php if($prioridadFiltro == "media") echo "selected"; ?>>Media</option>
        <option value="baja" <?php if($prioridadFiltro == "baja") echo "selected"; ?>>Baja</option>
    </select>
    
    <button type="submit">Filtrar</button>
    
    <!-- Botón para limpiar filtros -->
    <?php if($palabraBuscar != "" || $torreFiltro != "" || $estadoFiltro != "" || $prioridadFiltro != ""): ?>
        <a href="listar.php">Limpiar filtros</a>
    <?php endif; ?>
</form>

<br>

<!-- ========== TABLA DE RESULTADOS ========== -->
<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>Prioridad</th>
            <th>Descripcion</th>
            <th>Estado</th>
            <th>Responsable</th>
            <th>Costo</th>
            <th>Solicitante</th>
            <th>Torre</th>
            <th>Fecha Solicitud</th>
            <th>Fecha Finalizacion</th>
            <th>Evidencia</th>
            <th>Comentarios</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if($resultado->num_rows > 0): ?>
            <?php while($fila = $resultado->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $fila['id']; ?></td>
                    <td><?php echo mostrarPrioridad($fila['prioridad']); ?></td>
                    <td><?php echo $fila['descripcion']; ?></td>
                    <td><?php echo mostrarEstado($fila['estado']); ?></td>
                    <td>
                        <?php 
                        // Mostrar responsable o "-" si está vacío
                        if($fila['responsable'] != "") {
                            echo $fila['responsable'];
                        } else {
                            echo "-";
                        }
                        ?>
                    </td>
                    <td><?php echo mostrarCosto($fila['costo']); ?></td>
                    <td><?php echo $fila['nombre_usuario']; ?></td>
                    <td><?php echo $fila['nombre_torre']; ?></td>
                    <td><?php echo $fila['created_at']; ?></td>
                    <td>
                        <?php 
                        // Mostrar fecha de finalización o "-" si no existe
                        if($fila['fecha_finalizacion'] != "" && $fila['fecha_finalizacion'] != "0000-00-00 00:00:00") {
                            echo $fila['fecha_finalizacion'];
                        } else {
                            echo "-";
                        }
                        ?>
                    </td>
                    <td>
                        <?php 
                        // Mostrar enlace a la evidencia si existe
                        if($fila['evidencia'] != "" && file_exists($fila['evidencia'])) {
                            echo "<a href='" . $fila['evidencia'] . "' target='_blank'>📎 Ver archivo</a>";
                        } else {
                            echo "-";
                        }
                        ?>
                    </td>
                    <td>
                        <?php 
                        // Mostrar primeros 50 caracteres de comentarios
                        if($fila['comentarios'] != "") {
                            echo substr($fila['comentarios'], 0, 50);
                            if(strlen($fila['comentarios']) > 50) echo "...";
                        } else {
                            echo "-";
                        }
                        ?>
                    </td>
                    <td>
                        <a href="editar.php?id=<?php echo $fila['id']; ?>">Editar</a>
                        |
                        <a href="eliminar.php?id=<?php echo $fila['id']; ?>" 
                            onclick="return confirm('¿Seguro que quieres eliminar este mantenimiento?')">Eliminar</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="13">No hay mantenimientos registrados</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>

<?php $conn->close(); ?>