
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
   ONSULTA TIPOS DE DOCUMENTO
   ========================================================= */
$stmtTipos = $conexion->query("
    SELECT id_tipo_documento, codigo, nombre
    FROM tipos_documento
    WHERE estado=1
");
$tiposDocumento = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   GÉNEROS
========================================================= */
$stmtGeneros = $conexion->query("
    SELECT id_genero, codigo, nombre
    FROM generos
    WHERE estado = 1
    ORDER BY nombre
");
$generos = $stmtGeneros->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   Estados civiles
   ========================================================= */
   $stmtEstadosCiviles = $conexion->query("
    SELECT id_estado_civil, nombre 
    FROM estados_civiles
    WHERE estado = 1
    ORDER BY nombre
");
$estadosCiviles = $stmtEstadosCiviles->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   Ocupaciones
   ========================================================= */
   $stmtOcupaciones = $conexion->query("
    SELECT id_ocupacion, nombre 
    FROM ocupaciones
    WHERE estado = 1
    ORDER BY nombre
");
$ocupaciones = $stmtOcupaciones->fetchAll(PDO::FETCH_ASSOC);

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
    SELECT  u.id, u.nombres, u.apellidos, u.id_tipo_documento, u.numero_documento, u.correo, u.telefono,
        u.celular, u.estado, p.nombre AS pais, d.nombre AS departamento, c.nombre AS ciudad, u.foto, 
        td.codigo
    FROM usuario u
    LEFT JOIN paises p ON p.id_pais = u.id_pais
    LEFT JOIN departamentos d ON d.id_departamento = u.id_departamento
    LEFT JOIN ciudades c ON c.id_ciudad = u.id_ciudad
    LEFT JOIN tipos_documento td ON td.id_tipo_documento = u.id_tipo_documento
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
    ORDER BY u.id asc
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
                type="button" class="btn-filtrar btn-derecha" id="btnNuevaPersona">
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
                        type="text" id="buscar" name="buscar" value="<?= htmlspecialchars($buscar) ?>"
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
                            <th>Tipo de Documento</th>
                            <th>Documento</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Celular</th>
                            <th>Ciudad</th>
                            <th>Foto</th>
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
                                <!-- TIPO DE DOCUMENTO -->
                                <td>
                                    <?= htmlspecialchars(
                                        $persona['codigo'] ?? ''
                                    ) ?>
                            
                                <!-- DOCUMENTO -->
                                <td>
                                    <?= htmlspecialchars(
                                        $persona['numero_documento'] ?? '') ?>

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

                                <!-- FOTO -->
                                 
                                <td>
                                    <?php if (!empty($persona['foto'])): ?>
                                        <img 
                                            src="../<?= $persona['foto'] ?>"
                                            class="foto-persona-listado">
                                    <?php else: ?>
                                        <span class="sin-foto">
                                            Sin foto
                                        </span>
                                    <?php endif; ?>
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

        <!--h2>Nueva persona</h2-->

        <form
            class="modal-form-persona" action="../actions/guardar_persona.php" method="POST" enctype="multipart/form-data">

            <!-- =====================================
                 DATOS PERSONALES
            ====================================== -->

            <h2 class="titulo-seccion-persona">
                Datos personales
            </h2>

            <div class="form-grid-persona">
                <div class="form-group">
                    <label for="nombres">
                        Nombres
                    </label>

                    <input  type="text" id="nombres" name="nombres" maxlength="100" required>
                </div>

                <div class="form-group">
                    <label for="apellidos">
                        Apellidos
                    </label>

                    <input
                        type="text" id="apellidos" name="apellidos" maxlength="100" required>
                </div>

                <div class="form-group">
                    <label for="id_tipo_documento">
                        Tipo de documento
                    </label>

                    <select id="id_tipo_documento" name="id_tipo_documento" required>
                        <option value="">
                            Seleccione un tipo de documento
                        </option>

                        <?php foreach ($tiposDocumento as $tipo): ?>
                            <option value="<?= $tipo['id_tipo_documento'] ?>">
                                <?= htmlspecialchars(
                                    $tipo['nombre']
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>


                <div class="form-group">
                    <label for="numero_documento">
                        Número de documento
                    </label>

                    <input type="text" id="numero_documento" name="numero_documento" maxlength="30" required>
                </div>


                <div class="form-group">

                    <label for="fecha_nacimiento">
                        Fecha de nacimiento
                    </label>

                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento">
                </div>


                <div class="form-group">
                    <label for="genero">
                        Género
                    </label>

                    <select id="id_genero" name="id_genero">
                        <option value="">
                            Seleccione género
                        </option>

                        <?php foreach ($generos as $genero): ?>
                            <option value="<?= $genero['id_genero'] ?>">
                                <?= htmlspecialchars(
                                    $genero['nombre']
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="estado_civil">
                        Estado civil
                    </label>

                    <select id="id_estado_civil" name="id_estado_civil">
                        <option value="">
                            Seleccione estado civil
                        </option>

                        <?php foreach ($estadosCiviles as $estadoCivil): ?>
                            <option value="<?= $estadoCivil['id_estado_civil'] ?>">
                                <?= htmlspecialchars(
                                    $estadoCivil['nombre']
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ocupacion">
                        Ocupación
                    </label>

                    <select id="id_ocupacion" name="id_ocupacion">
                        <option value="">
                            Seleccione ocupación
                        </option>

                        <?php foreach ($ocupaciones as $ocupacion): ?>
                            <option value="<?= $ocupacion['id_ocupacion'] ?>">
                                <?= htmlspecialchars(
                                    $ocupacion['nombre']
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>


            </div>


            <!-- =====================================
                 CONTACTO
            ====================================== -->

            <h2 class="titulo-seccion-persona">
                Información de contacto
            </h2>

            <div class="form-grid-persona">
                <div class="form-group">
                    <label for="correo">
                        Correo electrónico
                    </label>

                    <input type="email" id="correo" name="correo" maxlength="150" required>
                </div>


                <div class="form-group">
                    <label for="telefono">
                        Teléfono
                    </label>

                    <input type="text" id="telefono" name="telefono" maxlength="20">
                </div>


                <div class="form-group">
                    <label for="celular">
                        Celular
                    </label>

                    <input type="text" id="celular" name="celular" maxlength="20">
                </div>

            </div>


            <!-- =====================================
                 UBICACIÓN
            ====================================== -->

            <h2 class="titulo-seccion-persona">
                Ubicación
            </h2>

            <div class="form-grid-persona">
                <div class="form-group">
                    <label for="id_pais">
                        País
                    </label>

                    <select d="id_pais" name="id_pais">
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
                        id="id_departamento" name="id_departamento">
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

                    <select  id="id_ciudad" name="id_ciudad">
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

                    <input type="text" id="direccion" name="direccion" maxlength="200">
                </div>
            </div>


            <!-- =====================================
                 FOTO
            ====================================== -->

            <h2 class="titulo-seccion-persona">
                Agregar una foto
            </h2>
            
            <div class="form-grid-persona">
                <div class="form-group full">
                    <label for="foto">
                        Foto de perfil
                    </label>

                    <input type="file" id="foto" name="foto" accept="image/*">
                </div>
            </div>


            <!-- =====================================
                 BOTONES
            ====================================== -->

            <div class="botones-persona">
                <button
                    type="button" class="btn-limpiar" id="cancelarPersona">
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
