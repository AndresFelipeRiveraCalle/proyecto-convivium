<?php

/**
 * =========================================================================
 * MÓDULO: Zonas Comunes - Convivium
 * ARCHIVO: index.php
 * DESCRIPCIÓN: Permite registrar nuevas zonas comunes y listar las existentes.
 * AUTOR: Andrés Felipe Rivera Calle
 * FECHA: 2026-07-24
 * HOJA DE ESTILOS: assets/css/zonas.css (1 solo link, todo incluido)
 * =========================================================================
 */

// 1. CONEXIÓN A BASE DE DATOS
// Importa la variable $conexion (PDO) desde la config central
require_once "../config/conexion.php";

// 2. MANEJO DE MENSAJES DE RETROALIMENTACIÓN
// Estos mensajes vienen de guardar.php?mensaje=registrado o ?mensaje=existe
// Se usan para mostrar al usuario si la operación fue exitosa o falló
$mensaje_tipo = $_GET['mensaje'] ?? ''; // Si no hay mensaje, queda vacío
$texto_mensaje = '';
$clase_mensaje = ''; // Clase CSS para darle color verde o rojo

// Caso: Se registró correctamente
if ($mensaje_tipo == 'registrado') {
    $texto_mensaje = 'Zona común registrada correctamente';
    $clase_mensaje = 'mensaje-ok'; // Verde - definida en zonas.css
}

// Caso: La zona ya existe (validación por nombre único)
if ($mensaje_tipo == 'existe') {
    $texto_mensaje = 'La zona común ya existe';
    $clase_mensaje = 'mensaje-error-zona'; // Rojo - definida en zonas.css
}

// 3. CONSULTA DE DATOS
// Trae todas las zonas de la tabla zona_comun para mostrarlas en la tabla
// IMPORTANTE: La tabla en BD se llama "zona_comun" (singular)
$sql = "SELECT * FROM zona_comun ORDER BY id ASC";
$resultado = $conexion->query($sql); // Ejecuta la consulta con PDO

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zonas Comunes - Convivium</title>

    <!-- 
        ESTILOS: Solo 1 archivo CSS
        zonas.css ya trae todo: reset, header, menú, formularios y tabla
        Colores usados: #12304A (azul header), #2ecc71 (verde botón), #f1f1f1 (fondo)
        Esto es para que el equipo no tenga que usar 2 links y no se confunda
    -->
    <link rel="stylesheet" href="../assets/css/zonas.css">
</head>

<body>

    <!-- 
        HEADER SUPERIOR
        Mismo diseño que style.css del dashboard
        Color: #12304A
    -->
    <header class="header">
        <div class="logo">
            <img src="../assets/img/logo.png" alt="Logo Convivium">
        </div>
        <div class="usuario">
            <img src="../assets/img/user.png" alt="Usuario">
            <span>Administrador</span>
        </div>
    </header>

    <!-- CONTENEDOR PRINCIPAL: Divide menú lateral + contenido -->
    <div class="contenedor">

        <!-- 
            MENÚ LATERAL
            Color fondo: #34495e
            Activo: #2ecc71
        -->
        <aside class="menu">
            <nav>
                <ul>
                    <li><a href="../dashboard/index.php">Dashboard</a></li>
                    <li class="activo"><a href="index.php">Zonas Comunes</a></li>
                    <!-- Agrega aquí los demás módulos -->
                </ul>
            </nav>
        </aside>

        <!-- 
            CONTENIDO CENTRAL DE ZONAS COMUNES
            Aquí va todo lo de tu módulo
        -->
        <main class="zonas-wrapper">

            <!-- Título principal de la página -->
            <h1 class="zonas-titulo-principal">Registrar Zona Común</h1>

            <!-- 
                BLOQUE DE MENSAJES
                Solo se muestra si $texto_mensaje tiene contenido
                htmlspecialchars() evita inyecciones XSS
            -->
            <?php if ($texto_mensaje): ?>
                <div class="<?= $clase_mensaje ?>">
                    <?= htmlspecialchars($texto_mensaje) ?>
                </div>
            <?php endif; ?>

            <!-- 
                FORMULARIO DE REGISTRO
                Action: guardar.php -> archivo que inserta en BD
                Method: POST -> envía datos ocultos
            -->
            <div class="form-card-zonas">
                <form action="guardar.php" method="POST">

                    <div class="form-row-zonas">
                        <!-- Campo: Nombre de la zona -->
                        <div class="form-group-zona">
                            <label for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre" placeholder="Ej: Salón Social" required>
                        </div>

                        <!-- Campo: Descripción -->
                        <div class="form-group-zona" style="flex:1.5">
                            <label for="descripcion">Descripción</label>
                            <input type="text" id="descripcion" name="descripcion" placeholder="Descripción breve" required>
                        </div>

                        <!-- Campo: Capacidad -->
                        <div class="form-group-zona" style="max-width:120px">
                            <label for="capacidad">Capacidad</label>
                            <input type="number" id="capacidad" name="capacidad" placeholder="50" required min="1">
                        </div>

                        <!-- Campo: Horario Disponible -->
                        <div class="form-group-zona">
                            <label for="horario">Horario Disponible</label>
                            <input type="text" id="horario" name="horario_disponible" placeholder="Ej: 08:00 - 22:00" required>
                        </div>

                        <!-- Botón: Guardar -->
                        <div class="form-group-zona" style="flex:0 0 auto">
                            <label>&nbsp;</label> <!-- Espaciado para alinear con inputs -->
                            <button type="submit" class="btn-guardar-zona">Guardar Zona</button>
                        </div>
                    </div>

                </form>
            </div>

            <!-- Subtítulo para la tabla -->
            <h2 class="zonas-titulo-principal" style="font-size:20px;">Listado de Zonas Comunes</h2>

            <!-- 
                TABLA DE ZONAS
                Muestra los datos traídos de la BD con el while
            -->
            <!-- TABLA DE ZONAS - CON ACCIONES -->
            <div class="card-tabla-zonas">
                <table class="tabla-zonas">
                    <thead>
                        <tr>
                            <th style="width:50px;">ID</th>
                            <th style="width:140px;">Nombre</th>
                            <th>Descripción</th>
                            <th style="width:80px;">Capacidad</th>
                            <th style="width:140px;">Acciones</th> <!-- NUEVA COLUMNA -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($fila = $resultado->fetch(PDO::FETCH_ASSOC)) { ?>
                            <tr>
                                <td><?= $fila['id'] ?></td>
                                <td><strong><?= htmlspecialchars($fila['nombre']) ?></strong></td>
                                <td><?= htmlspecialchars($fila['descripcion']) ?></td>
                                <td><?= $fila['capacidad'] ?></td>
                                <!-- BOTONES EDITAR Y ELIMINAR -->
                                <td>
                                    <div class="acciones-zonas">
                                        <!-- EDITAR: Manda a actualizar.php?id= -->
                                        <a href="actualizar.php?id=<?= $fila['id'] ?>" class="btn-accion btn-editar">Editar</a>

                                        <!-- ELIMINAR: Manda a eliminar.php?id= con confirmación -->
                                        <a href="eliminar.php?id=<?= $fila['id'] ?>"
                                            class="btn-accion btn-eliminar"
                                            onclick="return confirm('¿Seguro que quieres eliminar <?= htmlspecialchars($fila['nombre']) ?>?')">
                                            Eliminar
                                        </a>
                                    </div>
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