<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

if ($buscar != '') {
    $query = "SELECT id_articulo, nombre, categoria, unidad_medida, stock_actual 
                FROM articulos 
                WHERE activo = 1 AND (nombre LIKE :buscar OR categoria LIKE :buscar)
                ORDER BY nombre ASC";
    $stmt = $conexion->prepare($query);
    $stmt->execute([':buscar' => '%' . $buscar . '%']);
} else {
    $query = "SELECT id_articulo, nombre, categoria, unidad_medida, stock_actual 
                FROM articulos 
                WHERE activo = 1 
                ORDER BY nombre ASC";
    $stmt = $conexion->query($query);
}

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

            <div class="container">
                <h2>Control de Inventario</h2>

                <!-- Notificaciones de éxito -->
                <?php if (isset($_GET['mensaje'])): ?>
                    <div>
                        <strong>
                            <?php
                            if ($_GET['mensaje'] == 'creado') echo "Artículo creado exitosamente.";
                            if ($_GET['mensaje'] == 'movimiento_registrado') echo "Movimiento registrado. Stock actualizado.";
                            if ($_GET['mensaje'] == 'eliminado') echo "Artículo eliminado del catálogo.";
                            if ($_GET['mensaje'] == 'actualizado') echo "Artículo actualizado correctamente.";
                            ?>
                        </strong>
                        <br><br>
                    </div>
                <?php endif; ?>

                <div class="acciones">
                    <a href="agregar_articulo.php">Crear Nuevo Artículo</a> |
                    <a href="movimiento.php">Registrar Entrada/Salida o Ajuste</a> |
                    <a href="historial.php">Ver Historial de Movimientos</a>
                </div>
                <br>

                <!-- Formulario del Buscador -->
                <form action="listar.php" method="GET">
                    <label for="buscar">Buscar producto:</label>
                    <input type="text" id="buscar" name="buscar" placeholder="Nombre o categoría..." value="<?php echo htmlspecialchars($buscar); ?>">
                    <button type="submit">Buscar</button>
                    <a href="listar.php">Mostrar todos</a>
                </form>
                <br>

                <table border="1" cellpadding="10">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Unidad</th>
                            <th>Stock Actual</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($stmt->rowCount() > 0) {
                            while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<tr>";
                                echo "<td>" . $fila['id_articulo'] . "</td>";
                                echo "<td>" . htmlspecialchars($fila['nombre']) . "</td>";
                                echo "<td>" . htmlspecialchars($fila['categoria']) . "</td>";
                                echo "<td>" . htmlspecialchars($fila['unidad_medida']) . "</td>";

                                $color_stock = ($fila['stock_actual'] <= 0) ? 'color: red; font-weight: bold;' : '';
                                echo "<td style='$color_stock'>" . $fila['stock_actual'] . "</td>";

                                echo "<td>";
                                echo "<a href='editar_articulo.php?id=" . $fila['id_articulo'] . "'>Editar</a> | ";
                                echo "<a href='eliminar_articulo.php?id=" . $fila['id_articulo'] . "' onclick='return confirm(\"¿Estás seguro de que deseas ocultar este artículo del catálogo?\");'>Eliminar</a>";
                                echo "</td>";

                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No se encontraron artículos.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>

</html>