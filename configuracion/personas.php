
<?php


require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


$stmtPais = $conexion->query("
    SELECT id_pais AS id, nombre
    FROM paises
    ORDER BY nombre
");
$paises = $stmtPais->fetchAll(PDO::FETCH_ASSOC);

$stmtDepartamentos = $conexion->query("
    SELECT id_departamento AS id, nombre, codigo
    FROM departamentos
    ORDER BY nombre
");
$departamentos = $stmtDepartamentos->fetchAll(PDO::FETCH_ASSOC);

$stmtCiudades = $conexion->query("
    SELECT id_ciudad AS id, nombre, codigo_dane
    FROM ciudades
    ORDER BY nombre
");
$ciudades = $stmtCiudades->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   BÚSQUEDA
   ========================================================= */

$buscar = isset($_GET['buscar'])
    ? trim($_GET['buscar'])
    : '';

/* =========================================================
   CONSULTA DE PERSONAS
   ========================================================= */

$sql = "
    SELECT
        u.id,
        u.nombres,
        u.apellidos,
        u.tipo_documento,
        u.numero_documento,
        u.correo,
        u.telefono,
        u.celular,
        u.estado,
        p.nombre AS pais,
        d.nombre AS departamento,
        c.nombre AS ciudad
    FROM usuario u
    LEFT JOIN paises p
        ON p.id_pais = u.id_pais
    LEFT JOIN departamentos d
        ON d.id_departamento = u.id_departamento
    LEFT JOIN ciudades c
        ON c.id_ciudad = u.id_ciudad
";

/* =========================================================
   FILTRO DE BÚSQUEDA
   ========================================================= */

if ($buscar !== '') {

    $sql .= "
        WHERE
            u.nombres LIKE :buscar
            OR u.apellidos LIKE :buscar
            OR u.numero_documento LIKE :buscar
            OR u.correo LIKE :buscar
            OR u.celular LIKE :buscar
    ";
}

/* =========================================================
   ORDEN
   ========================================================= */

$sql .= "
    ORDER BY u.nombres ASC, u.apellidos ASC
";

/* =========================================================
   EJECUTAR CONSULTA
   ========================================================= */

$stmt = $conexion->prepare($sql);

if ($buscar !== '') {

    $parametro = '%' . $buscar . '%';

    $stmt->bindValue(
        ':buscar',
        $parametro,
        PDO::PARAM_STR
    );
}

$stmt->execute();

$personas = $stmt->fetchAll(PDO::FETCH_ASSOC);


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

        <!-- =====================================================
             ENCABEZADO
        ====================================================== -->

        <div class="form-actions">

            <button
                type="button"
                class="btn-filtrar btn-derecha"
                id="btnNuevaPersona">

                + Nueva persona

            </button>

        </div>

        <h2 align="center">Personas</h2>

        <br>

        <!-- =====================================================
             BÚSQUEDA
        ====================================================== -->

        <div class="bloque filtros">

            <form method="GET" class="form-actions">

                <div class="form-group">

                    <label for="buscar">
                        Buscar persona
                    </label>

                    <input
                        type="text"
                        id="buscar"
                        name="buscar"
                        value="<?= htmlspecialchars($buscar) ?>"
                        placeholder="Nombre, documento, correo o celular">

                </div>

                <button
                    type="submit"
                    class="btn-filtrar">

                    Buscar

                </button>

                <button
                    type="button"
                    class="btn-limpiar"
                    onclick="window.location.href='personas.php'">

                    Limpiar

                </button>

            </form>

        </div>

        <br>

        <!-- =====================================================
             TABLA DE PERSONAS
        ====================================================== -->

        <div class="bloque">

            <div class="table-responsive">

                <table class="tabla">

                    <thead>

                        <tr>

                            <th>Documento</th>

                            <th>Nombre</th>

                            <th>Correo</th>

                            <th>Celular</th>

                            <th>Ciudad</th>

                            <th>Estado</th>

                            <th>Acciones</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($personas)): ?>

                        <tr>

                            <td
                                colspan="7"
                                style="text-align:center;">

                                No existen personas registradas.

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($personas as $persona): ?>

                            <tr>

                                <!-- DOCUMENTO -->

                                <td>

                                    <?php

                                    $tipoDocumento =
                                        $persona['tipo_documento'] ?? '';

                                    $numeroDocumento =
                                        $persona['numero_documento'] ?? '';

                                    if ($tipoDocumento !== ''):

                                    ?>

                                        <?= htmlspecialchars($tipoDocumento) ?>

                                        -

                                    <?php endif; ?>

                                    <?= htmlspecialchars($numeroDocumento) ?>

                                </td>


                                <!-- NOMBRE -->

                                <td>

                                    <?= htmlspecialchars(
                                        $persona['nombres'] . ' ' . $persona['apellidos']
                                    ) ?>

                                </td>


                                <!-- CORREO -->

                                <td>

                                    <?= htmlspecialchars(
                                        $persona['correo']
                                    ) ?>

                                </td>


                                <!-- CELULAR -->

                                <td>

                                    <?= htmlspecialchars(
                                        $persona['celular'] ?? ''
                                    ) ?>

                                </td>


                                <!-- CIUDAD -->

                                <td>

                                    <?= htmlspecialchars(
                                        $persona['ciudad'] ?? ''
                                    ) ?>

                                </td>


                                <!-- ESTADO -->

                                <td>

                                    <?php if ((int)$persona['estado'] === 1): ?>

                                        <span class="estado activo">
                                            Activo
                                        </span>

                                    <?php else: ?>

                                        <span class="estado inactivo">
                                            Inactivo
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACCIONES -->

                                <td>

                                    <button
                                        type="button"
                                        class="btn-secondary btnEditarPersona"
                                        data-id="<?= $persona['id'] ?>">

                                        ✏ Editar

                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </main>

</div>

<!-- =========================================================
     MODAL NUEVA PERSONA
========================================================= -->

<div id="modalPersona" class="modal">

    <div class="modal-contenido">

        <span id="cerrarPersona" class="cerrar">
            &times;
        </span>

        <h2>Nueva persona</h2>

        <form
            class="modal-form-persona"
            action="../actions/guardar_usuario.php"
            method="POST"
            enctype="multipart/form-data">

            <!-- =====================================
                 DATOS PERSONALES
            ====================================== -->

            <h3 class="titulo-seccion-persona">
                Datos personales
            </h3>

            <div class="form-grid-persona">

                <div class="form-group">

                    <label for="nombres">
                        Nombres
                    </label>

                    <input
                        type="text"
                        id="nombres"
                        name="nombres"
                        maxlength="100"
                        required>

                </div>


                <div class="form-group">

                    <label for="apellidos">
                        Apellidos
                    </label>

                    <input
                        type="text"
                        id="apellidos"
                        name="apellidos"
                        maxlength="100"
                        required>

                </div>


                <div class="form-group">

                    <label for="tipo_documento">
                        Tipo de documento
                    </label>

                    <select
                        id="tipo_documento"
                        name="tipo_documento"
                        required>

                        <option value="">
                            Seleccione...
                        </option>

                        <option value="CC">
                            Cédula de Ciudadanía
                        </option>

                        <option value="TI">
                            Tarjeta de Identidad
                        </option>

                        <option value="CE">
                            Cédula de Extranjería
                        </option>

                        <option value="PA">
                            Pasaporte
                        </option>

                        <option value="PPT">
                            Permiso por Protección Temporal
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label for="numero_documento">
                        Número de documento
                    </label>

                    <input
                        type="text"
                        id="numero_documento"
                        name="numero_documento"
                        maxlength="30"
                        required>

                </div>


                <div class="form-group">

                    <label for="fecha_nacimiento">
                        Fecha de nacimiento
                    </label>

                    <input
                        type="date"
                        id="fecha_nacimiento"
                        name="fecha_nacimiento">

                </div>


                <div class="form-group">

                    <label for="genero">
                        Género
                    </label>

                    <select
                        id="genero"
                        name="genero">

                        <option value="">
                            Seleccione...
                        </option>

                        <option value="M">
                            Masculino
                        </option>

                        <option value="F">
                            Femenino
                        </option>

                        <option value="O">
                            Otro
                        </option>

                    </select>

                </div>

            </div>


            <!-- =====================================
                 CONTACTO
            ====================================== -->

            <h3 class="titulo-seccion-persona">
                Información de contacto
            </h3>

            <div class="form-grid-persona">

                <div class="form-group">

                    <label for="correo">
                        Correo electrónico
                    </label>

                    <input
                        type="email"
                        id="correo"
                        name="correo"
                        maxlength="150"
                        required>

                </div>


                <div class="form-group">

                    <label for="telefono">
                        Teléfono
                    </label>

                    <input
                        type="text"
                        id="telefono"
                        name="telefono"
                        maxlength="20">

                </div>


                <div class="form-group">

                    <label for="celular">
                        Celular
                    </label>

                    <input
                        type="text"
                        id="celular"
                        name="celular"
                        maxlength="20">

                </div>

            </div>


            <!-- =====================================
                 UBICACIÓN
            ====================================== -->

            <h3 class="titulo-seccion-persona">
                Ubicación
            </h3>

            <div class="form-grid-persona">

                <div class="form-group">

                    <label for="id_pais">
                        País
                    </label>

                    <select
                        id="id_pais"
                        name="id_pais">

                        <option value="">
                            Seleccione un país
                        </option>

                        <?php foreach ($paises as $pais): ?>

                            <option value="<?= $pais['id'] ?>">
                                <?= htmlspecialchars($pais['nombre']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="id_departamento">
                        Departamento
                    </label>

                    <select
                        id="id_departamento"
                        name="id_departamento">

                        <option value="">
                            Seleccione un departamento
                        </option>

                        <?php foreach ($departamentos as $departamento): ?>

                            <option value="<?= $departamento['id'] ?>">
                                <?= htmlspecialchars($departamento['nombre']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="id_ciudad">
                        Ciudad
                    </label>

                    <select
                        id="id_ciudad"
                        name="id_ciudad">

                        <option value="">
                            Seleccione una ciudad
                        </option>

                        <?php foreach ($ciudades as $ciudad): ?>

                            <option value="<?= $ciudad['id'] ?>">
                                <?= htmlspecialchars($ciudad['nombre']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="direccion">
                        Dirección
                    </label>

                    <input
                        type="text"
                        id="direccion"
                        name="direccion"
                        maxlength="200">

                </div>

            </div>


            <!-- =====================================
                 ACCESO
            ====================================== -->

            <h3 class="titulo-seccion-persona">
                Acceso al sistema
            </h3>

            <div class="form-grid-persona">

                <div class="form-group">

                    <label for="contrasena">
                        Contraseña
                    </label>

                    <input
                        type="password"
                        id="contrasena"
                        name="contrasena"
                        required>

                </div>

            </div>


            <!-- =====================================
                 FOTO
            ====================================== -->

            <h3 class="titulo-seccion-persona">
                Fotografía
            </h3>

            <div class="form-grid-persona">

                <div class="form-group full">

                    <label for="foto">
                        Foto de perfil
                    </label>

                    <input
                        type="file"
                        id="foto"
                        name="foto"
                        accept="image/*">

                </div>

            </div>


            <!-- =====================================
                 BOTONES
            ====================================== -->

            <div class="botones-persona">

                <button
                    type="button"
                    class="btn-limpiar"
                    id="cancelarPersona">

                    Cancelar

                </button>

                <button
                    type="submit"
                    class="btn-filtrar">

                    Guardar

                </button>

            </div>

        </form>

    </div>

</div>

</body>

</html>
