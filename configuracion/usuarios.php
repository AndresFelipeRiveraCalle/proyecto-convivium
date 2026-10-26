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
        <main class="contenido">
            <!-- ==
                    ENCABEZADO
            == -->

            <div class="form-actions">
                <div>
                    <h2>Usuarios</h2>
                    <br>
                    <h4> Personas registradas en el sistema </h4>
                </div>

                <button type="button"
                    class="btn-filtrar btn-derecha" id="btnNuevoUsuario">
                    Nuevo usuario
                </button>
            </div>

            <div class="form-card">
                <!-- ==
                    TABLA DE USUARIOS
                == -->
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
                                        <?php if (!empty($u['foto'])): ?>
                                            <img
                                                src="../<?= htmlspecialchars($u['foto']) ?>"
                                                class="foto-persona-listado">
                                        <?php else: ?>
                                            <span class="sin-foto">
                                                Sin foto
                                            </span>
                                        <?php endif; ?>
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
                                            class="btn-filtrar btnEditarUsuario"
                                            data-id="<?= $u['id'] ?>">
                                            Editar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
        
            </div>
        </main>

        <!-- ==
             MODAL NUEVO USUARIO
        == -->

        <div id="modalUsuario" class="modal">
            <div class="modal-contenido">
                <span id="cerrarUsuario" class="cerrar">
                    &times;
                </span>
                
                <h2>Nuevo usuario</h2>
                <br>

                <form action="<?= BASE_URL ?>actions/guardar_persona.php" method="POST"
                    enctype="multipart/form-data">

                    <!-- ==
                         DATOS PERSONALES
                    == -->
                    <h3>Datos personales</h3>
                    
                    <div class="bloque filtros">
                        <div class="card">
                            <label>Nombres *</label>
                            <input type="text" name="nombres" maxlength="100" required>
                        </div>
                        <div class="card">
                            <label>Apellidos *</label>
                            <input type="text" name="apellidos" maxlength="100" required>
                        </div>

                    </div>
                    

                    <div class="bloque filtros">
                        <div class="card"
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

                        <div class="card"
                            <label>Número de documento</label>

                            <input type="text" name="numero_documento" maxlength="30">
                        </div>
                    </div>
                    

                    <!-- ==
                         CONTACTO
                    == -->
                    <h3>Información de contacto</h3>

                    <div class="bloque filtros">
                        <div class="card">
                            <label>Correo electrónico</label>
                            <input type="email" name="correo" maxlength="150">
                        </div>

                        <div class="card">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" maxlength="20">
                        </div>
                    </div>
                    <div class="bloque filtros">
                        <div class="card">
                            <label>Celular</label>
                            <input type="text" name="celular" maxlength="20">
                        </div>
                    </div>

                    <!-- ==
                         DATOS ADICIONALES
                    == -->

                    <h3>Información adicional</h3>
                    <div class="bloque filtros">
                        <div class="card">
                            <label>Fecha de nacimiento</label>
                            <input type="date" name="fecha_nacimiento">
                        </div>

                        <div class="card">
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
                    

                    <div class="bloque filtros">
                        <div class="card">
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

                        <div class="card">
                            <label>Ocupación</label>
                            <input type="text" name="id_ocupacion" maxlength="100">
                        </div>
                    </div>
                    

                    <!-- ==
                         DIRECCIÓN
                    == -->
                    <div class="bloque filtros">
                        
                        <h3>Dirección</h3>
                        
                        <input type="text" name="direccion" maxlength="200">
                        <br>
                    </div>
                    <!-- ==
                         FOTO
                    == -->
                    <div class="bloque filtros">
                        <h3>Foto</h3>
                    
                        <input type="file" name="foto" accept="image/*">
                    </div>

                    <!-- ==
                         ESTADO
                    == -->
                    <div class="bloque filtros">
                        <label>Estado</label>
                        <div class="card" >
                            <select name="estado">
                                <option value="1" selected style="text-align: center";>
                                    Activo
                                </option>
                                <option value="0" style="text-align: center";>
                                    Inactivo
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- ==
                         BOTONES
                    == -->

                    <button type="reset" class="btn-limpiar" id="cancelarUsuario">
                        Cancelar
                    </button>

                    <button type="submit" class="btn-filtrar">
                        Guardar
                    </button>
                </form>
            </div>
        </div>

        <!-- =======
            MODAL EDITAR USUARIO== -->
        <div id="modalEditarUsuario" class="modal">
            <div class="modal-contenido">
                <span id="cerrarEditarUsuario" class="cerrar">
                    &times;
                </span>

                <h2>Editar usuario</h2>
                <br>
                <form id="formEditarUsuario" action="<?= BASE_URL ?>actions/actualizar_usuario.php" method="POST"
                    enctype="multipart/form-data">
                    <!-- ID -->
                    <input type="hidden" name="id"id="editar_id">

                    <!-- 
                        DATOS PERSONALES
                    = -->
                    <h3>Datos personales</h3>
                    <div class="bloque filtros">
                        <div class="card">
                            <label>Nombres</label>

                            <input type="text" name="nombres"id="editar_nombres"required>
                        </div>

                        <div class="card">
                            <label>Apellidos</label>

                            <input type="text" name="apellidos" id="editar_apellidos" required>
                        </div>
                    </div>
                    <div class="bloque filtros">                    
                        <div class="card">
                            <label>Tipo de documento</label>

                            <select name="id_tipo_documento" id="editar_id_tipo_documento"required>

                                <option value="">
                                    Seleccione...
                                </option>

                                <?php
                                    $stmtTiposDocumento = $conexion->query("SELECT id_tipo_documento,nombre
                                        FROM tipos_documento WHERE estado = 1
                                        ORDER BY nombre
                                    ");

                                    $tiposDocumento = $stmtTiposDocumento->fetchAll(PDO::FETCH_ASSOC);
                                ?>

                                <?php foreach ($tiposDocumento as $tipoDocumento): ?>
                                    <option
                                        value="<?= $tipoDocumento['id_tipo_documento'] ?>">

                                        <?= htmlspecialchars($tipoDocumento['nombre'])?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="card">
                            <label>Número de documento</label>

                            <input type="text" name="numero_documento" id="editar_numero_documento" required
                                maxlength="30">
                        </div>
                    </div>

                    <h3>Información de contacto</h3>
                    <div class="bloque filtros">
                        <div class="card">
                            <label>Correo</label>

                            <input type="email" name="correo" id="editar_correo">
                        </div>

                        <div class="card">
                            <label>Teléfono</label>

                            <input type="text" name="telefono" id="editar_telefono">
                        </div>
                    </div>
                    <div class="bloque filtros">                    
                        <div class="card">
                            <label>Celular</label>
                            <input type="text" name="celular" id="editar_celular">
                        </div>
                    </div>

                    <h3>Información adicional</h3>
                    <div class="bloque filtros"> 
                        <div class="card">
                            <label>Fecha de nacimiento</label>

                            <input type="date" name="fecha_nacimiento" id="editar_fecha_nacimiento">
                        </div>

                        <div class="card">
                            <label>Género</label>

                            <select name="id_genero" id="editar_id_genero">
                                <option value="">
                                    Seleccione...
                                </option>
                                <?php
                                    $stmtGeneros = $conexion->query(" SELECT id_genero, nombre
                                        FROM generos WHERE estado = 1
                                        ORDER BY nombre
                                    ");

                                    $generos = $stmtGeneros->fetchAll(PDO::FETCH_ASSOC);
                                ?>

                                <?php foreach ($generos as $genero): ?>
                                    <option
                                        value="<?= $genero['id_genero'] ?>">

                                        <?= htmlspecialchars($genero['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="bloque filtros">                     
                        <div class="card">
                            <label>
                                Estado civil
                            </label>

                            <select
                                name="id_estado_civil"
                                id="editar_id_estado_civil">

                                <option value="">
                                    Seleccione...
                                </option>

                                <?php
                                $stmtEstados = $conexion->query("
                                    SELECT
                                        id_estado_civil,
                                        nombre
                                    FROM estados_civiles
                                    WHERE estado = 1
                                    ORDER BY nombre
                                ");

                                $estadosCiviles = $stmtEstados->fetchAll(
                                    PDO::FETCH_ASSOC
                                );
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


                        <div class="card">

                            <label>Ocupación</label>

                            <select
                                name="id_ocupacion" id="editar_id_ocupacion">

                                <option value="">
                                    Seleccione...
                                </option>

                                <?php
                                $stmtOcupaciones = $conexion->query("
                                    SELECT
                                        id_ocupacion,
                                        nombre
                                    FROM ocupaciones
                                    WHERE estado = 1
                                    ORDER BY nombre
                                ");

                                $ocupaciones = $stmtOcupaciones->fetchAll(
                                    PDO::FETCH_ASSOC
                                );
                                ?>

                                <?php foreach ($ocupaciones as $ocupacion): ?>

                                    <option
                                        value="<?= $ocupacion['id_ocupacion'] ?>">

                                        <?= htmlspecialchars(
                                            $ocupacion['nombre']
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>
                    </div>
                    <!-- 
                        DIRECCIÓN
                    = -->

                    <div class="bloque filtros">
                        <h3>Dirección</h3>

                        <input
                            type="text" name="direccion" id="editar_direccion">

                    </div>

                    <!-- 
                        FOTO
                    = -->

                    <div class="bloque filtros";>
                        <div id="editar_foto_actual" ></div>

                        <br>
                                        
                        <input 
                            type="file"
                            name="foto"
                            id="editar_foto"
                            accept="image/*" >

                    </div>


                    <br>
                    <label>Estado </label>
                    <div class="bloque filtros">   
                        <div class="card">

                            <select name="estado" id="editar_estado">

                                <option value="1">
                                    Activo
                                </option>

                                <option value="0">
                                    Inactivo
                                </option>

                            </select>
                        </div>
                    </div>


                    <br>




                    <!-- 
                        BOTONES
                    = -->

                    <div class="botones-persona">

                        <button type="button" class="btn-limpiar" id="cancelarEditarUsuario">
                            Cancelar
                        </button>

                        <button type="submit" class="btn-filtrar">
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    
    <script src="<?= BASE_URL ?>assets/js/editar_usuario.js"></script>
</body>

</html>