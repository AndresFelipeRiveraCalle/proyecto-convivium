<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

try {
    $sql = "SELECT  u.id, u.nombres, u.apellidos, u.numero_documento, u.correo,
                u.telefono, u.celular, u.foto,u.estado,u.fecha_creacion,
                td.nombre AS tipo_documento
            FROM usuario u
            LEFT JOIN tipos_documento td ON td.id_tipo_documento = u.id_tipo_documento
            ORDER BY u.apellidos ASC, u.nombres ASC";

    $stmt = $conexion->prepare($sql);
    $stmt->execute();

    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al consultar usuarios: " . $e->getMessage());
}

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
        <main>
            <!-- ==========================================
                 ENCABEZADO
            ========================================== -->

            <div class="acciones-superior">
                <div>
                    <h2>Usuarios</h2>
                    <small> Personas registradas en el sistema </small>
                </div>

                <button type="button" class="btn-filtrar" id="btnNuevoUsuario">
                    + Nuevo usuario
                </button>
            </div>

            <!-- ==========================================
                 TABLA DE USUARIOS
            ========================================== -->
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nombre</th>
                        <th>Documento</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td
                                colspan="9"
                                style="text-align:center;">
                                No existen usuarios registrados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <!-- FOTO -->
                                <td>
                                    <?php
                                    $foto = !empty($u['foto'])
                                        ? BASE_URL . "uploads/usuarios/" . $u['foto']
                                        : BASE_URL . "assets/img/avatar.png";
                                    ?>

                                    <img
                                        src="<?= htmlspecialchars($foto) ?>"
                                        alt="Foto"
                                        style="
                                            width:45px;
                                            height:45px;
                                            object-fit:cover;
                                            border-radius:50%;
                                        ">
                                </td>

                                <!-- NOMBRE -->
                                <td>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $u['nombres'] . " " . $u['apellidos']
                                        ) ?>
                                    </strong>
                                </td>

                                <!-- DOCUMENTO -->
                                <td>
                                    <?= htmlspecialchars(
                                        $u['numero_documento'] ?? ''
                                    ) ?>
                                </td>

                                <!-- CORREO -->
                                <td>
                                    <?= htmlspecialchars(
                                        $u['correo'] ?? ''
                                    ) ?>
                                </td>

                                <!-- TELÉFONO -->
                                <td>
                                    <?= htmlspecialchars(
                                        $u['celular'] ?? $u['telefono'] ?? ''
                                    ) ?>
                                </td>

                                <!-- ESTADO -->
                                <td>
                                    <?php if ($u['estado'] == 1): ?>
                                        <span class="estado-activo">
                                            Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="estado-inactivo">
                                            Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- ACCIONES -->
                                <td>
                                    <button
                                        type="button"
                                        class="btn-secondary btnEditarUsuario"
                                        data-id="<?= $u['id'] ?>">
                                        ✏ Editar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>

        <!-- ==========================================
             MODAL NUEVO USUARIO
        ========================================== -->

        <div id="modalUsuario" class="modal">
            <div class="modal-contenido">
                <span id="cerrarUsuario" class="cerrar">
                    &times;
                </span>

                <h2>Nuevo usuario</h2>
                <br>

                <form action="<?= BASE_URL ?>actions/guardar_persona.php" method="POST"
                    enctype="multipart/form-data">

                    <!-- ==================================
                         DATOS PERSONALES
                    ================================== -->
                    <h3>Datos personales</h3>

                    <div class="form-group">
                        <div>
                            <label>Nombres *</label>
                            <input type="text" name="nombres" maxlength="100" required>
                        </div>

                        <div>
                            <label>Apellidos *</label>
                            <input type="text" name="apellidos" maxlength="100" required>
                        </div>
                    </div>
                    <br>

                    <div class="form-group">
                        <div>
                            <label>Tipo de documento</label>

                            <select name="id_tipo_documento">
                                <option value="">
                                    Seleccione...
                                </option>

                                <?php
                                    $sqlTipos = " SELECT id_tipo_documento, nombre
                                        FROM tipos_documento
                                        WHERE estado = 1
                                        ORDER BY nombre ASC
                                    ";

                                    $stmtTipos = $conexion->query($sqlTipos);
                                    $tiposDocumento = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

                                ?>

                                <?php foreach ($tiposDocumento as $tipo): ?>

                                    <option
                                        value="<?= $tipo['id_tipo_documento'] ?>">

                                        <?= htmlspecialchars(
                                            $tipo['nombre']
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label>Número de documento</label>

                            <input type="text" name="numero_documento" maxlength="30">
                        </div>
                    </div>
                    <br>

                    <!-- ==================================
                         CONTACTO
                    ================================== -->
                    <h3>Información de contacto</h3>

                    <label>Correo electrónico</label>
                    <input type="email" name="correo" maxlength="150">

                    <br><br>

                    <div class="form-group">
                        <div>
                            <label>Teléfono</label>
                            <input type="text" name="telefono" maxlength="20">
                        </div>

                        <div>
                            <label>Celular</label>

                            <input type="text" name="celular" maxlength="20">
                        </div>
                    </div>
                    <br>

                    <!-- ==================================
                         DATOS ADICIONALES
                    ================================== -->

                    <h3>Información adicional</h3>
                    <div class="form-group">
                        <div>
                            <label>Fecha de nacimiento</label>
                            <input type="date" name="fecha_nacimiento">
                        </div>

                        <div>
                            <label>Género</label>
                            <select name="id_genero">
                                <option value="">
                                    Seleccione...
                                </option>
                                <?php
                                    $sqlGeneros = " SELECT id_genero, nombre
                                        FROM generos
                                        WHERE estado = 1
                                        ORDER BY nombre ASC
                                    ";
                                    $stmtGeneros =
                                        $conexion->query($sqlGeneros);
                                    $generos =
                                        $stmtGeneros->fetchAll(PDO::FETCH_ASSOC);
                                ?>

                                <?php foreach ($generos as $genero): ?>
                                    <option
                                        value="<?= $genero['id_genero'] ?>">
                                        <?= htmlspecialchars(
                                            $genero['nombre']
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <br>

                    <div class="form-group">
                        <div>
                            <label>Estado civil</label>
                            <select name="id_estado_civil">
                                <option value="">
                                    Seleccione...
                                </option>
                                <?php
                                    $sqlEstados = " SELECT id_estado_civil, nombre
                                        FROM estados_civiles
                                        WHERE estado = 1
                                        ORDER BY nombre ASC
                                    ";
                                    $stmtEstados = $conexion->query($sqlEstados);
                                    $estadosCiviles =$stmtEstados->fetchAll(PDO::FETCH_ASSOC);
                                ?>

                                <?php foreach ($estadosCiviles as $estadoCivil): ?>
                                    <option
                                        value="<?= $estadoCivil['id_estado_civil'] ?>">
                                        <?= htmlspecialchars(
                                            $estadoCivil['nombre']
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label>Ocupación</label>
                            <input type="text" name="id_ocupacion" maxlength="100">
                        </div>
                    </div>
                    <br>

                    <!-- ==================================
                         DIRECCIÓN
                    ================================== -->
                    <h3>Dirección</h3>
                    <label>Dirección</label>
                    <input type="text" name="direccion" maxlength="200">
                    <br><br>

                    <!-- ==================================
                         FOTO
                    ================================== -->

                    <h3>Foto</h3>
                    <label>Foto</label>
                    <input type="file" name="foto" accept="image/*">
                    <br><br>

                    <!-- ==================================
                         ESTADO
                    ================================== -->

                    <label>Estado</label>

                    <select name="estado">
                        <option value="1" selected>
                            Activo
                        </option>
                        <option value="0">
                            Inactivo
                        </option>
                    </select>
                    <br><br>

                    <!-- ==================================
                         BOTONES
                    ================================== -->

                    <button type="reset" class="btn-limpiar" id="cancelarUsuario">
                        Cancelar
                    </button>

                    <button type="submit" class="btn-filtrar">
                        Guardar
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>

</html>