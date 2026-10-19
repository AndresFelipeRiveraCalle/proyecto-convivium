<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// FILTROS
// ==========================================================

$buscar = isset($_GET['buscar'])
    ? trim($_GET['buscar'])
    : '';


$tipoFiltro = isset($_GET['tipo_espacio'])
    ? trim($_GET['tipo_espacio'])
    : '';


// ==========================================================
// TIPOS PERMITIDOS
// ==========================================================

$tiposPermitidos = [
    'PARQUEADERO',
    'CUARTO_UTIL',
    'DEPOSITO',
    'BODEGA',
    'OTRO'
];


// ==========================================================
// UNIDADES ACTIVAS
// ==========================================================

$sqlUnidades = "
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


$stmtUnidades =
    $conexion->query($sqlUnidades);


$unidadesDisponibles =
    $stmtUnidades->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// CONSULTAR ESPACIOS ACTUALES
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

        u.codigo AS codigo_unidad,
        u.nombre AS nombre_unidad,

        d.nombre_grupo,

        us.nombres,
        us.apellidos,
        us.numero_documento

    FROM espacios_unidad eu

    LEFT JOIN unidades u
        ON u.id_unidad = eu.id_unidad

    LEFT JOIN detalle_tipos_unidad d
        ON d.id_tipo_config = u.id_tipo_config

    LEFT JOIN usuario us
        ON us.id = eu.usuario_id

    WHERE
        eu.activo = 1
        AND eu.fecha_hasta IS NULL
";


$parametros = [];


// ==========================================================
// FILTRO TIPO
// ==========================================================

if (
    $tipoFiltro !== '' &&
    in_array($tipoFiltro, $tiposPermitidos, true)
) {

    $sqlEspacios .= "
        AND eu.tipo_espacio = :tipo_espacio
    ";

    $parametros[':tipo_espacio'] =
        $tipoFiltro;
}


// ==========================================================
// BUSCADOR
// ==========================================================

if ($buscar !== '') {

    $sqlEspacios .= "
        AND
        (
            eu.codigo LIKE :buscar_codigo
            OR us.numero_documento LIKE :buscar_documento
            OR us.nombres LIKE :buscar_nombres
            OR us.apellidos LIKE :buscar_apellidos
            OR u.codigo LIKE :buscar_unidad
            OR u.nombre LIKE :buscar_nombre_unidad
        )
    ";


    $textoBuscar =
        '%' . $buscar . '%';


    $parametros[':buscar_codigo'] =
        $textoBuscar;

    $parametros[':buscar_documento'] =
        $textoBuscar;

    $parametros[':buscar_nombres'] =
        $textoBuscar;

    $parametros[':buscar_apellidos'] =
        $textoBuscar;

    $parametros[':buscar_unidad'] =
        $textoBuscar;

    $parametros[':buscar_nombre_unidad'] =
        $textoBuscar;
}


// ==========================================================
// ORDEN
// ==========================================================

$sqlEspacios .= "
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


// ==========================================================
// EJECUTAR CONSULTA
// ==========================================================

$stmtEspacios =
    $conexion->prepare($sqlEspacios);


$stmtEspacios->execute($parametros);


$espacios =
    $stmtEspacios->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// RESUMEN
// ==========================================================

$sqlResumen = "
    SELECT

        COUNT(*) AS total,

        SUM(
            CASE
                WHEN tipo_espacio = 'PARQUEADERO'
                THEN 1
                ELSE 0
            END
        ) AS parqueaderos,

        SUM(
            CASE
                WHEN tipo_espacio = 'CUARTO_UTIL'
                THEN 1
                ELSE 0
            END
        ) AS cuartos_utiles,

        SUM(
            CASE
                WHEN tipo_espacio = 'DEPOSITO'
                THEN 1
                ELSE 0
            END
        ) AS depositos,

        SUM(
            CASE
                WHEN tipo_espacio = 'BODEGA'
                THEN 1
                ELSE 0
            END
        ) AS bodegas,

        SUM(
            CASE
                WHEN id_unidad IS NULL
                THEN 1
                ELSE 0
            END
        ) AS sin_unidad,

        SUM(
            CASE
                WHEN usuario_id IS NULL
                THEN 1
                ELSE 0
            END
        ) AS sin_propietario

    FROM espacios_unidad

    WHERE
        activo = 1
        AND fecha_hasta IS NULL
