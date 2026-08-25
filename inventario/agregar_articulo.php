<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";
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
                <h2>Registrar Nuevo Artículo</h2>
                
                <!-- Formulario que envía los datos por POST a guardar_articulo.php -->
                <form action="guardar_articulo.php" method="POST">
                    
                    <label for="nombre">Nombre del artículo:</label><br>
                    <input type="text" id="nombre" name="nombre" required placeholder="Ej: Cloro, Escoba, Bombillo"><br><br>
                    
                    <label for="categoria">Categoría:</label><br>
                    <input type="text" id="categoria" name="categoria" placeholder="Ej: Aseo, Mantenimiento, Mobiliario"><br><br>
                    
                    <label for="unidad_medida">Unidad de medida:</label><br>
                    <select id="unidad_medida" name="unidad_medida" required>
                        <option value="Unidad">Unidad</option>
                        <option value="Galón">Galón</option>
                        <option value="Litro">Litro</option>
                        <option value="Caja">Caja</option>
                        <option value="Paquete">Paquete</option>
                    </select><br><br>
                    
                    <button type="submit">Guardar en Catálogo</button>
                    <a href="listar.php">Cancelar</a>
                </form>
            </div>
        </main>
    </div>
</body>
</html>