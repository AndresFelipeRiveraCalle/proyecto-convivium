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
        "Location: unidades.php?tipo=warning&texto=" .
        urlencode("Unidad no válida.")
    );

    exit;
}


// ==========================================================
// CONSULTAR UNIDAD
// ==========================================================

$sqlUnidad = "
    SELECT

        u.id_unidad,
        u.id_tipo_config,
        u.codigo,
        u.nombre,
        u.piso,
        u.area,
        u.coeficiente,
        u.estado,

        d.nombre_grupo

    FROM unidades u

    LEFT JOIN detalle_tipos_unidad d
        ON d.id_tipo_config = u.id_tipo_config

    WHERE u.id_unidad = :id_unidad

    LIMIT 1
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
        "Location: unidades.php?tipo=warning&texto=" .
        urlencode("La unidad no existe.")
    );

    exit;
}


// ==========================================================
// CONSULTAR PERSONAS ACTIVAS DE LA UNIDAD
// ==========================================================

$sqlPersonas = "
    SELECT

        r.id AS id_relacion,
        r.unidad_id,
        r.usuario_id,
        r.tipo,
        r.recibe_factura,
        r.fecha_desde,
        r.fecha_hasta,
        r.activo,

        u.nombres,
        u.apellidos,
        u.numero_documento,
        u.correo,
        u.telefono,
        u.celular,
        u.foto,

        td.codigo AS tipo_documento

    FROM residente r

    INNER JOIN usuario u
        ON u.id = r.usuario_id

    LEFT JOIN tipos_documento td
        ON td.id_tipo_documento = u.id_tipo_documento

    WHERE
        r.unidad_id = :unidad_id
        AND r.activo = 1
        AND r.fecha_hasta IS NULL

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
// SEPARAR PERSONAS
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


// ==========================================================
// CONSULTAR ESPACIOS ACTUALES DE LA UNIDAD
// ==========================================================

$sqlEspacios = "
    SELECT

        eu.id_espacio_unidad,
        eu.id_unidad,
        eu.usuario_id,
        eu.tipo_espacio,
        eu.codigo,
        eu.area,
        eu.fecha_desde,
        eu.fecha_hasta,
        eu.activo,
        eu.observaciones,
        eu.fecha_creacion,

        up.nombres AS propietario_nombres,
        up.apellidos AS propietario_apellidos,
        up.numero_documento AS propietario_documento

    FROM espacios_unidad eu

    LEFT JOIN usuario up
        ON up.id = eu.usuario_id

    WHERE
        eu.id_unidad = :id_unidad
        AND eu.activo = 1
        AND eu.fecha_hasta IS NULL

    ORDER BY

        CASE eu.tipo_espacio

            WHEN 'PARQUEADERO' THEN 1
            WHEN 'CUARTO_UTIL' THEN 2
            WHEN 'DEPOSITO' THEN 3
            WHEN 'BODEGA' THEN 4

            ELSE 5

        END,

        eu.codigo
";


$stmtEspacios = $conexion->prepare($sqlEspacios);

$stmtEspacios->execute([
    ':id_unidad' => $idUnidad
]);


$espacios = $stmtEspacios->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// UNIDADES ACTIVAS PARA TRANSFERENCIA
// ==========================================================

$sqlUnidadesTransferencia = "
    SELECT

        u.id_unidad,
        u.codigo,
        u.nombre,

        d.nombre_grupo

    FROM unidades u

    LEFT JOIN detalle_tipos_unidad d
        ON d.id_tipo_config = u.id_tipo_config

    WHERE u.activo = 1

    ORDER BY
        d.nombre_grupo,
        u.codigo
";


$stmtUnidadesTransferencia =
    $conexion->query($sqlUnidadesTransferencia);


$unidadesTransferencia =
    $stmtUnidadesTransferencia->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// FUNCIÓN NOMBRE TIPO ESPACIO
// ==========================================================

function nombreTipoEspacio($tipo)
{

    switch ($tipo) {

        case 'PARQUEADERO':
            return 'Parqueadero';

        case 'CUARTO_UTIL':
            return 'Cuarto útil';

        case 'DEPOSITO':
            return 'Depósito';

        case 'BODEGA':
            return 'Bodega';

        case 'OTRO':
            return 'Otro';

        default:
            return $tipo;
    }
}


// ==========================================================
// FUNCIÓN PARA TABLA DE PERSONAS
// ==========================================================