";


$stmtResumen =
    $conexion->query($sqlResumen);


$resumen =
    $stmtResumen->fetch(PDO::FETCH_ASSOC);


// ==========================================================
// HISTÓRICO GENERAL
// ==========================================================

$sqlHistorico = "
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

        u.codigo AS codigo_unidad,
        u.nombre AS nombre_unidad,

        d.nombre_grupo,

        us.nombres,
        us.apellidos,
        us.numero_documento

    FROM espacios_unidad eu

    LEFT JOIN unidades u
        ON u.id_unidad = eu.id_unidad

    LEFT JOIN detalle_tipos_unidad d
        ON d.id_tipo_config = u.id_tipo_config

    LEFT JOIN usuario us
        ON us.id = eu.usuario_id

    WHERE eu.activo = 1

    ORDER BY
        eu.tipo_espacio,
        eu.codigo,
        eu.fecha_desde DESC,
        eu.id_espacio_unidad DESC
";


$stmtHistorico =
    $conexion->query($sqlHistorico);


$historicoGeneral =
    $stmtHistorico->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// AGRUPAR HISTÓRICO
// ==========================================================

$historicos = [];


foreach ($historicoGeneral as $registro) {

    $clave =
        $registro['tipo_espacio'] .
        '|' .
        $registro['codigo'];


    if (!isset($historicos[$clave])) {

        $historicos[$clave] = [];
    }


    $historicos[$clave][] =
        $registro;
}


