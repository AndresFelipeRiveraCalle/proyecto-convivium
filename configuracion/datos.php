<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// CARGAR PAÍSES
// ==========================================================

$stmtPais = $conexion->query("
    SELECT
        id_pais,
        nombre
    FROM paises
    ORDER BY nombre
");

$paises = $stmtPais->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// CARGAR DEPARTAMENTOS
// ==========================================================

$stmtDepartamentos = $conexion->query("
    SELECT
        codigo,
        id_departamento,
        id_pais,
        nombre
    FROM departamentos
    ORDER BY nombre
");

$departamentos = $stmtDepartamentos->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// CARGAR CIUDADES
// ==========================================================

$stmtCiudades = $conexion->query("
    SELECT
        id_ciudad,
        id_departamento,
        nombre,
        codigo_dane
    FROM ciudades
    ORDER BY nombre
");

$ciudades = $stmtCiudades->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// TIPOS DE COPROPIEDAD
// ==========================================================

$stmtTipoCopropiedad = $conexion->query("
    SELECT
        id,
        nombre
    FROM tipos_copropiedad
    ORDER BY nombre
");

$tiposCopropiedad = $stmtTipoCopropiedad->fetchAll(
    PDO::FETCH_ASSOC
);


// ==========================================================
// CARGAR DATOS ACTUALES DE LA COPROPIEDAD
// ==========================================================

$stmtUnidad = $conexion->query("
    SELECT *
    FROM datos_unidad
    WHERE es_actual = 1
      AND activo = 1
    ORDER BY id DESC
    LIMIT 1
");

$unidad = $stmtUnidad->fetch(PDO::FETCH_ASSOC);


// ==========================================================
// SI EXISTE INFORMACIÓN, INICIAR BLOQUEADO
// ==========================================================

$bloqueado = ($unidad !== false);


// ==========================================================
// CALCULAR CANTIDAD TOTAL DE UNIDADES
// ==========================================================
//
// Este dato ya no se escribe manualmente.
//
// Se obtiene de los grupos configurados en
// detalle_tipos_unidad.
// ==========================================================

$stmtCantidadUnidades = $conexion->query("
    SELECT
        COALESCE(
            SUM(cantidad_unidades),
            0
        ) AS total_unidades

    FROM detalle_tipos_unidad

    WHERE activo = 1
");

$resultadoCantidad =
    $stmtCantidadUnidades->fetch(PDO::FETCH_ASSOC);

$cantidadTotalUnidades =
    (int)($resultadoCantidad['total_unidades'] ?? 0);

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


        <form
            id="formDatosCopropiedad"
            action="<?= BASE_URL ?>actions/guardar_datos.php"
            method="POST"
            enctype="multipart/form-data"
        >


            <!-- ==================================================
                 ID REGISTRO ACTUAL
            =================================================== -->

            <input
                type="hidden"
                name="id"
                value="<?= htmlspecialchars(
                    $unidad['id'] ?? ''
                ) ?>"
            >


            <!-- ==================================================
                 ENCABEZADO
            =================================================== -->

            <h2 align="center">
                Datos de la copropiedad
            </h2>


            <br>


            <p>

                Configure la información general,
                ubicación, datos de contacto,
                documentos e identidad visual de la
                copropiedad.

            </p>


            <br>


            <!-- ==================================================
                 DATOS GENERALES
            =================================================== -->

            <h3>
                Datos generales
            </h3>


            <div class="bloque filtros">


                <!-- NOMBRE -->

                <div class="card">


                    <label for="nombre_unidad">
                        Nombre de la copropiedad *
                    </label>


                    <input
                        type="text"
                        id="nombre_unidad"
                        name="nombre_unidad"
                        placeholder="Ej. Urbanización Las Palmas"
                        maxlength="150"
                        value="<?= htmlspecialchars(
                            $unidad['nombre'] ?? ''
                        ) ?>"
                        <?= $bloqueado ? 'readonly' : '' ?>
                        required
                    >


                </div>


                <!-- NIT -->

                <div class="card">


                    <label for="nit_unidad">
                        NIT *
                    </label>


                    <input
                        type="text"
                        id="nit_unidad"
                        name="nit_unidad"
                        placeholder="Ingrese el NIT"
                        maxlength="30"
                        value="<?= htmlspecialchars(
                            $unidad['nit'] ?? ''
                        ) ?>"
                        <?= $bloqueado ? 'readonly' : '' ?>
                        required
                    >


                </div>


                <!-- REPRESENTANTE LEGAL -->

                <div class="card">


                    <label for="representante_legal">
                        Representante legal *
                    </label>


                    <input
                        type="text"
                        id="representante_legal"
                        name="representante_legal"
                        placeholder="Nombre del representante legal"
                        maxlength="150"
                        value="<?= htmlspecialchars(
                            $unidad['representante_legal'] ?? ''
                        ) ?>"
                        <?= $bloqueado ? 'readonly' : '' ?>
                        required
                    >


                </div>


                <!-- TIPO DE COPROPIEDAD -->

                <div class="card">


                    <label for="tipo_copropiedad">
                        Tipo de copropiedad *
                    </label>


                    <select
                        name="tipo_copropiedad"
                        id="tipo_copropiedad"
                        class="form-control"
                        <?= $bloqueado ? 'disabled' : '' ?>
                        required
                    >


                        <option value="">
                            Seleccione un tipo
                        </option>


                        <?php foreach (
                            $tiposCopropiedad as $propiedad
                        ): ?>


                            <option
                                value="<?= (int)$propiedad['id'] ?>"
                                <?= (
                                    isset($unidad['id_tipo_copropiedad']) &&
                                    (int)$unidad['id_tipo_copropiedad'] ===
                                    (int)$propiedad['id']
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                <?= htmlspecialchars(
                                    $propiedad['nombre']
                                ) ?>

                            </option>


                        <?php endforeach; ?>


                    </select>


                </div>


            </div>


            <br>


            <!-- ==================================================
                 RESUMEN ESTRUCTURA FÍSICA
            =================================================== -->

            <h3>
                Estructura de la copropiedad
            </h3>


            <div class="bloque filtros">


                <div class="card">


                    <label>
                        Cantidad total de unidades
                    </label>


                    <input
                        type="number"
                        id="cantidad_total_unidades"
                        value="<?= $cantidadTotalUnidades ?>"
                        readonly
                    >


                    <small>

                        Este valor se calcula automáticamente
                        a partir de los grupos de unidades
                        configurados.

                    </small>


                </div>


                <div class="card">


                    <label>
                        Configuración física
                    </label>


                    <p>

                        Los grupos, cantidades, áreas y
                        coeficientes se administran desde
                        Configuración de áreas.

                    </p>


                    <br>


                    <button
                        type="button"
                        class="btn-secondary"
                        onclick="window.location.href='<?= BASE_URL ?>configuracion/basico.php'"
                    >

                        Ir a configuración de áreas

                    </button>


                </div>


            </div>


            <br>


            <!-- ==================================================
                 UBICACIÓN
            =================================================== -->

            <h3>
                Ubicación
            </h3>


            <div class="bloque filtros">


                <!-- PAÍS -->

                <div class="card">


                    <label for="id_pais">
                        País
                    </label>


                    <select
                        name="id_pais"
                        id="id_pais"
                        class="form-control"
                        <?= $bloqueado ? 'disabled' : '' ?>
                    >


                        <option value="">
                            Seleccione un país
                        </option>


                        <?php foreach ($paises as $pais): ?>


                            <option
                                value="<?= (int)$pais['id_pais'] ?>"
                                <?= (
                                    ($unidad['id_pais'] ?? '') ==
                                    $pais['id_pais']
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                <?= htmlspecialchars(
                                    $pais['nombre']
                                ) ?>

                            </option>


                        <?php endforeach; ?>


                    </select>


                </div>


                <!-- DEPARTAMENTO -->

                <div class="card">


                    <label for="id_departamento">
                        Departamento
                    </label>


                    <select
                        name="id_departamento"
                        id="id_departamento"
                        class="form-control"
                        <?= $bloqueado ? 'disabled' : '' ?>
                    >


                        <option value="">
                            Seleccione un departamento
                        </option>


                        <?php foreach (
                            $departamentos as $departamento
                        ): ?>


                            <option
                                value="<?= (int)$departamento['id_departamento'] ?>"
                                data-pais="<?= (int)$departamento['id_pais'] ?>"
                                <?= (
                                    ($unidad['id_departamento'] ?? '') ==
                                    $departamento['id_departamento']
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                <?php if (
                                    !empty($departamento['codigo'])
                                ): ?>

                                    <?= htmlspecialchars(
                                        $departamento['codigo']
                                    ) ?>

                                    -

                                <?php endif; ?>

                                <?= htmlspecialchars(
                                    $departamento['nombre']
                                ) ?>

                            </option>


                        <?php endforeach; ?>


                    </select>


                </div>


                <!-- CIUDAD -->

                <div class="card">


                    <label for="id_ciudad">
                        Ciudad
                    </label>


                    <select
                        name="id_ciudad"
                        id="id_ciudad"
                        class="form-control"
                        <?= $bloqueado ? 'disabled' : '' ?>
                    >


                        <option value="">
                            Seleccione una ciudad
                        </option>


                        <?php foreach ($ciudades as $ciudad): ?>


                            <option
                                value="<?= (int)$ciudad['id_ciudad'] ?>"
                                data-departamento="<?= (int)$ciudad['id_departamento'] ?>"
                                <?= (
                                    ($unidad['id_ciudad'] ?? '') ==
                                    $ciudad['id_ciudad']
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >

                                <?php if (
                                    !empty($ciudad['codigo_dane'])
                                ): ?>

                                    <?= htmlspecialchars(
                                        $ciudad['codigo_dane']
                                    ) ?>

                                    -

                                <?php endif; ?>

                                <?= htmlspecialchars(
                                    $ciudad['nombre']
                                ) ?>

                            </option>


                        <?php endforeach; ?>


                    </select>


                </div>


                <!-- DIRECCIÓN -->

                <div class="card">


                    <label for="direccion">
                        Dirección *
                    </label>


                    <input
                        type="text"
                        id="direccion"
                        name="direccion"
                        maxlength="200"
                        placeholder="Ej. Vía Las Palmas Km 4"
                        value="<?= htmlspecialchars(
                            $unidad['direccion'] ?? ''
                        ) ?>"
                        <?= $bloqueado ? 'readonly' : '' ?>
                        required
                    >


                </div>


                <!-- SECTOR -->

                <div class="card">


                    <label for="sector">
                        Sector
                    </label>


                    <input
                        type="text"
                        id="sector"
                        name="sector"
                        maxlength="150"
                        placeholder="Comuna - Barrio - Zona"
                        value="<?= htmlspecialchars(
                            $unidad['sector'] ?? ''
                        ) ?>"
                        <?= $bloqueado ? 'readonly' : '' ?>
                    >


                </div>


            </div>


            <br>


            <!-- ==================================================
                 CONTACTO
            =================================================== -->

            <h3>
                Datos de contacto
            </h3>


            <div class="bloque filtros">


                <!-- CORREO -->

                <div class="card">


                    <label for="correo_propiedad">
                        Correo electrónico
                    </label>


                    <input
                        type="email"
                        id="correo_propiedad"
                        name="correo_propiedad"
                        maxlength="150"
                        placeholder="administracion@copropiedad.com"
                        value="<?= htmlspecialchars(
                            $unidad['correo'] ?? ''
                        ) ?>"
                        <?= $bloqueado ? 'readonly' : '' ?>
                    >


                </div>


                <!-- TELÉFONO -->

                <div class="card">


                    <label for="telefono_propiedad">
                        Teléfono de contacto
                    </label>


                    <input
                        type="tel"
                        id="telefono_propiedad"
                        name="telefono_propiedad"
                        maxlength="30"
                        placeholder="Teléfono de contacto"
                        value="<?= htmlspecialchars(
                            $unidad['telefono'] ?? ''
                        ) ?>"
                        <?= $bloqueado ? 'readonly' : '' ?>
                    >


                </div>


            </div>


            <br>


            <!-- ==================================================
                 DOCUMENTOS
            =================================================== -->

            <h3>
                Documentos
            </h3>


            <div class="bloque filtros">


                <!-- REGLAMENTO -->

                <div class="form-card">


                    <label for="reglamento">

                        Reglamento de propiedad horizontal
                        (PDF)

                    </label>


                    <input
                        type="file"
                        id="reglamento"
                        name="reglamento"
                        accept="application/pdf"
                        <?= $bloqueado ? 'disabled' : '' ?>
                    >


                    <?php if (
                        !empty($unidad['reglamento'])
                    ): ?>


                        <br>


                        <a
                            href="<?= BASE_URL ?>assets/documentos/<?= htmlspecialchars(
                                $unidad['reglamento']
                            ) ?>"
                            target="_blank"
                            class="btn-secondary"
                        >

                            Ver reglamento

                        </a>


                    <?php endif; ?>


                </div>


                <!-- MANUAL -->

                <div class="form-card">


                    <label for="manual">

                        Manual de convivencia
                        (PDF)

                    </label>


                    <input
                        type="file"
                        id="manual"
                        name="manual"
                        accept="application/pdf"
                        <?= $bloqueado ? 'disabled' : '' ?>
                    >


                    <?php if (
                        !empty($unidad['manual'])
                    ): ?>


                        <br>


                        <a
                            href="<?= BASE_URL ?>assets/documentos/<?= htmlspecialchars(
                                $unidad['manual']
                            ) ?>"
                            target="_blank"
                            class="btn-secondary"
                        >

                            Ver manual

                        </a>


                    <?php endif; ?>


                </div>


            </div>


            <br>


            <!-- ==================================================
                 IDENTIDAD VISUAL
            =================================================== -->

            <h3>
                LOGO
            </h3>


            <div class="bloque filtros">


                <div class="form-card">


                    <label for="logo">
                        Logo de la copropiedad
                    </label>


                    <input
                        type="file"
                        id="logo"
                        name="logo"
                        accept="image/*"
                        <?= $bloqueado ? 'disabled' : '' ?>
                    >


                    <br><br>


                    <?php if (!empty($unidad['logo'])): ?>


                        <img
                            src="<?= BASE_URL ?>assets/logos/<?= htmlspecialchars(
                                $unidad['logo']
                            ) ?>"
                            alt="Logo de la copropiedad"
                            id="logoPreview"
                            class="preview-logo"
                        >


                    <?php else: ?>


                        <img
                            src="<?= BASE_URL ?>assets/img/user.png"
                            alt="Logo por defecto"
                            id="logoPreview"
                            class="preview-logo"
                        >


                    <?php endif; ?>


                </div>


            </div>


            <br>


            <!-- ==================================================
                 ACCIONES
            =================================================== -->

            <div class="form-actions">


                <?php if (!$bloqueado): ?>


                    <button
                        type="reset"
                        class="btn-limpiar"
                    >

                        Cancelar

                    </button>


                    <button
                        type="submit"
                        class="btn-filtrar"
                    >

                        Guardar

                    </button>


                <?php else: ?>


                    <button
                        type="button"
                        id="btnEditar"
                        class="btn-filtrar"
                    >

                        Editar

                    </button>


                    <button
                        type="submit"
                        id="btnGuardar"
                        class="btn-filtrar"
                        style="display:none;"
                    >

                        Guardar

                    </button>


                    <button
                        type="button"
                        id="btnCancelarEdicion"
                        class="btn-limpiar"
                        style="display:none;"
                    >

                        Cancelar

                    </button>


                <?php endif; ?>


                <button
                    type="button"
                    class="btn-filtrar btn-derecha"
                    onclick="window.location.href='<?= BASE_URL ?>configuracion/basico.php'"
                >

                    Configuración de áreas

                </button>


            </div>


        </form>


    </main>


</div>


<script>

    const BASE_URL = "<?= BASE_URL ?>";

</script>


<script src="<?= BASE_URL ?>assets/js/editar_datos.js"></script>


</body>

</html>