function imprimirTablaPersonas($lista)
{

    if (empty($lista)) {

        return;
    }

    ?>

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
                    <th>Acciones</th>

                </tr>

            </thead>

            <tbody>

            <?php foreach ($lista as $persona): ?>

                <tr>

                    <!-- FOTO -->

                    <td>

                        <?php if (!empty($persona['foto'])): ?>

                            <img
                                src="../<?= htmlspecialchars(
                                    $persona['foto']
                                ) ?>"
                                class="foto-persona-listado"
                            >

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
                            trim(
                                ($persona['nombres'] ?? '') .
                                ' ' .
                                ($persona['apellidos'] ?? '')
                            )
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


                    <!-- FACTURA -->

                    <td>

                        <?php if (
                            (int)$persona['recibe_factura'] === 1
                        ): ?>

                            <span class="activo">
                                Sí
                            </span>

                        <?php else: ?>

                            No

                        <?php endif; ?>

                    </td>


                    <!-- DESDE -->

                    <td>

                        <?= !empty($persona['fecha_desde'])
                            ? date(
                                'd/m/Y',
                                strtotime(
                                    $persona['fecha_desde']
                                )
                            )
                            : '-'
                        ?>

                    </td>


                    <!-- ACCIONES -->

                    <td>

                        <button
                            type="button"
                            class="btn-secondary btnEditarRelacion"
                            data-id="<?= (int)$persona['id_relacion'] ?>"
                        >

                            Editar

                        </button>


                        <button
                            type="button"
                            class="btn-limpiar btnRetirarRelacion"

                            data-id="<?= (int)$persona['id_relacion'] ?>"

                            data-nombre="<?= htmlspecialchars(
                                trim(
                                    ($persona['nombres'] ?? '') .
                                    ' ' .
                                    ($persona['apellidos'] ?? '')
                                ),
                                ENT_QUOTES
                            ) ?>"

                            data-desde="<?= !empty($persona['fecha_desde'])
                                ? date(
                                    'Y-m-d',
                                    strtotime(
                                        $persona['fecha_desde']
                                    )
                                )
                                : ''
                            ?>"
                        >

                            Retirar

                        </button>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

    <?php
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
             ACCIONES SUPERIORES
        =================================================== -->

        <div class="form-actions">


            <button
                type="button"
                class="btn-limpiar"
                onclick="window.location.href='unidades.php?id=<?= (int)$unidad['id_tipo_config'] ?>'"
            >

                ← Volver a unidades

            </button>


            <button
                type="button"
                class="btn-filtrar"
                onclick="abrirModalPersona()"
            >

                + Agregar persona

            </button>


            <button
                type="button"
                class="btn-filtrar"
                onclick="abrirModalEspacio()"
            >

                + Agregar espacio

            </button>


        </div>


        <br>


        <!-- ==================================================
             INFORMACIÓN UNIDAD
        =================================================== -->

        <div class="bloque">


            <h2>

                Unidad
                <?= htmlspecialchars($unidad['codigo']) ?>

            </h2>


            <br>


            <div class="bloque filtros">


                <div class="tabs-container">


                    <div class="tab-content">

                        <strong>Grupo</strong>

                        <br>

                        <?= htmlspecialchars(
                            $unidad['nombre_grupo'] ?? ''
                        ) ?>

                    </div>


                    <div class="tab-content">

                        <strong>Unidad</strong>

                        <br>

                        <?= htmlspecialchars(
                            $unidad['codigo']
                        ) ?>

                    </div>


                    <div class="tab-content">

                        <strong>Nombre</strong>

                        <br>

                        <?= htmlspecialchars(
                            $unidad['nombre'] ?? ''
                        ) ?>

                    </div>


                    <div class="tab-content">

                        <strong>Piso</strong>

                        <br>

                        <?= htmlspecialchars(
                            $unidad['piso'] ?? ''
                        ) ?>

                    </div>


                    <div class="tab-content">

                        <strong>Área</strong>

                        <br>

                        <?php if (
                            $unidad['area'] !== null &&
                            $unidad['area'] !== ''
                        ): ?>

                            <?= number_format(
                                (float)$unidad['area'],
                                2,
                                ',',
                                '.'
                            ) ?> m²

                        <?php else: ?>

                            -

                        <?php endif; ?>

                    </div>


                    <div class="tab-content">

                        <strong>Coeficiente</strong>

                        <br>

                        <?= htmlspecialchars(
                            $unidad['coeficiente'] ?? ''
                        ) ?>

                    </div>


                    <div class="tab-content">

                        <strong>Estado</strong>

                        <br>

                        <?= htmlspecialchars(
                            $unidad['estado'] ?? ''
                        ) ?>

                    </div>


                </div>


            </div>


        </div>


        <br>


        <!-- ==================================================
             ESPACIOS
        =================================================== -->

        <h3>
            Espacios asociados
        </h3>


        <div class="bloque">


            <br>


            <?php if (empty($espacios)): ?>


                <p>
                    Esta unidad no tiene espacios asociados.
                </p>


            <?php else: ?>


                <div class="table-responsive">


                    <table class="tabla">


                        <thead>

                            <tr>

                                <th>Tipo</th>

                                <th>Código</th>

                                <th>Área</th>

                                <th>Propietario</th>

                                <th>Desde</th>

                                <th>Observaciones</th>

                                <th>Acciones</th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach ($espacios as $espacio): ?>


                            <tr>


                                <!-- TIPO -->

                                <td>

                                    <?= htmlspecialchars(
                                        nombreTipoEspacio(
                                            $espacio['tipo_espacio']
                                        )
                                    ) ?>

                                </td>


                                <!-- CÓDIGO -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $espacio['codigo']
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- ÁREA -->

                                <td>

                                    <?php if (
                                        $espacio['area'] !== null &&
                                        $espacio['area'] !== ''
                                    ): ?>

                                        <?= number_format(
                                            (float)$espacio['area'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?> m²

                                    <?php else: ?>

                                        -

                                    <?php endif; ?>

                                </td>


                                <!-- PROPIETARIO -->

                                <td>

                                    <?php if (
                                        !empty($espacio['usuario_id'])
                                    ): ?>

                                        <strong>

                                            <?= htmlspecialchars(
                                                trim(
                                                    ($espacio['propietario_nombres'] ?? '') .
                                                    ' ' .
                                                    ($espacio['propietario_apellidos'] ?? '')
                                                )
                                            ) ?>

                                        </strong>


                                        <?php if (
                                            !empty(
                                                $espacio['propietario_documento']
                                            )
                                        ): ?>

                                            <br>

                                            <small>

                                                <?= htmlspecialchars(
                                                    $espacio['propietario_documento']
                                                ) ?>

                                            </small>

                                        <?php endif; ?>


                                    <?php else: ?>

                                        Sin propietario registrado

                                    <?php endif; ?>

                                </td>


                                <!-- DESDE -->

                                <td>

                                    <?= !empty(
                                        $espacio['fecha_desde']
                                    )
                                        ? date(
                                            'd/m/Y',
                                            strtotime(
                                                $espacio['fecha_desde']
                                            )
                                        )
                                        : '-'
                                    ?>

                                </td>


                                <!-- OBSERVACIONES -->

                                <td>

                                    <?= !empty(
                                        $espacio['observaciones']
                                    )
                                        ? htmlspecialchars(
                                            $espacio['observaciones']
                                        )
                                        : '-'
                                    ?>

                                </td>


                                <!-- ACCIONES -->

                                <td>


                                    <!-- EDITAR -->

                                    <button
                                        type="button"
                                        class="btn-secondary btnEditarEspacio"

                                        data-id="<?= (int)$espacio['id_espacio_unidad'] ?>"

                                        data-tipo="<?= htmlspecialchars(
                                            $espacio['tipo_espacio'],
                                            ENT_QUOTES
                                        ) ?>"

                                        data-codigo="<?= htmlspecialchars(
                                            $espacio['codigo'],
                                            ENT_QUOTES
                                        ) ?>"

                                        data-area="<?= htmlspecialchars(
                                            $espacio['area'] ?? '',
                                            ENT_QUOTES
                                        ) ?>"

                                        data-observaciones="<?= htmlspecialchars(
                                            $espacio['observaciones'] ?? '',
                                            ENT_QUOTES
                                        ) ?>"
                                    >

                                        Editar

                                    </button>


                                    <!-- TRANSFERIR -->

                                    <button
                                        type="button"
                                        class="btn-limpiar btnTransferirEspacio"

                                        data-id="<?= (int)$espacio['id_espacio_unidad'] ?>"

                                        data-codigo="<?= htmlspecialchars(
                                            $espacio['codigo'],
                                            ENT_QUOTES
                                        ) ?>"

                                        data-tipo="<?= htmlspecialchars(
                                            $espacio['tipo_espacio'],
                                            ENT_QUOTES
                                        ) ?>"

                                        data-area="<?= htmlspecialchars(
                                            $espacio['area'] ?? '',
                                            ENT_QUOTES
                                        ) ?>"
                                    >

                                        Transferir

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
             PROPIETARIOS
        =================================================== -->

        <h3>
            Propietarios
        </h3>


        <div class="bloque">

            <br>

            <?php if (empty($propietarios)): ?>

                <p>
                    No hay propietarios asociados a esta unidad.
                </p>

            <?php else: ?>

                <?php imprimirTablaPersonas($propietarios); ?>

            <?php endif; ?>

        </div>


        <br>


        <!-- ==================================================
             INQUILINOS
        =================================================== -->

        <h3>
            Inquilinos
        </h3>


        <div class="bloque">

            <br>

            <?php if (empty($inquilinos)): ?>

                <p>
                    No hay inquilinos asociados a esta unidad.
                </p>

            <?php else: ?>

                <?php imprimirTablaPersonas($inquilinos); ?>

            <?php endif; ?>

        </div>


        <br>


        <!-- ==================================================
             RESIDENTES
        =================================================== -->

        <h3>
            Residentes
        </h3>


        <div class="bloque">

            <br>

            <?php if (empty($residentes)): ?>

                <p>
                    No hay residentes asociados a esta unidad.
                </p>

            <?php else: ?>

                <?php imprimirTablaPersonas($residentes); ?>

            <?php endif; ?>

        </div>


    </main>


