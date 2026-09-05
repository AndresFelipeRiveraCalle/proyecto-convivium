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

$detallePreview = [];

$erroresGenerales = [];

$advertencias = [];

$totalCalculado = 0;

$cantidadDetalles = 0;

$cantidadErrores = 0;

$cantidadOmitidas = 0;

$cantidadFacturables = 0;


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


    // ======================================================
    // VALIDAR ESTADO
    // ======================================================

    if ($estadoCalendario === 'CERRADO') {

        $advertencias[] =
            "El período financiero está cerrado. " .
            "La vista previa puede consultarse, " .
            "pero no se permitirá confirmar la facturación " .
            "mientras permanezca cerrado.";
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


    // ======================================================
    // UNIR OBLIGATORIOS + OPCIONALES
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

        $erroresGenerales[] =
            "No existen conceptos seleccionados para facturar.";
    }


    // ======================================================
    // BUSCAR CONCEPTOS
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
            estado

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


        // ==================================================
        // SI YA ESTÁ FACTURADA
        // ==================================================

        if ($facturaExistente) {

            $detallePreview[] = [

                'id_unidad'
                    => $idUnidad,

                'unidad'
                    => $unidad['codigo'],

                'grupo'
                    => $unidad['nombre_grupo'],

                'concepto'
                    => '-',

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
                    => 'La unidad ya tiene una factura para este período' .
                       (
                           !empty(
                               $facturaExistente['numero_factura']
                           )
                               ? ' (' .
                                 $facturaExistente['numero_factura'] .
                                 ')'
                               : ''
                       ) .
                       '.',

                'url_corregir'
                    => null
            ];


            $cantidadOmitidas++;

            continue;
        }


        $unidadTieneDetalleValido = false;


        // ==================================================
        // RECORRER CONCEPTOS
        // ==================================================

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
                        => 'No existe una tarifa vigente para este concepto y tipo de unidad.',

                    'url_corregir'
                        => BASE_URL .
                           'configuracion/tarifas.php' .
                           '?id_concepto=' .
                           $idConcepto .
                           '&id_tipo_config=' .
                           $idTipoUnidad
                ];


                $cantidadErrores++;

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


                // ------------------------------------------
                // FIJO
                // ------------------------------------------

                case 'FIJO':

                    $cantidad = 1;

                    $baseCalculo = 1;

                    $valorCalculado =
                        $valorTarifa;

                    break;


                // ------------------------------------------
                // METRO CUADRADO
                // ------------------------------------------

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


                // ------------------------------------------
                // COEFICIENTE
                // ------------------------------------------

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


                // ------------------------------------------
                // PORCENTAJE
                // ------------------------------------------

                case 'PORCENTAJE':

                    $estadoFila =
                        'ERROR';

                    $mensaje =
                        'El concepto usa PORCENTAJE, pero todavía no se ha definido la base sobre la cual debe aplicarse.';

                    $urlCorregir =
                        BASE_URL .
                        'configuracion/conceptos_facturacion.php';

                    break;


                // ------------------------------------------
                // DESCONOCIDO
                // ------------------------------------------

                default:

                    $estadoFila =
                        'ERROR';

                    $mensaje =
                        'El tipo de cálculo no es reconocido por el sistema.';

                    break;
            }


            // ==============================================
            // REDONDEAR
            // ==============================================

            $valorCalculado =
                round(
                    $valorCalculado,
                    2
                );


            // ==============================================
            // VALIDAR RESULTADO
            // ==============================================

            if (
                $estadoFila === 'OK' &&
                $valorCalculado <= 0
            ) {

                $estadoFila =
                    'ERROR';

                $mensaje =
                    'El cálculo produjo un valor igual o menor que cero.';
            }


            // ==============================================
            // REGISTRAR CONTADORES
            // ==============================================

            if ($estadoFila === 'OK') {

                $totalCalculado +=
                    $valorCalculado;

                $cantidadDetalles++;

                $unidadTieneDetalleValido = true;

            } else {

                $cantidadErrores++;
            }


            // ==============================================
            // AGREGAR A PREVISUALIZACIÓN
            // ==============================================

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
                                Unidades facturables
                            </th>

                            <th>
                                Detalles válidos
                            </th>

                            <th>
                                Errores
                            </th>

                            <th>
                                Ya facturadas
                            </th>

                            <th>
                                Total preliminar
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
                                                Omitida
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

                        Confirmar y generar facturación

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