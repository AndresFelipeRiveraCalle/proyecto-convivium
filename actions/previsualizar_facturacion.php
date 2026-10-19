<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// VALIDAR MÉTODO
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/factura.php"
    );

    exit;
}


// ==========================================================
// FUNCIÓN ESCAPAR HTML
// ==========================================================

function e($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}


// ==========================================================
// FUNCIÓN TIPO DE CÁLCULO
// ==========================================================

function nombreTipoCalculo($tipo)
{
    switch ($tipo) {

        case 'FIJO':
            return 'Valor fijo';

        case 'METRO_CUADRADO':
            return 'Por metro cuadrado';

        case 'COEFICIENTE':
            return 'Por coeficiente';

        case 'PORCENTAJE':
            return 'Porcentaje';

        default:
            return $tipo;
    }
}


// ==========================================================
// DATOS RECIBIDOS
// ==========================================================

$idCalendario = isset($_POST['id_calendario'])
    ? (int)$_POST['id_calendario']
    : 0;


$idTipoConfig = isset($_POST['id_tipo_config'])
    ? (int)$_POST['id_tipo_config']
    : 0;


$conceptosOpcionales =
    isset($_POST['conceptos']) &&
    is_array($_POST['conceptos'])
        ? array_map(
            'intval',
            $_POST['conceptos']
        )
        : [];


// ==========================================================
// EXCLUIR CONCEPTOS QUE NO PERTENECEN A FACTURACIÓN NORMAL
// ==========================================================
//
// Intereses de mora (id_concepto = 4)
// se procesa posteriormente desde CARTERA.
//
// ==========================================================

$conceptosNoFacturablesDirectamente = [
    4
];


$conceptosOpcionales = array_values(
    array_diff(
        $conceptosOpcionales,
        $conceptosNoFacturablesDirectamente
    )
);


$observaciones = isset($_POST['observaciones'])
    ? trim($_POST['observaciones'])
    : '';


// ==========================================================
// LIMPIAR IDS OPCIONALES
// ==========================================================

$conceptosOpcionales = array_values(
    array_unique(
        array_filter(
            $conceptosOpcionales,
            function ($id) {
                return $id > 0;
            }
        )
    )
);


// ==========================================================
// VALIDAR CALENDARIO
// ==========================================================

if ($idCalendario <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/factura.php" .
        "?tipo=warning&texto=" .
        urlencode(
            "Debe seleccionar un período financiero."
        )
    );

    exit;
}


// ==========================================================
// VARIABLES DE PREVISUALIZACIÓN
// ==========================================================

$calendario = null;

$unidades = [];

$conceptos = [];

$conceptosEspacioConfigurados = [];

$idsConceptosEspacio = [];

$detallePreview = [];

$erroresGenerales = [];

$advertencias = [];

$totalCalculado = 0;

$cantidadDetalles = 0;

$cantidadErrores = 0;

$cantidadOmitidas = 0;

$cantidadFacturables = 0;

$cantidadEspaciosFacturados = 0;

$cantidadCargosFacturados = 0;

$cantidadEspaciosSueltos = 0;


// ==========================================================
// ESPACIOS
// ==========================================================
//
// El generador ya fue preparado para espacios, por lo que
// no bloquearemos la confirmación solamente por existir
// espacios asociados.
//
// ==========================================================

$tieneCargosEspacios = false;