</div>


<!-- =========================================================
     MODAL AGREGAR PERSONA
========================================================= -->

<div
    id="modalAgregarPersonaUnidad"
    class="modal"
    style="display:none;"
>


    <div class="modal-contenido">


        <div class="modal-header">


            <h3>
                Agregar persona a la unidad
            </h3>


            <button
                type="button"
                class="modal-cerrar"
                onclick="cerrarModalPersona()"
            >

                &times;

            </button>


        </div>


        <form
            action="<?= BASE_URL ?>actions/agregar_persona_unidad.php"
            method="POST"
        >


            <input
                type="hidden"
                name="unidad_id"
                value="<?= (int)$unidad['id_unidad'] ?>"
            >


            <div class="form-group">

                <label>
                    Número de documento *
                </label>

                <input
                    type="text"
                    name="numero_documento"
                    maxlength="30"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Tipo de relación *
                </label>

                <select
                    name="tipo"
                    required
                >

                    <option value="">
                        Seleccione...
                    </option>

                    <option value="propietario">
                        Propietario
                    </option>

                    <option value="inquilino">
                        Inquilino
                    </option>

                    <option value="residente">
                        Residente
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Recibe factura
                </label>

                <select name="recibe_factura">

                    <option value="0">
                        No
                    </option>

                    <option value="1">
                        Sí
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Fecha desde
                </label>

                <input
                    type="date"
                    name="fecha_desde"
                    value="<?= date('Y-m-d') ?>"
                >

            </div>


            <div class="form-actions">


                <button
                    type="button"
                    class="btn-limpiar"
                    onclick="cerrarModalPersona()"
                >

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="btn-filtrar"
                >

                    Agregar

                </button>


            </div>


        </form>


    </div>


