<?php
require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

$stmtPaises = $conexion->query("SELECT * FROM paises ORDER BY nombre");
$paises = $stmtPaises->fetchAll(PDO::FETCH_ASSOC);

$stmtDepartamentos = $conexion->query("SELECT * FROM departamentos ORDER BY nombre");
$departamentos = $stmtDepartamentos->fetchAll(PDO::FETCH_ASSOC);

$sqlCiudades = "SELECT c.id_ciudad, c.id_departamento,c.nombre,c.codigo_dane,c.Activo,d.nombre AS nombre_departamento,p.nombre AS nombre_pais
    FROM ciudades c
    INNER JOIN departamentos d ON d.id_departamento = c.id_departamento
    INNER JOIN paises p ON p.id_pais = d.id_pais
    ORDER BY p.nombre ASC, d.nombre ASC, c.nombre ASC";

$stmtCiudades = $conexion->prepare($sqlCiudades);
$stmtCiudades->execute();

$ciudades = $stmtCiudades->fetchAll(PDO::FETCH_ASSOC);



$stmtEstadosCiviles = $conexion->query("SELECT * FROM estados_civiles ORDER BY nombre");
$estadosCiviles = $stmtEstadosCiviles->fetchAll(PDO::FETCH_ASSOC);

$stmtOcupaciones = $conexion->query("SELECT * FROM ocupaciones ORDER BY nombre");
$ocupaciones = $stmtOcupaciones->fetchAll(PDO::FETCH_ASSOC);

$stmtTiposDocumento = $conexion->query("SELECT * FROM tipos_documento ORDER BY nombre");
$tiposDocumento = $stmtTiposDocumento->fetchAll(PDO::FETCH_ASSOC);

