<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// FILTROS
// ==========================================================

$buscar = trim($_GET['buscar'] ?? '');
$estado = trim($_GET['estado'] ?? '');


// ==========================================================
// CARGAR CONCEPTOS DISPONIBLES PARA CARGOS
// ==========================================================
//
// Excluimos:
//
// 1 = Administración
// 4 = Intereses de mora
//
// Estos conceptos tienen proceso automático y no deben
// generarse como cargos particulares.
//
// ==========================================================

$sqlConceptos = "
    SELECT
        id_concepto,
        nombre,
        descripcion,
        tipo_calculo,
        obligatorio

    FROM conceptos_facturacion

    WHERE
        estado = 1
        AND id_concepto NOT IN (1, 4)

    ORDER BY nombre
";

$stmtConceptos = $conexion->query($sqlConceptos);

$conceptos = $stmtConceptos->fetchAll(
    PDO::FETCH_ASSOC
);


// ==========================================================
// CARGAR GRUPOS / TIPOS DE UNIDAD
// ==========================================================

$sqlGrupos = "
    SELECT
        id_tipo_config,
        nombre_grupo,
        cantidad_unidades

    FROM detalle_tipos_unidad

    WHERE activo = 1

    ORDER BY nombre_grupo
";

$stmtGrupos = $conexion->query($sqlGrupos);

$grupos = $stmtGrupos->fetchAll(
    PDO::FETCH_ASSOC
);


// ==========================================================
// CARGAR UNIDADES
// ==========================================================

$sqlUnidades = "
    SELECT
        u.id_unidad,
        u.codigo,
        u.nombre,
        u.id_tipo_config,
        dtu.nombre_grupo

    FROM unidades u

    INNER JOIN detalle_tipos_unidad dtu
        ON dtu.id_tipo_config = u.id_tipo_config

    WHERE
        u.activo = 1
        AND dtu.activo = 1

    ORDER BY
        dtu.nombre_grupo,
        u.codigo
";

$stmtUnidades = $conexion->query($sqlUnidades);

$unidades = $stmtUnidades->fetchAll(
    PDO::FETCH_ASSOC
);


// ==========================================================
// CONSULTA DE CARGOS
// ==========================================================

$sql = "
    SELECT
        cf.id_cargo,
        cf.id_concepto,
        cf.nombre,
        cf.descripcion,
        cf.tipo_aplicacion,
        cf.id_tipo_config,
        cf.id_unidad,
        cf.tipo_distribucion,
        cf.valor_total,
        cf.numero_cuotas,
        cf.periodo_inicio,
        cf.estado,
        cf.observaciones,
        cf.fecha_creacion,

        c.nombre AS concepto,

        dtu.nombre_grupo,

        u.codigo AS unidad_codigo,

        (
            SELECT COUNT(*)

            FROM cargos_facturacion_unidades cfu

            WHERE
                cfu.id_cargo = cf.id_cargo
                AND cfu.estado = 'ACTIVO'

        ) AS unidades_asignadas,

        (
            SELECT COUNT(*)

            FROM cargos_facturacion_cuotas cfc

            INNER JOIN cargos_facturacion_unidades cfu2
                ON cfu2.id_cargo_unidad =
                   cfc.id_cargo_unidad

            WHERE
                cfu2.id_cargo = cf.id_cargo
                AND cfc.estado <> 'ANULADA'

        ) AS total_cuotas,

        (
            SELECT COUNT(*)

            FROM cargos_facturacion_cuotas cfc

            INNER JOIN cargos_facturacion_unidades cfu3
                ON cfu3.id_cargo_unidad =
                   cfc.id_cargo_unidad

            WHERE
                cfu3.id_cargo = cf.id_cargo
                AND cfc.estado = 'FACTURADA'

        ) AS cuotas_facturadas

    FROM cargos_facturacion cf

    INNER JOIN conceptos_facturacion c
        ON c.id_concepto = cf.id_concepto

    LEFT JOIN detalle_tipos_unidad dtu
        ON dtu.id_tipo_config = cf.id_tipo_config

    LEFT JOIN unidades u
        ON u.id_unidad = cf.id_unidad

    WHERE 1 = 1
