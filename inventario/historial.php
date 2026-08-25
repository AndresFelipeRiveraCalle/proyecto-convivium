<?php

/**
 * Módulo de Inventario - Historial de Movimientos (Kardex)
 * Propósito: Mostrar una auditoría completa uniendo las tablas de movimientos, artículos y usuarios.
 */

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// Consulta SQL con INNER JOIN para traer los nombres reales en lugar de los IDs
$query = "SELECT 
            m.fecha_movimiento, 
            a.nombre AS articulo, 
            u.nombres AS usuario, 
            m.tipo_movimiento, 
            m.cantidad, 
            m.nota 
            FROM movimientos_inventario m
            INNER JOIN articulos a ON m.id_articulo = a.id_articulo
            LEFT JOIN usuario u ON m.id_usuario = u.id
            ORDER BY m.fecha_movimiento DESC";

$stmt = $conexion->query($query);
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

            <h2>Historial de Movimientos (Auditoría)</h2>

            <div class="acciones">
                <a href="listar.php">Volver al Inventario</a>
            </div>
            <br>

            <table border="1" cellpadding="10">
                <thead>
                    <tr>
                        <th>Fecha y Hora</th>
                        <th>Artículo</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Usuario</th>
                        <th>Observación / Nota</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($stmt->rowCount() > 0) {
                        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>";
                            echo "<td>" . $fila['fecha_movimiento'] . "</td>";
                            echo "<td>" . htmlspecialchars($fila['articulo']) . "</td>";
                            echo "<td>" . htmlspecialchars($fila['tipo_movimiento']) . "</td>";
                            echo "<td>" . $fila['cantidad'] . "</td>";

                            // Validamos si el usuario existe, si no, mostramos 'Sistema'
                            $nombre_usuario = $fila['usuario'] ? htmlspecialchars($fila['usuario']) : 'Sistema';
                            echo "<td>" . $nombre_usuario . "</td>";

                            echo "<td>" . htmlspecialchars($fila['nota']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6'>No hay movimientos registrados en el historial.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </main>
    </div>
</body>

</html>