$stmtGeneros = $conexion->query("SELECT * FROM generos ORDER BY nombre");
$generos = $stmtGeneros->fetchAll(PDO::FETCH_ASSOC);

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

            <h1 align="center">Tablas Maestras</h1>
            <div class="grid-configuracion">
                <h2>Ubicacion</h2>
                <div class="bloque filtros">
                    <div class="bloque filtros">
                        <div class="form-card">
                            <h3>Paises</h3>

                            <p>Agrega países</p>
                            <table class="tabla">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paises as $pais): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($pais['nombre']) ?></td>

                                            <td>
                                                <?php if ($pais['Activo'] == 1): ?>
                                                    <span class="activo">Activo</span>
                                                <?php else: ?>
                                                    <span class="inactivo">Inactivo</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <button
                                                    type="button"
                                                    class="btn-secondary btnEditarPais"
                                                    data-id="<?= $pais['id_pais'] ?>"
                                                    data-nombre="<?= htmlspecialchars($pais['nombre']) ?>"
                                                    data-activo="<?= $pais['Activo'] ?>">
                                                    ✏ Editar
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <button type="button" id="btnNuevoPais" class="btn-filtrar btnNuevoPais">
                                Agregar
                            </button>
                        </div>
                    </div>


                    <div class="bloque filtros">
                        <div class="form-card">
                            <h3>Departamentos</h3>

                            <p>Agrega departamentos</p>
                            <table class="tabla">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Codigo</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($departamentos as $departamento): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($departamento['nombre']) ?></td>
                                            <td><?= htmlspecialchars($departamento['codigo']) ?></td>

                                            <td>
                                                <?php if ($departamento['Activo'] == 1): ?>
                                                    <span class="activo">Activo</span>
                                                <?php else: ?>
                                                    <span class="inactivo">Inactivo</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <button
                                                    type="button"
                                                    class="btn-secondary btnEditarDepartamento"
                                                    data-id="<?= $departamento['id_departamento'] ?>"
                                                    data-nombre="<?= htmlspecialchars($departamento['nombre']) ?>"
                                                    data-codigo="<?= htmlspecialchars($departamento['codigo']) ?>"
                                                    data-estado="<?= $departamento['Activo'] ?>">
                                                    ✏ Editar
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <button type="button" id="btnNuevoDepartamento" class="btn-filtrar btnNuevoDepartamento">
                                Agregar
                            </button>
                        </div>
                    </div>

                    <div class="bloque filtros">
                        <div class="form-card">
                            <h3>Ciudades</h3>

                            <p>Agrega ciudades</p>
                            <table class="tabla">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Codigo Dane</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ciudades as $ciudad): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($ciudad['nombre']) ?></td>
                                            <td><?= htmlspecialchars($ciudad['codigo_dane']) ?></td>

                                            <td>
                                                <?php if ($ciudad['Activo'] == 1): ?>
                                                    <span class="activo">Activo</span>
                                                <?php else: ?>
                                                    <span class="inactivo">Inactivo</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <button
                                                    type="button"
                                                    class="btn-secondary btnEditarCiudad"
                                                    data-id="<?= $ciudad['id_ciudad'] ?>"
                                                    data-nombre="<?= htmlspecialchars($ciudad['nombre']) ?>"
                                                    data-codigo="<?= htmlspecialchars($ciudad['codigo_dane']) ?>"
                                                    data-estado="<?= $ciudad['Activo'] ?>">
                                                    ✏ Editar
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <button type="button" id="btnNuevaCiudad" class="btn-filtrar btnNuevaCiudad">
                                Agregar
                            </button>
                        </div>
                    </div>

                </div>


                <div class="grid-configuracion">
                    <h2>Datos personales</h2>
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
                            -
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

                        <div class="form-card">
                            -
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
                                -
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
                </div>
            </div>
        </main>
    </div>

    <!-- =====================================================
        MODAL NUEVO PAIS
    ====================================================== -->
    <div id="modalPais" class="modal">
        <div class="modal-contenido">
            <span class="cerrar-modal" id="cerrarPais">
                &times;
            </span>

            <h2>Nuevo País</h2>
            <form action="../actions/guardar_pais.php" method="POST">
                <input type="hidden" name="origen" value="tablas_maestras">

                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre" required maxlength="100">
                </div>

                <div class="form-group">
                    <label> Estado </label>
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

                    <button type="button" class="btn-secondary" id="cancelarPais">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>


    <!-- =====================================================
        MODAL NUEVO DEPARTAMENTO
    ====================================================== -->
    <div id="modalDepartamento" class="modal">
        <div class="modal-contenido">
            <span class="cerrar-modal" id="cerrarDepartamento">
                &times;
            </span>

            <h2>Nuevo Departamento</h2>

            <form action="../actions/guardar_departamento.php" method="POST">

                <!-- Indica que viene de Tablas Maestras -->
                <input type="hidden" name="origen" value="tablas_maestras">

                <!-- =================================================
                    PAÍS
                ================================================== -->

                <div class="form-group">
                    <label for="id_pais">
                        País
                    </label>

                    <select name="id_pais" id="id_pais" required>

                        <option value="">
                            Seleccione un país
                        </option>

                        <?php foreach ($paises as $pais): ?>
                            <?php if ($pais['Activo'] == 1): ?>
                                <option
                                    value="<?= $pais['id_pais'] ?>">
                                    <?= htmlspecialchars($pais['nombre']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- =================================================
                    NOMBRE
                ================================================== -->
                <div class="form-group">
                    <label for="nombreDepartamento">
                        Departamento
                    </label>

                    <input type="text" name="nombre" id="nombreDepartamento" maxlength="100" required>
                </div>

                <!-- =================================================
                    CÓDIGO
                ================================================== -->

                <div class="form-group">
                    <label for="codigoDepartamento">
                        Código
                    </label>

                    <input type="text" name="codigo" id="codigoDepartamento" maxlength="10">
                </div>

                <!-- =================================================
                    ESTADO
                ================================================== -->
                <div class="form-group">
                    <label for="estadoDepartamento">
                        Estado
                    </label>

                    <select name="estado" id="estadoDepartamento">
                        <option value="1">
                            Activo
                        </option>

                        <option value="0">
                            Inactivo
                        </option>
                    </select>
                </div>

                <!-- =================================================
                    BOTONES
                ================================================== -->

                <div class="acciones-modal">
                    <button type="submit" class="btn-primary">
                        Guardar
                    </button>

                    <button type="button" class="btn-secondary" id="cancelarDepartamento">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- =====================================================
        MODAL NUEVA CIUDAD
    ====================================================== -->

    <div id="modalCiudad" class="modal">
        <div class="modal-contenido">
            <!-- CERRAR -->
            <span class="cerrar-modal" id="cerrarCiudad">
                &times;
            </span>

            <h2>Nueva Ciudad</h2>
            <form action="../actions/guardar_ciudad.php" method="POST">

                <!-- =================================================
                    DEPARTAMENTO
                ================================================== -->
                <div class="form-group">
                    <label for="id_departamento_ciudad">
                        Departamento
                    </label>

                    <select name="id_departamento" id="id_departamento_ciudad" required>
                        <option value="">
                            Seleccione un departamento
                        </option>
                        <?php foreach ($departamentos as $departamento): ?>
                            <?php if ($departamento['Activo'] == 1): ?>
                                <option
                                    value="<?= $departamento['id_departamento'] ?>">
                                    <?= htmlspecialchars($departamento['nombre']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- =================================================
                    NOMBRE CIUDAD
                ================================================== -->
                <div class="form-group">
                    <label for="nombre_ciudad">
                        Ciudad
                    </label>

                    <input type="text" name="nombre" id="nombre_ciudad" maxlength="100" required>
                </div>

                <!-- =================================================
                    CÓDIGO DANE
                ================================================== -->
                <div class="form-group">
                    <label for="codigo_dane">
                        Código DANE
                    </label>
                    <input type="text" name="codigo_dane" id="codigo_dane" maxlength="10">
                </div>

                <!-- =================================================
                    ESTADO
                ================================================== -->
                <div class="form-group">
                    <label for="estado_ciudad">
                        Estado
                    </label>
                    <select name="estado" id="estado_ciudad">
                        <option value="1">
                            Activo
                        </option>

                        <option value="0">
                            Inactivo
                        </option>
                    </select>
                </div>

                <!-- =================================================
                    BOTONES
                ================================================== -->
                <div class="acciones-modal">
                    <button type="submit" class="btn-primary">
                        Guardar
                    </button>

                    <button type="button" class="btn-secondary" id="cancelarCiudad">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>


    <!-- =====================================================
        MODAL EDITAR CIUDAD
    ====================================================== -->

    <div id="modalEditarCiudad" class="modal">
        <div class="modal-contenido">
            <span class="cerrar-modal" id="cerrarEditarCiudad">
                &times;
            </span>

            <h2>Editar Ciudad</h2>

            <form action="../actions/editar_ciudad.php" method="POST">

                <!-- ID DE LA CIUDAD -->
                <input type="hidden" name="id_ciudad" id="editar_id_ciudad">

                <!-- DEPARTAMENTO -->
                <input type="hidden" name="id_departamento" id="editar_id_departamento_ciudad">

                <div class="form-group">
                    <label for="editar_departamento_ciudad">
                        Departamento
                    </label>

                    <input type="text" id="editar_departamento_ciudad" readonly>
                </div>

                <!-- CIUDAD -->
                <div class="form-group">
                    <label for="editar_nombre_ciudad">
                        Ciudad
                    </label>

                    <input type="text" name="nombre" id="editar_nombre_ciudad" maxlength="100" required>
                </div>

                <!-- CÓDIGO DANE -->
                <div class="form-group">
                    <label for="editar_codigo_dane">
                        Código DANE
                    </label>

                    <input type="text" name="codigo_dane" id="editar_codigo_dane" maxlength="10">
                </div>

                <!-- ESTADO -->
                <div class="form-group">
                    <label for="editar_estado_ciudad">
                        Estado
                    </label>

                    <select name="estado" id="editar_estado_ciudad">
                        <option value="1">
                            Activo
                        </option>

                        <option value="0">
                            Inactivo
                        </option>
                    </select>
                </div>

                <!-- BOTONES -->
                <div class="acciones-modal">
                    <button type="submit" class="btn-primary">
                        Actualizar
                    </button>

                    <button type="button" class="btn-secondary" id="cancelarEditarCiudad">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>


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

            <form action="../actions/guardar_ocupacion.php" method="POST">
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
            <span class="cerrar-modal" id="cerrarNuevoGenero">
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
        MODAL EDITAR PAÍS
    ====================================================== -->
    <div id="modalEditarPais" class="modal">
        <div class="modal-contenido">
            <span class="cerrar-modal" id="cerrarEditarPais">&times;</span>

            <h2>Editar país</h2>
            <form action="../actions/editar_pais.php" id="formEditarPais" method="POST">

                <input type="hidden" name="id_pais" id="editar_id_pais">

                <div class="form-group">
                    <label for="editar_nombre_pais">
                        Nombre del país
                    </label>
                    <input type="text" name="nombre" id="editar_nombre_pais" required>
                </div>

                <div class="form-group">
                    <label for="editar_activo_pais">
                        Estado
                    </label>

                    <select name="activo" id="editar_activo_pais" required>
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>

                <div class="modal-botones">
                    <button type="button" class="btn-secondary" id="cancelarEditarPais">
                        Cancelar
                    </button>

                    <button type="submit" class="btn-primary">
                        Guardar cambios
                    </button>
                </div>
            </form>

        </div>
    </div>


    <!-- =====================================================
        MODAL EDITAR DEPARTAMENTO
    ====================================================== -->
    <div id="modalEditarDepartamento" class="modal">

        <div class="modal-contenido">

            <span
                class="cerrar-modal"
                id="cerrarEditarDepartamento">
                &times;
            </span>

            <h2>Editar Departamento</h2>

            <form
                action="../actions/editar_departamento.php"
                method="POST">

                <input
                    type="hidden"
                    name="id_departamento"
                    id="editar_id_departamento">

                <input
                    type="hidden"
                    name="id_pais"
                    id="editar_id_pais">


                <!-- PAÍS -->

                <div class="form-group">

                    <label for="editar_pais">
                        País
                    </label>

                    <input
                        type="text"
                        id="editar_pais"
                        readonly>

                </div>


                <!-- NOMBRE -->

                <div class="form-group">

                    <label for="editar_nombre_departamento">
                        Departamento
                    </label>

                    <input
                        type="text"
                        name="nombre"
                        id="editar_nombre_departamento"
                        maxlength="100"
                        required>

                </div>


                <!-- CÓDIGO -->

                <div class="form-group">

                    <label for="editar_codigo_departamento">
                        Código
                    </label>

                    <input
                        type="text"
                        name="codigo"
                        id="editar_codigo_departamento"
                        maxlength="10">

                </div>


                <!-- ESTADO -->

                <div class="form-group">

                    <label for="editar_estado_departamento">
                        Estado
                    </label>

                    <select
                        name="estado"
                        id="editar_estado_departamento">

                        <option value="1">
                            Activo
                        </option>

                        <option value="0">
                            Inactivo
                        </option>

                    </select>

                </div>


                <div class="acciones-modal">

                    <button
                        type="submit"
                        class="btn-primary">
                        Actualizar
                    </button>

                    <button
                        type="button"
                        class="btn-secondary"
                        id="cancelarEditarDepartamento">
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
                    <input type="text" name="nombre" id="editar_nombre_tipo_documento" maxlength="100" required>
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




</body>

</html>