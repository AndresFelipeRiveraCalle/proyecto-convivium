<?php

/**
 * Módulo de Inventario - Formulario de Movimientos (PDO)
 */
require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

$query_articulos = "SELECT id_articulo, nombre, unidad_medida FROM articulos WHERE activo = 1 ORDER BY nombre ASC";
$stmt_articulos = $conexion->query($query_articulos);
?>
<!-- (El HTML sigue siendo exactamente el mismo que el anterior, solo cambia la forma de imprimir el select) -->

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
            <h2>Registrar Entrada o Salida</h2>
            <form action="guardar_movimiento.php" method="POST">

                <label for="id_articulo">Seleccione el Artículo:</label><br>
                <select id="id_articulo" name="id_articulo" required>
                    <option value="">-- Seleccione --</option>
                    <?php
                    if ($stmt_articulos->rowCount() > 0) {
                        while ($art = $stmt_articulos->fetch()) {
                            echo "<option value='" . $art['id_articulo'] . "'>" . htmlspecialchars($art['nombre']) . " (" . $art['unidad_medida'] . ")</option>";
                        }
                    }
                    ?>
                </select><br><br>

                <!-- Resto del formulario idéntico al anterior -->
                <label for="tipo_movimiento">Tipo de Movimiento:</label><br>
                <select id="tipo_movimiento" name="tipo_movimiento" required>
                    <option value="entrada">Entrada (Sumar al stock)</option>
                    <option value="salida">Salida (Restar al stock)</option>
                </select><br><br>

                <label for="cantidad">Cantidad:</label><br>
                <input type="number" id="cantidad" name="cantidad" min="1" required><br><br>

                <label for="nota">Justificación / Nota (Opcional):</label><br>
                <input type="text" id="nota" name="nota" placeholder="Ej: Compra mensual, aseo de torre 1"><br><br>

                <button type="submit">Registrar Movimiento</button>
                <a href="listar.php">Cancelar</a>
            </form>
        </main>
    </div>
</body>

</html>