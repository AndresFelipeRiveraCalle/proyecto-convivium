<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// VALIDAR UNIDAD
// ==========================================================

$idUnidad = isset($_GET['id_unidad'])
    ? (int)$_GET['id_unidad']
    : 0;

if ($idUnidad <= 0) {

    header(
        "Location: unidades.php?tipo=warning&texto=Unidad no válida."
    );

    exit;
}


// ==========================================================
// CONSULTAR UNIDAD
// ==========================================================

$sqlUnidad = "SELECT u.id_unidad,u.id_tipo_config,u.codigo,u.nombre,u.piso,u.area,u.coeficiente,u.estado,d.nombre_grupo
    FROM unidades u
    LEFT JOIN detalle_tipos_unidad d ON d.id_tipo_config = u.id_tipo_config
    WHERE u.id_unidad = :id_unidad
";

$stmtUnidad = $conexion->prepare($sqlUnidad);

$stmtUnidad->execute([
    ':id_unidad' => $idUnidad
]);

$unidad = $stmtUnidad->fetch(PDO::FETCH_ASSOC);


// ==========================================================
// VALIDAR QUE EXISTA
// ==========================================================

if (!$unidad) {

    header(
        "Location: unidades.php?tipo=warning&texto=La unidad no existe."
    );

    exit;
}


// ==========================================================
// CONSULTAR PERSONAS DE LA UNIDAD
// ==========================================================

$sqlPersonas = " SELECT r.id AS id_relacion, r.tipo, r.fecha_desde, r.recibe_factura, r.fecha_hasta,r.activo,u.id AS usuario_id,
        u.nombres, u.apellidos, u.numero_documento, u.correo, u.telefono, u.celular, u.foto,
        td.codigo AS tipo_documento
    FROM residente r
    INNER JOIN usuario u ON u.id = r.usuario_id
    LEFT JOIN tipos_documento td ON td.id_tipo_documento = u.id_tipo_documento
    WHERE r.unidad_id = :unidad_id AND r.activo = 1
    ORDER BY
        CASE r.tipo
            WHEN 'propietario' THEN 1
            WHEN 'inquilino' THEN 2
            WHEN 'residente' THEN 3
            ELSE 4
        END,
        u.apellidos,
        u.nombres
";

$stmtPersonas = $conexion->prepare($sqlPersonas);

$stmtPersonas->execute([
    ':unidad_id' => $idUnidad
]);

$personas = $stmtPersonas->fetchAll(PDO::FETCH_ASSOC);

// ==========================================================
// SEPARAR POR TIPO
// ==========================================================

$propietarios = [];
$inquilinos = [];
$residentes = [];

// ==========================================================
// SEPARAR POR TIPO
// ==========================================================

$propietarios = [];
$inquilinos = [];
$residentes = [];

