<?php
/**
 * Formulario para Editar Artículo
 */
require_once '../config/conexion.php';

// Validamos que exista un ID válido en la URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_articulo = (int)$_GET['id'];

    // Consultamos los datos actuales del artículo
    $query = "SELECT id_articulo, nombre, categoria, unidad_medida FROM articulos WHERE id_articulo = :id AND activo = 1";
    $stmt = $conexion->prepare($query);
    $stmt->execute([':id' => $id_articulo]);

    // Si el artículo existe, extraemos los datos
    if ($stmt->rowCount() > 0) {
        $articulo = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        header("Location: listar.php");
        exit();
    }
} else {
    header("Location: listar.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Artículo</title>
</head>
<body>
    <div class="container">
        <h2>Editar Artículo</h2>
        
        <form action="actualizar_articulo.php" method="POST">
            <!-- Campo oculto importantísimo para saber qué artículo actualizar -->
            <input type="hidden" name="id_articulo" value="<?php echo $articulo['id_articulo']; ?>">
            
            <label for="nombre">Nombre del artículo:</label><br>
            <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($articulo['nombre']); ?>" required><br><br>
            
            <label for="categoria">Categoría:</label><br>
            <input type="text" id="categoria" name="categoria" value="<?php echo htmlspecialchars($articulo['categoria']); ?>"><br><br>
            
            <label for="unidad_medida">Unidad de medida:</label><br>
            <select id="unidad_medida" name="unidad_medida" required>
                <!-- Usamos una pequeña lógica PHP para marcar como seleccionada (selected) la opción que el producto ya tenía -->
                <option value="Unidad" <?php if($articulo['unidad_medida'] == 'Unidad') echo 'selected'; ?>>Unidad</option>
                <option value="Galón" <?php if($articulo['unidad_medida'] == 'Galón') echo 'selected'; ?>>Galón</option>
                <option value="Litro" <?php if($articulo['unidad_medida'] == 'Litro') echo 'selected'; ?>>Litro</option>
                <option value="Caja" <?php if($articulo['unidad_medida'] == 'Caja') echo 'selected'; ?>>Caja</option>
                <option value="Paquete" <?php if($articulo['unidad_medida'] == 'Paquete') echo 'selected'; ?>>Paquete</option>
            </select><br><br>
            
            <button type="submit">Actualizar Artículo</button>
            <a href="listar.php">Cancelar</a>
        </form>
    </div>
</body>
</html>