";

$parametros = [];


// ==========================================================
// FILTRO BUSCAR
// ==========================================================

if ($buscar !== '') {

    $sql .= "
        AND (
            cf.nombre LIKE :buscar
            OR cf.descripcion LIKE :buscar
            OR c.nombre LIKE :buscar
            OR u.codigo LIKE :buscar
            OR dtu.nombre_grupo LIKE :buscar
        )
    ";

    $parametros[':buscar'] =
        '%' . $buscar . '%';
}


// ==========================================================
// FILTRO ESTADO
// ==========================================================

$estadosPermitidos = [
    'BORRADOR',
    'ACTIVO',
    'FINALIZADO',
    'ANULADO'
];

if (
    $estado !== ''
    && in_array(
        $estado,
        $estadosPermitidos,
        true
    )
) {

    $sql .= "
        AND cf.estado = :estado
    ";

    $parametros[':estado'] = $estado;
}


// ==========================================================
// ORDEN
// ==========================================================

$sql .= "
    ORDER BY
        cf.periodo_inicio DESC,
        cf.id_cargo DESC
";

$stmt = $conexion->prepare($sql);

$stmt->execute($parametros);

$cargos = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);


// ==========================================================
// FUNCIONES AUXILIARES
// ==========================================================

function nombreTipoAplicacion($tipo)
{
    switch ($tipo) {

        case 'TODAS_UNIDADES':
            return 'Todas las unidades';

        case 'TIPO_UNIDAD':
            return 'Grupo de unidades';

        case 'UNIDAD':
            return 'Unidad específica';

        default:
            return $tipo;
    }
}


function nombreTipoDistribucion($tipo)
{
    switch ($tipo) {

        case 'VALOR_FIJO':
            return 'Valor fijo';

        case 'METRO_CUADRADO':
            return 'Metro cuadrado';

        case 'COEFICIENTE':
            return 'Coeficiente';

        default:
            return $tipo;
    }
}