foreach ($personas as $persona) {

    if ($persona['tipo'] === 'propietario') {

        $propietarios[] = $persona;

    } elseif ($persona['tipo'] === 'inquilino') {

        $inquilinos[] = $persona;

    } elseif ($persona['tipo'] === 'residente') {

        $residentes[] = $persona;
    }
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


        <!-- ==================================================
             ENCABEZADO
        =================================================== -->

        <div class="form-actions">
            <button type="button" class="btn-limpiar"
                onclick="window.location.href='unidades.php?id=<?= $unidad['id_tipo_config'] ?? '' ?>'">
                ← Volver a unidades
            </button>

            <button type="button" class="btn-filtrar" id="btnAgregarPersonaUnidad">
                + Agregar persona
            </button>
        </div>


        <!-- ==================================================
             INFORMACIÓN DE LA UNIDAD
        =================================================== -->

        <div class="bloque">

            <h2> Personas de la unidad</h2>
            <br>
            <div class="form-grid-persona">
                <div>
                    <strong>Unidad</strong>
                    <br>

                    <?= htmlspecialchars(
                        $unidad['codigo']
                    ) ?>
                </div>

                <div>
                    <strong>Nombre</strong>
                    <br>

                    <?= htmlspecialchars(
                        $unidad['nombre'] ?? ''
                    ) ?>

                </div>


                <div>
                    <strong>Piso</strong>
                    <br>
                    <?= htmlspecialchars(
                        $unidad['piso'] ?? ''
                    ) ?>

                </div>

                <div>
                    <strong>Área</strong>
                    <br>
                    <?= htmlspecialchars(
                        $unidad['area'] ?? ''
                    ) ?> m²
                </div>
            </div>
        </div>


        <br>


        <!-- ==================================================
             PROPIETARIOS
        =================================================== -->
        <h3>Propietarios</h3>
        <div class="bloque">

            
            <br>

            <?php if (empty($propietarios)): ?>

                <p>No hay propietarios asociados a esta unidad.</p>
            <?php else: ?>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Foto</th>
                                <th>Documento</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Celular</th>
                                <th>Factura</th>
                                <th>Desde</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($propietarios as $persona): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($persona['foto'])): ?>
                                            <img
                                                src="../<?= htmlspecialchars($persona['foto']) ?>"
                                                class="foto-persona-listado">

                                        <?php else: ?>

                                            <span class="sin-foto">
                                                Sin foto
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars(
                                            ($persona['tipo_documento'] ?? '') .
                                            ' - ' .
                                            ($persona['numero_documento'] ?? '')
                                        ) ?>

                                    </td>
                                    <td>
                                        <?= htmlspecialchars(
                                            $persona['nombres'] .
                                            ' ' .
                                            $persona['apellidos']
                                        ) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars(
                                            $persona['correo'] ?? ''
                                        ) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars(
                                            $persona['celular'] ?? ''
                                        ) ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= ($persona['recibe_factura'] ?? 0) == 1 ?>">
                                            <?= ($persona['recibe_factura'] ?? 0) == 1 ? 'SÍ' : 'NO' ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= !empty($persona['fecha_desde'])
                                            ? date(
                                                'd/m/Y',
                                                strtotime($persona['fecha_desde'])
                                            )
                                            : ''
                                        ?>
                                    </td>

                                    <td>
                                        <button
                                            type="button"class="btn-secondary btnEditarRelacion"
                                            data-id="<?= $persona['id_relacion'] ?>">
                                            ✏ Editar
                                        </button>

                                        <button type="button" class="btn-limpiar btnEliminarRelacion"
                                            data-id="<?= $persona['id_relacion'] ?>">
                                            Retirar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                </div>

            <?php endif; ?>

        </div>


        <br>


        <!-- ==================================================
             INQUILINOS
        =================================================== -->
        <h3>Inquilinos</h3>
        <div class="bloque">

            <br>

            <?php if (empty($inquilinos)): ?>

                <p>
                    No hay inquilinos asociados a esta unidad.
                </p>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="tabla">

                        <thead>

                            <tr>

                                <th>Foto</th>
                                <th>Documento</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Celular</th>
                                <th>Factura</th>
                                <th>Desde</th>
                                <th></th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php foreach ($inquilinos as $persona): ?>

                            <tr>

                                <td>

                                    <?php if (!empty($persona['foto'])): ?>

                                        <img
                                            src="../<?= htmlspecialchars($persona['foto']) ?>"
                                            class="foto-persona-listado">

                                    <?php else: ?>

                                        <span class="sin-foto">
                                            Sin foto
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        ($persona['tipo_documento'] ?? '') .
                                        ' - ' .
                                        ($persona['numero_documento'] ?? '')
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $persona['nombres'] .
                                        ' ' .
                                        $persona['apellidos']
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $persona['correo'] ?? ''
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $persona['celular'] ?? ''
                                    ) ?>

                                </td>
                                <td>
                                    <span class="badge <?= ($persona['recibe_factura'] ?? 0) == 1 ?>">
                                        <?= ($persona['recibe_factura'] ?? 0) == 1 ? 'SÍ' : 'NO' ?>
                                    </span>
                                </td>

                                <td>

                                    <?= !empty($persona['fecha_desde'])
                                        ? date(
                                            'd/m/Y',
                                            strtotime($persona['fecha_desde'])
                                        )
                                        : ''
                                    ?>

                                </td>


                                <td>

                                    <button
                                        type="button"
                                        class="btn-secondary btnEditarRelacion"
                                        data-id="<?= $persona['id_relacion'] ?>">

                                        ✏ Editar

                                    </button>

                                    <button
                                        type="button"
                                        class="btn-limpiar btnEliminarRelacion"
                                        data-id="<?= $persona['id_relacion'] ?>">

                                        Retirar

                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>


        <br>


        <!-- ==================================================
             RESIDENTES
        =================================================== -->


            <h3>Residentes</h3>
        <div class="bloque">

            <br>

            <?php if (empty($residentes)): ?>

                <p>
                    No hay residentes asociados a esta unidad.
                </p>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="tabla">

                        <thead>

                            <tr>
                                <th>Foto</th>
                                <th>Documento</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Celular</th>
                                <th>Factura</th>
                                <th>Desde</th>
                                <th></th>
                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($residentes as $persona): ?>

                            <tr>

                                <!-- FOTO -->
                                <td>

                                    <?php if (!empty($persona['foto'])): ?>

                                        <img
                                            src="../<?= htmlspecialchars($persona['foto']) ?>"
                                            class="foto-persona-listado">

                                    <?php else: ?>

                                        <span class="sin-foto">
                                            Sin foto
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- DOCUMENTO -->
                                <td>

                                    <?= htmlspecialchars(
                                        ($persona['tipo_documento'] ?? '') .
                                        ' - ' .
                                        ($persona['numero_documento'] ?? '')
                                    ) ?>

                                </td>


                                <!-- NOMBRE -->
                                <td>

                                    <?= htmlspecialchars(
                                        ($persona['nombres'] ?? '') .
                                        ' ' .
                                        ($persona['apellidos'] ?? '')
                                    ) ?>

                                </td>


                                <!-- CORREO -->
                                <td>

                                    <?= htmlspecialchars(
                                        $persona['correo'] ?? ''
                                    ) ?>

                                </td>


                                <!-- CELULAR -->
                                <td>

                                    <?= htmlspecialchars(
                                        $persona['celular'] ?? ''
                                    ) ?>

                                </td>
                                <!-- Recibe factura -->
                                <td>
                                    <span class="badge <?= ($persona['recibe_factura'] ?? 0) == 1 ?>">
                                        <?= ($persona['recibe_factura'] ?? 0) == 1 ? 'SÍ' : 'NO' ?>
                                    </span>
                                </td>

                                <!-- FECHA -->
                                <td>

                                    <?= !empty($persona['fecha_desde'])
                                        ? date(
                                            'd/m/Y',
                                            strtotime($persona['fecha_desde'])
                                        )
                                        : ''
                                    ?>

                                </td>


                                <!-- ACCIONES -->
                                <td>

                                    <button
                                        type="button"
                                        class="btn-secondary btnEditarRelacion"
                                        data-id="<?= $persona['id_relacion'] ?>">

                                        ✏ Editar

                                    </button>


                                    <button
                                        type="button"
                                        class="btn-limpiar btnEliminarRelacion"
                                        data-id="<?= $persona['id_relacion'] ?>">

                                        Retirar

                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>


    </main>

