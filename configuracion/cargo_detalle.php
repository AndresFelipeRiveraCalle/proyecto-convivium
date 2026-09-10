<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// RECIBIR ID
// ==========================================================

$idCargo =
    isset($_GET['id'])
        ? (int)$_GET['id']
        : 0;


if ($idCargo <= 0) {

    header(
        "Location: "
        . BASE_URL
        . "configuracion/cargos.php?"
        . http_build_query([
            'tipo' => 'error',
            'texto' => 'El cargo seleccionado no es válido.'
        ])
    );

    exit;
}


// ==========================================================
// CARGAR CABECERA DEL CARGO
// ==========================================================

$sqlCargo = "
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
        cf.fecha_actualizacion,

        c.nombre AS concepto,
        c.tipo_calculo,

        dtu.nombre_grupo,

        u.codigo AS unidad_codigo,
        u.nombre AS unidad_nombre

    FROM cargos_facturacion cf

    INNER JOIN conceptos_facturacion c
        ON c.id_concepto = cf.id_concepto

    LEFT JOIN detalle_tipos_unidad dtu
        ON dtu.id_tipo_config = cf.id_tipo_config

    LEFT JOIN unidades u
        ON u.id_unidad = cf.id_unidad

    WHERE
        cf.id_cargo = :id_cargo

    LIMIT 1
";


$stmtCargo =
    $conexion->prepare(
        $sqlCargo
    );


$stmtCargo->execute([
    ':id_cargo' =>
        $idCargo
]);


$cargo =
    $stmtCargo->fetch(
        PDO::FETCH_ASSOC
    );


if (!$cargo) {

    header(
        "Location: "
        . BASE_URL
        . "configuracion/cargos.php?"
        . http_build_query([
            'tipo' => 'error',
            'texto' => 'El cargo no existe.'
        ])
    );

    exit;
}


// ==========================================================
// CARGAR UNIDADES DEL CARGO
// ==========================================================

$sqlUnidades = "
    SELECT
        cfu.id_cargo_unidad,
        cfu.id_unidad,
        cfu.valor_asignado,
        cfu.cantidad_cuotas,
        cfu.estado,
        cfu.observaciones,

        u.codigo,
        u.nombre,
        u.area,
        u.coeficiente,

        dtu.nombre_grupo,

        (
            SELECT COUNT(*)

            FROM cargos_facturacion_cuotas cfc

            WHERE
                cfc.id_cargo_unidad =
                    cfu.id_cargo_unidad
                AND cfc.estado <> 'ANULADA'

        ) AS total_cuotas,

        (
            SELECT COUNT(*)

            FROM cargos_facturacion_cuotas cfc

            WHERE
                cfc.id_cargo_unidad =
                    cfu.id_cargo_unidad
                AND cfc.estado = 'FACTURADA'

        ) AS cuotas_facturadas,

        (
            SELECT COUNT(*)

            FROM cargos_facturacion_cuotas cfc

            WHERE
                cfc.id_cargo_unidad =
                    cfu.id_cargo_unidad
                AND cfc.estado = 'PENDIENTE'

        ) AS cuotas_pendientes,

        (
            SELECT COALESCE(
                SUM(cfc.valor),
                0
            )

            FROM cargos_facturacion_cuotas cfc

            WHERE
                cfc.id_cargo_unidad =
                    cfu.id_cargo_unidad
                AND cfc.estado <> 'ANULADA'

        ) AS valor_cuotas

    FROM cargos_facturacion_unidades cfu

    INNER JOIN unidades u
        ON u.id_unidad = cfu.id_unidad

    INNER JOIN detalle_tipos_unidad dtu
        ON dtu.id_tipo_config = u.id_tipo_config

    WHERE
        cfu.id_cargo = :id_cargo

    ORDER BY
        dtu.nombre_grupo,
        u.codigo
";


$stmtUnidades =
    $conexion->prepare(
        $sqlUnidades
    );