</div>


<!-- =========================================================
     MODAL EDITAR RELACIÓN
========================================================= -->

<div
    id="modalEditarRelacion"
    class="modal"
    style="display:none;"
>


    <div class="modal-contenido">


        <div class="modal-header">


            <h3>
                Editar relación
            </h3>


            <button
                type="button"
                class="modal-cerrar"
                onclick="cerrarModalEditarRelacion()"
            >

                &times;

            </button>


        </div>


        <form
            action="<?= BASE_URL ?>actions/editar_relacion_unidad.php"
            method="POST"
        >


            <input
                type="hidden"
                name="id_relacion"
                id="editar_relacion_id"
            >


            <!-- PERSONA -->

            <div class="form-group">

                <label>
                    Persona
                </label>

                <input
                    type="text"
                    id="editar_relacion_persona"
                    readonly
                >

            </div>


            <!-- DOCUMENTO -->

            <div class="form-group">

                <label>
                    Documento
                </label>

                <input
                    type="text"
                    id="editar_relacion_documento"
                    readonly
                >

            </div>


            <!-- TIPO -->

            <div class="form-group">

                <label>
                    Tipo de relación *
                </label>

                <select
                    name="tipo"
                    id="editar_relacion_tipo"
                    required
                >

                    <option value="propietario">
                        Propietario
                    </option>

                    <option value="inquilino">
                        Inquilino
                    </option>

                    <option value="residente">
                        Residente
                    </option>

                </select>

            </div>


            <!-- FACTURA -->

            <div class="form-group">

                <label>
                    Recibe factura
                </label>

                <select
                    name="recibe_factura"
                    id="editar_relacion_factura"
                >

                    <option value="0">
                        No
                    </option>

                    <option value="1">
                        Sí
                    </option>

                </select>

            </div>


            <!-- FECHA -->

            <div class="form-group">

                <label>
                    Fecha desde *
                </label>

                <input
                    type="date"
                    name="fecha_desde"
                    id="editar_relacion_fecha"
                    required
                >

            </div>


            <div class="form-actions">


                <button
                    type="button"
                    class="btn-limpiar"
                    onclick="cerrarModalEditarRelacion()"
                >

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="btn-filtrar"
                >

                    Guardar cambios

                </button>


            </div>


        </form>


    </div>