</div>


<!-- =========================================================
     MODAL AGREGAR PERSONA
========================================================= -->

<div
    id="modalAgregarPersonaUnidad"
    class="modal">

    <div class="modal-contenido">

        <span
            id="cerrarAgregarPersonaUnidad"
            class="cerrar">

            &times;

        </span>


        <h2>
            Agregar persona a la unidad
        </h2>

        <br>


        <form action="<?= BASE_URL ?>actions/agregar_persona_unidad.php" method="POST">

            <input type="hidden" name="unidad_id" value="<?= $unidad['id_unidad'] ?>">

            <label> Número de documento </label>
            <input type="text" name="numero_documento" required maxlength="30" placeholder="Digite el documento">
            <br><br>

            <label> Tipo de relación </label>

            <select name="tipo" required>
                <option value=""> Seleccione...</option>
                <option value="propietario">Propietario</option>
                <option value="inquilino">Inquilino </option>
                <option value="residente">Residente</option>
            </select>
            <br><br>

            <label>Recibe factura</label>
            <select name="recibe_factura">
                <option value="1">No</option>
                <option value="0">Sí</option>
            </select>
            <br><br>

            <label>
                Fecha desde
            </label>

            <input type="date" name="fecha_desde">
            <br><br>

            <div class="botones-persona">
                <button type="button" class="btn-limpiar" id="cancelarAgregarPersonaUnidad">
                    Cancelar
                </button>

                <button type="submit" class="btn-filtrar">
                    Agregar
                </button>
            </div>
        </form>
    </div>
</div>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("modalAgregarPersonaUnidad");
    const btnAbrir = document.getElementById("btnAgregarPersonaUnidad");
    const btnCerrar = document.getElementById("cerrarAgregarPersonaUnidad");
    const btnCancelar = document.getElementById("cancelarAgregarPersonaUnidad");

    if (btnAbrir) {
        btnAbrir.addEventListener("click", function () {
            modal.style.display = "flex";
        });
    }

    if (btnCerrar) {
        btnCerrar.addEventListener("click", function () {
            modal.style.display = "none";
        });
    }

    if (btnCancelar) {
        btnCancelar.addEventListener("click", function () {
            modal.style.display = "none";
        });
    }

    window.addEventListener("click", function (event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    });

});

</script>

</body>

</html>