$stmtUnidades->execute([
    ':id_cargo' =>
        $idCargo
]);


$unidadesCargo =
    $stmtUnidades->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// CARGAR TODAS LAS CUOTAS
// ==========================================================

$sqlCuotas = "
    SELECT
        cfc.id_cuota,
        cfc.id_cargo_unidad,
        cfc.numero_cuota,
        cfc.periodo,
        cfc.valor,
        cfc.estado,
        cfc.fecha_facturacion,
        cfc.observaciones

    FROM cargos_facturacion_cuotas cfc

    INNER JOIN cargos_facturacion_unidades cfu
        ON cfu.id_cargo_unidad =
           cfc.id_cargo_unidad

    WHERE
        cfu.id_cargo = :id_cargo

    ORDER BY
        cfu.id_unidad,
        cfc.numero_cuota
";


$stmtCuotas =
    $conexion->prepare(
        $sqlCuotas
    );


$stmtCuotas->execute([
    ':id_cargo' =>
        $idCargo
]);


$cuotas =
    $stmtCuotas->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// AGRUPAR CUOTAS POR CARGO-UNIDAD
// ==========================================================

$cuotasPorUnidad = [];


foreach ($cuotas as $cuota) {

    $idCargoUnidad =
        (int)$cuota[
            'id_cargo_unidad'
        ];


    if (
        !isset(
            $cuotasPorUnidad[
                $idCargoUnidad
            ]
        )
    ) {

        $cuotasPorUnidad[
            $idCargoUnidad
        ] = [];
    }


    $cuotasPorUnidad[
        $idCargoUnidad
    ][] = $cuota;
}


// ==========================================================
// RESUMEN
// ==========================================================

$totalAsignado = 0;

$totalCuotas = 0;

$cuotasFacturadas = 0;

$cuotasPendientes = 0;

$cuotasAnuladas = 0;


foreach ($unidadesCargo as $unidad) {

    if (
        $unidad['estado']
        === 'ACTIVO'
    ) {

        $totalAsignado +=
            (float)$unidad[
                'valor_asignado'
            ];
    }
}


foreach ($cuotas as $cuota) {

    $totalCuotas++;

    switch ($cuota['estado']) {

        case 'FACTURADA':

            $cuotasFacturadas++;

            break;

        case 'PENDIENTE':

            $cuotasPendientes++;

            break;

        case 'ANULADA':

            $cuotasAnuladas++;

            break;
    }
}