</div>


<!-- =========================================================
     MODAL RETIRAR PERSONA
========================================================= -->

<div
    id="modalRetirarRelacion"
    class="modal"
    style="display:none;"
>


    <div class="modal-contenido">


        <div class="modal-header">


            <h3>
                Retirar persona de la unidad
            </h3>


            <button
                type="button"
                class="modal-cerrar"
                onclick="cerrarModalRetirarRelacion()"
            >

                &times;

            </button>


        </div>


        <form
            action="<?= BASE_URL ?>actions/retirar_relacion_unidad.php"
            method="POST"
        >


            <input
                type="hidden"
                name="id_relacion"
                id="retirar_relacion_id"
            >


            <div class="form-group">

                <label>
                    Persona
                </label>

                <input
                    type="text"
                    id="retirar_relacion_persona"
                    readonly
                >

            </div>


            <div class="form-group">

                <label>
                    Fecha de retiro *
                </label>

                <input
                    type="date"
                    name="fecha_hasta"
                    id="retirar_relacion_fecha"
                    value="<?= date('Y-m-d') ?>"
                    required
                >

            </div>


            <p>

                La relación no será eliminada.
                Se conservará como histórico.

            </p>


            <br>


            <div class="form-actions">


                <button
                    type="button"
                    class="btn-limpiar"
                    onclick="cerrarModalRetirarRelacion()"
                >

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="btn-filtrar"
                >

                    Confirmar retiro

                </button>


            </div>


        </form>


    </div>


</div>


<!-- =========================================================
     MODAL AGREGAR ESPACIO
========================================================= -->
<!-- =========================================================
     MODAL AGREGAR ESPACIO
========================================================= -->

<div
    id="modalAgregarEspacioUnidad"
    class="modal"
    style="display:none;"
>

    <div class="modal-contenido">


        <div class="modal-header">

            <h3>
                Agregar espacio
            </h3>

            <button
                type="button"
                class="modal-cerrar"
                onclick="cerrarModalEspacio()"
            >
                &times;
            </button>

        </div>


        <form
            action="<?= BASE_URL ?>actions/agregar_espacio_unidad.php"
            method="POST"
        >


            <!-- UNIDAD -->

            <input
                type="hidden"
                name="id_unidad"
                value="<?= (int)$unidad['id_unidad'] ?>"
            >


            <!-- PROPIETARIO -->

            <div class="form-group">

                <label>
                    Documento del propietario *
                </label>

                <input
                    type="text"
                    name="numero_documento"
                    maxlength="30"
                    required
                    placeholder="Digite el documento del propietario"
                >

                <small>
                    El propietario debe estar registrado previamente en usuarios.
                </small>

            </div>


            <!-- TIPO -->

            <div class="form-group">

                <label>
                    Tipo de espacio *
                </label>

                <select
                    name="tipo_espacio"
                    required
                >

                    <option value="">
                        Seleccione...
                    </option>

                    <option value="PARQUEADERO">
                        Parqueadero
                    </option>

                    <option value="CUARTO_UTIL">
                        Cuarto útil
                    </option>

                    <option value="DEPOSITO">
                        Depósito
                    </option>

                    <option value="BODEGA">
                        Bodega
                    </option>

                    <option value="OTRO">
                        Otro
                    </option>

                </select>

            </div>


            <!-- CÓDIGO -->

            <div class="form-group">

                <label>
                    Código *
                </label>

                <input
                    type="text"
                    name="codigo"
                    maxlength="50"
                    required
                    placeholder="Ej: P-001 o CU-01"
                >

            </div>


            <!-- ÁREA -->

            <div class="form-group">

                <label>
                    Área
                </label>

                <input
                    type="number"
                    name="area"
                    step="0.01"
                    min="0"
                >

            </div>


            <!-- FECHA -->

            <div class="form-group">

                <label>
                    Fecha desde *
                </label>

                <input
                    type="date"
                    name="fecha_desde"
                    value="<?= date('Y-m-d') ?>"
                    required
                >

            </div>


            <!-- OBSERVACIONES -->

            <div class="form-group">

                <label>
                    Observaciones
                </label>

                <textarea
                    name="observaciones"
                    rows="3"
                    maxlength="255"
                ></textarea>

            </div>


            <!-- BOTONES -->

            <div class="form-actions">

                <button
                    type="button"
                    class="btn-limpiar"
                    onclick="cerrarModalEspacio()"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="btn-filtrar"
                >
                    Agregar espacio
                </button>

            </div>


        </form>


    </div>

</div>


<!-- =========================================================
     MODAL EDITAR ESPACIO
