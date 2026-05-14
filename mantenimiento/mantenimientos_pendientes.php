<?php
require_once 'conexion.php';

// Obtener filtros
$busqueda = isset($_GET["buscar"]) ? trim($_GET["buscar"]) : "";
$torre_filtro = isset($_GET["torre"]) ? trim($_GET["torre"]) : "";

// Consulta base con JOIN para traer nombres
$sql = "SELECT m.id, m.descripcion, m.estado, m.created_at,
                c.nombre as nombre_conjunto,
                u.nombre as nombre_usuario
        FROM mantenimiento m
        LEFT JOIN conjunto c ON m.conjunto_id = c.id
        LEFT JOIN usuario u ON m.usuario_id = u.id
        WHERE 1=1";

// Agregar filtros si existen
if (!empty($busqueda)) {
    $sql .= " AND m.descripcion LIKE '%$busqueda%'";
}

if (!empty($torre_filtro)) {
    $sql .= " AND c.nombre = '$torre_filtro'";
}

$sql .= " ORDER BY m.id DESC";

$result = $conn->query($sql);

// Obtener todas las torres para el select
$torres_sql = "SELECT DISTINCT c.nombre FROM conjunto c ORDER BY c.nombre";
$torres_result = $conn->query($torres_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mantenimientos</title>
</head>
<body>

<div>
    <div>
        <h2>Listado de Mantenimientos</h2>
    </div>
    
    <form method="GET" action="">
        <input type="text" name="buscar" placeholder="Buscar palabra clave" value="<?php echo htmlspecialchars($busqueda); ?>">
        
        <select name="torre">
            <option value="">Todas las torres</option>
            <?php while($torre = $torres_result->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($torre['nombre']); ?>" 
                    <?php echo ($torre_filtro == $torre['nombre']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($torre['nombre']); ?>
                </option>
            <?php endwhile; ?>
        </select>
        
        <button type="submit">Buscar</button>
        <a href="?">Limpiar filtros</a>
    </form>
</div>

<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>Descripción</th>
            <th>Estado</th>
            <th>Usuario</th>
            <th>Conjunto o Torre</th>
            <th>Fecha</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($mantenimiento = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $mantenimiento['id']; ?></td>
                    <td><?php echo htmlspecialchars($mantenimiento['descripcion']); ?></td>
                    <td><?php echo $mantenimiento['estado']; ?></td>
                    <td><?php echo htmlspecialchars($mantenimiento['nombre_usuario']); ?></td>
                    <td><?php echo htmlspecialchars($mantenimiento['nombre_conjunto']); ?></td>
                    <td><?php echo $mantenimiento['created_at']; ?></td>
                    <td>
                        <a href="">Editar</a>
                        <a href="eliminar.php?id=<?php echo $mantenimiento['id']; ?>" 
                        onclick="return confirm('¿Estás seguro de eliminar este mantenimiento?')">Eliminar</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="6">No hay registros</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>

<?php $conn->close(); ?>