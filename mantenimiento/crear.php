
<?php
require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/auth/requerir_sesion.php";
require_once ROOT_PATH . "/config/conexion.php";

$stmtZonas = $conexion->query(
    "SELECT id, nombre FROM zona_comun ORDER BY nombre"
);
$zonas = $stmtZonas->fetchAll();

$stmtUsuarios = $conexion->query(
    "SELECT id, nombres, apellidos FROM usuario ORDER BY nombres"
);
$usuarios = $stmtUsuarios->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <?php include ROOT_PATH . "/includes/head.php"; ?>

    <link rel="stylesheet"
          href="<?php echo BASE_URL; ?>assets/css/mantenimiento.css?v=<?php echo time(); ?>">
</head>

<body>

    <?php include ROOT_PATH . "/includes/header.php"; ?>
    <?php require_once ROOT_PATH . "/includes/mensajes.php"; ?>

    <div class="contenedor">

        <?php include ROOT_PATH . "/includes/sidebar.php"; ?>

        <main class="mantenimiento-crear">

            <!-- Encabezado -->
            <div class="mantenimiento-encabezado">
                <div>
                    <span class="mantenimiento-etiqueta">
                        GESTIÓN DE MANTENIMIENTO
                    </span>

                    <h1>Registrar mantenimiento</h1>

                    <p>
                        Registra una nueva solicitud y realiza seguimiento
                        a las novedades de las zonas comunes.
                    </p>
                </div>
            </div>

            <form action="guardar.php"
                  method="POST"
                  enctype="multipart/form-data"
                  class="formulario-mantenimiento">

                <!-- TARJETA 1: INFORMACIÓN GENERAL -->
                <section class="bloque filtros mantenimiento-card">

                    <div class="mantenimiento-card-titulo">
                        <div class="mantenimiento-icono">
                            <i class="fa-solid fa-building"></i>
                        </div>

                        <div>
                            <h3>Información general</h3>
                            <p>Identifica la zona y el responsable del reporte.</p>
                        </div>
                    </div>

                    <div class="mantenimiento-grid">

                        <div class="campo-mantenimiento">
                            <label for="zona_id">Zona común</label>

                            <select id="zona_id" name="zona_id" required>
                                <option value="">Seleccione una zona</option>

                                <?php foreach ($zonas as $zona): ?>
                                    <option value="<?php echo htmlspecialchars($zona['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($zona['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="campo-mantenimiento">
                            <label for="usuario_reporta_id">Solicitante</label>

                            <select id="usuario_reporta_id"
                                    name="usuario_reporta_id"
                                    required>

                                <option value="">Seleccione un usuario</option>

                                <?php foreach ($usuarios as $u): ?>
                                    <option value="<?php echo htmlspecialchars($u['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($u['nombres'] . ' ' . $u['apellidos'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="campo-mantenimiento">
                            <label for="prioridad">Prioridad</label>

                            <select id="prioridad" name="prioridad">
                                <option value="baja">Baja - No es urgente</option>
                                <option value="media" selected>
                                    Media - Se puede esperar unos días
                                </option>
                                <option value="alta">
                                    Alta - Urgente, atender pronto
                                </option>
                            </select>
                        </div>

                    </div>
                </section>


                <!-- TARJETA 2: DETALLES DEL MANTENIMIENTO -->
                <section class="bloque filtros mantenimiento-card">

                    <div class="mantenimiento-card-titulo">
                        <div class="mantenimiento-icono">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                        </div>

                        <div>
                            <h3>Detalles del mantenimiento</h3>
                            <p>Describe la novedad y establece su estado actual.</p>
                        </div>
                    </div>

                    <div class="mantenimiento-grid">

                        <div class="campo-mantenimiento campo-completo">
                            <label for="descripcion">
                                Descripción del problema
                            </label>

                            <textarea
                                id="descripcion"
                                class="textarea-tareas"
                                name="descripcion"
                                required
                                placeholder="Describe detalladamente el problema..."
                            ></textarea>
                        </div>

                        <div class="campo-mantenimiento">
                            <label for="estado">Estado actual</label>

                            <select id="estado" name="estado">
                                <option value="pendiente" selected>
                                    Pendiente
                                </option>

                                <option value="en_proceso">
                                    En proceso
                                </option>

                                <option value="solucionado">
                                    Solucionado
                                </option>
                            </select>
                        </div>

                        <div class="campo-mantenimiento">
                            <label for="fecha_solucion">
                                Fecha tentativa de solución
                            </label>

                            <input
                                type="datetime-local"
                                id="fecha_solucion"
                                name="fecha_solucion"
                            >
                        </div>

                    </div>
                </section>


                <!-- TARJETA 3: EVIDENCIAS -->
                <section class="bloque filtros mantenimiento-card">

                    <div class="mantenimiento-card-titulo">
                        <div class="mantenimiento-icono mantenimiento-icono-verde">
                            <i class="fa-solid fa-paperclip"></i>
                        </div>

                        <div>
                            <h3>Evidencias y documentación</h3>
                            <p>Adjunta fotografías o documentos de soporte.</p>
                        </div>
                    </div>

                    <div class="mantenimiento-ayuda">
                        <i class="fa-solid fa-circle-info"></i>

                        <span>
                            Si el mantenimiento ya fue solucionado, adjunta
                            una fotografía o PDF como evidencia y cambia
                            el estado a "Solucionado".
                        </span>
                    </div>

                    <div class="campo-mantenimiento">
                        <label for="evidencia">Fotografía o documento PDF</label>

                        <input
                            type="file"
                            id="evidencia"
                            name="evidencia"
                            accept="image/*,application/pdf"
                        >

                        <small>
                            Formatos permitidos: imágenes y documentos PDF.
                        </small>
                    </div>

                </section>


                <!-- BOTONES -->
                <div class="mantenimiento-acciones">

                    <a href="listar.php" class="btn-cancelar-mantenimiento">
                        <i class="fa-solid fa-arrow-left"></i>
                        Cancelar
                    </a>

                    <button type="submit" class="btn-guardar-mantenimiento">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Guardar mantenimiento
                    </button>

                </div>

            </form>

        </main>

    </div>

</body>
</html>