========================================================= -->

<div
    id="modalEditarEspacio"
    class="modal"
    style="display:none;"
>


    <div class="modal-contenido">


        <div class="modal-header">


            <h3>
                Editar espacio
            </h3>


            <button
                type="button"
                class="modal-cerrar"
                onclick="cerrarModalEditarEspacio()"
            >

                &times;

            </button>


        </div>


        <form
            action="<?= BASE_URL ?>actions/editar_espacio_unidad.php"
            method="POST"
        >


            <input
                type="hidden"
                name="id_espacio_unidad"
                id="editar_espacio_id"
            >

            <input
                type="hidden"
                name="origen"
                value="personas_unidad"
            >

            <div class="form-group">

                <label>
                    Tipo *
                </label>

                <select
                    name="tipo_espacio"
                    id="editar_espacio_tipo"
                    required
                >

                    <option value="PARQUEADERO">
                        Parqueadero
                    </option>

                    <option value="CUARTO_UTIL">
                        Cuarto útil
                    </option>

                    <option value="DEPOSITO">
                        Depósito
                    </option>

                    <option value="BODEGA">
                        Bodega
                    </option>

                    <option value="OTRO">
                        Otro
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Código *
                </label>

                <input
                    type="text"
                    name="codigo"
                    id="editar_espacio_codigo"
                    maxlength="50"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Área
                </label>

                <input
                    type="number"
                    name="area"
                    id="editar_espacio_area"
                    step="0.01"
                    min="0"
                >

            </div>


            <div class="form-group">

                <label>
                    Observaciones
                </label>

                <textarea
                    name="observaciones"
                    id="editar_espacio_observaciones"
                    rows="3"
                    maxlength="255"
                ></textarea>

            </div>


            <p>

                Para cambiar propietario o unidad,
                utiliza la opción Transferir.

            </p>


            <br>


            <div class="form-actions">


                <button
                    type="button"
                    class="btn-limpiar"
                    onclick="cerrarModalEditarEspacio()"
                >

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="btn-filtrar"
                >

                    Guardar cambios

                </button>


            </div>


        </form>


    </div>


</div>


<!-- =========================================================
     MODAL TRANSFERIR ESPACIO
========================================================= -->

<div
    id="modalTransferirEspacio"
    class="modal"
    style="display:none;"
>


    <div class="modal-contenido">


        <div class="modal-header">


            <h3>
                Transferir espacio
            </h3>


            <button
                type="button"
                class="modal-cerrar"
                onclick="cerrarModalTransferirEspacio()"
            >

                &times;

            </button>


        </div>


        <form
            action="<?= BASE_URL ?>actions/transferir_espacio.php"
            method="POST"
        >


            <input
                type="hidden"
                name="id_espacio_unidad"
                id="transferir_id_espacio_unidad"
            >

            <input
                type="hidden"
                name="origen"
                value="personas_unidad"
            >

            <div class="form-group">

                <label>
                    Espacio
                </label>

                <input
                    type="text"
                    id="transferir_codigo"
                    readonly
                >

            </div>


            <div class="form-group">

                <label>
                    Tipo
                </label>

                <input
                    type="text"
                    id="transferir_tipo"
                    readonly
                >

            </div>


            <div class="form-group">

                <label>
                    Área
                </label>

                <input
                    type="text"
                    id="transferir_area"
                    readonly
                >

            </div>


            <div class="form-group">

                <label>
                    Documento del nuevo propietario *
                </label>

                <input
                    type="text"
                    name="numero_documento"
                    id="transferir_documento_propietario"
                    maxlength="30"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Unidad asociada
                </label>

                <select
                    name="id_unidad"
                    id="transferir_id_unidad"
                >

                    <option value="">
                        Ninguna
                    </option>


                    <?php foreach (
                        $unidadesTransferencia
                        as $unidadTransferencia
                    ): ?>


                        <option
                            value="<?= (int)$unidadTransferencia['id_unidad'] ?>"
                        >

                            <?= htmlspecialchars(
                                $unidadTransferencia['codigo']
                            ) ?>

                            <?php if (
                                !empty(
                                    $unidadTransferencia['nombre']
                                )
                            ): ?>

                                -
                                <?= htmlspecialchars(
                                    $unidadTransferencia['nombre']
                                ) ?>

                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $unidadTransferencia['nombre_grupo']
                                )
                            ): ?>

                                (
                                <?= htmlspecialchars(
                                    $unidadTransferencia['nombre_grupo']
                                ) ?>
                                )

                            <?php endif; ?>

                        </option>


                    <?php endforeach; ?>


                </select>

            </div>


            <div class="form-group">

                <label>
                    Fecha de transferencia *
                </label>

                <input
                    type="date"
                    name="fecha_transferencia"
                    id="transferir_fecha"
                    value="<?= date('Y-m-d') ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Observaciones
                </label>

                <textarea
                    name="observaciones"
                    id="transferir_observaciones"
                    rows="3"
                    maxlength="255"
                ></textarea>

            </div>


            <div class="form-actions">


                <button
                    type="button"
                    class="btn-limpiar"
                    onclick="cerrarModalTransferirEspacio()"
                >

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="btn-filtrar"
                >

                    Transferir espacio

                </button>


            </div>


        </form>


    </div>