// ==========================================================
// NOMBRE DEL TIPO
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
                onclick="window.location.href='unidades.php'"
            >
                ← Volver a unidades
            </button>


            <button
                type="button"
                class="btn-filtrar"
                onclick="abrirModalNuevoEspacio()"
            >
                + Nuevo espacio
            </button>


        </div>


        <br>


        <!-- ==================================================
             ENCABEZADO
        =================================================== -->

        <h2 align="center">
            Espacios
        </h2>


        <br>


        <p>

            Inventario general de parqueaderos,
            cuartos útiles, depósitos, bodegas
            y otros espacios de la copropiedad.

        </p>


        <br>


        <!-- ==================================================
             RESUMEN
        =================================================== -->

        <div class="bloque filtros">


            <div class="tabs-container">


                <div class="tab-content">

                    <strong>Total</strong>
                    <br>
                    <?= (int)($resumen['total'] ?? 0) ?>

                </div>


                <div class="tab-content">

                    <strong>Parqueaderos</strong>
                    <br>
                    <?= (int)($resumen['parqueaderos'] ?? 0) ?>

                </div>


                <div class="tab-content">

                    <strong>Cuartos útiles</strong>
                    <br>
                    <?= (int)($resumen['cuartos_utiles'] ?? 0) ?>

                </div>


                <div class="tab-content">

                    <strong>Depósitos</strong>
                    <br>
                    <?= (int)($resumen['depositos'] ?? 0) ?>

                </div>


                <div class="tab-content">

                    <strong>Bodegas</strong>
                    <br>
                    <?= (int)($resumen['bodegas'] ?? 0) ?>

                </div>


                <div class="tab-content">

                    <strong>Sin unidad</strong>
                    <br>
                    <?= (int)($resumen['sin_unidad'] ?? 0) ?>

                </div>


                <div class="tab-content">

                    <strong>Sin propietario</strong>
                    <br>
                    <?= (int)($resumen['sin_propietario'] ?? 0) ?>

                </div>


            </div>


        </div>


        <br>


        <!-- ==================================================
             FILTROS
        =================================================== -->

        <div class="bloque">


            <form
                method="GET"
                action="espacios.php"
            >


                <div class="bloque filtros">


                    <div class="form-group">

                        <label for="buscar">
                            Buscar
                        </label>

                        <input
                            type="text"
                            name="buscar"
                            id="buscar"
                            value="<?= htmlspecialchars($buscar) ?>"
                            placeholder="Código, propietario, documento o unidad"
                        >

                    </div>


                    <div class="form-group">

                        <label for="tipo_espacio">
                            Tipo de espacio
                        </label>

                        <select
                            name="tipo_espacio"
                            id="tipo_espacio"
                        >

                            <option value="">
                                Todos
                            </option>

                            <option
                                value="PARQUEADERO"
                                <?= $tipoFiltro === 'PARQUEADERO'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Parqueadero
                            </option>

                            <option
                                value="CUARTO_UTIL"
                                <?= $tipoFiltro === 'CUARTO_UTIL'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Cuarto útil
                            </option>

                            <option
                                value="DEPOSITO"
                                <?= $tipoFiltro === 'DEPOSITO'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Depósito
                            </option>

                            <option
                                value="BODEGA"
                                <?= $tipoFiltro === 'BODEGA'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Bodega
                            </option>

                            <option
                                value="OTRO"
                                <?= $tipoFiltro === 'OTRO'
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Otro
                            </option>

                        </select>

                    </div>


                    <div class="form-actions">

                        <button
                            type="button"
                            class="btn-limpiar"
                            onclick="window.location.href='espacios.php'"
                        >
                            Limpiar
                        </button>

                        <button
                            type="submit"
                            class="btn-filtrar"
                        >
                            Buscar
                        </button>

                    </div>


                </div>


            </form>


        </div>


        <br>


        <!-- ==================================================
             ESPACIOS ACTUALES
        =================================================== -->

        <div class="bloque">


            <h3>
                Espacios actuales
            </h3>


            <br>


            <?php if (empty($espacios)): ?>


                <p>

                    No existen espacios que coincidan
                    con los filtros seleccionados.

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
                                <th>Unidad</th>
                                <th>Desde</th>
                                <th>Observaciones</th>
                                <th>Acciones</th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach ($espacios as $espacio): ?>


                            <?php

                            $claveHistorico =
                                $espacio['tipo_espacio'] .
                                '|' .
                                $espacio['codigo'];


                            $historicoEspacio =
                                $historicos[$claveHistorico]
                                ?? [];

                            ?>


                            <!-- ==============================
                                 FILA PRINCIPAL
                            =============================== -->

                            <tr>


                                <td>

                                    <?= htmlspecialchars(
                                        nombreTipoEspacio(
                                            $espacio['tipo_espacio']
                                        )
                                    ) ?>

                                </td>


                                <td>

                                    <strong>
                                        <?= htmlspecialchars(
                                            $espacio['codigo']
                                        ) ?>
                                    </strong>

                                </td>


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


                                <td>

                                    <?php if (
                                        !empty($espacio['usuario_id'])
                                    ): ?>

                                        <strong>

                                            <?= htmlspecialchars(
                                                trim(
                                                    ($espacio['nombres'] ?? '') .
                                                    ' ' .
                                                    ($espacio['apellidos'] ?? '')
                                                )
                                            ) ?>

                                        </strong>

                                        <?php if (
                                            !empty(
                                                $espacio['numero_documento']
                                            )
                                        ): ?>

                                            <br>

                                            <small>

                                                <?= htmlspecialchars(
                                                    $espacio['numero_documento']
                                                ) ?>

                                            </small>

                                        <?php endif; ?>

                                    <?php else: ?>

                                        <span class="inactivo">
                                            Sin propietario
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php if (
                                        !empty($espacio['id_unidad'])
                                    ): ?>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $espacio['codigo_unidad']
                                            ) ?>

                                        </strong>

                                        <?php if (
                                            !empty(
                                                $espacio['nombre_unidad']
                                            )
                                        ): ?>

                                            <br>

                                            <?= htmlspecialchars(
                                                $espacio['nombre_unidad']
                                            ) ?>

                                        <?php endif; ?>

                                        <?php if (
                                            !empty(
                                                $espacio['nombre_grupo']
                                            )
                                        ): ?>

                                            <br>

                                            <small>

                                                <?= htmlspecialchars(
                                                    $espacio['nombre_grupo']
                                                ) ?>

                                            </small>

                                        <?php endif; ?>

                                    <?php else: ?>

                                        Ninguna

                                    <?php endif; ?>

                                </td>


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


                                    <!-- HISTÓRICO -->

                                    <button
                                        type="button"
                                        class="btn-secondary btnVerHistorico"

                                        data-id="<?= (int)$espacio['id_espacio_unidad'] ?>"
                                    >

                                        Ver histórico

                                    </button>


                                    <!-- UNIDAD -->

                                    <?php if (
                                        !empty($espacio['id_unidad'])
                                    ): ?>

                                        <a
                                            href="personas_unidad.php?id_unidad=<?= (int)$espacio['id_unidad'] ?>"
                                            class="btn-secondary"
                                        >

                                            Ver unidad

                                        </a>

                                    <?php endif; ?>


                                </td>


                            </tr>


                            <!-- ==============================
                                 HISTÓRICO
                            =============================== -->

                            <tr
                                id="historico_<?= (int)$espacio['id_espacio_unidad'] ?>"
                                class="fila-historico"
                                style="display:none;"
                            >

                                <td colspan="8">


                                    <div class="bloque">


                                        <h4>

                                            Histórico de

                                            <?= htmlspecialchars(
                                                nombreTipoEspacio(
                                                    $espacio['tipo_espacio']
                                                )
                                            ) ?>

                                            <?= htmlspecialchars(
                                                $espacio['codigo']
                                            ) ?>

                                        </h4>


                                        <br>


                                        <?php if (
                                            empty($historicoEspacio)
                                        ): ?>

                                            <p>
                                                No existe histórico para este espacio.
                                            </p>

                                        <?php else: ?>


                                            <div class="table-responsive">


                                                <table class="tabla">


                                                    <thead>

                                                        <tr>

                                                            <th>Propietario</th>
                                                            <th>Documento</th>
                                                            <th>Unidad</th>
                                                            <th>Desde</th>
                                                            <th>Hasta</th>
                                                            <th>Estado</th>
                                                            <th>Observaciones</th>

                                                        </tr>

                                                    </thead>


                                                    <tbody>


                                                    <?php foreach (
                                                        $historicoEspacio
                                                        as $registro
                                                    ): ?>


                                                        <tr>


                                                            <td>

                                                                <?php if (
                                                                    !empty(
                                                                        $registro['usuario_id']
                                                                    )
                                                                ): ?>

                                                                    <strong>

                                                                        <?= htmlspecialchars(
                                                                            trim(
                                                                                ($registro['nombres'] ?? '') .
                                                                                ' ' .
                                                                                ($registro['apellidos'] ?? '')
                                                                            )
                                                                        ) ?>

                                                                    </strong>

                                                                <?php else: ?>

                                                                    Sin propietario registrado

                                                                <?php endif; ?>

                                                            </td>


                                                            <td>

                                                                <?= !empty(
                                                                    $registro['numero_documento']
                                                                )
                                                                    ? htmlspecialchars(
                                                                        $registro['numero_documento']
                                                                    )
                                                                    : '-'
                                                                ?>

                                                            </td>


                                                            <td>

                                                                <?php if (
                                                                    !empty(
                                                                        $registro['id_unidad']
                                                                    )
                                                                ): ?>

                                                                    <strong>

                                                                        <?= htmlspecialchars(
                                                                            $registro['codigo_unidad']
                                                                        ) ?>

                                                                    </strong>

                                                                    <?php if (
                                                                        !empty(
                                                                            $registro['nombre_unidad']
                                                                        )
                                                                    ): ?>

                                                                        <br>

                                                                        <?= htmlspecialchars(
                                                                            $registro['nombre_unidad']
                                                                        ) ?>

                                                                    <?php endif; ?>

                                                                    <?php if (
                                                                        !empty(
                                                                            $registro['nombre_grupo']
                                                                        )
                                                                    ): ?>

                                                                        <br>

                                                                        <small>

                                                                            <?= htmlspecialchars(
                                                                                $registro['nombre_grupo']
                                                                            ) ?>

                                                                        </small>

                                                                    <?php endif; ?>

                                                                <?php else: ?>

                                                                    Ninguna

                                                                <?php endif; ?>

                                                            </td>


                                                            <td>

                                                                <?= !empty(
                                                                    $registro['fecha_desde']
                                                                )
                                                                    ? date(
                                                                        'd/m/Y',
                                                                        strtotime(
                                                                            $registro['fecha_desde']
                                                                        )
                                                                    )
                                                                    : '-'
                                                                ?>

                                                            </td>


                                                            <td>

                                                                <?= !empty(
                                                                    $registro['fecha_hasta']
                                                                )
                                                                    ? date(
                                                                        'd/m/Y',
                                                                        strtotime(
                                                                            $registro['fecha_hasta']
                                                                        )
                                                                    )
                                                                    : 'Actual'
                                                                ?>

                                                            </td>


                                                            <td>

                                                                <?php if (
                                                                    empty(
                                                                        $registro['fecha_hasta']
                                                                    )
                                                                ): ?>

                                                                    <span class="activo">
                                                                        Actual
                                                                    </span>

                                                                <?php else: ?>

                                                                    Histórico

                                                                <?php endif; ?>

                                                            </td>


                                                            <td>

                                                                <?= !empty(
                                                                    $registro['observaciones']
                                                                )
                                                                    ? htmlspecialchars(
                                                                        $registro['observaciones']
                                                                    )
                                                                    : '-'
                                                                ?>

                                                            </td>


                                                        </tr>


                                                    <?php endforeach; ?>


                                                    </tbody>


                                                </table>


                                            </div>


                                        <?php endif; ?>


                                    </div>


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
     MODAL NUEVO ESPACIO
