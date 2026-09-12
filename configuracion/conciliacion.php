<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// FUNCIONES
// ==========================================================

function e($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

function dinero($valor)
{
    return '$' . number_format(
        (float)$valor,
        2,
        ',',
        '.'
    );
}

function diferenciaDias($fecha1, $fecha2)
{
    $f1 = new DateTime($fecha1);
    $f2 = new DateTime($fecha2);

    return abs(
        (int)$f1->diff($f2)->format('%r%a')
    );
}


// ==========================================================
// FILTROS
// ==========================================================

$buscar =
    trim(
        $_GET['buscar'] ?? ''
    );

$estadoVista =
    trim(
        $_GET['estado_vista'] ?? ''
    );

$estadoConciliacion =
    trim(
        $_GET['estado_conciliacion'] ?? 'PENDIENTE'
    );

$estadosPermitidos = [
    'PENDIENTE',
    'CONCILIADO',
    'RECHAZADO',
    'CON_DIFERENCIA'
];

if (!in_array($estadoConciliacion, $estadosPermitidos, true)) {
    $estadoConciliacion = 'PENDIENTE';
}

$fechaDesde =
    trim(
        $_GET['fecha_desde'] ?? ''
    );

$fechaHasta =
    trim(
        $_GET['fecha_hasta'] ?? ''
    );


// ==========================================================
// WHERE DE MOVIMIENTOS
// ==========================================================

$where = [
    "eb.tipo_movimiento = 'INGRESO'",
    "eb.estado_conciliacion = :estado_conciliacion"
];

$params = [
    ':estado_conciliacion' => $estadoConciliacion
];


if ($buscar !== '') {

    $where[] = "
        (
            eb.descripcion LIKE :buscar
            OR eb.referencia LIKE :buscar
            OR eb.numero_documento LIKE :buscar
            OR eb.archivo_origen LIKE :buscar
        )
    ";

    $params[':buscar'] =
        '%' . $buscar . '%';
}


if ($fechaDesde !== '') {

    $where[] = "
        eb.fecha_movimiento >= :fecha_desde
    ";

    $params[':fecha_desde'] =
        $fechaDesde;
}


if ($fechaHasta !== '') {

    $where[] = "
        eb.fecha_movimiento <= :fecha_hasta
    ";

    $params[':fecha_hasta'] =
        $fechaHasta;
}


$whereSql =
    implode(
        ' AND ',
        $where
    );


// ==========================================================
// MOVIMIENTOS BANCARIOS PENDIENTES
// ==========================================================

$sqlMovimientos = "
    SELECT
        eb.id_extracto,
        eb.id_documento,
        eb.id_cuenta_bancaria,
        eb.fecha_movimiento,
        eb.fecha_valor,
        eb.descripcion,
        eb.referencia,
        eb.numero_documento,
        eb.valor,
        eb.tipo_movimiento,
        eb.estado_conciliacion,
        eb.archivo_origen,
        eb.observaciones,

        cb.banco,
        cb.tipo_cuenta,
        cb.numero_cuenta,

        p_rel.id_pago AS pago_relacionado,
        p_rel.id_unidad AS pago_id_unidad,
        p_rel.fecha_pago AS pago_fecha,
        p_rel.valor AS pago_valor,
        p_rel.referencia AS pago_referencia,
        p_rel.estado_conciliacion AS pago_estado_conciliacion,

        u_rel.codigo AS pago_unidad_codigo,
        u_rel.nombre AS pago_unidad_nombre

    FROM extractos_bancarios eb

    LEFT JOIN cuentas_bancarias cb
        ON cb.id_cuenta_bancaria =
           eb.id_cuenta_bancaria

    LEFT JOIN pagos p_rel
        ON p_rel.id_extracto =
           eb.id_extracto

    LEFT JOIN unidades u_rel
        ON u_rel.id_unidad =
           p_rel.id_unidad

    WHERE
        $whereSql

    ORDER BY
        eb.fecha_movimiento DESC,
        eb.id_extracto DESC
";


$stmtMovimientos =
    $conexion->prepare(
        $sqlMovimientos
    );


$stmtMovimientos->execute(
    $params
);


$movimientos =
    $stmtMovimientos->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// PREPARAR DATOS SEGÚN ESTADO
// ==========================================================

$filas = [];

$conciliacionReciente = null;

$conteo = [
    'EXACTA'            => 0,
    'PROBABLE'          => 0,
    'AMBIGUA'           => 0,
    'CON_DIFERENCIA'    => 0,
    'SIN_COINCIDENCIA'  => 0
];

if ($estadoConciliacion === 'PENDIENTE') {

// ==========================================================
// PREPARAR BÚSQUEDA DE CANDIDATOS
// ==========================================================

$sqlCandidatos = "
    SELECT
        p.id_pago,
        p.id_unidad,
        p.fecha_pago,
        p.valor,
        p.referencia,
        p.referencia_externa,
        p.estado_conciliacion,
        p.estado,

        u.codigo AS unidad_codigo,
        u.nombre AS unidad_nombre,

        dtu.nombre_grupo

    FROM pagos p

    INNER JOIN unidades u
        ON u.id_unidad =
           p.id_unidad

    LEFT JOIN detalle_tipos_unidad dtu
        ON dtu.id_tipo_config =
           u.id_tipo_config

    WHERE
        p.estado = 'REGISTRADO'

        AND p.estado_conciliacion = 'PENDIENTE'

        AND p.id_extracto IS NULL

        AND (
            ABS(p.valor - :valor) <= 0.01

            OR ABS(p.valor - :valor_tolerancia)
               <= GREATEST(
                    1000,
                    p.valor * 0.01
               )
        )

        AND ABS(
            DATEDIFF(
                p.fecha_pago,
                :fecha_movimiento
            )
        ) <= 3

    ORDER BY
        ABS(
            DATEDIFF(
                p.fecha_pago,
                :fecha_movimiento_orden
            )
        ) ASC,
        p.id_pago ASC
";


$stmtCandidatos =
    $conexion->prepare(
        $sqlCandidatos
    );


// ==========================================================
// CLASIFICAR MOVIMIENTOS
// ==========================================================



/*
 * IMPORTANTE:
 * Primero construimos TODOS los candidatos.
 * Después contamos cuántos movimientos bancarios compiten por cada pago.
 *
 * Esto evita el falso "COINCIDENCIA EXACTA" cuando:
 *
 *   Extracto #95 -> Pago #5
 *   Extracto #96 -> Pago #5
 *
 * Aunque cada extracto tenga un solo candidato, el mismo pago no puede
 * conciliarse con dos movimientos distintos.
 */

$usoCandidatos = [];


foreach ($movimientos as $movimiento) {

    $stmtCandidatos->execute([
        ':valor'                  => $movimiento['valor'],
        ':valor_tolerancia'       => $movimiento['valor'],
        ':fecha_movimiento'       => $movimiento['fecha_movimiento'],
        ':fecha_movimiento_orden' => $movimiento['fecha_movimiento']
    ]);

    $candidatos =
        $stmtCandidatos->fetchAll(
            PDO::FETCH_ASSOC
        );


    foreach ($candidatos as $candidato) {

        $idPagoCandidato =
            (int)$candidato['id_pago'];

        if (!isset($usoCandidatos[$idPagoCandidato])) {

            $usoCandidatos[$idPagoCandidato] = 0;
        }

        $usoCandidatos[$idPagoCandidato]++;
    }


    $filas[] = [
        'movimiento'    => $movimiento,
        'candidatos'    => $candidatos,
        'clasificacion' => null
    ];
}


// ==========================================================
// CLASIFICAR TENIENDO EN CUENTA CONFLICTOS GLOBALES
// ==========================================================

foreach ($filas as $indice => $fila) {

    $movimiento =
        $fila['movimiento'];

    $candidatos =
        $fila['candidatos'];

    $cantidad =
        count(
            $candidatos
        );


    if ($cantidad === 0) {

        $clasificacion =
            'SIN_COINCIDENCIA';

    } elseif ($cantidad > 1) {

        // Un movimiento tiene varios pagos posibles.
        $clasificacion =
            'AMBIGUA';

    } else {

        $idPagoCandidato =
            (int)$candidatos[0]['id_pago'];

        $cantidadMovimientosParaPago =
            $usoCandidatos[$idPagoCandidato] ?? 0;


        if ($cantidadMovimientosParaPago > 1) {

            // El mismo pago aparece como candidato de varios movimientos.
            $clasificacion =
                'AMBIGUA';

        } else {

            $dias =
                diferenciaDias(
                    $movimiento['fecha_movimiento'],
                    $candidatos[0]['fecha_pago']
                );

            $diferenciaValor =
                abs(
                    (float)$movimiento['valor']
                    -
                    (float)$candidatos[0]['valor']
                );

            if ($diferenciaValor <= 0.01) {

                $clasificacion =
                    $dias === 0
                        ? 'EXACTA'
                        : 'PROBABLE';

            } else {

                $clasificacion =
                    'CON_DIFERENCIA';
            }
        }
    }


    $filas[$indice]['clasificacion'] =
        $clasificacion;


    $conteo[$clasificacion]++;
}


// ==========================================================
// FILTRO DE VISTA
// ==========================================================

if (
    in_array(
        $estadoVista,
        [
            'EXACTA',
            'PROBABLE',
            'AMBIGUA',
            'CON_DIFERENCIA',
            'SIN_COINCIDENCIA'
        ],
        true
    )
) {

    $filas =
        array_values(
            array_filter(
                $filas,
                function ($fila) use ($estadoVista) {

                    return
                        $fila['clasificacion']
                        === $estadoVista;
                }
            )
        );
}


// ==========================================================
// DETALLE DE CONCILIACIÓN RECIENTE
// ==========================================================

if (
    isset($_GET['conciliado']) &&
    (int)$_GET['conciliado'] === 1 &&
    !empty($_GET['id_extracto']) &&
    !empty($_GET['id_pago'])
) {

    $idExtractoReciente =
        (int)$_GET['id_extracto'];

    $idPagoReciente =
        (int)$_GET['id_pago'];


    $sqlConciliacionReciente = "
        SELECT
            p.id_pago,
            p.id_unidad,
            p.id_extracto,
            p.fecha_pago,
            p.valor,
            p.fecha_conciliacion,
            p.referencia,

            u.codigo AS unidad_codigo,
            u.nombre AS unidad_nombre,

            eb.id_extracto,
            eb.fecha_movimiento,
            eb.referencia AS referencia_bancaria,
            eb.descripcion AS descripcion_bancaria,

            cb.banco,
            cb.tipo_cuenta,
            cb.numero_cuenta

        FROM pagos p

        INNER JOIN unidades u
            ON u.id_unidad =
               p.id_unidad

        INNER JOIN extractos_bancarios eb
            ON eb.id_extracto =
               p.id_extracto

        LEFT JOIN cuentas_bancarias cb
            ON cb.id_cuenta_bancaria =
               eb.id_cuenta_bancaria

        WHERE
            p.id_pago = :id_pago
            AND eb.id_extracto = :id_extracto

        LIMIT 1
    ";


    $stmtConciliacionReciente =
        $conexion->prepare(
            $sqlConciliacionReciente
        );


    $stmtConciliacionReciente->execute([
        ':id_pago'     => $idPagoReciente,
        ':id_extracto' => $idExtractoReciente
    ]);


    $conciliacionReciente =
        $stmtConciliacionReciente->fetch(
            PDO::FETCH_ASSOC
        );
}



} else {

    foreach ($movimientos as $movimiento) {

        $filas[] = [
            'movimiento'    => $movimiento,
            'candidatos'    => [],
            'clasificacion' => null
        ];
    }
}


// ==========================================================
// DETALLE DE MOVIMIENTO RECHAZADO RECIENTE
// ==========================================================

$rechazoReciente = null;

if (
    isset($_GET['rechazado']) &&
    (int)$_GET['rechazado'] === 1 &&
    !empty($_GET['id_extracto'])
) {

    $idExtractoRechazado =
        (int)$_GET['id_extracto'];


    $sqlRechazoReciente = "
        SELECT
            eb.id_extracto,
            eb.fecha_movimiento,
            eb.descripcion,
            eb.referencia,
            eb.numero_documento,
            eb.valor,
            eb.estado_conciliacion,
            eb.observaciones,
            eb.archivo_origen,

            cb.banco,
            cb.tipo_cuenta,
            cb.numero_cuenta

        FROM extractos_bancarios eb

        LEFT JOIN cuentas_bancarias cb
            ON cb.id_cuenta_bancaria =
               eb.id_cuenta_bancaria

        WHERE
            eb.id_extracto = :id_extracto

        LIMIT 1
    ";


    $stmtRechazoReciente =
        $conexion->prepare(
            $sqlRechazoReciente
        );


    $stmtRechazoReciente->execute([
        ':id_extracto' => $idExtractoRechazado
    ]);


    $rechazoReciente =
        $stmtRechazoReciente->fetch(
            PDO::FETCH_ASSOC
        );
}

// ==========================================================
// URLS DE EXPORTACIÓN
// ==========================================================

$queryExportacion = [
    'estado_conciliacion' => $estadoConciliacion,
    'buscar'               => $buscar,
    'fecha_desde'          => $fechaDesde,
    'fecha_hasta'          => $fechaHasta
];

if (
    isset($estadoVista)
    &&
    $estadoVista !== ''
) {
    $queryExportacion['estado_vista'] =
        $estadoVista;
}

$urlExcelVista =
    BASE_URL .
    'actions/exportar_conciliacion_excel.php?' .
    http_build_query($queryExportacion);

$urlPdfVista =
    BASE_URL .
    'actions/exportar_conciliacion_pdf.php?' .
    http_build_query($queryExportacion);

$urlExcelTodo =
    BASE_URL .
    'actions/exportar_conciliacion_excel.php?todos=1';

$urlPdfTodo =
    BASE_URL .
    'actions/exportar_conciliacion_pdf.php?todos=1';

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <?php include ROOT_PATH . "/includes/head.php"; ?>

</head>


<body>


<?php include ROOT_PATH . "/includes/header.php"; ?>


<div class="contenedor">


    <?php include ROOT_PATH . "/includes/sidebar.php"; ?>


    <main class="contenido">


        <!-- ======================================================
             ENCABEZADO
        ======================================================= -->

        <div
            style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                gap:15px;
                flex-wrap:wrap;
            "
        >

            <div>

                <h2>
                    Conciliación bancaria
                </h2>

                <p>
                    Comparación de ingresos bancarios con pagos registrados.
                </p>

            </div>

        </div>


        <!-- ======================================================
             EXPORTACIÓN
        ======================================================= -->

        <div
            style="
                display:flex;
                justify-content:flex-end;
                gap:8px;
                flex-wrap:wrap;
                margin-bottom:16px;
            "
        >

            <a
                href="<?= e($urlExcelVista) ?>"
                class="btn-secondary"
            >
                <i class="fa-solid fa-file-excel"></i>
                Exportar Excel
            </a>


            <a
                href="<?= e($urlPdfVista) ?>"
                class="btn-secondary"
                target="_blank"
            >
                <i class="fa-solid fa-file-pdf"></i>
                Exportar PDF
            </a>


            <a
                href="<?= e($urlExcelTodo) ?>"
                class="btn-limpiar"
            >
                Excel - Todo
            </a>


            <a
                href="<?= e($urlPdfTodo) ?>"
                class="btn-limpiar"
                target="_blank"
            >
                PDF - Todo
            </a>

        </div>


        <!-- ======================================================
             ESTADOS DE CONCILIACIÓN
        ======================================================= -->

        <div
            style="
                display:flex;
                gap:8px;
                flex-wrap:wrap;
                margin-bottom:16px;
            "
        >

            <?php
                $tabsConciliacion = [
                    'PENDIENTE'      => 'Pendientes',
                    'CONCILIADO'     => 'Conciliados',
                    'RECHAZADO'      => 'Rechazados',
                    'CON_DIFERENCIA' => 'Con diferencia'
                ];
            ?>

            <?php foreach ($tabsConciliacion as $valorTab => $textoTab): ?>

                <a
                    href="<?= BASE_URL ?>configuracion/conciliacion.php?estado_conciliacion=<?= e($valorTab) ?>"
                    class="<?= $estadoConciliacion === $valorTab ? 'btn-filtrar' : 'btn-limpiar' ?>"
                >
                    <?= e($textoTab) ?>
                </a>

            <?php endforeach; ?>

        </div>


        <!-- ======================================================
             RESULTADO DE LA CONCILIACIÓN
        ======================================================= -->

        <?php if ($conciliacionReciente): ?>

            <div
                id="modalConciliacionExitosa"
                class="modal"
                style="
                    display:flex;
                    position:fixed;
                    inset:0;
                    z-index:9999;
                    background:rgba(0,0,0,.55);
                    align-items:center;
                    justify-content:center;
                    padding:20px;
                "
            >

                <div
                    class="modal-contenido"
                    style="
                        width:min(760px, 96vw);
                        max-height:90vh;
                        overflow:auto;
                        background:#fff;
                        border-radius:12px;
                        box-shadow:0 20px 60px rgba(0,0,0,.25);
                        padding:0;
                    "
                >

                    <div
                        class="modal-header"
                        style="
                            display:flex;
                            justify-content:space-between;
                            align-items:center;
                            gap:15px;
                            padding:18px 20px;
                            border-bottom:1px solid #ddd;
                        "
                    >

                        <div>

                            <h3 style="margin:0;">
                                ✓ Conciliación realizada correctamente
                            </h3>

                            <small>
                                El movimiento bancario quedó asociado al pago registrado.
                            </small>

                        </div>


                        <button
                            type="button"
                            id="cerrarModalConciliacion"
                            class="modal-cerrar"
                            style="
                                border:0;
                                background:transparent;
                                font-size:28px;
                                cursor:pointer;
                                line-height:1;
                            "
                        >
                            &times;
                        </button>

                    </div>


                    <div style="padding:20px;">

                        <div
                            style="
                                display:flex;
                                justify-content:flex-end;
                                margin-bottom:12px;
                            "
                        >
                            <strong>CONCILIADO</strong>
                        </div>


                        <div class="tabla-responsive">

                            <table class="tabla">

                                <thead>

                                    <tr>

                                        <th>Movimiento</th>
                                        <th>Pago</th>
                                        <th>Unidad</th>
                                        <th>Valor</th>
                                        <th>Fecha pago</th>
                                        <th>Banco</th>
                                        <th>Fecha conciliación</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <tr>

                                        <td>
                                            #<?= (int)$conciliacionReciente['id_extracto'] ?>
                                        </td>

                                        <td>
                                            #<?= (int)$conciliacionReciente['id_pago'] ?>
                                        </td>

                                        <td>

                                            <strong>
                                                <?= e($conciliacionReciente['unidad_codigo']) ?>
                                            </strong>

                                            <?php if (
                                                !empty(
                                                    $conciliacionReciente['unidad_nombre']
                                                )
                                            ): ?>

                                                <br>

                                                <small>
                                                    <?= e(
                                                        $conciliacionReciente['unidad_nombre']
                                                    ) ?>
                                                </small>

                                            <?php endif; ?>

                                        </td>

                                        <td>
                                            <strong>
                                                <?= dinero(
                                                    $conciliacionReciente['valor']
                                                ) ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?= e(
                                                date(
                                                    'd/m/Y',
                                                    strtotime(
                                                        $conciliacionReciente['fecha_pago']
                                                    )
                                                )
                                            ) ?>
                                        </td>

                                        <td>

                                            <?= e(
                                                $conciliacionReciente['banco']
                                                ?? '-'
                                            ) ?>

                                            <?php if (
                                                !empty(
                                                    $conciliacionReciente['tipo_cuenta']
                                                )
                                            ): ?>

                                                <br>

                                                <small>
                                                    <?= e(
                                                        $conciliacionReciente['tipo_cuenta']
                                                    ) ?>
                                                </small>

                                            <?php endif; ?>

                                        </td>

                                        <td>
                                            <?= e(
                                                date(
                                                    'd/m/Y H:i',
                                                    strtotime(
                                                        $conciliacionReciente['fecha_conciliacion']
                                                    )
                                                )
                                            ) ?>
                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>


                        <?php if (
                            !empty(
                                $conciliacionReciente['descripcion_bancaria']
                            )
                        ): ?>

                            <br>

                            <p>
                                <strong>Movimiento bancario:</strong>
                                <?= e(
                                    $conciliacionReciente['descripcion_bancaria']
                                ) ?>
                            </p>

                        <?php endif; ?>


                        <br>


                        <div
                            class="form-actions"
                            style="
                                display:flex;
                                justify-content:flex-end;
                                gap:8px;
                                flex-wrap:wrap;
                            "
                        >

                            <a
                                href="<?= BASE_URL ?>configuracion/pagos.php"
                                class="btn-secondary"
                            >
                                Ver pagos
                            </a>

                            <a
                                href="<?= BASE_URL ?>configuracion/cartera_detalle.php?id_unidad=<?= (int)$conciliacionReciente['id_unidad'] ?>"
                                class="btn-secondary"
                            >
                                Ver cartera de la unidad
                            </a>

                            <a
                                href="<?= BASE_URL ?>configuracion/conciliacion.php"
                                class="btn-limpiar"
                            >
                                Cerrar
                            </a>

                        </div>

                    </div>

                </div>

            </div>


            <script>

                document.addEventListener(
                    'DOMContentLoaded',
                    function () {

                        const modal =
                            document.getElementById(
                                'modalConciliacionExitosa'
                            );

                        const cerrar =
                            document.getElementById(
                                'cerrarModalConciliacion'
                            );


                        function cerrarModal()
                        {
                            window.location.href =
                                '<?= BASE_URL ?>configuracion/conciliacion.php';
                        }


                        if (cerrar) {

                            cerrar.addEventListener(
                                'click',
                                cerrarModal
                            );
                        }


                        if (modal) {

                            modal.addEventListener(
                                'click',
                                function (event) {

                                    if (event.target === modal) {

                                        cerrarModal();
                                    }
                                }
                            );
                        }


                        document.addEventListener(
                            'keydown',
                            function (event) {

                                if (event.key === 'Escape') {

                                    cerrarModal();
                                }
                            }
                        );

                    }
                );

            </script>

        <?php elseif ($rechazoReciente): ?>

            <div
                id="modalRechazoExitoso"
                class="modal"
                style="
                    display:flex;
                    position:fixed;
                    inset:0;
                    z-index:9999;
                    background:rgba(0,0,0,.55);
                    align-items:center;
                    justify-content:center;
                    padding:20px;
                "
            >

                <div
                    class="modal-contenido"
                    style="
                        width:min(720px, 96vw);
                        max-height:90vh;
                        overflow:auto;
                        background:#fff;
                        border-radius:12px;
                        box-shadow:0 20px 60px rgba(0,0,0,.25);
                        padding:0;
                    "
                >

                    <div
                        class="modal-header"
                        style="
                            display:flex;
                            justify-content:space-between;
                            align-items:center;
                            gap:15px;
                            padding:18px 20px;
                            border-bottom:1px solid #ddd;
                        "
                    >

                        <div>

                            <h3 style="margin:0;">
                                Movimiento rechazado
                            </h3>

                            <small>
                                El movimiento fue descartado para conciliación de pagos.
                            </small>

                        </div>

                        <button
                            type="button"
                            id="cerrarModalRechazo"
                            class="modal-cerrar"
                            style="
                                border:0;
                                background:transparent;
                                font-size:28px;
                                cursor:pointer;
                                line-height:1;
                            "
                        >
                            &times;
                        </button>

                    </div>


                    <div style="padding:20px;">

                        <div class="tabla-responsive">

                            <table class="tabla">

                                <thead>

                                    <tr>
                                        <th>Movimiento</th>
                                        <th>Fecha</th>
                                        <th>Valor</th>
                                        <th>Banco</th>
                                        <th>Estado</th>
                                    </tr>

                                </thead>

                                <tbody>

                                    <tr>

                                        <td>
                                            #<?= (int)$rechazoReciente['id_extracto'] ?>
                                        </td>

                                        <td>
                                            <?= e(
                                                date(
                                                    'd/m/Y',
                                                    strtotime(
                                                        $rechazoReciente['fecha_movimiento']
                                                    )
                                                )
                                            ) ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= dinero(
                                                    $rechazoReciente['valor']
                                                ) ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?= e(
                                                $rechazoReciente['banco']
                                                ?? '-'
                                            ) ?>

                                            <?php if (
                                                !empty(
                                                    $rechazoReciente['tipo_cuenta']
                                                )
                                            ): ?>

                                                <br>

                                                <small>
                                                    <?= e(
                                                        $rechazoReciente['tipo_cuenta']
                                                    ) ?>
                                                </small>

                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            RECHAZADO
                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>


                        <?php if (
                            !empty(
                                $rechazoReciente['descripcion']
                            )
                        ): ?>

                            <br>

                            <p>
                                <strong>Descripción:</strong>
                                <?= e(
                                    $rechazoReciente['descripcion']
                                ) ?>
                            </p>

                        <?php endif; ?>


                        <?php if (
                            !empty(
                                $rechazoReciente['observaciones']
                            )
                        ): ?>

                            <p>
                                <strong>Observación:</strong>
                                <?= e(
                                    $rechazoReciente['observaciones']
                                ) ?>
                            </p>

                        <?php endif; ?>


                        <br>


                        <div
                            class="form-actions"
                            style="
                                display:flex;
                                justify-content:flex-end;
                                gap:8px;
                                flex-wrap:wrap;
                            "
                        >

                            <a
                                href="<?= BASE_URL ?>configuracion/conciliacion.php?estado_conciliacion=RECHAZADO"
                                class="btn-secondary"
                            >
                                Ver rechazados
                            </a>

                            <a
                                href="<?= BASE_URL ?>configuracion/conciliacion.php"
                                class="btn-limpiar"
                            >
                                Cerrar
                            </a>

                        </div>

                    </div>

                </div>

            </div>


            <script>

                document.addEventListener(
                    'DOMContentLoaded',
                    function () {

                        const modal =
                            document.getElementById(
                                'modalRechazoExitoso'
                            );

                        const cerrar =
                            document.getElementById(
                                'cerrarModalRechazo'
                            );


                        function cerrarModal()
                        {
                            window.location.href =
                                '<?= BASE_URL ?>configuracion/conciliacion.php';
                        }


                        if (cerrar) {

                            cerrar.addEventListener(
                                'click',
                                cerrarModal
                            );
                        }


                        if (modal) {

                            modal.addEventListener(
                                'click',
                                function (event) {

                                    if (event.target === modal) {

                                        cerrarModal();
                                    }
                                }
                            );
                        }


                        document.addEventListener(
                            'keydown',
                            function (event) {

                                if (event.key === 'Escape') {

                                    cerrarModal();
                                }
                            }
                        );

                    }
                );

            </script>

        <?php elseif (!empty($_GET['texto'])): ?>

            <div class="info-box">

                <strong>
                    <?= e(strtoupper($_GET['tipo'] ?? 'info')) ?>
                </strong>

                <p>
                    <?= e($_GET['texto']) ?>
                </p>

            </div>

            <br>

        <?php endif; ?>


        <!-- ======================================================
             RESUMEN
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <?php if ($estadoConciliacion === 'PENDIENTE'): ?>

                    <h3>
                        Resumen de coincidencias
                    </h3>

                    <br>

                    <div class="tabla-responsive">

                        <table class="tabla">

                            <thead>

                                <tr>
                                    <th>Exactas</th>
                                    <th>Probables</th>
                                    <th>Ambiguas</th>
                                    <th>Con diferencia</th>
                                    <th>Sin coincidencia</th>
                                    <th>Total pendientes</th>
                                </tr>

                            </thead>

                            <tbody>

                                <tr>
                                    <td><strong><?= (int)$conteo['EXACTA'] ?></strong></td>
                                    <td><?= (int)$conteo['PROBABLE'] ?></td>
                                    <td><?= (int)$conteo['AMBIGUA'] ?></td>
                                    <td><?= (int)$conteo['CON_DIFERENCIA'] ?></td>
                                    <td><?= (int)$conteo['SIN_COINCIDENCIA'] ?></td>
                                    <td><?= count($movimientos) ?></td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                <?php else: ?>

                    <h3>
                        Historial de conciliación
                    </h3>

                    <p>
                        Estado consultado:
                        <strong><?= e($estadoConciliacion) ?></strong>
                    </p>

                    <br>

                    <p>
                        Registros encontrados:
                        <strong><?= count($movimientos) ?></strong>
                    </p>

                <?php endif; ?>

            </div>

        </div>


        <br>


        <!-- ======================================================
             FILTROS
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Filtros
                </h3>

                <br>

                <form
                    method="GET"
                    action=""
                >

                    <div
                        style="
                            display:grid;
                            grid-template-columns:
                                repeat(
                                    auto-fit,
                                    minmax(210px,1fr)
                                );
                            gap:15px;
                        "
                    >

                        <div>

                            <label>
                                Buscar
                            </label>

                            <input
                                type="text"
                                name="buscar"
                                value="<?= e($buscar) ?>"
                                placeholder="Descripción, referencia, archivo..."
                            >

                        </div>


                        <input
                            type="hidden"
                            name="estado_conciliacion"
                            value="<?= e($estadoConciliacion) ?>"
                        >


                        <?php if ($estadoConciliacion === 'PENDIENTE'): ?>

                        <div>

                            <label>
                                Coincidencia
                            </label>

                            <select
                                name="estado_vista"
                            >

                                <option value="">
                                    Todas
                                </option>

                                <option
                                    value="EXACTA"
                                    <?= $estadoVista === 'EXACTA'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Exacta
                                </option>

                                <option
                                    value="PROBABLE"
                                    <?= $estadoVista === 'PROBABLE'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Probable
                                </option>

                                <option
                                    value="AMBIGUA"
                                    <?= $estadoVista === 'AMBIGUA'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Ambigua
                                </option>

                                <option
                                    value="CON_DIFERENCIA"
                                    <?= $estadoVista === 'CON_DIFERENCIA'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Con diferencia
                                </option>

                                <option
                                    value="SIN_COINCIDENCIA"
                                    <?= $estadoVista === 'SIN_COINCIDENCIA'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Sin coincidencia
                                </option>

                            </select>

                        </div>

                        <?php endif; ?>


                        <div>

                            <label>
                                Desde
                            </label>

                            <input
                                type="date"
                                name="fecha_desde"
                                value="<?= e($fechaDesde) ?>"
                            >

                        </div>


                        <div>

                            <label>
                                Hasta
                            </label>

                            <input
                                type="date"
                                name="fecha_hasta"
                                value="<?= e($fechaHasta) ?>"
                            >

                        </div>

                    </div>


                    <br>


                    <div class="form-actions">

                        <button
                            type="submit"
                            class="btn-filtrar"
                        >
                            Filtrar
                        </button>


                        <a
                            href="<?= BASE_URL ?>configuracion/conciliacion.php"
                            class="btn-limpiar"
                        >
                            Limpiar
                        </a>

                    </div>

                </form>

            </div>

        </div>


        <br>


        <!-- ======================================================
             MOVIMIENTOS / HISTORIAL
        ======================================================= -->

        <?php if (empty($filas)): ?>

            <div class="bloque filtros">

                <div class="form-card">

                    <p>
                        No hay movimientos para los filtros seleccionados.
                    </p>

                </div>

            </div>

        <?php else: ?>


            <?php foreach ($filas as $fila): ?>

                <?php
                    $m = $fila['movimiento'];
                    $candidatos = $fila['candidatos'];
                    $clasificacion = $fila['clasificacion'];
                ?>


                <div class="bloque filtros">

                    <div class="form-card">


                        <div
                            style="
                                display:flex;
                                justify-content:space-between;
                                align-items:flex-start;
                                gap:15px;
                                flex-wrap:wrap;
                            "
                        >

                            <div>

                                <h3>
                                    Movimiento bancario #<?= (int)$m['id_extracto'] ?>
                                </h3>

                                <p>
                                    <?= e($m['descripcion'] ?? 'Sin descripción') ?>
                                </p>

                            </div>


                            <div>

                                <?php if ($estadoConciliacion === 'PENDIENTE'): ?>

                                    <?php if ($clasificacion === 'EXACTA'): ?>
                                        <span class="activo">COINCIDENCIA EXACTA</span>
                                    <?php elseif ($clasificacion === 'PROBABLE'): ?>
                                        <strong>COINCIDENCIA PROBABLE</strong>
                                    <?php elseif ($clasificacion === 'AMBIGUA'): ?>
                                        <strong>REVISIÓN MANUAL</strong>
                                    <?php elseif ($clasificacion === 'CON_DIFERENCIA'): ?>
                                        <strong>CON DIFERENCIA</strong>
                                    <?php else: ?>
                                        <span class="inactivo">SIN COINCIDENCIA</span>
                                    <?php endif; ?>

                                <?php elseif ($estadoConciliacion === 'CONCILIADO'): ?>

                                    <span class="activo">CONCILIADO</span>

                                <?php elseif ($estadoConciliacion === 'RECHAZADO'): ?>

                                    <span class="inactivo">RECHAZADO</span>

                                <?php else: ?>

                                    <strong>CON DIFERENCIA</strong>

                                <?php endif; ?>

                            </div>

                        </div>


                        <br>


                        <div class="tabla-responsive">

                            <table class="tabla">

                                <thead>

                                    <tr>
                                        <th>Fecha</th>
                                        <th>Valor</th>
                                        <th>Referencia</th>
                                        <th>Documento</th>
                                        <th>Cuenta</th>
                                        <th>Archivo</th>
                                    </tr>

                                </thead>

                                <tbody>

                                    <tr>

                                        <td>
                                            <?= e(date('d/m/Y', strtotime($m['fecha_movimiento']))) ?>
                                        </td>

                                        <td>
                                            <strong><?= dinero($m['valor']) ?></strong>
                                        </td>

                                        <td>
                                            <?= e($m['referencia'] ?? '-') ?>
                                        </td>

                                        <td>
                                            <?= e($m['numero_documento'] ?? '-') ?>
                                        </td>

                                        <td>

                                            <?= e($m['banco'] ?? '-') ?>

                                            <?php if (!empty($m['tipo_cuenta'])): ?>
                                                <br>
                                                <small><?= e($m['tipo_cuenta']) ?></small>
                                            <?php endif; ?>

                                            <?php if (!empty($m['numero_cuenta'])): ?>
                                                <br>
                                                <small><?= e($m['numero_cuenta']) ?></small>
                                            <?php endif; ?>

                                        </td>

                                        <td>
                                            <?= e($m['archivo_origen'] ?? '-') ?>
                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>


                        <?php if ($estadoConciliacion === 'PENDIENTE'): ?>

                            <br>

                            <h4>Pagos candidatos</h4>

                            <br>

                            <div class="tabla-responsive">

                                <table class="tabla">

                                    <thead>

                                        <tr>
                                            <th>Pago</th>
                                            <th>Unidad</th>
                                            <th>Fecha pago</th>
                                            <th>Diferencia días</th>
                                            <th>Valor</th>
                                            <th>Diferencia</th>
                                            <th>Referencia</th>
                                            <th>Referencia externa</th>
                                            <th>Evaluación</th>
                                            <th>Acción</th>
                                        </tr>

                                    </thead>

                                    <tbody>

                                    <?php if (empty($candidatos)): ?>

                                        <tr>
                                            <td colspan="10" align="center">
                                                No se encontró ningún pago registrado
                                                con el mismo valor dentro de una ventana de ±3 días.
                                            </td>
                                        </tr>

                                    <?php else: ?>

                                        <?php foreach ($candidatos as $c): ?>

                                            <?php
                                                $dias = diferenciaDias(
                                                    $m['fecha_movimiento'],
                                                    $c['fecha_pago']
                                                );

                                                $idPagoEvaluado = (int)$c['id_pago'];

                                                $cantidadMovimientosParaPago =
                                                    $usoCandidatos[$idPagoEvaluado] ?? 0;
                                            ?>

                                            <tr>

                                                <td>#<?= (int)$c['id_pago'] ?></td>

                                                <td>
                                                    <strong><?= e($c['unidad_codigo']) ?></strong>
                                                    <?php if (!empty($c['nombre_grupo'])): ?>
                                                        <br>
                                                        <small><?= e($c['nombre_grupo']) ?></small>
                                                    <?php endif; ?>
                                                </td>

                                                <td>
                                                    <?= e(date('d/m/Y', strtotime($c['fecha_pago']))) ?>
                                                </td>

                                                <td><?= $dias ?></td>

                                                <td><strong><?= dinero($c['valor']) ?></strong></td>

                                                <td>
                                                    <?php
                                                        $diferenciaValorFila =
                                                            abs(
                                                                (float)$m['valor']
                                                                -
                                                                (float)$c['valor']
                                                            );
                                                    ?>

                                                    <?= dinero($diferenciaValorFila) ?>
                                                </td>

                                                <td><?= e($c['referencia'] ?? '-') ?></td>

                                                <td><?= e($c['referencia_externa'] ?? '-') ?></td>

                                                <td>

                                                    <?php if (
                                                        count($candidatos) > 1
                                                        || $cantidadMovimientosParaPago > 1
                                                    ): ?>

                                                        Revisión manual

                                                        <?php if ($cantidadMovimientosParaPago > 1): ?>
                                                            <br>
                                                            <small>
                                                                Este pago coincide con
                                                                <?= (int)$cantidadMovimientosParaPago ?>
                                                                movimientos bancarios.
                                                            </small>
                                                        <?php endif; ?>

                                                    <?php elseif ($diferenciaValorFila > 0.01): ?>

                                                        Con diferencia

                                                    <?php elseif ($dias === 0): ?>

                                                        Coincidencia exacta

                                                    <?php else: ?>

                                                        Coincidencia probable

                                                    <?php endif; ?>

                                                </td>

                                                <td>

                                                    <?php if ($diferenciaValorFila > 0.01): ?>

                                                        <form
                                                            method="POST"
                                                            action="<?= BASE_URL ?>actions/marcar_con_diferencia.php"
                                                            onsubmit="return confirm('¿Desea relacionar este movimiento con el pago #<?= (int)$c['id_pago'] ?> como CON DIFERENCIA?');"
                                                        >

                                                            <input
                                                                type="hidden"
                                                                name="id_extracto"
                                                                value="<?= (int)$m['id_extracto'] ?>"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="id_pago"
                                                                value="<?= (int)$c['id_pago'] ?>"
                                                            >

                                                            <button
                                                                type="submit"
                                                                class="btn-secondary"
                                                            >
                                                                Marcar diferencia
                                                            </button>

                                                        </form>

                                                    <?php else: ?>

                                                        <form
                                                            method="POST"
                                                            action="<?= BASE_URL ?>actions/conciliar_movimiento.php"
                                                            onsubmit="return confirm('¿Desea conciliar este movimiento con el pago #<?= (int)$c['id_pago'] ?>?');"
                                                        >

                                                            <input
                                                                type="hidden"
                                                                name="id_extracto"
                                                                value="<?= (int)$m['id_extracto'] ?>"
                                                            >

                                                            <input
                                                                type="hidden"
                                                                name="id_pago"
                                                                value="<?= (int)$c['id_pago'] ?>"
                                                            >

                                                            <button
                                                                type="submit"
                                                                class="btn-secondary"
                                                            >
                                                                Conciliar
                                                            </button>

                                                        </form>

                                                    <?php endif; ?>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    <?php endif; ?>

                                    </tbody>

                                </table>

                            </div>


                            <br>


                            <form
                                method="POST"
                                action="<?= BASE_URL ?>actions/rechazar_movimiento.php"
                                onsubmit="return confirm('¿Desea marcar este movimiento bancario como rechazado para conciliación?');"
                            >

                                <input
                                    type="hidden"
                                    name="id_extracto"
                                    value="<?= (int)$m['id_extracto'] ?>"
                                >

                                <div
                                    style="
                                        display:flex;
                                        gap:8px;
                                        align-items:center;
                                        flex-wrap:wrap;
                                    "
                                >

                                    <input
                                        type="text"
                                        name="observacion"
                                        placeholder="Motivo del rechazo (opcional)"
                                        style="min-width:280px;"
                                    >

                                    <button
                                        type="submit"
                                        class="btn-limpiar"
                                    >
                                        Rechazar movimiento
                                    </button>

                                </div>

                            </form>


                        <?php else: ?>

                            <br>

                            <h4>Resultado del proceso</h4>

                            <br>

                            <div class="tabla-responsive">

                                <table class="tabla">

                                    <thead>

                                        <tr>
                                            <th>Pago relacionado</th>
                                            <th>Unidad</th>
                                            <th>Fecha pago</th>
                                            <th>Valor pago</th>
                                            <th>Referencia pago</th>
                                            <th>Observaciones</th>
                                        </tr>

                                    </thead>

                                    <tbody>

                                        <tr>

                                            <td>
                                                <?= !empty($m['pago_relacionado'])
                                                    ? '#' . (int)$m['pago_relacionado']
                                                    : '-'
                                                ?>
                                            </td>

                                            <td>
                                                <?= e($m['pago_unidad_codigo'] ?? '-') ?>

                                                <?php if (!empty($m['pago_unidad_nombre'])): ?>
                                                    <br>
                                                    <small><?= e($m['pago_unidad_nombre']) ?></small>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?= !empty($m['pago_fecha'])
                                                    ? e(date('d/m/Y', strtotime($m['pago_fecha'])))
                                                    : '-'
                                                ?>
                                            </td>

                                            <td>
                                                <?= !empty($m['pago_relacionado'])
                                                    ? dinero($m['pago_valor'])
                                                    : '-'
                                                ?>
                                            </td>

                                            <td>
                                                <?= e($m['pago_referencia'] ?? '-') ?>
                                            </td>

                                            <td>
                                                <?= e($m['observaciones'] ?? '-') ?>
                                            </td>

                                        </tr>

                                    </tbody>

                                </table>

                            </div>


                            <?php if (
                                $estadoConciliacion === 'RECHAZADO'
                                &&
                                empty($m['pago_relacionado'])
                            ): ?>

                                <br>

                                <form
                                    method="POST"
                                    action="<?= BASE_URL ?>actions/reabrir_movimiento.php"
                                    onsubmit="return confirm('¿Desea reabrir este movimiento para volver a conciliarlo?');"
                                >

                                    <input
                                        type="hidden"
                                        name="id_extracto"
                                        value="<?= (int)$m['id_extracto'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn-secondary"
                                    >
                                        Reabrir conciliación
                                    </button>

                                </form>

                            <?php endif; ?>

                        <?php endif; ?>


                    </div>

                </div>


                <br>


            <?php endforeach; ?>


        <?php endif; ?>


    </main>


</div>


</body>

</html>
