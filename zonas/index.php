<?php

/**
 * =========================================================================
 * MÓDULO: Zonas Comunes - Convivium
 * ARCHIVO: index.php
 * DESCRIPCIÓN: Permite registrar nuevas zonas comunes y listar las existentes.
 * AUTOR: Andrés Felipe Rivera Calle
 * FECHA: 2026-07-24
 * HOJA DE ESTILOS: assets/css/zonas.css 
 * =========================================================================
 */

// Importa la conexión a la base de datos 
require_once "../config/conexion.php";

// Consulta a la base de datos todas la zonas comunes organizadas ascendentemente
$sql = "SELECT * FROM zona_comun ORDER BY id ASC";
$resultado = $conexion->query($sql);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zonas Comunes</title>
    <link rel="stylesheet" href="../assets/css/zonas.css">
</head>

<body>

    <header class="header">

        <div class="logo">
            <h2>Convivium</h2>
        </div>

        <div class="usuario">
            <span>Administrador</span>
        </div>

    </header>

    <div class="contenedor">
        <aside class="menu">

            <nav>
                <ul>
                    <li>
                        <a href="../dashboard/index.php">Dashboard</a>
                    </li>
                    <li class="activo">
                        <a href="index.php">Zonas comunes</a>
                    </li>
                </ul>
            </nav>

        </aside>

        <main class="contenido">
            <h1>Zonas Comunes</h1>
            <div class="card">
                <form action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">Nombre</label>
                            <input
                                type="text"
                                id="nombre"
                                name="nombre"
                                placeholder="Ej: Salón Social">
                        </div>
                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <input
                                type="text"
                                id="descripcion"
                                name="descripcion"
                                placeholder="Descripción de la zona">
                        </div>
                        <div class="form-group">
                            <label for="capacidad">Capacidad</label>
                            <input
                                type="number"
                                id="capacidad"
                                name="capacidad"
                                placeholder="capacidad">
                        </div>
                        <div class="form-group">
                            <label for="horario">Horario Disponible</label>
                            <input
                                type="text"
                                id="horario"
                                name="horario"
                                placeholder="Horario Disponible">
                        </div>
                    </div>
                    <div class="form-botones">
                        <button type="submit" class="btn-guardar">Guardar Zona</button>
                    </div>
                </form>

                <h2>Listado de Zonas Comunes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Capacidad</th>
                            <th>Horario Disponible</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($fila = $resultado->fetch(PDO::FETCH_ASSOC)) { ?>
                            <tr>
                                <td><?= $fila['id'] ?></td>
                                <td><?= $fila['nombre'] ?></td>
                                <td><?= $fila['descripcion'] ?></td>
                                <td><?= $fila['capacidad'] ?></td>
                                <td><?= $fila['horario_disponible'] ?></td>
                                <td>
                                    <a href="index.php?editar=<?= $fila['id'] ?>">Editar</a>
                                    <a href="eliminar.php?id=<?= $fila['id'] ?>">Eliminar</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

</body>

</html>