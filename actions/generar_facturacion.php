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
// REDIRECCIONAR
// ==========================================================

function redireccionarFacturacion($tipo, $texto)
{
    header(
        "Location: " .
        BASE_URL .
        "configuracion/factura.php?" .
        http_build_query([
            'tipo'  => $tipo,
            'texto' => $texto
        ])
    );

    exit;
}


// ==========================================================
// DATOS RECIBIDOS
// ==========================================================

$idCalendario =
    isset($_POST['id_calendario'])
        ? (int)$_POST['id_calendario']
        : 0;


$idTipoConfig =
    isset($_POST['id_tipo_config'])
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


$observaciones =
    trim(
        $_POST['observaciones'] ?? ''
    );


// ==========================================================
// CONCEPTOS NO FACTURABLES DIRECTAMENTE
// ==========================================================
//
// Intereses de mora.
//
// Se manejarán posteriormente desde CARTERA.
//
// ==========================================================

$conceptosNoFacturablesDirectamente = [
    4
];


$conceptosOpcionales =
    array_values(
        array_unique(
            array_filter(
                array_diff(
                    $conceptosOpcionales,
                    $conceptosNoFacturablesDirectamente
                ),
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

    redireccionarFacturacion(
        'warning',
        'Debe seleccionar un período financiero.'
    );
}


// ==========================================================
// CONTADORES
// ==========================================================

$facturasGeneradas = 0;

$facturasActualizadas = 0;

$facturasOmitidas = 0;

$detallesGenerados = 0;

$carterasGeneradas = 0;

$conceptosSinTarifa = 0;

$cargosFacturados = 0;

$espaciosFacturados = 0;

$interesesGenerados = 0;

$valorInteresesGenerados = 0;


// ==========================================================
// INICIAR PROCESO
// ==========================================================

try {

    // ======================================================
    // INICIAR TRANSACCIÓN
    // ======================================================

    $conexion->beginTransaction();


    // ======================================================
    // BUSCAR CALENDARIO
    // ======================================================

    $sqlCalendario = "
        SELECT
            id_calendario,
            periodo,
            fecha_facturacion,
            fecha_vencimiento,
            estado

        FROM calendario_financiero

        WHERE id_calendario =
            :id_calendario

        LIMIT 1
    ";


    $stmtCalendario =
        $conexion->prepare(
            $sqlCalendario
        );


    $stmtCalendario->execute([

        ':id_calendario'
            => $idCalendario

    ]);


    $calendario =
        $stmtCalendario->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$calendario) {

        throw new Exception(
            'El período financiero seleccionado no existe.'
        );
    }


    // ======================================================
    // VALIDAR ESTADO DEL PERÍODO
    // ======================================================

    if (
        $calendario['estado']
        === 'CERRADO'
    ) {

        throw new Exception(
            'El período financiero está cerrado y no permite generar ni actualizar facturación.'
        );
    }


    // ======================================================
    // DATOS DEL PERÍODO
    // ======================================================

    $periodo =
        $calendario[
            'periodo'
        ];


    $fechaFacturacion =
        $calendario[
            'fecha_facturacion'
        ];


    $fechaVencimiento =
        $calendario[
            'fecha_vencimiento'
        ];


    $anio =
        (int)date(
            'Y',
            strtotime($periodo)
        );


    $mes =
        (int)date(
            'm',
            strtotime($periodo)
        );


    $periodoCargo =
        date(
            'Y-m-01',
            strtotime($periodo)
        );


    // ======================================================
    // CONFIGURACIÓN DE MORA VIGENTE
    // ======================================================

    $sqlConfiguracionMora = "
        SELECT
            cm.id_configuracion_mora,
            cm.nombre,
            cm.tasa,
            cm.id_tasa_interes,
            cm.id_concepto,

            cf.nombre AS concepto_nombre,
            cf.id_tipo_obligacion

        FROM configuracion_mora cm

        INNER JOIN conceptos_facturacion cf
            ON cf.id_concepto =
               cm.id_concepto

        LEFT JOIN tasas_interes ti
            ON ti.id_tasa_interes =
               cm.id_tasa_interes

        WHERE
            cm.estado = 1
            AND cm.tipo_tasa = 'PORCENTAJE'
            AND cm.periodicidad = 'MENSUAL'
            AND cm.fecha_inicio <= :fecha_mora_inicio
            AND (
                cm.fecha_fin IS NULL
                OR cm.fecha_fin >= :fecha_mora_fin
            )
            AND cf.estado = 1
            AND (
                cm.id_tasa_interes IS NULL
                OR ti.activo = 1
            )

        ORDER BY
            cm.fecha_inicio DESC,
            cm.id_configuracion_mora DESC

        LIMIT 1
    ";


    $stmtConfiguracionMora =
        $conexion->prepare(
            $sqlConfiguracionMora
        );


    $stmtConfiguracionMora->execute([

        ':fecha_mora_inicio'
            => $fechaFacturacion,

        ':fecha_mora_fin'
            => $fechaFacturacion

    ]);


    $configuracionMora =
        $stmtConfiguracionMora->fetch(
            PDO::FETCH_ASSOC
        );


    if ($configuracionMora) {

        if (
            empty(
                $configuracionMora[
                    'id_tipo_obligacion'
                ]
            )
        ) {

            throw new Exception(
                'El concepto de intereses de mora no tiene tipo de obligación configurado.'
            );
        }


        if (
            (float)$configuracionMora['tasa'] <= 0
        ) {

            throw new Exception(
                'La configuración de mora vigente tiene una tasa mensual igual o menor que cero.'
            );
        }
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

        WHERE
            u.activo = 1
            AND dtu.activo = 1
            
    ";


    $parametrosUnidades = [];


    if ($idTipoConfig > 0) {

        $sqlUnidades .= "
            AND u.id_tipo_config =
                :id_tipo_config
        ";


        $parametrosUnidades[
            ':id_tipo_config'
        ] = $idTipoConfig;
    }


    $sqlUnidades .= "
        ORDER BY
            dtu.nombre_grupo,
            u.codigo,
            u.id_unidad
    ";


    $stmtUnidades =
        $conexion->prepare(
            $sqlUnidades
        );


    $stmtUnidades->execute(
        $parametrosUnidades
    );


    $unidades =
        $stmtUnidades->fetchAll(
            PDO::FETCH_ASSOC
        );


    if (empty($unidades)) {

        throw new Exception(
            'No existen unidades activas para los criterios seleccionados.'
        );
    }


    // ======================================================
    // CONFIGURACIÓN DE CONCEPTOS DE ESPACIOS
    // ======================================================

    $sqlConfigEspacios = "
        SELECT
            cce.tipo_espacio,
            cce.id_concepto

        FROM configuracion_conceptos_espacio cce

        INNER JOIN conceptos_facturacion cf
            ON cf.id_concepto =
               cce.id_concepto

        WHERE
            cce.activo = 1
            AND cf.estado = 1
    ";


    $stmtConfigEspacios =
        $conexion->query(
            $sqlConfigEspacios
        );


    $configEspacios =
        $stmtConfigEspacios->fetchAll(
            PDO::FETCH_ASSOC
        );


    $idsConceptosEspacio = [];


    foreach (
        $configEspacios
        as $configEspacio
    ) {

        $idsConceptosEspacio[] =
            (int)$configEspacio[
                'id_concepto'
            ];
    }


    $idsConceptosEspacio =
        array_values(
            array_unique(
                $idsConceptosEspacio
            )
        );


    // ======================================================
    // BUSCAR CONCEPTOS OBLIGATORIOS
    // ======================================================

    $sqlObligatorios = "
        SELECT
            id_concepto

        FROM conceptos_facturacion

        WHERE
            estado = 1
            AND obligatorio = 1
    ";


    $stmtObligatorios =
        $conexion->query(
            $sqlObligatorios
        );


    $idsObligatorios =
        $stmtObligatorios->fetchAll(
            PDO::FETCH_COLUMN
        );


    $idsObligatorios =
        array_map(
            'intval',
            $idsObligatorios
        );


    $idsObligatorios =
        array_values(
            array_diff(
                $idsObligatorios,
                $conceptosNoFacturablesDirectamente
            )
        );


    // ======================================================
    // EXCLUIR CONCEPTOS DE ESPACIOS DE LOS GENERALES
    // ======================================================

    $conceptosOpcionalesGenerales =
        array_values(
            array_diff(
                $conceptosOpcionales,
                $idsConceptosEspacio
            )
        );


    // ======================================================
    // UNIR CONCEPTOS GENERALES
    // ======================================================

    $idsConceptos =
        array_values(
            array_unique(
                array_merge(
                    $idsObligatorios,
                    $conceptosOpcionalesGenerales
                )
            )
        );


    // ======================================================
    // CARGAR CONCEPTOS GENERALES
    // ======================================================

    $conceptos = [];


    if (!empty($idsConceptos)) {

        $marcadores =
            implode(
                ',',
                array_fill(
                    0,
                    count($idsConceptos),
                    '?'
                )
            );


        $sqlConceptos = "
            SELECT
                id_concepto,
                nombre,
                descripcion,
                tipo_calculo,
                obligatorio,
                id_tipo_obligacion

            FROM conceptos_facturacion

            WHERE
                estado = 1

                AND id_concepto
                    IN ($marcadores)

            ORDER BY
                obligatorio DESC,
                nombre
        ";


        $stmtConceptos =
            $conexion->prepare(
                $sqlConceptos
            );


        $stmtConceptos->execute(
            $idsConceptos
        );


        $conceptos =
            $stmtConceptos->fetchAll(
                PDO::FETCH_ASSOC
            );
    }


    // ======================================================
    // CONSULTA TARIFA
    // ======================================================

    $sqlTarifa = "
        SELECT
            id_tarifa,
            nombre,
            valor,
            fecha_inicio,
            fecha_fin

        FROM tarifas_facturacion

        WHERE
            id_concepto =
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


    $stmtTarifa =
        $conexion->prepare(
            $sqlTarifa
        );


    // ======================================================
    // CONSULTA FACTURA EXISTENTE
    // ======================================================
    //
    // FOR UPDATE protege la factura mientras se agregan
    // cargos nuevos dentro de esta transacción.
    //
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

        WHERE
            id_unidad =
                :id_unidad

            AND periodo =
                :anio

            AND mes =
                :mes

            AND estado <>
                'ANULADA'

        LIMIT 1

        FOR UPDATE
    ";


    $stmtFacturaExiste =
        $conexion->prepare(
            $sqlFacturaExiste
        );


    // ======================================================
    // CONSULTA CUOTAS DE CARGOS
    // ======================================================

    $sqlCargosUnidad = "
        SELECT
            cfc.id_cuota,
            cfc.id_cargo_unidad,
            cfc.numero_cuota,
            cfc.periodo,
            cfc.valor,
            cfc.estado,

            cfu.cantidad_cuotas,

            c.id_cargo,
            c.id_concepto,
            c.nombre AS cargo_nombre,
            c.descripcion AS cargo_descripcion,

            cf.nombre AS concepto_nombre,
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

        FOR UPDATE
    ";


    $stmtCargosUnidad =
        $conexion->prepare(
            $sqlCargosUnidad
        );


    // ======================================================
    // CONSULTA ESPACIOS VIGENTES
    // ======================================================

    $sqlEspacios = "
        SELECT
            eu.id_espacio_unidad,
            eu.tipo_espacio,
            eu.codigo,
            eu.area,

            cce.id_concepto,

            cf.nombre AS concepto,
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
            eu.id_unidad =
                :id_unidad

            AND eu.activo = 1

            AND eu.fecha_desde <=
                :fecha_facturacion_inicio

            AND (
                eu.fecha_hasta IS NULL
                OR eu.fecha_hasta >=
                   :fecha_facturacion_fin
            )

        ORDER BY
            eu.tipo_espacio,
            eu.codigo,
            eu.id_espacio_unidad
    ";


    $stmtEspacios =
        $conexion->prepare(
            $sqlEspacios
        );


    // ======================================================
    // CARTERA VENCIDA PARA MORA
    // ======================================================

    $stmtCarteraVencida = null;


    if ($configuracionMora) {

        $sqlCarteraVencida = "
            SELECT
                c.id_cartera,
                c.saldo,
                c.fecha_vencimiento,
                c.descripcion

            FROM cartera c

            LEFT JOIN facturas_detalle fd
                ON fd.id_detalle =
                   c.id_detalle

            WHERE
                c.id_unidad = :id_unidad
                AND c.estado = 'PENDIENTE'
                AND c.saldo > 0.009
                AND c.fecha_vencimiento < :fecha_facturacion_mora

                AND fd.id_interes IS NULL

                AND (
                    fd.id_concepto IS NULL
                    OR fd.id_concepto <> :id_concepto_mora
                )

                AND NOT EXISTS (
                    SELECT 1
                    FROM intereses_cartera ic
                    WHERE
                        ic.id_cartera = c.id_cartera
                        AND ic.periodo_interes = :periodo_interes
                )

            ORDER BY
                c.fecha_vencimiento,
                c.id_cartera

            FOR UPDATE
        ";


        $stmtCarteraVencida =
            $conexion->prepare(
                $sqlCarteraVencida
            );
    }


    // ======================================================
    // INSERTAR INTERÉS DE CARTERA
    // ======================================================

    $sqlInsertInteres = "
        INSERT INTO intereses_cartera
        (
            id_cartera,
            id_tasa_interes,
            periodo_interes,
            fecha_calculo,
            fecha_vencimiento,
            dias_mora,
            tasa_interes,
            valor_base,
            valor_interes,
            valor_pagado,
            saldo,
            estado,
            observaciones
        )
        VALUES
        (
            :id_cartera,
            :id_tasa_interes,
            :periodo_interes,
            :fecha_calculo,
            :fecha_vencimiento,
            :dias_mora,
            :tasa_interes,
            :valor_base,
            :valor_interes,
            0,
            :saldo,
            'PENDIENTE',
            :observaciones
        )
    ";


    $stmtInsertInteres =
        $conexion->prepare(
            $sqlInsertInteres
        );


    // ======================================================
    // INSERTAR FACTURA NUEVA
    // ======================================================

    $sqlInsertFactura = "
        INSERT INTO facturas
        (
            id_unidad,
            numero_factura,
            periodo,
            mes,
            fecha_generacion,
            fecha_vencimiento,
            subtotal,
            intereses,
            saldos_anteriores,
            total,
            estado,
            observaciones
        )
        VALUES
        (
            :id_unidad,
            NULL,
            :periodo,
            :mes,
            :fecha_generacion,
            :fecha_vencimiento,
            :subtotal,
            :intereses,
            0,
            :total,
            'GENERADA',
            :observaciones
        )
    ";


    $stmtInsertFactura =
        $conexion->prepare(
            $sqlInsertFactura
        );


    // ======================================================
    // ACTUALIZAR NÚMERO DE FACTURA
    // ======================================================

    $sqlNumeroFactura = "
        UPDATE facturas

        SET numero_factura =
            :numero_factura

        WHERE id_factura =
            :id_factura
    ";


    $stmtNumeroFactura =
        $conexion->prepare(
            $sqlNumeroFactura
        );


    // ======================================================
    // INSERTAR DETALLE
    // ======================================================

    $sqlInsertDetalle = "
        INSERT INTO facturas_detalle
        (
            id_factura,
            id_concepto,
            id_tarifa,
            id_interes,
            descripcion,
            cantidad,
            valor_unitario,
            subtotal,
            tipo_calculo,
            base_calculo
        )
        VALUES
        (
            :id_factura,
            :id_concepto,
            :id_tarifa,
            :id_interes,
            :descripcion,
            :cantidad,
            :valor_unitario,
            :subtotal,
            :tipo_calculo,
            :base_calculo
        )
    ";


    $stmtInsertDetalle =
        $conexion->prepare(
            $sqlInsertDetalle
        );


    // ======================================================
    // INSERTAR CARTERA DESDE DETALLE DE FACTURA
    // ======================================================

    $sqlInsertCartera = "
        INSERT INTO cartera
        (
            id_factura,
            id_detalle,
            id_unidad,
            id_tipo_obligacion,
            periodo,
            descripcion,
            valor_original,
            valor_pagado,
            saldo,
            fecha_vencimiento,
            estado,
            observaciones
        )
        VALUES
        (
            :id_factura,
            :id_detalle,
            :id_unidad,
            :id_tipo_obligacion,
            :periodo,
            :descripcion,
            :valor_original,
            0,
            :saldo,
            :fecha_vencimiento,
            'PENDIENTE',
            :observaciones
        )
    ";


    $stmtInsertCartera =
        $conexion->prepare(
            $sqlInsertCartera
        );


    // ======================================================
    // MARCAR CUOTA COMO FACTURADA
    // ======================================================

    $sqlActualizarCuota = "
        UPDATE cargos_facturacion_cuotas

        SET
            estado =
                'FACTURADA',

            fecha_facturacion =
                :fecha_facturacion,

            id_detalle =
                :id_detalle

        WHERE
            id_cuota =
                :id_cuota

            AND estado =
                'PENDIENTE'
    ";


    $stmtActualizarCuota =
        $conexion->prepare(
            $sqlActualizarCuota
        );


    // ======================================================
    // ACTUALIZAR TOTALES DE FACTURA EXISTENTE
    // ======================================================
    //
    // Solo incrementamos el valor del cargo nuevo.
    //
    // No recalculamos Administración ni espacios existentes.
    //
    // ======================================================

    $sqlIncrementarFactura = "
        UPDATE facturas

        SET
            subtotal =
                subtotal + :incremento_subtotal,

            intereses =
                intereses + :incremento_intereses,

            total =
                total + :incremento_total

        WHERE
            id_factura =
                :id_factura

            AND estado IN (
                'BORRADOR',
                'GENERADA'
            )
    ";


    $stmtIncrementarFactura =
        $conexion->prepare(
            $sqlIncrementarFactura
        );


    // ======================================================
    // RECORRER UNIDADES
    // ======================================================

    foreach ($unidades as $unidad) {


        $idUnidad =
            (int)$unidad[
                'id_unidad'
            ];


        $idTipoUnidad =
            (int)$unidad[
                'id_tipo_config'
            ];


        $area =
            (float)(
                $unidad['area']
                ?? 0
            );


        $coeficiente =
            (float)(
                $unidad['coeficiente']
                ?? 0
            );


        // ==================================================
        // BUSCAR FACTURA EXISTENTE
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


        // ==================================================
        // BUSCAR CARGOS PENDIENTES SIEMPRE
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


        // ==================================================
        // CALCULAR INTERESES DE MORA DEL MES
        // ==================================================

        $interesesUnidad = [];


        if (
            $configuracionMora
            && $stmtCarteraVencida
        ) {

            $stmtCarteraVencida->execute([

                ':id_unidad'
                    => $idUnidad,

                ':fecha_facturacion_mora'
                    => $fechaFacturacion,

                ':id_concepto_mora'
                    => (int)$configuracionMora[
                        'id_concepto'
                    ],

                ':periodo_interes'
                    => $periodoCargo

            ]);


            $carteraVencidaUnidad =
                $stmtCarteraVencida->fetchAll(
                    PDO::FETCH_ASSOC
                );


            foreach (
                $carteraVencidaUnidad
                as $deudaVencida
            ) {

                $valorBase =
                    round(
                        (float)$deudaVencida['saldo'],
                        2
                    );


                if ($valorBase <= 0) {
                    continue;
                }


                $valorInteres =
                    round(
                        $valorBase *
                        (
                            (float)$configuracionMora['tasa']
                            / 100
                        ),
                        2
                    );


                if ($valorInteres <= 0) {
                    continue;
                }


                $fechaDeuda =
                    new DateTime(
                        $deudaVencida[
                            'fecha_vencimiento'
                        ]
                    );


                $fechaCalculoMora =
                    new DateTime(
                        $fechaFacturacion
                    );


                $diasMora =
                    (int)$fechaDeuda
                        ->diff(
                            $fechaCalculoMora
                        )
                        ->days;


                $interesesUnidad[] = [

                    'origen'
                        => 'INTERES',

                    'id_cartera_origen'
                        => (int)$deudaVencida[
                            'id_cartera'
                        ],

                    'id_concepto'
                        => (int)$configuracionMora[
                            'id_concepto'
                        ],

                    'id_tarifa'
                        => null,

                    'id_tipo_obligacion'
                        => (int)$configuracionMora[
                            'id_tipo_obligacion'
                        ],

                    'id_tasa_interes'
                        => !empty(
                            $configuracionMora[
                                'id_tasa_interes'
                            ]
                        )
                            ? (int)$configuracionMora[
                                'id_tasa_interes'
                            ]
                            : null,

                    'descripcion'
                        => $configuracionMora[
                            'concepto_nombre'
                        ] .
                           ' - cartera #' .
                           (int)$deudaVencida[
                               'id_cartera'
                           ] .
                           ' - período ' .
                           date(
                               'm/Y',
                               strtotime(
                                   $periodoCargo
                               )
                           ),

                    'cantidad'
                        => 1,

                    'valor_unitario'
                        => $valorInteres,

                    'subtotal'
                        => $valorInteres,

                    'tipo_calculo'
                        => 'PORCENTAJE',

                    'base_calculo'
                        => $valorBase,

                    'tasa_interes'
                        => (float)$configuracionMora[
                            'tasa'
                        ],

                    'dias_mora'
                        => $diasMora,

                    'fecha_vencimiento_origen'
                        => $deudaVencida[
                            'fecha_vencimiento'
                        ],

                    'id_cuota'
                        => null

                ];
            }
        }


        // ==================================================
        // CASO A:
        // YA EXISTE FACTURA
        // ==================================================
        //
        // Solo agregamos CARGOS NUEVOS e INTERESES pendientes.
        //
        // NO volvemos a generar:
        //
        // - Administración
        // - conceptos generales
        // - espacios
        //
        // ==================================================

        if ($facturaExistente) {


            // ==============================================
            // VALIDAR ESTADO MODIFICABLE
            // ==============================================

            if (
                !in_array(
                    $facturaExistente['estado'],
                    ['BORRADOR', 'GENERADA'],
                    true
                )
            ) {

                $facturasOmitidas++;

                continue;
            }


            // ==============================================
            // SIN CARGOS NI INTERESES NUEVOS
            // ==============================================

            if (
                empty($cargosUnidad)
                && empty($interesesUnidad)
            ) {

                $facturasOmitidas++;

                continue;
            }


            $idFactura =
                (int)$facturaExistente[
                    'id_factura'
                ];


            $incrementoSubtotal = 0;

            $incrementoIntereses = 0;

            $cantidadCargosAgregados = 0;

            $cantidadInteresesAgregados = 0;


            // ==============================================
            // AGREGAR CARGOS A FACTURA EXISTENTE
            // ==============================================

            foreach (
                $cargosUnidad
                as $cargo
            ) {


                $valorCargo =
                    round(
                        (float)$cargo[
                            'valor'
                        ],
                        2
                    );


                if ($valorCargo <= 0) {

                    throw new Exception(
                        'La cuota ' .
                        $cargo['numero_cuota'] .
                        ' del cargo "' .
                        $cargo['cargo_nombre'] .
                        '" tiene un valor igual o menor que cero.'
                    );
                }


                $cantidadCuotas =
                    (int)$cargo[
                        'cantidad_cuotas'
                    ];


                if ($cantidadCuotas <= 0) {

                    $cantidadCuotas = 1;
                }


                $descripcionCargo =
                    $cargo[
                        'cargo_nombre'
                    ] .
                    ' - cuota ' .
                    (int)$cargo[
                        'numero_cuota'
                    ] .
                    '/' .
                    $cantidadCuotas;


                // ==========================================
                // INSERTAR DETALLE DEL CARGO
                // ==========================================

                $stmtInsertDetalle->execute([

                    ':id_factura'
                        => $idFactura,

                    ':id_concepto'
                        => (int)$cargo[
                            'id_concepto'
                        ],

                    ':id_tarifa'
                        => null,

                    ':id_interes'
                        => null,

                    ':descripcion'
                        => $descripcionCargo,

                    ':cantidad'
                        => 1,

                    ':valor_unitario'
                        => $valorCargo,

                    ':subtotal'
                        => $valorCargo,

                    ':tipo_calculo'
                        => 'FIJO',

                    ':base_calculo'
                        => 1

                ]);


                $idDetalle =
                    (int)$conexion->lastInsertId();


                if ($idDetalle <= 0) {

                    throw new Exception(
                        'No fue posible obtener el ID del detalle del cargo "' .
                        $cargo['cargo_nombre'] .
                        '".'
                    );
                }


                // ==========================================
                // CREAR CARTERA DEL CARGO NUEVO
                // ==========================================

                if (
                    empty(
                        $cargo[
                            'id_tipo_obligacion'
                        ]
                    )
                ) {

                    throw new Exception(
                        'El concepto del cargo "' .
                        $cargo['cargo_nombre'] .
                        '" no tiene tipo de obligación configurado.'
                    );
                }


                $stmtInsertCartera->execute([

                    ':id_factura'
                        => $idFactura,

                    ':id_detalle'
                        => $idDetalle,

                    ':id_unidad'
                        => $idUnidad,

                    ':id_tipo_obligacion'
                        => (int)$cargo[
                            'id_tipo_obligacion'
                        ],

                    ':periodo'
                        => $periodoCargo,

                    ':descripcion'
                        => $descripcionCargo,

                    ':valor_original'
                        => $valorCargo,

                    ':saldo'
                        => $valorCargo,

                    ':fecha_vencimiento'
                        => $fechaVencimiento,

                    ':observaciones'
                        => 'Generado desde factura ' .
                           (
                               !empty($facturaExistente['numero_factura'])
                                   ? $facturaExistente['numero_factura']
                                   : '#' . $idFactura
                           )

                ]);


                $carterasGeneradas++;


                // ==========================================
                // MARCAR CUOTA COMO FACTURADA
                // ==========================================

                $stmtActualizarCuota->execute([

                    ':fecha_facturacion'
                        => $fechaFacturacion,

                    ':id_detalle'
                        => $idDetalle,

                    ':id_cuota'
                        => (int)$cargo[
                            'id_cuota'
                        ]

                ]);


                if (
                    $stmtActualizarCuota->rowCount()
                    !== 1
                ) {

                    throw new Exception(
                        'No fue posible marcar como facturada la cuota ' .
                        $cargo['id_cuota'] .
                        '. La cuota pudo haber cambiado de estado.'
                    );
                }


                $incrementoSubtotal +=
                    $valorCargo;


                $cantidadCargosAgregados++;

                $cargosFacturados++;

                $detallesGenerados++;
            }


            // ==============================================
            // AGREGAR INTERESES A FACTURA EXISTENTE
            // ==============================================

            foreach (
                $interesesUnidad
                as $interes
            ) {

                $stmtInsertInteres->execute([

                    ':id_cartera'
                        => $interes[
                            'id_cartera_origen'
                        ],

                    ':id_tasa_interes'
                        => $interes[
                            'id_tasa_interes'
                        ],

                    ':periodo_interes'
                        => $periodoCargo,

                    ':fecha_calculo'
                        => $fechaFacturacion,

                    ':fecha_vencimiento'
                        => $interes[
                            'fecha_vencimiento_origen'
                        ],

                    ':dias_mora'
                        => $interes[
                            'dias_mora'
                        ],

                    ':tasa_interes'
                        => $interes[
                            'tasa_interes'
                        ],

                    ':valor_base'
                        => $interes[
                            'base_calculo'
                        ],

                    ':valor_interes'
                        => $interes[
                            'subtotal'
                        ],

                    ':saldo'
                        => $interes[
                            'subtotal'
                        ],

                    ':observaciones'
                        => 'Generado automáticamente durante la facturación del período ' .
                           date(
                               'm/Y',
                               strtotime(
                                   $periodoCargo
                               )
                           )

                ]);


                $idInteres =
                    (int)$conexion->lastInsertId();


                if ($idInteres <= 0) {

                    throw new Exception(
                        'No fue posible obtener el ID del interés generado.'
                    );
                }


                $stmtInsertDetalle->execute([

                    ':id_factura'
                        => $idFactura,

                    ':id_concepto'
                        => $interes[
                            'id_concepto'
                        ],

                    ':id_tarifa'
                        => null,

                    ':id_interes'
                        => $idInteres,

                    ':descripcion'
                        => $interes[
                            'descripcion'
                        ],

                    ':cantidad'
                        => 1,

                    ':valor_unitario'
                        => $interes[
                            'subtotal'
                        ],

                    ':subtotal'
                        => $interes[
                            'subtotal'
                        ],

                    ':tipo_calculo'
                        => 'PORCENTAJE',

                    ':base_calculo'
                        => $interes[
                            'base_calculo'
                        ]

                ]);


                $idDetalle =
                    (int)$conexion->lastInsertId();


                if ($idDetalle <= 0) {

                    throw new Exception(
                        'No fue posible obtener el ID del detalle de intereses.'
                    );
                }


                $stmtInsertCartera->execute([

                    ':id_factura'
                        => $idFactura,

                    ':id_detalle'
                        => $idDetalle,

                    ':id_unidad'
                        => $idUnidad,

                    ':id_tipo_obligacion'
                        => $interes[
                            'id_tipo_obligacion'
                        ],

                    ':periodo'
                        => $periodoCargo,

                    ':descripcion'
                        => $interes[
                            'descripcion'
                        ],

                    ':valor_original'
                        => $interes[
                            'subtotal'
                        ],

                    ':saldo'
                        => $interes[
                            'subtotal'
                        ],

                    ':fecha_vencimiento'
                        => $fechaVencimiento,

                    ':observaciones'
                        => 'Interés generado automáticamente desde cartera #' .
                           $interes[
                               'id_cartera_origen'
                           ]

                ]);


                $incrementoIntereses +=
                    (float)$interes[
                        'subtotal'
                    ];


                $cantidadInteresesAgregados++;
                $interesesGenerados++;
                $valorInteresesGenerados +=
                    (float)$interes['subtotal'];
                $carterasGeneradas++;
                $detallesGenerados++;
            }


            // ==============================================
            // ACTUALIZAR TOTAL DE LA FACTURA EXISTENTE
            // ==============================================

            if (
                $cantidadCargosAgregados > 0
                || $cantidadInteresesAgregados > 0
            ) {

                $incrementoSubtotal =
                    round(
                        $incrementoSubtotal,
                        2
                    );


                $incrementoIntereses =
                    round(
                        $incrementoIntereses,
                        2
                    );


                $incrementoTotal =
                    round(
                        $incrementoSubtotal +
                        $incrementoIntereses,
                        2
                    );


                $stmtIncrementarFactura->execute([

                    ':incremento_subtotal'
                        => $incrementoSubtotal,

                    ':incremento_intereses'
                        => $incrementoIntereses,

                    ':incremento_total'
                        => $incrementoTotal,

                    ':id_factura'
                        => $idFactura

                ]);


                if (
                    $stmtIncrementarFactura->rowCount()
                    !== 1
                ) {

                    throw new Exception(
                        'No fue posible actualizar el total de la factura ' .
                        (
                            !empty($facturaExistente['numero_factura'])
                                ? $facturaExistente['numero_factura']
                                : '#' . $idFactura
                        ) .
                        '.'
                    );
                }


                $facturasActualizadas++;
            }


            // ==============================================
            // YA TERMINAMOS ESTA UNIDAD
            // ==============================================

            continue;
        }


        // ==================================================
        // CASO B:
        // NO EXISTE FACTURA
        // ==================================================
        //
        // Crear factura completa con:
        //
        // 1. Conceptos generales
        // 2. Cargos pendientes
        // 3. Espacios
        // 4. Intereses de mora
        //
        // ==================================================

        $detallesUnidad = [];


        // ==================================================
        // 1. CONCEPTOS GENERALES
        // ==================================================

        foreach (
            $conceptos
            as $concepto
        ) {


            $idConcepto =
                (int)$concepto[
                    'id_concepto'
                ];


            $tipoCalculo =
                $concepto[
                    'tipo_calculo'
                ];


            // ==============================================
            // VALIDAR TIPO DE OBLIGACIÓN
            // ==============================================

            if (
                empty(
                    $concepto[
                        'id_tipo_obligacion'
                    ]
                )
            ) {

                throw new Exception(
                    'El concepto "' .
                    $concepto['nombre'] .
                    '" no tiene tipo de obligación configurado.'
                );
            }


            // ==============================================
            // BUSCAR TARIFA ACTIVA Y VIGENTE
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


            $tarifa =
                $stmtTarifa->fetch(
                    PDO::FETCH_ASSOC
                );


            // ==============================================
            // SIN TARIFA = OMITIR CONCEPTO
            // ==============================================

            if (!$tarifa) {

                $conceptosSinTarifa++;

                continue;
            }


            $idTarifa =
                (int)$tarifa[
                    'id_tarifa'
                ];


            $valorTarifa =
                (float)$tarifa[
                    'valor'
                ];


            $cantidad = 1;

            $baseCalculo = null;

            $valorCalculado = 0;


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

                        throw new Exception(
                            'La unidad ' .
                            $unidad['codigo'] .
                            ' no tiene un área válida.'
                        );
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

                    if (
                        $coeficiente <= 0
                    ) {

                        throw new Exception(
                            'La unidad ' .
                            $unidad['codigo'] .
                            ' no tiene un coeficiente válido.'
                        );
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

                    throw new Exception(
                        'El concepto "' .
                        $concepto['nombre'] .
                        '" usa PORCENTAJE y todavía no tiene una base de cálculo definida.'
                    );


                default:

                    throw new Exception(
                        'El concepto "' .
                        $concepto['nombre'] .
                        '" tiene un tipo de cálculo no reconocido.'
                    );
            }


            $valorCalculado =
                round(
                    $valorCalculado,
                    2
                );


            if ($valorCalculado <= 0) {

                throw new Exception(
                    'El concepto "' .
                    $concepto['nombre'] .
                    '" produjo un valor igual o menor que cero.'
                );
            }


            $detallesUnidad[] = [

                'origen'
                    => 'GENERAL',

                'id_concepto'
                    => $idConcepto,

                'id_tarifa'
                    => $idTarifa,

                'id_tipo_obligacion'
                    => (int)$concepto[
                        'id_tipo_obligacion'
                    ],

                'descripcion'
                    => $concepto[
                        'nombre'
                    ],

                'cantidad'
                    => $cantidad,

                'valor_unitario'
                    => $valorTarifa,

                'subtotal'
                    => $valorCalculado,

                'tipo_calculo'
                    => $tipoCalculo,

                'base_calculo'
                    => $baseCalculo,

                'id_cuota'
                    => null

            ];
        }


        // ==================================================
        // 2. CARGOS PENDIENTES
        // ==================================================

        foreach (
            $cargosUnidad
            as $cargo
        ) {


            $valorCargo =
                round(
                    (float)$cargo[
                        'valor'
                    ],
                    2
                );


            if ($valorCargo <= 0) {

                throw new Exception(
                    'La cuota ' .
                    $cargo['numero_cuota'] .
                    ' del cargo "' .
                    $cargo['cargo_nombre'] .
                    '" tiene un valor igual o menor que cero.'
                );
            }


            $cantidadCuotas =
                (int)$cargo[
                    'cantidad_cuotas'
                ];


            if ($cantidadCuotas <= 0) {

                $cantidadCuotas = 1;
            }


            $descripcionCargo =
                $cargo[
                    'cargo_nombre'
                ] .
                ' - cuota ' .
                (int)$cargo[
                    'numero_cuota'
                ] .
                '/' .
                $cantidadCuotas;


            $detallesUnidad[] = [

                'origen'
                    => 'CARGO',

                'id_concepto'
                    => (int)$cargo[
                        'id_concepto'
                    ],

                'id_tarifa'
                    => null,

                'id_tipo_obligacion'
                    => (int)$cargo[
                        'id_tipo_obligacion'
                    ],

                'descripcion'
                    => $descripcionCargo,

                'cantidad'
                    => 1,

                'valor_unitario'
                    => $valorCargo,

                'subtotal'
                    => $valorCargo,

                'tipo_calculo'
                    => 'FIJO',

                'base_calculo'
                    => 1,

                'id_cuota'
                    => (int)$cargo[
                        'id_cuota'
                    ]

            ];
        }


        // ==================================================
        // 3. ESPACIOS VIGENTES
        // ==================================================

        $stmtEspacios->execute([

            ':id_unidad'
                => $idUnidad,

            ':fecha_facturacion_inicio'
                => $fechaFacturacion,

            ':fecha_facturacion_fin'
                => $fechaFacturacion

        ]);


        $espacios =
            $stmtEspacios->fetchAll(
                PDO::FETCH_ASSOC
            );


        foreach (
            $espacios
            as $espacio
        ) {


            $idConceptoEspacio =
                (int)$espacio[
                    'id_concepto'
                ];


            $tipoCalculoEspacio =
                $espacio[
                    'tipo_calculo'
                ];


            if (
                empty(
                    $espacio[
                        'id_tipo_obligacion'
                    ]
                )
            ) {

                throw new Exception(
                    'El concepto asociado al espacio "' .
                    $espacio['codigo'] .
                    '" no tiene tipo de obligación configurado.'
                );
            }


            // ==============================================
            // BUSCAR TARIFA DEL ESPACIO
            // ==============================================

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

                throw new Exception(
                    'No existe una tarifa activa y vigente para el espacio "' .
                    $espacio['codigo'] .
                    '" de la unidad ' .
                    $unidad['codigo'] .
                    '.'
                );
            }


            $idTarifaEspacio =
                (int)$tarifaEspacio[
                    'id_tarifa'
                ];


            $valorTarifaEspacio =
                (float)$tarifaEspacio[
                    'valor'
                ];


            $areaEspacio =
                (float)(
                    $espacio[
                        'area'
                    ] ?? 0
                );


            $cantidadEspacio = 1;

            $baseEspacio = null;

            $valorEspacio = 0;


            // ==============================================
            // CALCULAR ESPACIO
            // ==============================================

            switch (
                $tipoCalculoEspacio
            ) {


                case 'FIJO':

                    $cantidadEspacio = 1;

                    $baseEspacio = 1;

                    $valorEspacio =
                        $valorTarifaEspacio;

                    break;


                case 'METRO_CUADRADO':

                    if (
                        $areaEspacio <= 0
                    ) {

                        throw new Exception(
                            'El espacio "' .
                            $espacio['codigo'] .
                            '" no tiene un área válida.'
                        );
                    }


                    $cantidadEspacio =
                        $areaEspacio;


                    $baseEspacio =
                        $areaEspacio;


                    $valorEspacio =
                        $areaEspacio *
                        $valorTarifaEspacio;

                    break;


                case 'COEFICIENTE':

                    if (
                        $coeficiente <= 0
                    ) {

                        throw new Exception(
                            'La unidad ' .
                            $unidad['codigo'] .
                            ' no tiene coeficiente válido para calcular el espacio "' .
                            $espacio['codigo'] .
                            '".'
                        );
                    }


                    $cantidadEspacio =
                        $coeficiente;


                    $baseEspacio =
                        $coeficiente;


                    $valorEspacio =
                        $coeficiente *
                        $valorTarifaEspacio;

                    break;


                case 'PORCENTAJE':

                    throw new Exception(
                        'El concepto del espacio "' .
                        $espacio['codigo'] .
                        '" usa PORCENTAJE y todavía no tiene una base de cálculo definida.'
                    );


                default:

                    throw new Exception(
                        'El espacio "' .
                        $espacio['codigo'] .
                        '" tiene un tipo de cálculo no reconocido.'
                    );
            }


            $valorEspacio =
                round(
                    $valorEspacio,
                    2
                );


            if (
                $valorEspacio <= 0
            ) {

                throw new Exception(
                    'El cálculo del espacio "' .
                    $espacio['codigo'] .
                    '" produjo un valor igual o menor que cero.'
                );
            }


            $detallesUnidad[] = [

                'origen'
                    => 'ESPACIO',

                'id_concepto'
                    => $idConceptoEspacio,

                'id_tarifa'
                    => $idTarifaEspacio,

                'id_tipo_obligacion'
                    => (int)$espacio[
                        'id_tipo_obligacion'
                    ],

                'descripcion'
                    => $espacio[
                        'concepto'
                    ] .
                    ' - ' .
                    $espacio[
                        'codigo'
                    ],

                'cantidad'
                    => $cantidadEspacio,

                'valor_unitario'
                    => $valorTarifaEspacio,

                'subtotal'
                    => $valorEspacio,

                'tipo_calculo'
                    => $tipoCalculoEspacio,

                'base_calculo'
                    => $baseEspacio,

                'id_cuota'
                    => null

            ];
        }


        // ==================================================
        // 4. INTERESES DE MORA
        // ==================================================

        foreach (
            $interesesUnidad
            as $interes
        ) {

            $detallesUnidad[] =
                $interes;
        }


        // ==================================================
        // SIN NADA PARA FACTURAR
        // ==================================================

        if (
            empty(
                $detallesUnidad
            )
        ) {

            $facturasOmitidas++;

            continue;
        }


        // ==================================================
        // CALCULAR SUBTOTAL
        // ==================================================

        $subtotalFactura = 0;

        $interesesFactura = 0;


        foreach (
            $detallesUnidad
            as $detalle
        ) {

            if (
                ($detalle['origen'] ?? '')
                === 'INTERES'
            ) {

                $interesesFactura +=
                    (float)$detalle[
                        'subtotal'
                    ];

            } else {

                $subtotalFactura +=
                    (float)$detalle[
                        'subtotal'
                    ];
            }
        }


        $subtotalFactura =
            round(
                $subtotalFactura,
                2
            );


        $interesesFactura =
            round(
                $interesesFactura,
                2
            );


        $totalFactura =
            round(
                $subtotalFactura +
                $interesesFactura,
                2
            );


        // ==================================================
        // INSERTAR FACTURA
        // ==================================================

        $stmtInsertFactura->execute([

            ':id_unidad'
                => $idUnidad,

            ':periodo'
                => $anio,

            ':mes'
                => $mes,

            ':fecha_generacion'
                => $fechaFacturacion,

            ':fecha_vencimiento'
                => $fechaVencimiento,

            ':subtotal'
                => $subtotalFactura,

            ':intereses'
                => $interesesFactura,

            ':total'
                => $totalFactura,

            ':observaciones'
                => $observaciones !== ''
                    ? $observaciones
                    : null

        ]);


        $idFactura =
            (int)$conexion->lastInsertId();


        if ($idFactura <= 0) {

            throw new Exception(
                'No fue posible obtener el ID de la factura de la unidad ' .
                $unidad['codigo'] .
                '.'
            );
        }


        // ==================================================
        // NÚMERO DE FACTURA
        // ==================================================

        $numeroFactura =
            'FAC-' .
            $anio .
            str_pad(
                (string)$mes,
                2,
                '0',
                STR_PAD_LEFT
            ) .
            '-' .
            str_pad(
                (string)$idFactura,
                6,
                '0',
                STR_PAD_LEFT
            );


        $stmtNumeroFactura->execute([

            ':numero_factura'
                => $numeroFactura,

            ':id_factura'
                => $idFactura

        ]);


        // ==================================================
        // INSERTAR DETALLES
        // ==================================================

        foreach (
            $detallesUnidad
            as $detalle
        ) {


            $idInteresDetalle = null;


            if (
                ($detalle['origen'] ?? '')
                === 'INTERES'
            ) {

                $stmtInsertInteres->execute([

                    ':id_cartera'
                        => $detalle[
                            'id_cartera_origen'
                        ],

                    ':id_tasa_interes'
                        => $detalle[
                            'id_tasa_interes'
                        ],

                    ':periodo_interes'
                        => $periodoCargo,

                    ':fecha_calculo'
                        => $fechaFacturacion,

                    ':fecha_vencimiento'
                        => $detalle[
                            'fecha_vencimiento_origen'
                        ],

                    ':dias_mora'
                        => $detalle[
                            'dias_mora'
                        ],

                    ':tasa_interes'
                        => $detalle[
                            'tasa_interes'
                        ],

                    ':valor_base'
                        => $detalle[
                            'base_calculo'
                        ],

                    ':valor_interes'
                        => $detalle[
                            'subtotal'
                        ],

                    ':saldo'
                        => $detalle[
                            'subtotal'
                        ],

                    ':observaciones'
                        => 'Generado automáticamente durante la facturación del período ' .
                           date(
                               'm/Y',
                               strtotime(
                                   $periodoCargo
                               )
                           )

                ]);


                $idInteresDetalle =
                    (int)$conexion->lastInsertId();


                if ($idInteresDetalle <= 0) {

                    throw new Exception(
                        'No fue posible obtener el ID del interés generado.'
                    );
                }
            }


            $stmtInsertDetalle->execute([

                ':id_factura'
                    => $idFactura,

                ':id_concepto'
                    => $detalle[
                        'id_concepto'
                    ],

                ':id_tarifa'
                    => $detalle[
                        'id_tarifa'
                    ],

                ':id_interes'
                    => $idInteresDetalle,

                ':descripcion'
                    => $detalle[
                        'descripcion'
                    ],

                ':cantidad'
                    => $detalle[
                        'cantidad'
                    ],

                ':valor_unitario'
                    => $detalle[
                        'valor_unitario'
                    ],

                ':subtotal'
                    => $detalle[
                        'subtotal'
                    ],

                ':tipo_calculo'
                    => $detalle[
                        'tipo_calculo'
                    ],

                ':base_calculo'
                    => $detalle[
                        'base_calculo'
                    ]

            ]);


            $idDetalle =
                (int)$conexion->lastInsertId();


            if ($idDetalle <= 0) {

                throw new Exception(
                    'No fue posible obtener el ID del detalle de factura.'
                );
            }


            // ==============================================
            // CREAR CARTERA DEL DETALLE
            // ==============================================

            if (
                empty(
                    $detalle[
                        'id_tipo_obligacion'
                    ]
                )
            ) {

                throw new Exception(
                    'El detalle "' .
                    $detalle['descripcion'] .
                    '" no tiene tipo de obligación configurado.'
                );
            }


            $stmtInsertCartera->execute([

                ':id_factura'
                    => $idFactura,

                ':id_detalle'
                    => $idDetalle,

                ':id_unidad'
                    => $idUnidad,

                ':id_tipo_obligacion'
                    => (int)$detalle[
                        'id_tipo_obligacion'
                    ],

                ':periodo'
                    => $periodoCargo,

                ':descripcion'
                    => $detalle[
                        'descripcion'
                    ],

                ':valor_original'
                    => $detalle[
                        'subtotal'
                    ],

                ':saldo'
                    => $detalle[
                        'subtotal'
                    ],

                ':fecha_vencimiento'
                    => $fechaVencimiento,

                ':observaciones'
                    => 'Generado desde factura ' .
                       $numeroFactura

            ]);


            $carterasGeneradas++;

            $detallesGenerados++;


            if (
                ($detalle['origen'] ?? '')
                === 'INTERES'
            ) {

                $interesesGenerados++;

                $valorInteresesGenerados +=
                    (float)$detalle[
                        'subtotal'
                    ];
            }


            // ==============================================
            // SI ES CARGO, CERRAR CUOTA
            // ==============================================

            if (
                $detalle[
                    'origen'
                ] === 'CARGO'
            ) {


                $stmtActualizarCuota->execute([

                    ':fecha_facturacion'
                        => $fechaFacturacion,

                    ':id_detalle'
                        => $idDetalle,

                    ':id_cuota'
                        => (int)$detalle[
                            'id_cuota'
                        ]

                ]);


                if (
                    $stmtActualizarCuota->rowCount()
                    !== 1
                ) {

                    throw new Exception(
                        'No fue posible marcar como facturada la cuota ' .
                        $detalle['id_cuota'] .
                        '. La cuota pudo haber cambiado de estado.'
                    );
                }


                $cargosFacturados++;
            }


            // ==============================================
            // CONTADOR ESPACIOS
            // ==============================================

            if (
                $detalle[
                    'origen'
                ] === 'ESPACIO'
            ) {

                $espaciosFacturados++;
            }
        }


        $facturasGeneradas++;
    }


    // ======================================================
    // CONFIRMAR TRANSACCIÓN
    // ======================================================

    $conexion->commit();


    // ======================================================
    // MENSAJE FINAL
    // ======================================================

    $mensaje =
        'Proceso de facturación completado correctamente. ' .
        'Facturas nuevas: ' .
        $facturasGeneradas .
        '. Facturas existentes actualizadas: ' .
        $facturasActualizadas .
        '. Facturas/unidades sin cambios: ' .
        $facturasOmitidas .
        '. Detalles nuevos: ' .
        $detallesGenerados .
        '. Registros de cartera generados: ' .
        $carterasGeneradas .
        '. Cargos facturados: ' .
        $cargosFacturados .
        '. Espacios facturados: ' .
        $espaciosFacturados .
        '. Intereses de mora generados: ' .
        $interesesGenerados .
        ' por $' .
        number_format(
            $valorInteresesGenerados,
            2,
            ',',
            '.'
        ) .
        '. Conceptos sin tarifa activa/vigente omitidos: ' .
        $conceptosSinTarifa .
        '.';


    redireccionarFacturacion(
        'success',
        $mensaje
    );


// ==========================================================
// ERROR
// ==========================================================

} catch (Throwable $e) {


    if (
        $conexion->inTransaction()
    ) {

        $conexion->rollBack();
    }


    echo "<div style='
        font-family: Arial, sans-serif;
        max-width: 900px;
        margin: 40px auto;
        padding: 25px;
        border: 1px solid #ddd;
        border-radius: 10px;
        background: #fff;
    '>";


    echo "
        <h2 style='color:#b91c1c;'>
            Error al generar facturación
        </h2>
    ";


    echo "
        <p>
            <strong>Mensaje:</strong>
        </p>
    ";


    echo "<pre style='
        background:#f5f5f5;
        padding:15px;
        overflow:auto;
        border-radius:6px;
    '>";

    echo htmlspecialchars(
        $e->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );

    echo "</pre>";


    echo "
        <p>
            <strong>Archivo:</strong><br>
    ";


    echo htmlspecialchars(
        $e->getFile(),
        ENT_QUOTES,
        'UTF-8'
    );


    echo "
        </p>
    ";


    echo "
        <p>
            <strong>Línea:</strong><br>
    ";


    echo (int)$e->getLine();


    echo "
        </p>
    ";


    echo "<hr>";


    echo "
        <a href='javascript:history.back()'>
            ← Regresar
        </a>
    ";


    echo "</div>";

    exit;
}