try {

    // ======================================================
    // BUSCAR CALENDARIO
    // ======================================================

    $sqlCalendario = "
        SELECT
            id_calendario,
            periodo,
            fecha_facturacion,
            fecha_vencimiento,
            estado,
            observaciones

        FROM calendario_financiero

        WHERE id_calendario = :id_calendario

        LIMIT 1
    ";


    $stmtCalendario = $conexion->prepare(
        $sqlCalendario
    );


    $stmtCalendario->execute([

        ':id_calendario'
            => $idCalendario

    ]);


    $calendario = $stmtCalendario->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$calendario) {

        throw new RuntimeException(
            "El período financiero seleccionado no existe."
        );
    }


    // ======================================================
    // DATOS DEL PERÍODO
    // ======================================================

    $periodo =
        $calendario['periodo'];

    $fechaFacturacion =
        $calendario['fecha_facturacion'];

    $fechaVencimiento =
        $calendario['fecha_vencimiento'];

    $estadoCalendario =
        $calendario['estado'];


    $anio = (int)date(
        'Y',
        strtotime($periodo)
    );


    $mes = (int)date(
        'm',
        strtotime($periodo)
    );


    $periodoCargo = date(
        'Y-m-01',
        strtotime($periodo)
    );


    // ======================================================
    // VALIDAR ESTADO
    // ======================================================

    if ($estadoCalendario === 'CERRADO') {

        $advertencias[] =
            "El período financiero está cerrado. " .
            "La vista previa puede consultarse, " .
            "pero no se permitirá confirmar la facturación.";
    }


    // ======================================================
    // BUSCAR UNIDADES ACTIVAS
    // ======================================================

    $sqlUnidades = "
        SELECT
            u.id_unidad,
            u.id_tipo_config,
            u.codigo,
            u.nombre,
            u.area,
            u.coeficiente,

            dtu.nombre_grupo

        FROM unidades u

        INNER JOIN detalle_tipos_unidad dtu
            ON dtu.id_tipo_config =
               u.id_tipo_config

        WHERE u.activo = 1
    ";


    $paramsUnidades = [];


    if ($idTipoConfig > 0) {

        $sqlUnidades .= "
            AND u.id_tipo_config =
                :id_tipo_config
        ";

        $paramsUnidades[
            ':id_tipo_config'
        ] = $idTipoConfig;
    }


    $sqlUnidades .= "
        ORDER BY
            dtu.nombre_grupo,
            u.codigo,
            u.id_unidad
    ";


    $stmtUnidades = $conexion->prepare(
        $sqlUnidades
    );


    $stmtUnidades->execute(
        $paramsUnidades
    );


    $unidades = $stmtUnidades->fetchAll(
        PDO::FETCH_ASSOC
    );


    if (empty($unidades)) {

        $erroresGenerales[] =
            "No existen unidades activas para los criterios seleccionados.";
    }


    // ======================================================
    // CARGAR CONFIGURACIÓN DE CONCEPTOS DE ESPACIOS
    // ======================================================

    $sqlConfigEspacios = "
        SELECT
            cce.id_config,
            cce.tipo_espacio,
            cce.id_concepto,

            cf.nombre,
            cf.descripcion,
            cf.tipo_calculo,
            cf.obligatorio,
            cf.id_tipo_obligacion,
            cf.estado

        FROM configuracion_conceptos_espacio cce

        INNER JOIN conceptos_facturacion cf
            ON cf.id_concepto =
               cce.id_concepto

        WHERE
            cce.activo = 1
            AND cf.estado = 1

        ORDER BY
            cce.tipo_espacio
    ";


    $stmtConfigEspacios =
        $conexion->query(
            $sqlConfigEspacios
        );


    $conceptosEspacioConfigurados =
        $stmtConfigEspacios->fetchAll(
            PDO::FETCH_ASSOC
        );


    foreach (
        $conceptosEspacioConfigurados
        as $configEspacio
    ) {

        $idsConceptosEspacio[] =
            (int)$configEspacio['id_concepto'];
    }


    $idsConceptosEspacio =
        array_values(
            array_unique(
                $idsConceptosEspacio
            )
        );


    // ======================================================
    // CARGAR CONCEPTOS OBLIGATORIOS
    // ======================================================

    $sqlObligatorios = "
        SELECT
            id_concepto

        FROM conceptos_facturacion

        WHERE estado = 1
        AND obligatorio = 1
    ";


    $stmtObligatorios = $conexion->query(
        $sqlObligatorios
    );


    $idsObligatorios = $stmtObligatorios->fetchAll(
        PDO::FETCH_COLUMN
    );


    $idsObligatorios = array_map(
        'intval',
        $idsObligatorios
    );


    $idsObligatorios = array_values(
        array_diff(
            $idsObligatorios,
            $conceptosNoFacturablesDirectamente
        )
    );


    // ======================================================
    // EVITAR DUPLICAR CONCEPTOS DE ESPACIOS
    // ======================================================

    $conceptosOpcionalesGenerales =
        array_values(
            array_diff(
                $conceptosOpcionales,
                $idsConceptosEspacio
            )
        );


    // ======================================================
    // UNIR OBLIGATORIOS + OPCIONALES GENERALES
    // ======================================================

    $idsConceptos = array_values(
        array_unique(
            array_merge(
                $idsObligatorios,
                $conceptosOpcionalesGenerales
            )
        )
    );


    // ======================================================
    // BUSCAR CONCEPTOS GENERALES
    // ======================================================

    if (!empty($idsConceptos)) {

        $marcadores = implode(
            ',',
            array_fill(
                0,
                count($idsConceptos),
                '?'
            )
        );


        $sqlConceptos = "
            SELECT
                cf.id_concepto,
                cf.nombre,
                cf.descripcion,
                cf.tipo_calculo,
                cf.obligatorio,
                cf.id_tipo_obligacion

            FROM conceptos_facturacion cf

            WHERE cf.estado = 1

            AND cf.id_concepto
                IN ($marcadores)

            ORDER BY
                cf.obligatorio DESC,
                cf.nombre
        ";


        $stmtConceptos = $conexion->prepare(
            $sqlConceptos
        );


        $stmtConceptos->execute(
            $idsConceptos
        );


        $conceptos = $stmtConceptos->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    // ======================================================
    // PREPARAR CONSULTA TARIFA
    // ======================================================

    $sqlTarifa = "
        SELECT
            id_tarifa,
            nombre,
            valor,
            fecha_inicio,
            fecha_fin

        FROM tarifas_facturacion

        WHERE id_concepto =
            :id_concepto

        AND id_tipo_config =
            :id_tipo_config

        AND estado = 1

        AND fecha_inicio <=
            :fecha_facturacion_inicio

        AND (
            fecha_fin IS NULL
            OR fecha_fin >=
               :fecha_facturacion_fin
        )

        ORDER BY
            fecha_inicio DESC,
            id_tarifa DESC

        LIMIT 1
    ";


    $stmtTarifa = $conexion->prepare(
        $sqlTarifa
    );


    // ======================================================
    // PREPARAR CONSULTA FACTURA EXISTENTE
    // ======================================================

    $sqlFacturaExiste = "
        SELECT
            id_factura,
            numero_factura,
            estado,
            subtotal,
            intereses,
            saldos_anteriores,
            total

        FROM facturas

        WHERE id_unidad = :id_unidad

        AND periodo = :anio

        AND mes = :mes

        AND estado <> 'ANULADA'

        LIMIT 1
    ";


    $stmtFacturaExiste = $conexion->prepare(
        $sqlFacturaExiste
    );


    // ======================================================
    // PREPARAR CONSULTA DE CARGOS PENDIENTES
    // ======================================================

    $sqlCargosUnidad = "
        SELECT
            cfc.id_cuota,
            cfc.id_cargo_unidad,
            cfc.numero_cuota,
            cfc.periodo,
            cfc.valor,

            cfu.cantidad_cuotas,

            c.id_cargo,
            c.id_concepto,
            c.nombre AS cargo_nombre,
            c.descripcion AS cargo_descripcion,
            c.tipo_distribucion,

            cf.nombre AS concepto_nombre,
            cf.tipo_calculo,
            cf.id_tipo_obligacion

        FROM cargos_facturacion_cuotas cfc

        INNER JOIN cargos_facturacion_unidades cfu
            ON cfu.id_cargo_unidad =
               cfc.id_cargo_unidad

        INNER JOIN cargos_facturacion c
            ON c.id_cargo =
               cfu.id_cargo

        INNER JOIN conceptos_facturacion cf
            ON cf.id_concepto =
               c.id_concepto

        WHERE
            cfu.id_unidad =
                :id_unidad

            AND cfu.estado =
                'ACTIVO'

            AND c.estado =
                'ACTIVO'

            AND cfc.estado =
                'PENDIENTE'

            AND cfc.periodo =
                :periodo

            AND cf.estado = 1

        ORDER BY
            c.id_cargo,
            cfc.numero_cuota
    ";


    $stmtCargosUnidad =
        $conexion->prepare(
            $sqlCargosUnidad
        );


    // ======================================================
    // PREPARAR CONSULTA ESPACIOS DE UNA UNIDAD
    // ======================================================

    $sqlEspaciosUnidad = "
        SELECT

            eu.id_espacio_unidad,
            eu.id_unidad,
            eu.usuario_id,
            eu.tipo_espacio,
            eu.codigo,
            eu.area,
            eu.fecha_desde,
            eu.fecha_hasta,

            cce.id_concepto,

            cf.nombre AS concepto,
            cf.descripcion,
            cf.tipo_calculo,
            cf.id_tipo_obligacion

        FROM espacios_unidad eu

        INNER JOIN configuracion_conceptos_espacio cce
            ON cce.tipo_espacio =
               eu.tipo_espacio
            AND cce.activo = 1

        INNER JOIN conceptos_facturacion cf
            ON cf.id_concepto =
               cce.id_concepto
            AND cf.estado = 1

        WHERE
            eu.id_unidad = :id_unidad
            AND eu.activo = 1

            AND eu.fecha_desde <=
                :fecha_facturacion_desde

            AND (
                eu.fecha_hasta IS NULL
                OR eu.fecha_hasta >=
                   :fecha_facturacion_hasta
            )

        ORDER BY
            eu.tipo_espacio,
            eu.codigo,
            eu.id_espacio_unidad
    ";


    $stmtEspaciosUnidad =
        $conexion->prepare(
            $sqlEspaciosUnidad
        );


    // ======================================================
    // DETECTAR ESPACIOS SUELTOS
    // ======================================================

    $sqlEspaciosSueltos = "
        SELECT
            COUNT(*)

        FROM espacios_unidad eu

        INNER JOIN configuracion_conceptos_espacio cce
            ON cce.tipo_espacio =
               eu.tipo_espacio
            AND cce.activo = 1

        INNER JOIN conceptos_facturacion cf
            ON cf.id_concepto =
               cce.id_concepto
            AND cf.estado = 1

        WHERE
            eu.id_unidad IS NULL
            AND eu.activo = 1

            AND eu.fecha_desde <=
                :fecha_facturacion_desde

            AND (
                eu.fecha_hasta IS NULL
                OR eu.fecha_hasta >=
                   :fecha_facturacion_hasta
            )
    ";


    $stmtEspaciosSueltos =
        $conexion->prepare(
            $sqlEspaciosSueltos
        );


    $stmtEspaciosSueltos->execute([

        ':fecha_facturacion_desde'
            => $fechaFacturacion,

        ':fecha_facturacion_hasta'
            => $fechaFacturacion

    ]);


    $cantidadEspaciosSueltos =
        (int)$stmtEspaciosSueltos->fetchColumn();


    if ($cantidadEspaciosSueltos > 0) {

        $advertencias[] =
            "Existen " .
            $cantidadEspaciosSueltos .
            " espacio(s) vigente(s) sin unidad asociada. " .
            "Estos espacios requieren facturación directa al propietario " .
            "y todavía no se incluyen en esta generación por unidad.";
    }


    // ======================================================
    // RECORRER UNIDADES
    // ======================================================

    foreach ($unidades as $unidad) {

        $idUnidad =
            (int)$unidad['id_unidad'];

        $idTipoUnidad =
            (int)$unidad['id_tipo_config'];

        $area =
            (float)($unidad['area'] ?? 0);

        $coeficiente =
            (float)($unidad['coeficiente'] ?? 0);


        // ==================================================
        // VERIFICAR SI YA EXISTE FACTURA
        // ==================================================

        $stmtFacturaExiste->execute([

            ':id_unidad'
                => $idUnidad,

            ':anio'
                => $anio,

            ':mes'
                => $mes

        ]);


        $facturaExistente =
            $stmtFacturaExiste->fetch(
                PDO::FETCH_ASSOC
            );


        $unidadTieneDetalleValido = false;

        $facturaExistenteModificable = false;


        // ==================================================
        // FACTURA EXISTENTE
        // ==================================================
        //
        // BORRADOR / GENERADA:
        // puede recibir cargos nuevos del mismo período.
        //
        // PARCIAL / PAGADA / VENCIDA:
        // no se modifica automáticamente.
        //
        // ==================================================

        if ($facturaExistente) {

            $cantidadOmitidas++;

            if (
                in_array(
                    $facturaExistente['estado'],
                    ['BORRADOR', 'GENERADA'],
                    true
                )
            ) {

                $facturaExistenteModificable = true;

                $detallePreview[] = [

                    'id_unidad'
                        => $idUnidad,

                    'unidad'
                        => $unidad['codigo'],

                    'grupo'
                        => $unidad['nombre_grupo'],

                    'concepto'
                        => 'Factura existente',

                    'tipo_calculo'
                        => '-',

                    'tarifa'
                        => null,

                    'base'
                        => null,

                    'valor'
                        => 0,

                    'estado'
                        => 'OMITIDA',

                    'mensaje'
                        => 'La unidad ya tiene la factura ' .
                           (
                               !empty($facturaExistente['numero_factura'])
                                   ? $facturaExistente['numero_factura']
                                   : '#' . $facturaExistente['id_factura']
                           ) .
                           ' por $' .
                           number_format(
                               (float)$facturaExistente['total'],
                               2,
                               ',',
                               '.'
                           ) .
                           '. Se revisarán cargos nuevos pendientes para agregarlos a esta factura.',

                    'url_corregir'
                        => null
                ];

            } else {

                $detallePreview[] = [

                    'id_unidad'
                        => $idUnidad,

                    'unidad'
                        => $unidad['codigo'],

                    'grupo'
                        => $unidad['nombre_grupo'],

                    'concepto'
                        => 'Factura existente',

                    'tipo_calculo'
                        => '-',

                    'tarifa'
                        => null,

                    'base'
                        => null,

                    'valor'
                        => 0,

                    'estado'
                        => 'OMITIDA',

                    'mensaje'
                        => 'La unidad ya tiene una factura en estado ' .
                           $facturaExistente['estado'] .
                           '. No se agregarán cargos automáticamente.',

                    'url_corregir'
                        => null
                ];

                continue;
            }
        }


        // ==================================================
        // SI NO EXISTE FACTURA:
        // CONCEPTOS GENERALES DE LA UNIDAD
        // ==================================================

        if (!$facturaExistente) {

            foreach ($conceptos as $concepto) {

                $idConcepto =
                    (int)$concepto['id_concepto'];

                $tipoCalculo =
                    $concepto['tipo_calculo'];


                // ==============================================
                // VALIDAR TIPO DE OBLIGACIÓN
                // ==============================================

                if (
                    empty(
                        $concepto['id_tipo_obligacion']
                    )
                ) {

                    $detallePreview[] = [

                        'id_unidad'
                            => $idUnidad,

                        'unidad'
                            => $unidad['codigo'],

                        'grupo'
                            => $unidad['nombre_grupo'],

                        'concepto'
                            => $concepto['nombre'],

                        'tipo_calculo'
                            => nombreTipoCalculo(
                                $tipoCalculo
                            ),

                        'tarifa'
                            => null,

                        'base'
                            => null,

                        'valor'
                            => 0,

                        'estado'
                            => 'ERROR',

                        'mensaje'
                            => 'El concepto no tiene tipo de obligación configurado.',

                        'url_corregir'
                            => BASE_URL .
                               'configuracion/conceptos_facturacion.php'
                    ];


                    $cantidadErrores++;

                    continue;
                }


                // ==============================================
                // BUSCAR TARIFA VIGENTE
                // ==============================================

                $stmtTarifa->execute([

                    ':id_concepto'
                        => $idConcepto,

                    ':id_tipo_config'
                        => $idTipoUnidad,

                    ':fecha_facturacion_inicio'
                        => $fechaFacturacion,

                    ':fecha_facturacion_fin'
                        => $fechaFacturacion

                ]);


                $tarifa = $stmtTarifa->fetch(
                    PDO::FETCH_ASSOC
                );


                // ==============================================
                // SIN TARIFA
                // ==============================================

                if (!$tarifa) {

                    $advertencias[] =
                        'La unidad '
                        . $unidad['codigo']
                        . ' no tiene una tarifa activa y vigente para '
                        . $concepto['nombre']
                        . '. No se generará este concepto.';

                    continue;
                }


                $valorTarifa =
                    (float)$tarifa['valor'];


                $baseCalculo = null;

                $cantidad = 1;

                $valorCalculado = 0;

                $mensaje = 'Correcto';

                $estadoFila = 'OK';

                $urlCorregir = null;


                // ==============================================
                // CALCULAR
                // ==============================================

                switch ($tipoCalculo) {


                    case 'FIJO':

                        $cantidad = 1;

                        $baseCalculo = 1;

                        $valorCalculado =
                            $valorTarifa;

                        break;


                    case 'METRO_CUADRADO':

                        if ($area <= 0) {

                            $estadoFila =
                                'ERROR';

                            $mensaje =
                                'La unidad no tiene un área válida configurada.';

                            break;
                        }


                        $cantidad =
                            $area;

                        $baseCalculo =
                            $area;

                        $valorCalculado =
                            $area *
                            $valorTarifa;

                        break;


                    case 'COEFICIENTE':

                        if ($coeficiente <= 0) {

                            $estadoFila =
                                'ERROR';

                            $mensaje =
                                'La unidad no tiene un coeficiente válido configurado.';

                            break;
                        }


                        $cantidad =
                            $coeficiente;

                        $baseCalculo =
                            $coeficiente;

                        $valorCalculado =
                            $coeficiente *
                            $valorTarifa;

                        break;


                    case 'PORCENTAJE':

                        $estadoFila =
                            'ERROR';

                        $mensaje =
                            'El concepto usa PORCENTAJE, pero todavía no se ha definido la base sobre la cual debe aplicarse.';

                        $urlCorregir =
                            BASE_URL .
                            'configuracion/conceptos_facturacion.php';

                        break;


                    default:

                        $estadoFila =
                            'ERROR';

                        $mensaje =
                            'El tipo de cálculo no es reconocido por el sistema.';

                        break;
                }


                $valorCalculado =
                    round(
                        $valorCalculado,
                        2
                    );


                if (
                    $estadoFila === 'OK' &&
                    $valorCalculado <= 0
                ) {

                    $estadoFila =
                        'ERROR';

                    $mensaje =
                        'El cálculo produjo un valor igual o menor que cero.';
                }


                if ($estadoFila === 'OK') {

                    $totalCalculado +=
                        $valorCalculado;

                    $cantidadDetalles++;

                    $unidadTieneDetalleValido = true;

                } else {

                    $cantidadErrores++;
                }


                $detallePreview[] = [

                    'id_unidad'
                        => $idUnidad,

                    'unidad'
                        => $unidad['codigo'],

                    'grupo'
                        => $unidad['nombre_grupo'],

                    'concepto'
                        => $concepto['nombre'],

                    'tipo_calculo'
                        => nombreTipoCalculo(
                            $tipoCalculo
                        ),

                    'tarifa'
                        => $valorTarifa,

                    'base'
                        => $baseCalculo,

                    'valor'
                        => $valorCalculado,

                    'estado'
                        => $estadoFila,

                    'mensaje'
                        => $mensaje,

                    'url_corregir'
                        => $urlCorregir
                ];
            }
        }


        // ==================================================
        // CARGOS PENDIENTES DEL PERÍODO
        // ==================================================
        //
        // Se revisan tanto para facturas nuevas como para
        // facturas existentes BORRADOR / GENERADA.
        //
        // ==================================================

        $stmtCargosUnidad->execute([

            ':id_unidad'
                => $idUnidad,

            ':periodo'
                => $periodoCargo

        ]);


        $cargosUnidad =
            $stmtCargosUnidad->fetchAll(
                PDO::FETCH_ASSOC
            );


        foreach ($cargosUnidad as $cargo) {

            $valorCargo =
                round(
                    (float)$cargo['valor'],
                    2
                );


            if ($valorCargo <= 0) {

                $detallePreview[] = [

                    'id_unidad'
                        => $idUnidad,

                    'unidad'
                        => $unidad['codigo'],

                    'grupo'
                        => $unidad['nombre_grupo'],

                    'concepto'
                        => $cargo['cargo_nombre'],

                    'tipo_calculo'
                        => 'Cuota de cargo',

                    'tarifa'
                        => null,

                    'base'
                        => null,

                    'valor'
                        => 0,

                    'estado'
                        => 'ERROR',

                    'mensaje'
                        => 'La cuota del cargo tiene un valor igual o menor que cero.',

                    'url_corregir'
                        => null

                ];


                $cantidadErrores++;

                continue;
            }


            $cantidadCuotas =
                (int)$cargo['cantidad_cuotas'];


            if ($cantidadCuotas <= 0) {

                $cantidadCuotas = 1;
            }


            $descripcionCargo =
                $cargo['cargo_nombre'] .
                ' - cuota ' .
                (int)$cargo['numero_cuota'] .
                '/' .
                $cantidadCuotas;


            $mensajeCargo =
                $facturaExistenteModificable
                    ? 'Se agregará a la factura existente ' .
                      (
                          !empty($facturaExistente['numero_factura'])
                              ? $facturaExistente['numero_factura']
                              : '#' . $facturaExistente['id_factura']
                      ) .
                      '.'
                    : 'Se incluirá en la nueva factura del período.';


            $totalCalculado +=
                $valorCargo;


            $cantidadDetalles++;

            $cantidadCargosFacturados++;

            $unidadTieneDetalleValido = true;


            $detallePreview[] = [

                'id_unidad'
                    => $idUnidad,

                'unidad'
                    => $unidad['codigo'],

                'grupo'
                    => $unidad['nombre_grupo'],

                'concepto'
                    => $descripcionCargo,

                'tipo_calculo'
                    => 'Cuota de cargo',

                'tarifa'
                    => null,

                'base'
                    => null,

                'valor'
                    => $valorCargo,

                'estado'
                    => 'OK',

                'mensaje'
                    => $mensajeCargo,

                'url_corregir'
                    => null

            ];
        }


        // ==================================================
        // ESPACIOS VIGENTES
        // ==================================================
        //
        // Solo se generan automáticamente cuando se va a
        // crear una factura nueva.
        //
        // Si ya existe factura, no repetimos cargos de
        // espacios que pudieron haber sido facturados antes.
        //
        // ==================================================

        if (!$facturaExistente) {

            $stmtEspaciosUnidad->execute([

                ':id_unidad'
                    => $idUnidad,

                ':fecha_facturacion_desde'
                    => $fechaFacturacion,

                ':fecha_facturacion_hasta'
                    => $fechaFacturacion

            ]);


            $espaciosUnidad =
                $stmtEspaciosUnidad->fetchAll(
                    PDO::FETCH_ASSOC
                );


            foreach ($espaciosUnidad as $espacio) {

                $tieneCargosEspacios = true;


                $idConceptoEspacio =
                    (int)$espacio['id_concepto'];


                $tipoCalculoEspacio =
                    $espacio['tipo_calculo'];


                $nombreConceptoEspacio =
                    $espacio['concepto'] .
                    ' - ' .
                    $espacio['codigo'];


                if (
                    empty(
                        $espacio['id_tipo_obligacion']
                    )
                ) {

                    $detallePreview[] = [

                        'id_unidad'
                            => $idUnidad,

                        'unidad'
                            => $unidad['codigo'],

                        'grupo'
                            => $unidad['nombre_grupo'],

                        'concepto'
                            => $nombreConceptoEspacio,

                        'tipo_calculo'
                            => nombreTipoCalculo(
                                $tipoCalculoEspacio
                            ),

                        'tarifa'
                            => null,

                        'base'
                            => null,

                        'valor'
                            => 0,

                        'estado'
                            => 'ERROR',

                        'mensaje'
                            => 'El concepto asociado al espacio no tiene tipo de obligación configurado.',

                        'url_corregir'
                            => BASE_URL .
                               'configuracion/conceptos_facturacion.php'
                    ];


                    $cantidadErrores++;

                    continue;
                }


                $stmtTarifa->execute([

                    ':id_concepto'
                        => $idConceptoEspacio,

                    ':id_tipo_config'
                        => $idTipoUnidad,

                    ':fecha_facturacion_inicio'
                        => $fechaFacturacion,

                    ':fecha_facturacion_fin'
                        => $fechaFacturacion

                ]);


                $tarifaEspacio =
                    $stmtTarifa->fetch(
                        PDO::FETCH_ASSOC
                    );


                if (!$tarifaEspacio) {

                    $detallePreview[] = [

                        'id_unidad'
                            => $idUnidad,

                        'unidad'
                            => $unidad['codigo'],

                        'grupo'
                            => $unidad['nombre_grupo'],

                        'concepto'
                            => $nombreConceptoEspacio,

                        'tipo_calculo'
                            => nombreTipoCalculo(
                                $tipoCalculoEspacio
                            ),

                        'tarifa'
                            => null,

                        'base'
                            => null,

                        'valor'
                            => 0,

                        'estado'
                            => 'ERROR',

                        'mensaje'
                            => 'No existe una tarifa vigente para este tipo de espacio y grupo de unidad.',

                        'url_corregir'
                            => BASE_URL .
                               'configuracion/tarifas.php' .
                               '?id_concepto=' .
                               $idConceptoEspacio .
                               '&id_tipo_config=' .
                               $idTipoUnidad
                    ];


                    $cantidadErrores++;

                    continue;
                }


                $valorTarifaEspacio =
                    (float)$tarifaEspacio['valor'];


                $areaEspacio =
                    (float)($espacio['area'] ?? 0);


                $baseCalculoEspacio = null;

                $valorCalculadoEspacio = 0;

                $estadoEspacio = 'OK';

                $mensajeEspacio =
                    'Espacio vigente en la fecha de facturación.';

                $urlCorregirEspacio = null;


                switch ($tipoCalculoEspacio) {


                    case 'FIJO':

                        $baseCalculoEspacio = 1;

                        $valorCalculadoEspacio =
                            $valorTarifaEspacio;

                        break;


                    case 'METRO_CUADRADO':

                        if ($areaEspacio <= 0) {

                            $estadoEspacio =
                                'ERROR';

                            $mensajeEspacio =
                                'El espacio no tiene un área válida configurada.';

                            break;
                        }


                        $baseCalculoEspacio =
                            $areaEspacio;

                        $valorCalculadoEspacio =
                            $areaEspacio *
                            $valorTarifaEspacio;

                        break;


                    case 'COEFICIENTE':

                        if ($coeficiente <= 0) {

                            $estadoEspacio =
                                'ERROR';

                            $mensajeEspacio =
                                'La unidad asociada al espacio no tiene un coeficiente válido.';

                            break;
                        }


                        $baseCalculoEspacio =
                            $coeficiente;

                        $valorCalculadoEspacio =
                            $coeficiente *
                            $valorTarifaEspacio;

                        break;


                    case 'PORCENTAJE':

                        $estadoEspacio =
                            'ERROR';

                        $mensajeEspacio =
                            'El concepto del espacio usa PORCENTAJE y todavía no tiene una base de cálculo definida.';

                        $urlCorregirEspacio =
                            BASE_URL .
                            'configuracion/conceptos_facturacion.php';

                        break;


                    default:

                        $estadoEspacio =
                            'ERROR';

                        $mensajeEspacio =
                            'El tipo de cálculo del espacio no es reconocido.';

                        break;
                }


                $valorCalculadoEspacio =
                    round(
                        $valorCalculadoEspacio,
                        2
                    );


                if (
                    $estadoEspacio === 'OK' &&
                    $valorCalculadoEspacio <= 0
                ) {

                    $estadoEspacio =
                        'ERROR';

                    $mensajeEspacio =
                        'El cálculo del espacio produjo un valor igual o menor que cero.';
                }


                if ($estadoEspacio === 'OK') {

                    $totalCalculado +=
                        $valorCalculadoEspacio;

                    $cantidadDetalles++;

                    $cantidadEspaciosFacturados++;

                    $unidadTieneDetalleValido = true;

                } else {

                    $cantidadErrores++;
                }


                $detallePreview[] = [

                    'id_unidad'
                        => $idUnidad,

                    'unidad'
                        => $unidad['codigo'],

                    'grupo'
                        => $unidad['nombre_grupo'],

                    'concepto'
                        => $nombreConceptoEspacio,

                    'tipo_calculo'
                        => nombreTipoCalculo(
                            $tipoCalculoEspacio
                        ),

                    'tarifa'
                        => $valorTarifaEspacio,

                    'base'
                        => $baseCalculoEspacio,

                    'valor'
                        => $valorCalculadoEspacio,

                    'estado'
                        => $estadoEspacio,

                    'mensaje'
                        => $mensajeEspacio,

                    'url_corregir'
                        => $urlCorregirEspacio
                ];
            }
        }


        // ==================================================
        // UNIDAD FACTURABLE / ACTUALIZABLE
        // ==================================================

        if ($unidadTieneDetalleValido) {

            $cantidadFacturables++;
        }
    }


} catch (Throwable $e) {

    error_log(
        "Error previsualizando facturación: " .
        $e->getMessage()
    );


    $erroresGenerales[] =
        "No fue posible preparar la vista previa de la facturación.";
}