function nombreEstadoCargo($estado)
{
    switch ($estado) {

        case 'BORRADOR':
            return 'Borrador';

        case 'ACTIVO':
            return 'Activo';

        case 'FINALIZADO':
            return 'Finalizado';

        case 'ANULADO':
            return 'Anulado';

        default:
            return $estado;
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


        <!-- ======================================================
             ENCABEZADO
        ======================================================= -->

        <h2 align="center">
            Cargos y cuotas
        </h2>


        <br>


        <p>
            Administre cuotas extraordinarias, multas,
            cobros de zonas comunes y otros cargos que puedan
            aplicarse a una o varias unidades.
        </p>


        <br>


        <!-- ======================================================
             INFORMACIÓN
        ======================================================= -->

        <div class="info-box">

            <strong>
                Facturación automática
            </strong>

            <p>
                La administración y los intereses de mora
                no se crean desde esta pantalla.
            </p>

            <p>
                Los demás conceptos pueden generarse como
                cargos de una o varias cuotas.
            </p>

        </div>


        <br>


        <!-- ======================================================
             FILTROS
        ======================================================= -->

        <div class="bloque filtros">


            <form
                method="GET"
                action=""
            >


                <div class="form-group">

                    <label for="buscar">
                        Buscar
                    </label>

                    <input
                        type="text"
                        name="buscar"
                        id="buscar"
                        value="<?= htmlspecialchars($buscar) ?>"
                        placeholder="Nombre, concepto, unidad..."
                    >

                </div>


                <div class="form-group">

                    <label for="estado">
                        Estado
                    </label>

                    <select
                        name="estado"
                        id="estado"
                    >

                        <option value="">
                            Todos
                        </option>

                        <option
                            value="BORRADOR"
                            <?= $estado === 'BORRADOR'
                                ? 'selected'
                                : '' ?>
                        >
                            Borrador
                        </option>

                        <option
                            value="ACTIVO"
                            <?= $estado === 'ACTIVO'
                                ? 'selected'
                                : '' ?>
                        >
                            Activo
                        </option>

                        <option
                            value="FINALIZADO"
                            <?= $estado === 'FINALIZADO'
                                ? 'selected'
                                : '' ?>
                        >
                            Finalizado
                        </option>

                        <option
                            value="ANULADO"
                            <?= $estado === 'ANULADO'
                                ? 'selected'
                                : '' ?>
                        >
                            Anulado
                        </option>

                    </select>

                </div>


                <div class="form-actions">

                    <a
                        href="<?= BASE_URL ?>configuracion/cargos.php"
                        class="btn-limpiar"
                    >
                        Limpiar
                    </a>


                    <button
                        type="submit"
                        class="btn-filtrar"
                    >
                        Buscar
                    </button>


                    <button
                        type="button"
                        class="btn-guardar"
                        id="abrirNuevoCargo"
                    >
                        Nuevo cargo
                    </button>

                </div>


            </form>


        </div>


        <br>


        <!-- ======================================================
             LISTADO DE CARGOS
        ======================================================= -->

        <div class="bloque">


            <div class="tabla-responsive">


                <table class="tabla">


                    <thead>

                        <tr>

                            <th>
                                Cargo
                            </th>

                            <th>
                                Concepto
                            </th>

                            <th>
                                Aplicación
                            </th>

                            <th>
                                Distribución
                            </th>

                            <th>
                                Valor
                            </th>

                            <th>
                                Cuotas
                            </th>

                            <th>
                                Inicio
                            </th>

                            <th>
                                Estado
                            </th>

                            <th>
                                Acciones
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (empty($cargos)): ?>


                        <tr>

                            <td
                                colspan="9"
                                align="center"
                            >

                                No existen cargos registrados.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach ($cargos as $cargo): ?>


                            <?php

                            $detalleAplicacion = '';

                            if (
                                $cargo['tipo_aplicacion']
                                === 'TODAS_UNIDADES'
                            ) {

                                $detalleAplicacion =
                                    'Todas las unidades';

                            } elseif (
                                $cargo['tipo_aplicacion']
                                === 'TIPO_UNIDAD'
                            ) {

                                $detalleAplicacion =
                                    $cargo['nombre_grupo']
                                    ?? 'Grupo';

                            } elseif (
                                $cargo['tipo_aplicacion']
                                === 'UNIDAD'
                            ) {

                                $detalleAplicacion =
                                    'Unidad '
                                    . (
                                        $cargo['unidad_codigo']
                                        ?? ''
                                    );

                            }


                            $totalCuotas =
                                (int)$cargo['total_cuotas'];

                            $cuotasFacturadas =
                                (int)$cargo['cuotas_facturadas'];

                            ?>


                            <tr>


                                <!-- CARGO -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $cargo['nombre']
                                        ) ?>

                                    </strong>


                                    <?php if (
                                        !empty(
                                            $cargo['descripcion']
                                        )
                                    ): ?>

                                        <br>

                                        <small>

                                            <?= htmlspecialchars(
                                                $cargo['descripcion']
                                            ) ?>

                                        </small>

                                    <?php endif; ?>


                                    <br>

                                    <small>

                                        Unidades:

                                        <?= (int)$cargo[
                                            'unidades_asignadas'
                                        ] ?>

                                    </small>

                                </td>


                                <!-- CONCEPTO -->

                                <td>

                                    <?= htmlspecialchars(
                                        $cargo['concepto']
                                    ) ?>

                                </td>


                                <!-- APLICACIÓN -->

                                <td>

                                    <?= htmlspecialchars(
                                        nombreTipoAplicacion(
                                            $cargo[
                                                'tipo_aplicacion'
                                            ]
                                        )
                                    ) ?>

                                    <br>

                                    <small>

                                        <?= htmlspecialchars(
                                            $detalleAplicacion
                                        ) ?>

                                    </small>

                                </td>


                                <!-- DISTRIBUCIÓN -->

                                <td>

                                    <?= htmlspecialchars(
                                        nombreTipoDistribucion(
                                            $cargo[
                                                'tipo_distribucion'
                                            ]
                                        )
                                    ) ?>

                                </td>


                                <!-- VALOR -->

                                <td>

                                    <strong>

                                        $

                                        <?= number_format(
                                            (float)$cargo[
                                                'valor_total'
                                            ],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- CUOTAS -->

                                <td>

                                    <?= (int)$cargo[
                                        'numero_cuotas'
                                    ] ?>

                                    <br>

                                    <small>

                                        Facturadas:

                                        <?= $cuotasFacturadas ?>

                                        /

                                        <?= $totalCuotas ?>

                                    </small>

                                </td>


                                <!-- PERÍODO -->

                                <td>

                                    <?= date(
                                        'm/Y',
                                        strtotime(
                                            $cargo[
                                                'periodo_inicio'
                                            ]
                                        )
                                    ) ?>

                                </td>


                                <!-- ESTADO -->

                                <td>

                                    <?php if (
                                        $cargo['estado']
                                        === 'ACTIVO'
                                    ): ?>

                                        <span class="activo">

                                            Activo

                                        </span>


                                    <?php elseif (
                                        $cargo['estado']
                                        === 'BORRADOR'
                                    ): ?>

                                        <span>

                                            Borrador

                                        </span>


                                    <?php elseif (
                                        $cargo['estado']
                                        === 'FINALIZADO'
                                    ): ?>

                                        <span class="inactivo">

                                            Finalizado

                                        </span>


                                    <?php else: ?>

                                        <span class="inactivo">

                                            Anulado

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACCIONES -->

                                <td>

                                    <a
                                        href="<?= BASE_URL ?>configuracion/cargo_detalle.php?id=<?= (int)$cargo['id_cargo'] ?>"
                                        class="btn-editar"
                                    >
                                        Ver detalle
                                    </a>

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



<!-- ==========================================================
     MODAL NUEVO CARGO
=========================================================== -->

<div
    id="modalNuevoCargo"
    class="modal"
    style="display:none;"
>


    <div class="modal-contenido">


        <div class="modal-header">


            <h3>
                Nuevo cargo
            </h3>


            <button
                type="button"
                class="modal-cerrar"
                id="cerrarNuevoCargo"
            >
                &times;
            </button>


        </div>


        <form
            id="formNuevoCargo"
            method="POST"
            action="<?= BASE_URL ?>actions/guardar_cargo.php"
        >


            <!-- ==================================================
                 CONCEPTO
            =================================================== -->

            <div class="form-group">

                <label for="id_concepto">
                    Concepto *
                </label>

                <select
                    name="id_concepto"
                    id="id_concepto"
                    required
                >

                    <option value="">
                        Seleccione...
                    </option>

                    <?php foreach ($conceptos as $concepto): ?>

                        <option
                            value="<?= (int)$concepto[
                                'id_concepto'
                            ] ?>"
                        >

                            <?= htmlspecialchars(
                                $concepto['nombre']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- ==================================================
                 NOMBRE
            =================================================== -->

            <div class="form-group">

                <label for="nombre">
                    Nombre del cargo *
                </label>

                <input
                    type="text"
                    name="nombre"
                    id="nombre"
                    maxlength="150"
                    required
                    placeholder="Ej. Reparación fachada 2026"
                >

            </div>


            <!-- ==================================================
                 DESCRIPCIÓN
            =================================================== -->

            <div class="form-group">

                <label for="descripcion">
                    Descripción
                </label>

                <textarea
                    name="descripcion"
                    id="descripcion"
                    rows="3"
                    maxlength="255"
                    placeholder="Descripción del cargo"
                ></textarea>

            </div>


            <!-- ==================================================
                 APLICACIÓN
            =================================================== -->

            <div class="form-group">

                <label for="tipo_aplicacion">
                    Aplicar a *
                </label>

                <select
                    name="tipo_aplicacion"
                    id="tipo_aplicacion"
                    required
                >

                    <option value="TODAS_UNIDADES">
                        Todas las unidades
                    </option>

                    <option value="TIPO_UNIDAD">
                        Grupo de unidades
                    </option>

                    <option value="UNIDAD">
                        Unidad específica
                    </option>

                </select>

            </div>


            <!-- ==================================================
                 GRUPO
            =================================================== -->

            <div
                class="form-group"
                id="grupoTipoUnidad"
                style="display:none;"
            >

                <label for="id_tipo_config">
                    Grupo *
                </label>

                <select
                    name="id_tipo_config"
                    id="id_tipo_config"
                >

                    <option value="">
                        Seleccione...
                    </option>

                    <?php foreach ($grupos as $grupo): ?>

                        <option
                            value="<?= (int)$grupo[
                                'id_tipo_config'
                            ] ?>"
                        >

                            <?= htmlspecialchars(
                                $grupo['nombre_grupo']
                            ) ?>

                            <?php if (
                                isset(
                                    $grupo['cantidad_unidades']
                                )
                            ): ?>

                                (
                                <?= (int)$grupo[
                                    'cantidad_unidades'
                                ] ?>
                                )

                            <?php endif; ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- ==================================================
                 UNIDAD
            =================================================== -->

            <div
                class="form-group"
                id="grupoUnidad"
                style="display:none;"
            >

                <label for="id_unidad">
                    Unidad *
                </label>

                <select
                    name="id_unidad"
                    id="id_unidad"
                >

                    <option value="">
                        Seleccione...
                    </option>

                    <?php foreach ($unidades as $unidad): ?>

                        <option
                            value="<?= (int)$unidad[
                                'id_unidad'
                            ] ?>"
                        >

                            <?= htmlspecialchars(
                                $unidad['codigo']
                            ) ?>

                            -

                            <?= htmlspecialchars(
                                $unidad['nombre_grupo']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- ==================================================
                 DISTRIBUCIÓN
            =================================================== -->

            <div class="form-group">

                <label for="tipo_distribucion">
                    Forma de distribución *
                </label>

                <select
                    name="tipo_distribucion"
                    id="tipo_distribucion"
                    required
                >

                    <option value="VALOR_FIJO">
                        Valor fijo
                    </option>

                    <option value="METRO_CUADRADO">
                        Por metro cuadrado
                    </option>

                    <option value="COEFICIENTE">
                        Por coeficiente
                    </option>

                </select>

            </div>


            <div class="info-box">

                <small>

                    <strong>
                        Valor fijo:
                    </strong>

                    el valor ingresado se distribuye
                    entre las unidades seleccionadas.

                    <br><br>

                    <strong>
                        Metro cuadrado:
                    </strong>

                    se distribuye proporcionalmente
                    según el área de cada unidad.

                    <br><br>

                    <strong>
                        Coeficiente:
                    </strong>

                    se distribuye proporcionalmente
                    según el coeficiente de cada unidad.

                </small>

            </div>


            <br>


            <!-- ==================================================
                 VALOR TOTAL
            =================================================== -->

            <div class="form-group">

                <label for="valor_total">
                    Valor total del cargo *
                </label>

                <input
                    type="number"
                    name="valor_total"
                    id="valor_total"
                    min="0.01"
                    step="0.01"
                    required
                    placeholder="0.00"
                >

            </div>


            <!-- ==================================================
                 CUOTAS
            =================================================== -->

            <div class="form-group">

                <label for="numero_cuotas">
                    Número de cuotas *
                </label>

                <input
                    type="number"
                    name="numero_cuotas"
                    id="numero_cuotas"
                    min="1"
                    max="120"
                    value="1"
                    required
                >

            </div>


            <!-- ==================================================
                 PERÍODO INICIAL
            =================================================== -->

            <div class="form-group">

                <label for="periodo_inicio">
                    Primera cuota *
                </label>

                <input
                    type="month"
                    name="periodo_inicio"
                    id="periodo_inicio"
                    required
                >

            </div>


            <!-- ==================================================
                 OBSERVACIONES
            =================================================== -->

            <div class="form-group">

                <label for="observaciones">
                    Observaciones
                </label>

                <textarea
                    name="observaciones"
                    id="observaciones"
                    rows="3"
                    maxlength="255"
                    placeholder="Observaciones"
                ></textarea>

            </div>


            <!-- ==================================================
                 ESTADO
            =================================================== -->

            <div class="form-group">

                <label for="estado_cargo">
                    Estado inicial
                </label>

                <select
                    name="estado"
                    id="estado_cargo"
                >

                    <option value="BORRADOR">
                        Borrador
                    </option>

                    <option value="ACTIVO">
                        Activo
                    </option>

                </select>

                <small>
                    Un cargo en borrador no será tomado
                    por la facturación mensual.
                </small>

            </div>


            <!-- ==================================================
                 BOTONES
            =================================================== -->

            <div class="form-actions">


                <button
                    type="button"
                    class="btn-limpiar"
                    id="cancelarNuevoCargo"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="btn-guardar"
                >
                    Guardar cargo
                </button>


            </div>


        </form>


    </div>


</div>



<!-- ==========================================================
     JAVASCRIPT
=========================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const modal =
            document.getElementById(
                "modalNuevoCargo"
            );


        const abrir =
            document.getElementById(
                "abrirNuevoCargo"
            );


        const cerrar =
            document.getElementById(
                "cerrarNuevoCargo"
            );


        const cancelar =
            document.getElementById(
                "cancelarNuevoCargo"
            );


        const tipoAplicacion =
            document.getElementById(
                "tipo_aplicacion"
            );


        const grupoTipoUnidad =
            document.getElementById(
                "grupoTipoUnidad"
            );


        const grupoUnidad =
            document.getElementById(
                "grupoUnidad"
            );


        const selectTipoUnidad =
            document.getElementById(
                "id_tipo_config"
            );


        const selectUnidad =
            document.getElementById(
                "id_unidad"
            );


        // ======================================================
        // ABRIR MODAL
        // ======================================================

        if (abrir) {

            abrir.addEventListener(
                "click",
                function () {

                    modal.style.display =
                        "flex";

                }
            );

        }


        // ======================================================
        // CERRAR MODAL
        // ======================================================

        function cerrarModal() {

            modal.style.display =
                "none";

        }


        if (cerrar) {

            cerrar.addEventListener(
                "click",
                cerrarModal
            );

        }


        if (cancelar) {

            cancelar.addEventListener(
                "click",
                cerrarModal
            );

        }


        // ======================================================
        // CERRAR AL HACER CLICK FUERA
        // ======================================================

        window.addEventListener(
            "click",
            function (event) {

                if (event.target === modal) {

                    cerrarModal();

                }

            }
        );


        // ======================================================
        // MOSTRAR CAMPO SEGÚN APLICACIÓN
        // ======================================================

        function actualizarAplicacion() {


            grupoTipoUnidad.style.display =
                "none";


            grupoUnidad.style.display =
                "none";


            selectTipoUnidad.required =
                false;


            selectUnidad.required =
                false;


            if (
                tipoAplicacion.value
                === "TIPO_UNIDAD"
            ) {

                grupoTipoUnidad.style.display =
                    "block";

                selectTipoUnidad.required =
                    true;

            }


            if (
                tipoAplicacion.value
                === "UNIDAD"
            ) {

                grupoUnidad.style.display =
                    "block";

                selectUnidad.required =
                    true;

            }

        }


        tipoAplicacion.addEventListener(
            "change",
            actualizarAplicacion
        );


        actualizarAplicacion();


    }
);

</script>


</body>

</html>