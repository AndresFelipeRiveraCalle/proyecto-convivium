<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/auth/requerir_sesion.php";
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
        <main class="contenido inventario-page">

            <div class="container inventario-container">
                <h2 class="inventario-titulo">Control de Inventario</h2>

                <!-- Notificaciones de éxito -->
                <?php if (isset($_GET['mensaje'])): ?>
                    <div class="inventario-alert" role="status">
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

                <div class="acciones inventario-acciones">
                    <a href="agregar_articulo.php"><i class="fa-solid fa-plus"></i> Crear nuevo artículo</a>
                    <a href="movimiento.php"><i class="fa-solid fa-arrow-right-arrow-left"></i> Registrar entrada / salida</a>
                    <a href="historial.php"><i class="fa-solid fa-clock-rotate-left"></i> Historial de movimientos</a>
                </div>

                <!-- Formulario del Buscador -->
                <form class="inventario-filtros" action="listar.php" method="GET">
                    <div>
                        <label for="buscar">Buscar producto</label>
                        <input type="text" id="buscar" name="buscar" placeholder="Nombre o categoría..." value="<?php echo htmlspecialchars($buscar); ?>">
                    </div>
                    <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
                    <a class="inventario-mostrar-todos" href="listar.php">Mostrar todos</a>
                </form>

                <div class="inventario-tabla-wrap">
                <table class="tabla-general inventario-tabla">
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

                                $stockAgotado = ((float) $fila['stock_actual'] <= 0);
                                $claseStock = $stockAgotado ? 'inventario-stock inventario-stock--agotado' : 'inventario-stock';
                                echo "<td class='" . $claseStock . "'>" . htmlspecialchars((string) $fila['stock_actual']) . "</td>";

                                echo "<td><div class='inventario-acciones-fila'>";
                                echo "<a href='editar_articulo.php?id=" . (int) $fila['id_articulo'] . "'>Editar</a>";
                                echo "<a class='btn-eliminar' href='eliminar_articulo.php?id=" . (int) $fila['id_articulo'] . "' onclick='return confirm(\"¿Estás seguro de que deseas ocultar este artículo del catálogo?\");'>Eliminar</a>";
                                echo "</div></td>";

                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No se encontraron artículos.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                </div>
            </div>
        </main>
    </div>
</body>

</html>