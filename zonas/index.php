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

// Incluir la ruta de la conexión a la base de datos y config.php
require_once "../config/config.php";
require_once "../config/conexion.php";

// Consultar a la base de datos todas la zonas comunes organizadas ascendentemente
$sql_listar = "SELECT * FROM zona_comun ORDER BY id ASC";
$resultado = $conexion->query($sql_listar);

// Validar que existen los campos mensaje y tipo de mensaje y que no estén vacíos
if (isset($_GET['tipo']) && !empty($_GET['tipo']) && isset($_GET['mensaje']) && !empty($_GET['mensaje'])) {

    // Capturar las variables de la URL
    $tipo = $_GET['tipo'];
    $mensaje = $_GET['mensaje'];

    // Validar que tipo no vaya a contender algo diferente a éxito o error
    if (!($tipo === 'exito' || $tipo === 'error')) {
        $tipo = 'error';
    }
}

// Cargar la zona común a editar en el formulario
$nombre = '';
$descripcion = '';
$capacidad = '';
$horario = [];

if (isset($_GET['id_editar']) && !empty($_GET['id_editar'])) {

    // Capturar el valor de ID
    $id = $_GET['id_editar'];

    // Preparar la consulta SQL
    $sql_consultar_id = 'SELECT * FROM zona_comun WHERE id = ?';

    try {

        $stmt = $conexion->prepare($sql_consultar_id);
        $stmt->execute([$id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        // Validar si encuentra el ID
        if ($fila) {

            // Asignar los valores de la zona común a las variables
            $nombre = $fila['nombre'];
            $descripcion = $fila['descripcion'];
            $capacidad = $fila['capacidad'];

            // Consultar los horarios de la zona a editar
            $sql_horarios = 'SELECT id, dia_semana, hora_inicio, hora_fin
                            FROM horario_zona
                            WHERE id_zona = ?
                            ORDER BY dia_semana, hora_inicio';

            $stmt_horarios = $conexion->prepare($sql_horarios);
            $stmt_horarios->execute([$id]);

            $horarios = $stmt_horarios->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php require_once "../includes/head.php"; ?>    
    <title>Zonas Comunes</title>
    <!-- Estilos exclusivos del módulo -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/zonas.css?v=<?= time() ?>">
    <!-- Datos enviados desde PHP hacia JavaScript -->
    <script>
        const horariosExistentes = <?= json_encode($horarios ?? []) ?>;
    </script>
    <!-- JavaScript exclusivo del módulo -->
    <script src="<?= BASE_URL ?>assets/js/zonas.js?v=<?= time() ?>" defer></script>
</head>

<body>

    <?php require_once "../includes/header.php"; ?>

    <div class="zc-contenedor">
        <?php require_once "../includes/sidebar.php"; ?>

        <main class="zc-contenido">
            <h1>Administración de Zonas Comunes</h1>
            <div class="zc-card">

                <h2>Datos de la Zona</h2>

                <form id="form-zona" class="zc-form-zona" action="<?= isset($id) ? 'actualizar.php' : 'guardar.php' ?>" method="POST">
                    <div class="zc-form-row">
                        <?php if (isset($id)) { ?>
                            <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                        <?php } ?>
                        <div class="zc-form-group">
                            <label for="nombre">Nombre</label>
                            <input
                                type="text"
                                id="nombre"
                                name="nombre"
                                placeholder="Ej: Salón Social"
                                value="<?= htmlspecialchars($nombre) ?>"
                                required>
                        </div>
                        <div class="zc-form-group">
                            <label for="descripcion">Descripción</label>
                            <input
                                type="text"
                                id="descripcion"
                                name="descripcion"
                                placeholder="Descripción de la zona"
                                value="<?= htmlspecialchars($descripcion) ?>"
                                required>
                        </div>
                        <div class="zc-form-group">
                            <label for="capacidad">Capacidad</label>
                            <input
                                type="number"
                                id="capacidad"
                                name="capacidad"
                                placeholder="capacidad"
                                value="<?= htmlspecialchars($capacidad) ?>"
                                required>
                        </div>
                    </div>

                    <fieldset class="zc-disponibilidad" id="seccion-horarios" disabled>
                        <h3>Días Disponibles</h3>

                        <p id="mensaje-horarios" class="zc-mensaje-horarios">
                            Completa los datos de la zona para configurar los horarios.
                        </p>

                        <div class="zc-dias-semana">
                            <label>
                                <input type="checkbox" name="dias[]" value="1">Lunes
                            </label>
                            <label>
                                <input type="checkbox" name="dias[]" value="2">Martes
                            </label>
                            <label>
                                <input type="checkbox" name="dias[]" value="3">Miércoles
                            </label>
                            <label>
                                <input type="checkbox" name="dias[]" value="4">Jueves
                            </label>
                            <label>
                                <input type="checkbox" name="dias[]" value="5">Viernes
                            </label>
                            <label>
                                <input type="checkbox" name="dias[]" value="6">Sábado
                            </label>
                            <label>
                                <input type="checkbox" name="dias[]" value="7">Domingo
                            </label>
                        </div>

                        <div class="zc-horario-form">
                            <div class="zc-form-group">
                                <label for="hora_inicio">Hora de inicio</label>
                                <input type="time" id="hora_inicio" name="hora_inicio">
                            </div>
                            <div class="zc-form-group">
                                <label for="hora_fin">Hora de fin</label>
                                <input type="time" id="hora_fin" name="hora_fin">
                            </div>
                        </div>

                        <button type="button" id="btn-agregar-horario" class="zc-btn zc-btn-secundario">
                            Agregar horario
                        </button>

                        <div id="horarios-configurados" class="zc-horarios-configurados"> <!-- Mostrar visualmente los horarios al usuario -->
                            <h3 id="titulo-horarios">Horarios Configurados</h3>
                        </div>
                        <div id="horarios-inputs"></div> <!-- Contener los Inputs hidden que posteriormente recibirá PHP -->
                    </fieldset>

                    <div class="zc-form-botones">
                        <button type="submit" class="zc-btn zc-btn-principal">
                            <?= isset($id) ? 'Actualizar Zona' : 'Registrar Zona' ?> <!-- Operador ternario si existe la variable $id cambia a modo actualizar sino permanece en modo guardar -->
                        </button>

                        <?php if (isset($id)) { ?> <!-- Si existe $id se genera el botón cancelar -->
                            <a href="index.php" class="zc-btn zc-btn-cancelar">Cancelar</a>
                        <?php } ?>
                    </div>
                </form>

                <?php if (isset($mensaje)) { ?>
                    <div id="mensaje-alerta" class="zc-mensaje zc-mensaje-<?= htmlspecialchars($tipo) ?>"> <!-- convertir los caracteres especiales en entidades HTML y el navegador los muestra como texto, no como código. -->
                        <?= htmlspecialchars($mensaje) ?>
                    </div>
                <?php } ?>

                <hr class="zc-separador-listado">

                <h2>Listado de Zonas Comunes</h2>

                <table class="zc-tabla-zonas">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Capacidad</th>
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
                                <td>
                                    <a href="index.php?id_editar=<?= $fila['id'] ?>" class="zc-btn-tabla zc-btn-editar">Editar</a>
                                    <a href="eliminar.php?id=<?= $fila['id'] ?>" class="zc-btn-tabla zc-btn-eliminar">Eliminar</a>
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