// ==========================================================
// ¿SE PUEDE CONFIRMAR?
// ==========================================================

$puedeGenerar =
    empty($erroresGenerales) &&
    $cantidadErrores === 0 &&
    $cantidadFacturables > 0 &&
    isset($calendario['estado']) &&
    $calendario['estado'] !== 'CERRADO';

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

        <h2 align="center">
            Vista previa de facturación
        </h2>


        <br>


        <!-- ======================================================
             INFORMACIÓN DEL PERÍODO
        ======================================================= -->

        <?php if ($calendario): ?>


            <div class="bloque filtros">


                <div class="form-card">


                    <h3>
                        Período seleccionado
                    </h3>


                    <br>


                    <p>

                        <strong>Período:</strong>

                        <?= e(
                            date(
                                'm/Y',
                                strtotime(
                                    $calendario['periodo']
                                )
                            )
                        ) ?>

                    </p>


                    <p>

                        <strong>
                            Fecha de facturación:
                        </strong>

                        <?= e(
                            date(
                                'd/m/Y',
                                strtotime(
                                    $calendario['fecha_facturacion']
                                )
                            )
                        ) ?>

                    </p>


                    <p>

                        <strong>
                            Fecha de vencimiento:
                        </strong>

                        <?= e(
                            date(
                                'd/m/Y',
                                strtotime(
                                    $calendario['fecha_vencimiento']
                                )
                            )
                        ) ?>

                    </p>


                    <p>

                        <strong>
                            Estado:
                        </strong>

                        <?= e(
                            $calendario['estado']
                        ) ?>

                    </p>


                </div>


            </div>


            <br>


        <?php endif; ?>


        <!-- ======================================================
             ERRORES GENERALES
        ======================================================= -->

        <?php if (!empty($erroresGenerales)): ?>


            <div class="info-box">


                <strong>
                    No es posible continuar
                </strong>


                <?php foreach ($erroresGenerales as $error): ?>

                    <p class="inactivo">
                        <?= e($error) ?>
                    </p>

                <?php endforeach; ?>


            </div>


            <br>


        <?php endif; ?>


        <!-- ======================================================
             ADVERTENCIAS
        ======================================================= -->

        <?php if (!empty($advertencias)): ?>


            <div class="info-box">


                <strong>
                    Advertencias
                </strong>


                <?php foreach ($advertencias as $advertencia): ?>

                    <p>
                        <?= e($advertencia) ?>
                    </p>

                <?php endforeach; ?>


            </div>


            <br>


        <?php endif; ?>


        <!-- ======================================================
             RESUMEN
        ======================================================= -->

        <div class="bloque filtros">


            <div class="form-card">


                <h3>
                    Resumen
                </h3>


                <br>


                <table class="tabla">


                    <thead>

                        <tr>

                            <th>
                                Unidades revisadas
                            </th>

                            <th>
                                Unidades facturables / actualizables
                            </th>

                            <th>
                                Detalles nuevos
                            </th>

                            <th>
                                Cargos incluidos
                            </th>

                            <th>
                                Espacios incluidos
                            </th>

                            <th>
                                Espacios sueltos
                            </th>

                            <th>
                                Errores
                            </th>

                            <th>
                                Facturas existentes
                            </th>

                            <th>
                                Total nuevo a generar / agregar
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <tr>

                            <td>
                                <?= count($unidades) ?>
                            </td>

                            <td>
                                <?= $cantidadFacturables ?>
                            </td>

                            <td>
                                <?= $cantidadDetalles ?>
                            </td>

                            <td>
                                <?= $cantidadCargosFacturados ?>
                            </td>

                            <td>
                                <?= $cantidadEspaciosFacturados ?>
                            </td>

                            <td>

                                <?php if ($cantidadEspaciosSueltos > 0): ?>

                                    <span class="inactivo">
                                        <?= $cantidadEspaciosSueltos ?>
                                    </span>

                                <?php else: ?>

                                    0

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($cantidadErrores > 0): ?>

                                    <span class="inactivo">
                                        <?= $cantidadErrores ?>
                                    </span>

                                <?php else: ?>

                                    <span class="activo">
                                        0
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= $cantidadOmitidas ?>
                            </td>

                            <td>

                                <strong>

                                    $<?= number_format(
                                        $totalCalculado,
                                        2,
                                        ',',
                                        '.'
                                    ) ?>

                                </strong>

                            </td>

                        </tr>

                    </tbody>


                </table>


            </div>


        </div>


        <br>


        <!-- ======================================================
             DETALLE
        ======================================================= -->

        <div class="bloque filtros">


            <div class="form-card">


                <h3>
                    Detalle de la previsualización
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
                                    Concepto
                                </th>

                                <th>
                                    Tipo cálculo
                                </th>

                                <th>
                                    Tarifa
                                </th>

                                <th>
                                    Base
                                </th>

                                <th>
                                    Valor calculado
                                </th>

                                <th>
                                    Resultado
                                </th>

                            </tr>


                        </thead>


                        <tbody>


                        <?php if (empty($detallePreview)): ?>


                            <tr>

                                <td
                                    colspan="8"
                                    align="center"
                                >

                                    No existen datos para mostrar.

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach ($detallePreview as $fila): ?>


                                <tr>


                                    <td>

                                        <?= e(
                                            $fila['unidad']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $fila['grupo']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $fila['concepto']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $fila['tipo_calculo']
                                        ) ?>

                                    </td>


                                    <td>


                                        <?php if (
                                            $fila['tarifa'] !== null
                                        ): ?>

                                            $<?= number_format(
                                                $fila['tarifa'],
                                                2,
                                                ',',
                                                '.'
                                            ) ?>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>


                                    </td>


                                    <td>


                                        <?php if (
                                            $fila['base'] !== null
                                        ): ?>

                                            <?= number_format(
                                                $fila['base'],
                                                4,
                                                ',',
                                                '.'
                                            ) ?>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>


                                    </td>


                                    <td>


                                        <?php if (
                                            $fila['estado'] === 'OK'
                                        ): ?>

                                            <strong>

                                                $<?= number_format(
                                                    $fila['valor'],
                                                    2,
                                                    ',',
                                                    '.'
                                                ) ?>

                                            </strong>

                                        <?php else: ?>

                                            -

                                        <?php endif; ?>


                                    </td>


                                    <td>


                                        <?php if (
                                            $fila['estado'] === 'OK'
                                        ): ?>


                                            <span class="activo">
                                                Correcto
                                            </span>


                                        <?php elseif (
                                            $fila['estado'] === 'OMITIDA'
                                        ): ?>


                                            <span>
                                                Información
                                            </span>


                                        <?php else: ?>


                                            <span class="inactivo">
                                                Error
                                            </span>


                                        <?php endif; ?>


                                        <br>


                                        <small>

                                            <?= e(
                                                $fila['mensaje']
                                            ) ?>

                                        </small>


                                        <?php if (
                                            !empty(
                                                $fila['url_corregir']
                                            )
                                        ): ?>


                                            <br><br>


                                            <a
                                                href="<?= e(
                                                    $fila['url_corregir']
                                                ) ?>"
                                                class="btn-secondary"
                                            >

                                                <?php if (
                                                    strpos(
                                                        $fila['url_corregir'],
                                                        'tarifas.php'
                                                    ) !== false
                                                ): ?>

                                                    Configurar tarifa

                                                <?php else: ?>

                                                    Corregir configuración

                                                <?php endif; ?>

                                            </a>


                                        <?php endif; ?>


                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            </div>


        </div>


        <br>


        <!-- ======================================================
             ACCIONES
        ======================================================= -->

        <div class="form-actions">


            <a
                href="<?= BASE_URL ?>configuracion/factura.php"
                class="btn-limpiar"
            >

                ← Regresar

            </a>


            <?php if ($puedeGenerar): ?>


                <form
                    method="POST"
                    action="<?= BASE_URL ?>actions/generar_facturacion.php"
                >


                    <input
                        type="hidden"
                        name="id_calendario"
                        value="<?= (int)$idCalendario ?>"
                    >


                    <input
                        type="hidden"
                        name="id_tipo_config"
                        value="<?= (int)$idTipoConfig ?>"
                    >


                    <?php foreach (
                        $conceptosOpcionales as $idConcepto
                    ): ?>

                        <input
                            type="hidden"
                            name="conceptos[]"
                            value="<?= (int)$idConcepto ?>"
                        >

                    <?php endforeach; ?>


                    <input
                        type="hidden"
                        name="observaciones"
                        value="<?= e($observaciones) ?>"
                    >


                    <button
                        type="submit"
                        class="btn-filtrar"
                    >

                        Confirmar y generar / actualizar facturación

                    </button>


                </form>


            <?php else: ?>


                <button
                    type="button"
                    class="btn-filtrar"
                    disabled
                >

                    Corrija los errores antes de generar

                </button>


            <?php endif; ?>


        </div>


    </main>


</div>


</body>

</html>