========================================================= -->

<div
    id="modalNuevoEspacio"
    class="modal"
    style="display:none;"
>


    <div class="modal-contenido">


        <div class="modal-header">


            <h3>
                Nuevo espacio
            </h3>


            <button
                type="button"
                class="modal-cerrar"
                onclick="cerrarModalNuevoEspacio()"
            >
                &times;
            </button>


        </div>


        <form
            action="<?= BASE_URL ?>actions/agregar_espacio.php"
            method="POST"
        >


            <div class="form-group">

                <label>
                    Documento del propietario *
                </label>

                <input
                    type="text"
                    name="numero_documento"
                    maxlength="30"
                    required
                    placeholder="Digite el documento"
                >

            </div>


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


            <div class="form-group">

                <label>
                    Código *
                </label>

                <input
                    type="text"
                    name="codigo"
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
                    step="0.01"
                    min="0"
                >

            </div>


            <div class="form-group">

                <label>
                    Unidad asociada
                </label>

                <select name="id_unidad">

                    <option value="">
                        Ninguna
                    </option>


                    <?php foreach (
                        $unidadesDisponibles
                        as $unidadDisponible
                    ): ?>

                        <option
                            value="<?= (int)$unidadDisponible['id_unidad'] ?>"
                        >

                            <?= htmlspecialchars(
                                $unidadDisponible['codigo']
                            ) ?>

                            <?php if (
                                !empty($unidadDisponible['nombre'])
                            ): ?>

                                -
                                <?= htmlspecialchars(
                                    $unidadDisponible['nombre']
                                ) ?>

                            <?php endif; ?>

                            <?php if (
                                !empty($unidadDisponible['nombre_grupo'])
                            ): ?>

                                (
                                <?= htmlspecialchars(
                                    $unidadDisponible['nombre_grupo']
                                ) ?>
                                )

                            <?php endif; ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


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


            <div class="form-actions">


                <button
                    type="button"
                    class="btn-limpiar"
                    onclick="cerrarModalNuevoEspacio()"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="btn-filtrar"
                >
                    Guardar espacio
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
                value="espacios"
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
                Para cambiar propietario o unidad utiliza Transferir.
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
                value="espacios"
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
                        $unidadesDisponibles
                        as $unidadDisponible
                    ): ?>

                        <option
                            value="<?= (int)$unidadDisponible['id_unidad'] ?>"
                        >

                            <?= htmlspecialchars(
                                $unidadDisponible['codigo']
                            ) ?>

                            <?php if (
                                !empty($unidadDisponible['nombre'])
                            ): ?>

                                -
                                <?= htmlspecialchars(
                                    $unidadDisponible['nombre']
                                ) ?>

                            <?php endif; ?>

                            <?php if (
                                !empty($unidadDisponible['nombre_grupo'])
                            ): ?>

                                (
                                <?= htmlspecialchars(
                                    $unidadDisponible['nombre_grupo']
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


// ==========================================================
// NUEVO ESPACIO
// ==========================================================

function abrirModalNuevoEspacio()
{

    const modal =
        document.getElementById(
            "modalNuevoEspacio"
        );


    if (modal) {

        modal.style.display =
            "flex";
    }
}


function cerrarModalNuevoEspacio()
{

    const modal =
        document.getElementById(
            "modalNuevoEspacio"
        );


    if (modal) {

        modal.style.display =
            "none";
    }
}


// ==========================================================
// EDITAR
// ==========================================================

function cerrarModalEditarEspacio()
{

    const modal =
        document.getElementById(
            "modalEditarEspacio"
        );


    if (modal) {

        modal.style.display =
            "none";
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

        modal.style.display =
            "none";
    }
}


// ==========================================================
// DOM READY
// ==========================================================

document.addEventListener(
    "DOMContentLoaded",
    function () {


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
        // HISTÓRICO
        // ==================================================

        document
            .querySelectorAll(
                ".btnVerHistorico"
            )
            .forEach(
                function (boton) {


                    boton.addEventListener(
                        "click",
                        function () {


                            const id =
                                this.dataset.id;


                            const fila =
                                document.getElementById(
                                    "historico_" + id
                                );


                            if (!fila) {

                                return;
                            }


                            if (
                                fila.style.display === "none" ||
                                fila.style.display === ""
                            ) {


                                // ==========================
                                // CERRAR OTROS
                                // ==========================

                                document
                                    .querySelectorAll(
                                        ".fila-historico"
                                    )
                                    .forEach(
                                        function (otraFila) {

                                            otraFila.style.display =
                                                "none";

                                        }
                                    );


                                document
                                    .querySelectorAll(
                                        ".btnVerHistorico"
                                    )
                                    .forEach(
                                        function (otroBoton) {

                                            otroBoton.textContent =
                                                "Ver histórico";

                                        }
                                    );


                                // ==========================
                                // ABRIR ACTUAL
                                // ==========================

                                fila.style.display =
                                    "table-row";


                                this.textContent =
                                    "Ocultar histórico";


                            } else {


                                fila.style.display =
                                    "none";


                                this.textContent =
                                    "Ver histórico";

                            }


                        }
                    );


                }
            );


        // ==================================================
        // CERRAR MODALES HACIENDO CLIC FUERA
        // ==================================================

        window.addEventListener(
            "click",
            function (event) {


                const modalNuevo =
                    document.getElementById(
                        "modalNuevoEspacio"
                    );


                const modalEditar =
                    document.getElementById(
                        "modalEditarEspacio"
                    );


                const modalTransferir =
                    document.getElementById(
                        "modalTransferirEspacio"
                    );


                if (
                    modalNuevo &&
                    event.target === modalNuevo
                ) {

                    cerrarModalNuevoEspacio();

                }


                if (
                    modalEditar &&
                    event.target === modalEditar
                ) {

                    cerrarModalEditarEspacio();

                }


                if (
                    modalTransferir &&
                    event.target === modalTransferir
                ) {

                    cerrarModalTransferirEspacio();

                }


            }
        );


    }
);

</script>


</body>

</html>