</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

const BASE_URL =
    <?= json_encode(BASE_URL) ?>;


// ==========================================================
// AGREGAR PERSONA
// ==========================================================

function abrirModalPersona()
{

    const modal =
        document.getElementById(
            "modalAgregarPersonaUnidad"
        );


    if (modal) {

        modal.style.display = "flex";
    }
}


function cerrarModalPersona()
{

    const modal =
        document.getElementById(
            "modalAgregarPersonaUnidad"
        );


    if (modal) {

        modal.style.display = "none";
    }
}


// ==========================================================
// EDITAR RELACIÓN
// ==========================================================

function cerrarModalEditarRelacion()
{

    const modal =
        document.getElementById(
            "modalEditarRelacion"
        );


    if (modal) {

        modal.style.display = "none";
    }
}


// ==========================================================
// RETIRAR RELACIÓN
// ==========================================================

function cerrarModalRetirarRelacion()
{

    const modal =
        document.getElementById(
            "modalRetirarRelacion"
        );


    if (modal) {

        modal.style.display = "none";
    }
}


// ==========================================================
// AGREGAR ESPACIO
// ==========================================================

function abrirModalEspacio()
{

    const modal =
        document.getElementById(
            "modalAgregarEspacioUnidad"
        );


    if (modal) {

        modal.style.display = "flex";
    }
}


function cerrarModalEspacio()
{

    const modal =
        document.getElementById(
            "modalAgregarEspacioUnidad"
        );


    if (modal) {

        modal.style.display = "none";
    }
}


// ==========================================================
// EDITAR ESPACIO
// ==========================================================

function cerrarModalEditarEspacio()
{

    const modal =
        document.getElementById(
            "modalEditarEspacio"
        );


    if (modal) {

        modal.style.display = "none";
    }
}


// ==========================================================
// TRANSFERIR
// ==========================================================

function cerrarModalTransferirEspacio()
{

    const modal =
        document.getElementById(
            "modalTransferirEspacio"
        );


    if (modal) {

        modal.style.display = "none";
    }
}


// ==========================================================
// DOM READY
// ==========================================================

