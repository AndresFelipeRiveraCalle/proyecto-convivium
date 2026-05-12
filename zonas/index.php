<?php

// Importa la conexión a la base de datos
require_once "../config/conexion.php";

// Mensaje confirmación
if (isset($_GET['mensaje'])) {

    if ($_GET['mensaje'] == 'registrado') {
        echo "<p>Zona común registrada correctamente</p>";
    }

    if ($_GET['mensaje'] == 'existe') {
        echo "<p>La zona común ya existe</p>";
    }
}

// Consulta SQL para obtener todas las zonas comunes
$sql = "SELECT * FROM zona_comun";

// Ejecuta la consulta usando PDO
$resultado = $conexion->query($sql);

$modo_edicion = false;
$id = '';
$nombre = '';
$descripcion = '';
$capacidad = '';
$horario = '';

// Editar
if (isset($_GET['editar'])) {

    $modo_edicion = true;
    $id = $_GET['editar'];

    $sql_editar = "SELECT * FROM zona_comun WHERE id = :id";
    $stmt_editar = $conexion->prepare($sql_editar);
    $stmt_editar->execute([
        ':id' => $id
    ]);

    $zona = $stmt_editar->fetch();

    if ($zona) {
        $id = $zona['id'];
        $nombre = $zona['nombre'];
        $descripcion = $zona['descripcion'];
        $capacidad = $zona['capacidad'];
        $horario = $zona['horario_disponible'];
    }
}

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
    <form action="<?= $modo_edicion ? 'actualizar.php' : 'guardar.php' ?>" method="POST">

        <input type="hidden" name="id" value="<?= $id ?>">

        <label>Nombre</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>

        <label>Descripción</label>
        <textarea name="descripcion" required><?= htmlspecialchars($descripcion) ?></textarea>

        <label>Capacidad</label>
        <input type="number" name="capacidad" value="<?= htmlspecialchars($capacidad) ?>" required>

        <label>Horario Disponible</label>
        <input type="text" name="horario_disponible" value="<?= htmlspecialchars($horario) ?>" required>

        <button type="submit">
            <?= $modo_edicion ? 'Actualizar Zona' : 'Guardar Zona' ?>
        </button>
        <a href="index.php">
            <button type="button">Cancelar</button>
        </a>

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
            <th>Horario Disponible</th>
            <th>Acciones</th>
        </tr>

        <!-- Recorre cada zona obtenida desde la base de datos -->
        <?php while ($fila = $resultado->fetch()) { ?>

            <tr>
                <td><?= $fila['id'] ?></td>
                <td><?= $fila['nombre'] ?></td>
                <td><?= $fila['descripcion'] ?></td>
                <td><?= $fila['capacidad'] ?></td>
                <td><?= $fila['horario_disponible'] ?></td>
                <td>
                    <a href="index.php?editar=<?= $fila['id'] ?>">Editar /</a>
                    <a href="eliminar.php?id=<?= $fila['id'] ?>">Eliminar</a>
                </td>
            </tr>

        <?php } ?>

    </table>

</body>

</html>