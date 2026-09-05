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
// REDIRECCIÓN
// ==========================================================

function redireccionarFacturacion(
    $tipo,
    $mensaje
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/factura.php" .
        "?tipo=" .
        urlencode($tipo) .
        "&texto=" .
        urlencode($mensaje)
    );

    exit;
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


$observaciones = isset($_POST['observaciones'])
    ? trim($_POST['observaciones'])
    : null;


// ==========================================================
// LIMPIAR CONCEPTOS
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
// VALIDACIONES BÁSICAS
// ==========================================================

if ($idCalendario <= 0) {

    redireccionarFacturacion(
        "warning",
        "Debe seleccionar un período financiero."
    );
}


if (
    $observaciones !== null &&
    mb_strlen($observaciones) > 500
) {

    redireccionarFacturacion(
        "warning",
        "Las observaciones no pueden superar los 500 caracteres."
    );
}


// ==========================================================
// CONTADORES
// ==========================================================

$facturasGeneradas = 0;

$facturasOmitidas = 0;

$detallesGenerados = 0;

$totalGenerado = 0;


try {

    // ======================================================
    // INICIAR TRANSACCIÓN
    // ======================================================

    $conexion->beginTransaction();


    // ======================================================
    // CONSULTAR CALENDARIO
    // ======================================================

    $sqlCalendario = "
        SELECT
            id_calendario,
            periodo,
            fecha_facturacion,
            fecha_vencimiento,
            estado

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
    // VALIDAR PERÍODO CERRADO
    // ======================================================

    if ($calendario['estado'] === 'CERRADO') {

        throw new RuntimeException(
            "El período financiero está cerrado y no permite generar facturación."
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


    $anio = (int)date(
        'Y',
        strtotime($periodo)
    );


    $mes = (int)date(
        'm',
        strtotime($periodo)
    );


    // ======================================================
    // CONSULTAR UNIDADES
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

        throw new RuntimeException(
            "No existen unidades activas para los criterios seleccionados."
        );
    }


    // ======================================================
    // CONSULTAR CONCEPTOS OBLIGATORIOS
    // ======================================================

    $sqlObligatorios = "
        SELECT
            id_concepto

        FROM conceptos_facturacion

        WHERE estado = 1
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


    // ======================================================
    // COMBINAR OBLIGATORIOS + OPCIONALES
    // ======================================================

    $idsConceptos = array_values(
        array_unique(
            array_merge(
                $idsObligatorios,
                $conceptosOpcionales
            )
        )
    );


    if (empty($idsConceptos)) {

        throw new RuntimeException(
            "No existen conceptos para generar la facturación."
        );
    }


    // ======================================================
    // CONSULTAR CONCEPTOS
    // ======================================================

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
            id_concepto,
            nombre,
            descripcion,
            tipo_calculo,
            id_tipo_obligacion,
            obligatorio

        FROM conceptos_facturacion

        WHERE estado = 1

        AND id_concepto IN (
            $marcadores
        )

        ORDER BY
            obligatorio DESC,
            nombre
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


    if (empty($conceptos)) {

        throw new RuntimeException(
            "No fue posible encontrar los conceptos de facturación seleccionados."
        );
    }


    // ======================================================
    // PREPARAR CONSULTA TARIFA VIGENTE
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
    // CONSULTA FACTURA EXISTENTE
    // ======================================================

    $sqlFacturaExiste = "
        SELECT
            id_factura,
            numero_factura

        FROM facturas

        WHERE id_unidad =
            :id_unidad

        AND periodo =
            :periodo

        AND mes =
            :mes

        AND estado <> 'ANULADA'

        LIMIT 1
    ";


    $stmtFacturaExiste =
        $conexion->prepare(
            $sqlFacturaExiste
        );


    // ======================================================
    // INSERTAR FACTURA
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
            0,
            0,
            0,
            0,
            'GENERADA',
            :observaciones
        )
    ";


    $stmtInsertFactura =
        $conexion->prepare(
            $sqlInsertFactura
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
    // ACTUALIZAR TOTALES FACTURA
    // ======================================================

    $sqlActualizarFactura = "
        UPDATE facturas

        SET
            numero_factura =
                :numero_factura,

            subtotal =
                :subtotal,

            total =
                :total

        WHERE id_factura =
            :id_factura
    ";


    $stmtActualizarFactura =
        $conexion->prepare(
            $sqlActualizarFactura
        );


    // ======================================================
    // RECORRER UNIDADES
    // ======================================================

    foreach ($unidades as $unidad) {

        $idUnidad =
            (int)$unidad['id_unidad'];

        $idTipoUnidad =
            (int)$unidad['id_tipo_config'];

        $codigoUnidad =
            $unidad['codigo'];

        $area =
            (float)($unidad['area'] ?? 0);

        $coeficiente =
            (float)($unidad['coeficiente'] ?? 0);


        // ==================================================
        // VERIFICAR FACTURA EXISTENTE
        // ==================================================

        $stmtFacturaExiste->execute([

            ':id_unidad'
                => $idUnidad,

            ':periodo'
                => $anio,

            ':mes'
                => $mes

        ]);


        $facturaExistente =
            $stmtFacturaExiste->fetch(
                PDO::FETCH_ASSOC
            );


        // ==================================================
        // OMITIR SI YA EXISTE
        // ==================================================

        if ($facturaExistente) {

            $facturasOmitidas++;

            continue;
        }


        // ==================================================
        // CALCULAR TODOS LOS DETALLES ANTES DE INSERTAR
        // ==================================================

        $detallesUnidad = [];

        $subtotalFactura = 0;


        foreach ($conceptos as $concepto) {

            $idConcepto =
                (int)$concepto['id_concepto'];

            $tipoCalculo =
                $concepto['tipo_calculo'];


            // ==============================================
            // VALIDAR TIPO OBLIGACIÓN
            // ==============================================

            if (
                empty(
                    $concepto['id_tipo_obligacion']
                )
            ) {

                throw new RuntimeException(
                    "El concepto \"" .
                    $concepto['nombre'] .
                    "\" no tiene un tipo de obligación configurado."
                );
            }


            // ==============================================
            // BUSCAR TARIFA
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


            if (!$tarifa) {

                throw new RuntimeException(
                    "No existe una tarifa vigente para el concepto \"" .
                    $concepto['nombre'] .
                    "\" en la unidad " .
                    $codigoUnidad .
                    "."
                );
            }


            $idTarifa =
                (int)$tarifa['id_tarifa'];

            $valorTarifa =
                (float)$tarifa['valor'];


            // ==============================================
            // VARIABLES DE CÁLCULO
            // ==============================================

            $cantidad = 1;

            $baseCalculo = null;

            $valorUnitario =
                $valorTarifa;

            $subtotalDetalle = 0;


            // ==============================================
            // FIJO
            // ==============================================

            if ($tipoCalculo === 'FIJO') {

                $cantidad = 1;

                $baseCalculo = 1;

                $subtotalDetalle =
                    $valorTarifa;
            }


            // ==============================================
            // METRO CUADRADO
            // ==============================================

            elseif (
                $tipoCalculo ===
                'METRO_CUADRADO'
            ) {

                if ($area <= 0) {

                    throw new RuntimeException(
                        "La unidad " .
                        $codigoUnidad .
                        " no tiene un área válida para calcular el concepto \"" .
                        $concepto['nombre'] .
                        "\"."
                    );
                }


                $cantidad =
                    $area;

                $baseCalculo =
                    $area;

                $subtotalDetalle =
                    $area *
                    $valorTarifa;
            }


            // ==============================================
            // COEFICIENTE
            // ==============================================

            elseif (
                $tipoCalculo ===
                'COEFICIENTE'
            ) {

                if ($coeficiente <= 0) {

                    throw new RuntimeException(
                        "La unidad " .
                        $codigoUnidad .
                        " no tiene un coeficiente válido para calcular el concepto \"" .
                        $concepto['nombre'] .
                        "\"."
                    );
                }


                $cantidad =
                    $coeficiente;

                $baseCalculo =
                    $coeficiente;

                $subtotalDetalle =
                    $coeficiente *
                    $valorTarifa;
            }


            // ==============================================
            // PORCENTAJE
            // ==============================================

            elseif (
                $tipoCalculo ===
                'PORCENTAJE'
            ) {

                throw new RuntimeException(
                    "El concepto \"" .
                    $concepto['nombre'] .
                    "\" utiliza cálculo PORCENTAJE, pero todavía no se ha definido la base sobre la cual debe aplicarse."
                );
            }


            // ==============================================
            // TIPO DESCONOCIDO
            // ==============================================

            else {

                throw new RuntimeException(
                    "El concepto \"" .
                    $concepto['nombre'] .
                    "\" tiene un tipo de cálculo no reconocido."
                );
            }


            // ==============================================
            // REDONDEAR SUBTOTAL
            // ==============================================

            $subtotalDetalle =
                round(
                    $subtotalDetalle,
                    2
                );


            if ($subtotalDetalle <= 0) {

                throw new RuntimeException(
                    "El concepto \"" .
                    $concepto['nombre'] .
                    "\" produjo un valor inválido para la unidad " .
                    $codigoUnidad .
                    "."
                );
            }


            // ==============================================
            // DESCRIPCIÓN
            // ==============================================

            $descripcionDetalle =
                $concepto['nombre'];


            if (
                !empty(
                    $concepto['descripcion']
                )
            ) {

                $descripcionDetalle .=
                    ' - ' .
                    $concepto['descripcion'];
            }


            // ==============================================
            // LIMITAR LONGITUD
            // ==============================================

            $descripcionDetalle =
                mb_substr(
                    $descripcionDetalle,
                    0,
                    255
                );


            // ==============================================
            // GUARDAR TEMPORALMENTE
            // ==============================================

            $detallesUnidad[] = [

                'id_concepto'
                    => $idConcepto,

                'id_tarifa'
                    => $idTarifa,

                'descripcion'
                    => $descripcionDetalle,

                'cantidad'
                    => $cantidad,

                'valor_unitario'
                    => $valorUnitario,

                'subtotal'
                    => $subtotalDetalle,

                'tipo_calculo'
                    => $tipoCalculo,

                'base_calculo'
                    => $baseCalculo

            ];


            $subtotalFactura +=
                $subtotalDetalle;
        }


        // ==================================================
        // VALIDAR QUE HAYA DETALLES
        // ==================================================

        if (empty($detallesUnidad)) {

            throw new RuntimeException(
                "La unidad " .
                $codigoUnidad .
                " no produjo conceptos facturables."
            );
        }


        // ==================================================
        // REDONDEAR TOTAL
        // ==================================================

        $subtotalFactura =
            round(
                $subtotalFactura,
                2
            );


        // ==================================================
        // CREAR FACTURA
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

            ':observaciones'
                => $observaciones

        ]);


        // ==================================================
        // OBTENER ID FACTURA
        // ==================================================

        $idFactura =
            (int)$conexion->lastInsertId();


        if ($idFactura <= 0) {

            throw new RuntimeException(
                "No fue posible obtener el identificador de la factura generada."
            );
        }


        // ==================================================
        // GENERAR NÚMERO DE FACTURA
        // ==========================================================
        //
        // Ejemplo:
        //
        // FAC-2026-09-000001
        //
        // ==========================================================

        $numeroFactura =
            sprintf(
                'FAC-%04d-%02d-%06d',
                $anio,
                $mes,
                $idFactura
            );


        // ==================================================
        // INSERTAR DETALLES
        // ==================================================

        foreach ($detallesUnidad as $detalle) {

            $stmtInsertDetalle->execute([

                ':id_factura'
                    => $idFactura,

                ':id_concepto'
                    => $detalle['id_concepto'],

                ':id_tarifa'
                    => $detalle['id_tarifa'],

                ':descripcion'
                    => $detalle['descripcion'],

                ':cantidad'
                    => $detalle['cantidad'],

                ':valor_unitario'
                    => $detalle['valor_unitario'],

                ':subtotal'
                    => $detalle['subtotal'],

                ':tipo_calculo'
                    => $detalle['tipo_calculo'],

                ':base_calculo'
                    => $detalle['base_calculo']

            ]);


            $detallesGenerados++;
        }


        // ==================================================
        // ACTUALIZAR TOTALES Y NÚMERO
        // ==================================================

        $stmtActualizarFactura->execute([

            ':numero_factura'
                => $numeroFactura,

            ':subtotal'
                => $subtotalFactura,

            ':total'
                => $subtotalFactura,

            ':id_factura'
                => $idFactura

        ]);


        // ==================================================
        // CONTADORES
        // ==================================================

        $facturasGeneradas++;

        $totalGenerado +=
            $subtotalFactura;
    }


    // ======================================================
    // VALIDAR QUE REALMENTE SE GENERÓ ALGO
    // ======================================================

    if (
        $facturasGeneradas === 0 &&
        $facturasOmitidas > 0
    ) {

        $conexion->rollBack();


        redireccionarFacturacion(
            "warning",
            "Todas las unidades seleccionadas ya tenían una factura generada para este período."
        );
    }


    if ($facturasGeneradas === 0) {

        throw new RuntimeException(
            "No se generó ninguna factura."
        );
    }


    // ======================================================
    // CONFIRMAR TRANSACCIÓN
    // ======================================================

    $conexion->commit();


    // ======================================================
    // MENSAJE RESULTADO
    // ======================================================

    $mensaje =
        "Facturación generada correctamente. " .
        "Facturas creadas: " .
        $facturasGeneradas .
        ". Detalles creados: " .
        $detallesGenerados .
        ". Unidades omitidas por estar previamente facturadas: " .
        $facturasOmitidas .
        ". Total generado: $" .
        number_format(
            $totalGenerado,
            2,
            ',',
            '.'
        ) .
        ".";


    // ======================================================
    // REDIRECCIÓN AL LISTADO DE FACTURAS
    // ======================================================

    header(
        "Location: " .
        BASE_URL .
        "configuracion/facturacion.php" .
        "?tipo=success&texto=" .
        urlencode($mensaje)
    );

    exit;


} catch (Throwable $e) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    echo "<pre>";

    echo "ERROR:\n\n";

    echo htmlspecialchars(
        $e->getMessage()
    );

    echo "\n\nARCHIVO:\n";

    echo htmlspecialchars(
        $e->getFile()
    );

    echo "\n\nLÍNEA:\n";

    echo htmlspecialchars(
        $e->getLine()
    );

    echo "</pre>";

    exit;
}