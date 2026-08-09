<?php
require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

$stmtEstadosCiviles = $conexion->query("SELECT * FROM estados_civiles ORDER BY nombre");
$estadosCiviles = $stmtEstadosCiviles->fetchAll(PDO::FETCH_ASSOC);

$stmtOcupaciones = $conexion->query("SELECT * FROM ocupaciones ORDER BY nombre");
$ocupaciones = $stmtOcupaciones->fetchAll(PDO::FETCH_ASSOC);

$stmtTiposDocumento = $conexion->query("SELECT * FROM tipos_documento ORDER BY nombre");
$tiposDocumento = $stmtTiposDocumento->fetchAll(PDO::FETCH_ASSOC);

$stmtGeneros = $conexion->query("SELECT * FROM generos ORDER BY nombre");
$generos = $stmtGeneros->fetchAll(PDO::FETCH_ASSOC);

?>

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

            <h1 align="center">Tablas Maestras</h1>

            <div class="grid-configuracion">

                <!-- Tipos de documento -->
                <div class="bloque filtros">
                    <div class="form-card">
                        <h3>Tipos de Documento</h3>

                        <p>Administración de documentos de identificación</p>
                        <table class="tabla">
                            <thead>
                                <tr>
                                    <th>Codigo</th>
                                    <th>Nombre</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($tiposDocumento as $tipo): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($tipo['codigo']) ?></td>
                                        <td><?= htmlspecialchars($tipo['nombre']) ?></td>

                                        <td><?php if ($tipo['estado'] == 1): ?>
                                                <span class="activo">Activo</span>
                                            <?php else: ?>
                                                <span class="inactivo">Inactivo</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <button class="btn-filtrar btnEditarTipoDocumento"
                                                data-id="<?= $tipo['id_tipo_documento'] ?>">
                                                ✏ Editar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="button" id="btnNuevoTipoDocumento" class="btn-filtrar btnNuevoTipoDocumento">
                            Agregar
                        </button>
                    </div>

                    <div class="form-card">
                        ---
                    </div>


                    <!-- Estados civiles -->

                    <div class="form-card">
                        <h3>Estados Civiles</h3>

                        <p>Estado civil de las personas</p>
                        <table class="tabla">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($estadosCiviles as $estado): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($estado['nombre']) ?></td>

                                        <td>
                                            <?php if ($estado['estado'] == 1): ?>
                                                <span class="activo">Activo</span>
                                            <?php else: ?>
                                                <span class="inactivo">Inactivo</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <button class="btn-filtrar btnEditarEstadoCivil"
                                                data-id="<?= $estado['id_estado_civil'] ?>">
                                                ✏ Editar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <button type="button" id="btnNuevoEstadoCivil" class="btn-filtrar btnNuevoEstadoCivil">
                            Agregar
                        </button>
                    </div>


                    <!-- Ocupaciones -->

                    <div class="bloque filtros">
                        <div class="form-card">
                            <h3>Ocupaciones</h3>

                            <p>Actividad laboral o profesión</p>

                            <table class="tabla">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ocupaciones as $ocupacion): ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($ocupacion['nombre']) ?>
                                            </td>

                                            <td>
                                                <?php if ($ocupacion['estado'] == 1): ?>
                                                    <span class="activo">Activo</span>
                                                <?php else: ?>
                                                    <span class="inactivo">Inactivo</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <button class="btn-filtrar btnEditarOcupacion"
                                                    data-id="<?= $ocupacion['id_ocupacion'] ?>">
                                                    ✏ Editar
                                                </button>
                                            </td>
                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        <button type="button" id="btnNuevaOcupacion" class="btn-filtrar btnNuevaOcupacion">
                            Agregar
                        </button>
                        </div>

                        <div class="form-card">
                            ---
                        </div>

                        <!-- Géneros -->
                        <div class="form-card">


                            <h3>Géneros</h3>
                            <p>Configuración de géneros</p>
                            <table class="tabla">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($generos as $genero): ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($genero['nombre']) ?>
                                            </td>

                                            <td>
                                                <?php if ($genero['estado'] == 1): ?>
                                                    <span class="activo">Activo</span>
                                                <?php else: ?>
                                                    <span class="inactivo">Inactivo</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <button class="btn-filtrar btnEditarGenero"
                                                    data-id="<?= $genero['id_genero'] ?>">
                                                    ✏ Editar
                                                </button>
                                            </td>
                                        </tr>

                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <button type="button" id="btnNuevoGenero" class="btn-filtrar btnNuevoGenero">
                                Agregar
                            </button>

                        </div>
                    </div>
                </div>
        </main>

        <!-- =====================================================
            MODAL NUEVO TIPO DE DOCUMENTO
        ====================================================== -->

        <div id="modalNuevoTipoDocumento" class="modal">
            <div class="modal-contenido">
                <span class="cerrar-modal" id="cerrarNuevoTipoDocumento">
                    &times;
                </span>

                <h2>Nuevo Tipo de Documento</h2>

                <form action="../actions/guardar_tipo_documento.php" method="POST">
                    <div class="form-group">
                        <label>Código</label>
                        <input type="text" name="codigo" maxlength="10" required>
                    </div>

                    <div class="form-group">
                        <label>Nombre</label>
                        <input
                            type="text" name="nombre" maxlength="100" required>
                    </div>

                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>

                    <div class="acciones-modal">
                        <button type="submit" class="btn-primary">
                            Guardar
                        </button>

                        <button
                            type="button" class="btn-secondary" id="cancelarNuevoTipoDocumento">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>


        <!-- MODAL ESTADO CIVIL -->

        <div id="modalNuevoEstadoCivil" class="modal">
            <div class="modal-contenido">
                <span class="cerrar-modal" id="cerrarNuevoEstadoCivil">
                    &times;
                </span>

                <h2>Nuevo Estado Civil </h2>

                <form action="../actions/guardar_estado_civil.php" method="POST">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="nombre" required>
                    </div>

                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado">
                            <option value="1">
                                Activo
                            </option>

                            <option value="0">
                                Inactivo
                            </option>
                        </select>
                    </div>

                    <div class="acciones-modal">
                        <button type="submit" class="btn-primary">
                            Guardar
                        </button>

                        <button type="button" class="btn-secondary" id="cancelarNuevoEstadoCivil">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- =====================================================
            MODAL NUEVA OCUPACIÓN
        ====================================================== -->

        <div id="modalNuevaOcupacion" class="modal">
            <div class="modal-contenido">

                <span class="cerrar-modal" id="cerrarNuevaOcupacion">
                    &times;
                </span>

                <h2>Nueva Ocupación</h2>

                <form  action="../actions/guardar_ocupacion.php" method="POST">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input
                            type="text" name="nombre" maxlength="100" required>
                    </div>

                    <div class="form-group">
                        <label>Estado</label>

                        <select name="estado">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>

                    <div class="acciones-modal">
                        <button type="submit" class="btn-primary">
                            Guardar
                        </button>

                        <button
                            type="button" class="btn-secondary" id="cancelarNuevaOcupacion">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- =====================================================
            MODAL NUEVO GÉNERO
        ====================================================== -->

        <div id="modalNuevoGenero" class="modal">
            <div class="modal-contenido">
                <span  class="cerrar-modal" id="cerrarNuevoGenero">
                    &times;
                </span>

                <h2>Nuevo Género</h2>
                <form action="../actions/guardar_genero.php" method="POST">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="nombre" maxlength="50" required>
                    </div>

                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    <div class="acciones-modal">
                        <button type="submit" class="btn-primary">
                            Guardar
                        </button>

                        <button type="button" class="btn-secondary" id="cancelarNuevoGenero">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>


        <!-- =====================================================
            MODAL EDITAR TIPO DE DOCUMENTO
        ====================================================== -->
        <div id="modalEditarTipoDocumento" class="modal">
            <div class="modal-contenido">
                <span class="cerrar-modal" id="cerrarEditarTipoDocumento">
                    &times;
                </span>

                <h2>Editar Tipo de Documento</h2>
                <form action="../actions/editar_tipo_documento.php" method="POST">
                    <!-- ID -->
                    <input type="hidden" name="id_tipo_documento" id="editar_id_tipo_documento">

                    <!-- CÓDIGO -->
                    <div class="form-group">
                        <label>Código </label>
                        <input type="text" name="codigo" id="editar_codigo_tipo_documento" maxlength="20" required>
                    </div>

                    <!-- NOMBRE -->
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="nombre" id="editar_nombre_tipo_documento"maxlength="100"required>
                    </div>

                    <!-- ESTADO -->
                    <div class="form-group">
                        <label>Estado</label>

                        <select name="estado" id="editar_estado_tipo_documento">
                            <option value="1">
                                Activo
                            </option>

                            <option value="0">
                                Inactivo
                            </option>
                        </select>
                    </div>

                    <!-- ACCIONES -->
                    <div class="acciones-modal">
                        <button type="submit" class="btn-primary">
                            Actualizar
                        </button>

                        <button type="button" class="btn-secondary" id="cancelarEditarTipoDocumento">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>


        <!-- =====================================================
            MODAL EDITAR ESTADO CIVIL
        ====================================================== -->
        <div id="modalEditarEstadoCivil" class="modal">

            <div class="modal-contenido">
                <span class="cerrar-modal" id="cerrarEditarEstadoCivil">
                    &times;
                </span>

                <h2>Editar Estado Civil</h2>

                <form action="../actions/editar_estado_civil.php" method="POST">

                    <!-- ID OCULTO -->
                    <input type="hidden" name="id_estado_civil" id="editar_id_estado_civil">

                    <div class="form-group">
                        <label>Nombre </label>
                        <input type="text" name="nombre" id="editar_nombre_estado_civil" maxlength="100" required>
                    </div>

                    <div class="form-group">
                        <label>Estado </label>
                        <select name="estado" id="editar_estado_estado_civil">
                            <option value="1">
                                Activo
                            </option>

                            <option value="0">
                                Inactivo
                            </option>
                        </select>
                    </div>

                    <div class="acciones-modal">
                        <button type="submit" class="btn-primary">
                            Actualizar
                        </button>

                        <button type="button" class="btn-secondary" id="cancelarEditarEstadoCivil">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- =====================================================
            MODAL EDITAR OCUPACIÓN
        ====================================================== -->

        <div id="modalEditarOcupacion" class="modal">
            <div class="modal-contenido">
                <span class="cerrar-modal" id="cerrarEditarOcupacion">
                    &times;
                </span>

                <h2>Editar Ocupación</h2>

                <form action="../actions/editar_ocupacion.php" method="POST">
                    <input type="hidden" name="id_ocupacion" id="editar_id_ocupacion">
                    <div class="form-group">
                        <label>Nombre </label>
                        <input type="text" name="nombre" id="editar_nombre_ocupacion" maxlength="100" required>
                    </div>

                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado" id="editar_estado_ocupacion">
                            <option value="1">
                                Activo
                            </option>

                            <option value="0">
                                Inactivo
                            </option>
                        </select>
                    </div>

                    <div class="acciones-modal">
                        <button type="submit" class="btn-primary">
                            Actualizar
                        </button>

                        <button type="button" class="btn-secondary" id="cancelarEditarOcupacion">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- =====================================================
            MODAL EDITAR GÉNERO
        ====================================================== -->

        <div id="modalEditarGenero" class="modal">
            <div class="modal-contenido">
                <span class="cerrar-modal" id="cerrarEditarGenero">
                    &times;
                </span>

                <h2>Editar Género</h2>

                <form action="../actions/editar_genero.php" method="POST">
                    <input type="hidden" name="id_genero" id="editar_id_genero">
                    <div class="form-group">
                        <label>Nombre </label>
                        <input type="text" name="nombre" id="editar_nombre_genero" maxlength="100" required>
                    </div>

                    <div class="form-group">
                        <label>Estado </label>
                        <select name="estado" id="editar_estado_genero">
                            <option value="1">
                                Activo
                            </option>

                            <option value="0">
                                Inactivo
                            </option>
                        </select>
                    </div>

                    <div class="acciones-modal">
                        <button type="submit" class="btn-primary">
                            Actualizar
                        </button>

                        <button type="button" class="btn-secondary" id="cancelarEditarGenero">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>


    </div>

</body>

</html>