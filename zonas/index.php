<?php

// Importa la conexión a la base de datos
require_once "../config/conexion.php";

// Consulta SQL para obtener todas las zonas comunes
$sql = "SELECT * FROM zona_comun";

// Ejecuta la consulta usando PDO
$resultado = $conexion->query($sql);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Zonas Comunes</title>
</head>

<body>

    <h1>Registrar Zona Común</h1>

    <!-- Formulario zonas comunes -->
    <form action="guardar.php" method="POST">

        <label>Nombre</label>
        <input type="text" name="nombre" required>

        <label>Descripción</label>
        <textarea name="descripcion" required></textarea>

        <label>Capacidad</label>
        <input type="number" name="capacidad" required>

        <label>Horario Disponible</label>
        <input type="text" name="horario_disponible" required>

        <button type="submit">Guardar Zona</button>

    </form>

    <h2>Listado de Zonas Comunes</h2>

    <!-- Tabla de zonas comunes -->
    <table border="1">

        <!-- Encabezados -->
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Capacidad</th>
        </tr>

        <!-- Recorre cada zona obtenida desde la base de datos -->
        <?php while ($fila = $resultado->fetch()) { ?>

            <tr>
                <td><?= $fila['id'] ?></td>
                <td><?= $fila['nombre'] ?></td>
                <td><?= $fila['descripcion'] ?></td>
                <td><?= $fila['capacidad'] ?></td>
            </tr>

        <?php } ?>

    </table>

</body>

</html>