// ==========================================================
// FUNCIONES
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
            return 'Por metro cuadrado';

        case 'COEFICIENTE':
            return 'Por coeficiente';

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


        <!-- ======================================================
             ENCABEZADO
        ======================================================= -->

        <h2 align="center">
            Detalle del cargo
        </h2>


        <br>


        <div class="form-actions">

            <a
                href="<?= BASE_URL ?>configuracion/cargos.php"
                class="btn-limpiar"
            >
                Volver
            </a>

        </div>


        <br>


        <!-- ======================================================
             INFORMACIÓN DEL CARGO
        ======================================================= -->

        <div class="bloque">


            <div class="form-card">


                <h3>

                    <?= htmlspecialchars(
                        $cargo['nombre']
                    ) ?>

                </h3>


                <br>


                <p>

                    <strong>
                        Concepto:
                    </strong>

                    <?= htmlspecialchars(
                        $cargo['concepto']
                    ) ?>

                </p>


                <?php if (
                    !empty(
                        $cargo['descripcion']
                    )
                ): ?>

                    <p>

                        <strong>
                            Descripción:
                        </strong>

                        <?= htmlspecialchars(
                            $cargo['descripcion']
                        ) ?>

                    </p>

                <?php endif; ?>


                <p>

                    <strong>
                        Aplicación:
                    </strong>

                    <?= htmlspecialchars(
                        nombreTipoAplicacion(
                            $cargo[
                                'tipo_aplicacion'
                            ]
                        )
                    ) ?>

                </p>


                <?php if (
                    $cargo['tipo_aplicacion']
                    === 'TIPO_UNIDAD'
                ): ?>

                    <p>

                        <strong>
                            Grupo:
                        </strong>

                        <?= htmlspecialchars(
                            $cargo[
                                'nombre_grupo'
                            ] ?? ''
                        ) ?>

                    </p>

                <?php endif; ?>


                <?php if (
                    $cargo['tipo_aplicacion']
                    === 'UNIDAD'
                ): ?>

                    <p>

                        <strong>
                            Unidad:
                        </strong>

                        <?= htmlspecialchars(
                            $cargo[
                                'unidad_codigo'
                            ] ?? ''
                        ) ?>

                    </p>

                <?php endif; ?>


                <p>

                    <strong>
                        Distribución:
                    </strong>

                    <?= htmlspecialchars(
                        nombreTipoDistribucion(
                            $cargo[
                                'tipo_distribucion'
                            ]
                        )
                    ) ?>

                </p>


                <p>

                    <strong>
                        Valor total:
                    </strong>

                    $

                    <?= number_format(
                        (float)$cargo[
                            'valor_total'
                        ],
                        2,
                        ',',
                        '.'
                    ) ?>

                </p>


                <p>

                    <strong>
                        Número de cuotas:
                    </strong>

                    <?= (int)$cargo[
                        'numero_cuotas'
                    ] ?>

                </p>


                <p>

                    <strong>
                        Primera cuota:
                    </strong>

                    <?= date(
                        'm/Y',
                        strtotime(
                            $cargo[
                                'periodo_inicio'
                            ]
                        )
                    ) ?>

                </p>


                <p>

                    <strong>
                        Estado:
                    </strong>

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

                        Borrador

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

                </p>


                <?php if (
                    !empty(
                        $cargo['observaciones']
                    )
                ): ?>

                    <p>

                        <strong>
                            Observaciones:
                        </strong>

                        <?= htmlspecialchars(
                            $cargo['observaciones']
                        ) ?>

                    </p>

                <?php endif; ?>


            </div>


        </div>


        <br>


        <!-- ======================================================
             RESUMEN
        ======================================================= -->

        <div class="bloque">


            <div class="tabla-responsive">


                <table class="tabla">


                    <thead>

                        <tr>

                            <th>
                                Unidades
                            </th>

                            <th>
                                Valor cargo
                            </th>

                            <th>
                                Valor distribuido
                            </th>

                            <th>
                                Cuotas
                            </th>

                            <th>
                                Facturadas
                            </th>

                            <th>
                                Pendientes
                            </th>

                            <th>
                                Anuladas
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <tr>

                            <td>

                                <?= count(
                                    $unidadesCargo
                                ) ?>

                            </td>


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


                            <td>

                                <strong>

                                    $

                                    <?= number_format(
                                        $totalAsignado,
                                        2,
                                        ',',
                                        '.'
                                    ) ?>

                                </strong>

                            </td>


                            <td>

                                <?= $totalCuotas ?>

                            </td>


                            <td>

                                <?= $cuotasFacturadas ?>

                            </td>


                            <td>

                                <?= $cuotasPendientes ?>

                            </td>


                            <td>

                                <?= $cuotasAnuladas ?>

                            </td>


                        </tr>

                    </tbody>


                </table>


            </div>


        </div>


        <br>


        <!-- ======================================================
             DISTRIBUCIÓN POR UNIDAD
        ======================================================= -->

        <div class="bloque">


            <h3>
                Distribución por inmueble
            </h3>


            <br>


            <div class="tabla-responsive">


                <table class="tabla">


                    <thead>

                        <tr>

                            <th>
                                Unidad
                            </th>

                            <th>
                                Grupo
                            </th>

                            <th>
                                Área
                            </th>

                            <th>
                                Coeficiente
                            </th>

                            <th>
                                Valor asignado
                            </th>

                            <th>
                                Cuotas
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


                    <?php if (
                        empty(
                            $unidadesCargo
                        )
                    ): ?>


                        <tr>

                            <td
                                colspan="8"
                                align="center"
                            >

                                No existen unidades
                                asignadas a este cargo.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $unidadesCargo
                            as $unidad
                        ): ?>


                            <?php

                            $idCargoUnidad =
                                (int)$unidad[
                                    'id_cargo_unidad'
                                ];

                            $listaCuotas =
                                $cuotasPorUnidad[
                                    $idCargoUnidad
                                ] ?? [];

                            ?>


                            <tr>


                                <!-- UNIDAD -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $unidad[
                                                'codigo'
                                            ]
                                        ) ?>

                                    </strong>


                                    <?php if (
                                        !empty(
                                            $unidad[
                                                'nombre'
                                            ]
                                        )
                                    ): ?>

                                        <br>

                                        <small>

                                            <?= htmlspecialchars(
                                                $unidad[
                                                    'nombre'
                                                ]
                                            ) ?>

                                        </small>

                                    <?php endif; ?>

                                </td>


                                <!-- GRUPO -->

                                <td>

                                    <?= htmlspecialchars(
                                        $unidad[
                                            'nombre_grupo'
                                        ]
                                    ) ?>

                                </td>


                                <!-- ÁREA -->

                                <td>

                                    <?= number_format(
                                        (float)$unidad[
                                            'area'
                                        ],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>

                                    m²

                                </td>


                                <!-- COEFICIENTE -->

                                <td>

                                    <?= number_format(
                                        (float)$unidad[
                                            'coeficiente'
                                        ],
                                        8,
                                        ',',
                                        '.'
                                    ) ?>

                                </td>


                                <!-- VALOR ASIGNADO -->

                                <td>

                                    <strong>

                                        $

                                        <?= number_format(
                                            (float)$unidad[
                                                'valor_asignado'
                                            ],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- CUOTAS -->

                                <td>

                                    <?= (int)$unidad[
                                        'cuotas_facturadas'
                                    ] ?>

                                    facturadas

                                    <br>

                                    <?= (int)$unidad[
                                        'cuotas_pendientes'
                                    ] ?>

                                    pendientes

                                    <br>

                                    <small>

                                        Total:

                                        <?= (int)$unidad[
                                            'total_cuotas'
                                        ] ?>

                                    </small>

                                </td>


                                <!-- ESTADO -->

                                <td>

                                    <?php if (
                                        $unidad[
                                            'estado'
                                        ]
                                        === 'ACTIVO'
                                    ): ?>

                                        <span class="activo">

                                            Activo

                                        </span>

                                    <?php else: ?>

                                        <span class="inactivo">

                                            Anulado

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACCIONES -->

                                <td>

                                    <button
                                        type="button"
                                        class="btn-editar btn-ver-cuotas"
                                        data-id="<?= $idCargoUnidad ?>"
                                    >
                                        Ver cuotas
                                    </button>

                                </td>


                            </tr>


                            <!-- ==================================
                                 FILA DE CUOTAS
                            =================================== -->

                            <tr
                                id="cuotas-<?= $idCargoUnidad ?>"
                                style="display:none;"
                            >


                                <td
                                    colspan="8"
                                >


                                    <div
                                        style="
                                            padding:15px;
                                        "
                                    >


                                        <strong>

                                            Cuotas de la unidad

                                            <?= htmlspecialchars(
                                                $unidad[
                                                    'codigo'
                                                ]
                                            ) ?>

                                        </strong>


                                        <br><br>


                                        <div class="tabla-responsive">


                                            <table class="tabla">


                                                <thead>

                                                    <tr>

                                                        <th>
                                                            Cuota
                                                        </th>

                                                        <th>
                                                            Período
                                                        </th>

                                                        <th>
                                                            Valor
                                                        </th>

                                                        <th>
                                                            Estado
                                                        </th>

                                                        <th>
                                                            Fecha facturación
                                                        </th>

                                                    </tr>

                                                </thead>


                                                <tbody>


                                                <?php if (
                                                    empty(
                                                        $listaCuotas
                                                    )
                                                ): ?>


                                                    <tr>

                                                        <td
                                                            colspan="5"
                                                            align="center"
                                                        >

                                                            No existen cuotas.

                                                        </td>

                                                    </tr>


                                                <?php else: ?>


                                                    <?php foreach (
                                                        $listaCuotas
                                                        as $cuota
                                                    ): ?>


                                                        <tr>


                                                            <td>

                                                                <?= (int)$cuota[
                                                                    'numero_cuota'
                                                                ] ?>

                                                                /

                                                                <?= (int)$unidad[
                                                                    'cantidad_cuotas'
                                                                ] ?>

                                                            </td>


                                                            <td>

                                                                <?= date(
                                                                    'm/Y',
                                                                    strtotime(
                                                                        $cuota[
                                                                            'periodo'
                                                                        ]
                                                                    )
                                                                ) ?>

                                                            </td>


                                                            <td>

                                                                <strong>

                                                                    $

                                                                    <?= number_format(
                                                                        (float)$cuota[
                                                                            'valor'
                                                                        ],
                                                                        2,
                                                                        ',',
                                                                        '.'
                                                                    ) ?>

                                                                </strong>

                                                            </td>


                                                            <td>

                                                                <?php if (
                                                                    $cuota[
                                                                        'estado'
                                                                    ]
                                                                    === 'FACTURADA'
                                                                ): ?>

                                                                    <span class="activo">

                                                                        Facturada

                                                                    </span>


                                                                <?php elseif (
                                                                    $cuota[
                                                                        'estado'
                                                                    ]
                                                                    === 'PENDIENTE'
                                                                ): ?>

                                                                    Pendiente


                                                                <?php else: ?>

                                                                    <span class="inactivo">

                                                                        Anulada

                                                                    </span>

                                                                <?php endif; ?>

                                                            </td>


                                                            <td>

                                                                <?php if (
                                                                    !empty(
                                                                        $cuota[
                                                                            'fecha_facturacion'
                                                                        ]
                                                                    )
                                                                ): ?>

                                                                    <?= date(
                                                                        'd/m/Y',
                                                                        strtotime(
                                                                            $cuota[
                                                                                'fecha_facturacion'
                                                                            ]
                                                                        )
                                                                    ) ?>

                                                                <?php else: ?>

                                                                    -

                                                                <?php endif; ?>

                                                            </td>


                                                        </tr>


                                                    <?php endforeach; ?>


                                                <?php endif; ?>


                                                </tbody>


                                            </table>


                                        </div>


                                    </div>


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
     JAVASCRIPT
=========================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const botones =
            document.querySelectorAll(
                ".btn-ver-cuotas"
            );


        botones.forEach(
            function (boton) {


                boton.addEventListener(
                    "click",
                    function () {


                        const id =
                            this.dataset.id;


                        const fila =
                            document.getElementById(
                                "cuotas-" + id
                            );


                        if (!fila) {

                            return;
                        }


                        const visible =
                            fila.style.display
                            !== "none";


                        document
                            .querySelectorAll(
                                '[id^="cuotas-"]'
                            )
                            .forEach(
                                function (otraFila) {

                                    otraFila.style.display =
                                        "none";

                                }
                            );


                        document
                            .querySelectorAll(
                                ".btn-ver-cuotas"
                            )
                            .forEach(
                                function (otroBoton) {

                                    otroBoton.textContent =
                                        "Ver cuotas";

                                }
                            );


                        if (!visible) {

                            fila.style.display =
                                "table-row";

                            this.textContent =
                                "Ocultar cuotas";
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