document.addEventListener(
    "DOMContentLoaded",
    function () {


        // ==================================================
        // EDITAR RELACIÓN
        // ==================================================

        document
            .querySelectorAll(
                ".btnEditarRelacion"
            )
            .forEach(
                function (boton) {


                    boton.addEventListener(
                        "click",
                        async function () {


                            const id =
                                this.dataset.id;


                            try {


                                const respuesta =
                                    await fetch(
                                        BASE_URL +
                                        "actions/obtener_relacion_unidad.php?id=" +
                                        encodeURIComponent(id)
                                    );


                                const resultado =
                                    await respuesta.json();


                                if (!resultado.success) {

                                    alert(
                                        resultado.message ||
                                        "No fue posible consultar la relación."
                                    );

                                    return;
                                }


                                const r =
                                    resultado.data;


                                document.getElementById(
                                    "editar_relacion_id"
                                ).value =
                                    r.id;


                                document.getElementById(
                                    "editar_relacion_persona"
                                ).value =
                                    (
                                        (r.nombres || "") +
                                        " " +
                                        (r.apellidos || "")
                                    ).trim();


                                document.getElementById(
                                    "editar_relacion_documento"
                                ).value =
                                    r.numero_documento || "";


                                document.getElementById(
                                    "editar_relacion_tipo"
                                ).value =
                                    r.tipo;


                                document.getElementById(
                                    "editar_relacion_factura"
                                ).value =
                                    String(
                                        r.recibe_factura
                                    );


                                let fechaDesde = "";


                                if (r.fecha_desde) {

                                    fechaDesde =
                                        r.fecha_desde.substring(
                                            0,
                                            10
                                        );
                                }


                                document.getElementById(
                                    "editar_relacion_fecha"
                                ).value =
                                    fechaDesde;


                                document.getElementById(
                                    "modalEditarRelacion"
                                ).style.display =
                                    "flex";


                            } catch (error) {


                                console.error(error);


                                alert(
                                    "No fue posible cargar la relación."
                                );

                            }


                        }
                    );


                }
            );


        // ==================================================
        // RETIRAR RELACIÓN
        // ==================================================

        document
            .querySelectorAll(
                ".btnRetirarRelacion"
            )
            .forEach(
                function (boton) {


                    boton.addEventListener(
                        "click",
                        function () {


                            document.getElementById(
                                "retirar_relacion_id"
                            ).value =
                                this.dataset.id || "";


                            document.getElementById(
                                "retirar_relacion_persona"
                            ).value =
                                this.dataset.nombre || "";


                            const campoFecha =
                                document.getElementById(
                                    "retirar_relacion_fecha"
                                );


                            if (
                                this.dataset.desde
                            ) {

                                campoFecha.min =
                                    this.dataset.desde;

                            } else {

                                campoFecha.removeAttribute(
                                    "min"
                                );
                            }


                            document.getElementById(
                                "modalRetirarRelacion"
                            ).style.display =
                                "flex";


                        }
                    );


                }
            );


        // ==================================================
        // EDITAR ESPACIO
        // ==================================================

        document
            .querySelectorAll(
                ".btnEditarEspacio"
            )
            .forEach(
                function (boton) {


                    boton.addEventListener(
                        "click",
                        function () {


                            document.getElementById(
                                "editar_espacio_id"
                            ).value =
                                this.dataset.id || "";


                            document.getElementById(
                                "editar_espacio_tipo"
                            ).value =
                                this.dataset.tipo || "";


                            document.getElementById(
                                "editar_espacio_codigo"
                            ).value =
                                this.dataset.codigo || "";


                            document.getElementById(
                                "editar_espacio_area"
                            ).value =
                                this.dataset.area || "";


                            document.getElementById(
                                "editar_espacio_observaciones"
                            ).value =
                                this.dataset.observaciones || "";


                            document.getElementById(
                                "modalEditarEspacio"
                            ).style.display =
                                "flex";


                        }
                    );


                }
            );


        // ==================================================
        // TRANSFERIR ESPACIO
        // ==================================================

        document
            .querySelectorAll(
                ".btnTransferirEspacio"
            )
            .forEach(
                function (boton) {


                    boton.addEventListener(
                        "click",
                        function () {


                            document.getElementById(
                                "transferir_id_espacio_unidad"
                            ).value =
                                this.dataset.id || "";


                            document.getElementById(
                                "transferir_codigo"
                            ).value =
                                this.dataset.codigo || "";


                            let tipo =
                                this.dataset.tipo || "";


                            let tipoVisible =
                                tipo;


                            switch (tipo) {

                                case "PARQUEADERO":

                                    tipoVisible =
                                        "Parqueadero";

                                    break;


                                case "CUARTO_UTIL":

                                    tipoVisible =
                                        "Cuarto útil";

                                    break;


                                case "DEPOSITO":

                                    tipoVisible =
                                        "Depósito";

                                    break;


                                case "BODEGA":

                                    tipoVisible =
                                        "Bodega";

                                    break;


                                case "OTRO":

                                    tipoVisible =
                                        "Otro";

                                    break;

                            }


                            document.getElementById(
                                "transferir_tipo"
                            ).value =
                                tipoVisible;


                            const area =
                                this.dataset.area || "";


                            document.getElementById(
                                "transferir_area"
                            ).value =
                                area !== ""
                                    ? area + " m²"
                                    : "";


                            document.getElementById(
                                "transferir_documento_propietario"
                            ).value = "";


                            document.getElementById(
                                "transferir_id_unidad"
                            ).value = "";


                            document.getElementById(
                                "transferir_observaciones"
                            ).value = "";


                            document.getElementById(
                                "modalTransferirEspacio"
                            ).style.display =
                                "flex";


                        }
                    );


                }
            );


        // ==================================================
        // CERRAR MODALES AL HACER CLIC FUERA
        // ==================================================

        window.addEventListener(
            "click",
            function (event) {


                const modales = [

                    document.getElementById(
                        "modalAgregarPersonaUnidad"
                    ),

                    document.getElementById(
                        "modalEditarRelacion"
                    ),

                    document.getElementById(
                        "modalRetirarRelacion"
                    ),

                    document.getElementById(
                        "modalAgregarEspacioUnidad"
                    ),

                    document.getElementById(
                        "modalEditarEspacio"
                    ),

                    document.getElementById(
                        "modalTransferirEspacio"
                    )

                ];


                modales.forEach(
                    function (modal) {


                        if (
                            modal &&
                            event.target === modal
                        ) {

                            modal.style.display =
                                "none";
                        }


                    }
                );


            }
        );


    }
);

</script>


</body>

</html>