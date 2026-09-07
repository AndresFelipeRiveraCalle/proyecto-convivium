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
// TIPOS DE ESPACIO PERMITIDOS
// ==========================================================

$tiposPermitidos = [
    'PARQUEADERO',
    'CUARTO_UTIL',
    'DEPOSITO',
    'BODEGA',
    'OTRO'
];


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
// FILTRAR POR TIPO
// ==========================================================

if (
    $tipoFiltro !== '' &&
    in_array(
        $tipoFiltro,
        $tiposPermitidos,
        true
    )
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
// EJECUTAR CONSULTA ESPACIOS
// ==========================================================

$stmtEspacios =
    $conexion->prepare(
        $sqlEspacios
    );


$stmtEspacios->execute(
    $parametros
);


$espacios =
    $stmtEspacios->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// RESUMEN GENERAL
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
    $conexion->query(
        $sqlResumen
    );


$resumen =
    $stmtResumen->fetch(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// CONSULTAR TODO EL HISTÓRICO
// ==========================================================
//
// Se hace una sola consulta.
// Después agrupamos los registros por:
// tipo_espacio + codigo.
//
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
    $conexion->query(
        $sqlHistorico
    );


$historicoGeneral =
    $stmtHistorico->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// AGRUPAR HISTÓRICO POR ESPACIO
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


                <!-- TOTAL -->

                <div class="tab-content">

                    <strong>
                        Total
                    </strong>

                    <br>

                    <?= (int)($resumen['total'] ?? 0) ?>

                </div>


                <!-- PARQUEADEROS -->

                <div class="tab-content">

                    <strong>
                        Parqueaderos
                    </strong>

                    <br>

                    <?= (int)($resumen['parqueaderos'] ?? 0) ?>

                </div>


                <!-- CUARTOS ÚTILES -->

                <div class="tab-content">

                    <strong>
                        Cuartos útiles
                    </strong>

                    <br>

                    <?= (int)($resumen['cuartos_utiles'] ?? 0) ?>

                </div>


                <!-- DEPÓSITOS -->

                <div class="tab-content">

                    <strong>
                        Depósitos
                    </strong>

                    <br>

                    <?= (int)($resumen['depositos'] ?? 0) ?>

                </div>


                <!-- BODEGAS -->

                <div class="tab-content">

                    <strong>
                        Bodegas
                    </strong>

                    <br>

                    <?= (int)($resumen['bodegas'] ?? 0) ?>

                </div>


                <!-- SIN UNIDAD -->

                <div class="tab-content">

                    <strong>
                        Sin unidad
                    </strong>

                    <br>

                    <?= (int)($resumen['sin_unidad'] ?? 0) ?>

                </div>


                <!-- SIN PROPIETARIO -->

                <div class="tab-content">

                    <strong>
                        Sin propietario
                    </strong>

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


                    <!-- BUSCAR -->

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


                    <!-- TIPO -->

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


                    <!-- BOTONES -->

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


                            <!-- ==================================
                                 FILA PRINCIPAL
                            =================================== -->

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


                                <!-- UNIDAD -->

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


                                    <button
                                        type="button"
                                        class="btn-secondary btnVerHistorico"
                                        data-id="<?= (int)$espacio['id_espacio_unidad'] ?>"
                                    >

                                        Ver histórico

                                    </button>


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


                            <!-- ==================================
                                 FILA HISTÓRICO
                            =================================== -->

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
                                            empty(
                                                $historicoEspacio
                                            )
                                        ): ?>


                                            <p>

                                                No existe histórico
                                                para este espacio.

                                            </p>


                                        <?php else: ?>


                                            <div class="table-responsive">


                                                <table class="tabla">


                                                    <thead>


                                                        <tr>

                                                            <th>
                                                                Propietario
                                                            </th>

                                                            <th>
                                                                Documento
                                                            </th>

                                                            <th>
                                                                Unidad
                                                            </th>

                                                            <th>
                                                                Desde
                                                            </th>

                                                            <th>
                                                                Hasta
                                                            </th>

                                                            <th>
                                                                Estado
                                                            </th>

                                                            <th>
                                                                Observaciones
                                                            </th>

                                                        </tr>


                                                    </thead>


                                                    <tbody>


                                                    <?php foreach (
                                                        $historicoEspacio
                                                        as $registro
                                                    ): ?>


                                                        <tr>


                                                            <!-- =================
                                                                 PROPIETARIO
                                                            ================== -->

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


                                                            <!-- =================
                                                                 DOCUMENTO
                                                            ================== -->

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


                                                            <!-- =================
                                                                 UNIDAD
                                                            ================== -->

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


                                                            <!-- =================
                                                                 DESDE
                                                            ================== -->

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


                                                            <!-- =================
                                                                 HASTA
                                                            ================== -->

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


                                                            <!-- =================
                                                                 ESTADO
                                                            ================== -->

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


                                                                    <span>

                                                                        Histórico

                                                                    </span>


                                                                <?php endif; ?>


                                                            </td>


                                                            <!-- =================
                                                                 OBSERVACIONES
                                                            ================== -->

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


<!-- ==========================================================
     JAVASCRIPT
========================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        // ======================================================
        // BOTONES VER HISTÓRICO
        // ======================================================

        const botonesHistorico =
            document.querySelectorAll(
                ".btnVerHistorico"
            );


        botonesHistorico.forEach(
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


                        // ==================================================
                        // SI ESTÁ CERRADO
                        // ==================================================

                        if (
                            fila.style.display === "none" ||
                            fila.style.display === ""
                        ) {


                            // ==============================================
                            // CERRAR TODOS LOS DEMÁS
                            // ==============================================

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


                            // ==============================================
                            // RESTAURAR TEXTO DE BOTONES
                            // ==============================================

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


                            // ==============================================
                            // ABRIR ACTUAL
                            // ==============================================

                            fila.style.display =
                                "table-row";


                            this.textContent =
                                "Ocultar histórico";


                        } else {


                            // ==================================================
                            // CERRAR ACTUAL
                            // ==================================================

                            fila.style.display =
                                "none";


                            this.textContent =
                                "Ver histórico";

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