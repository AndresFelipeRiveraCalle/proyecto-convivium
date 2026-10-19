<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// SOLO POST
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: "
        . BASE_URL
        . "configuracion/cargos.php"
    );

    exit;
}


// ==========================================================
// FUNCIÓN PARA REDIRECCIONAR CON MENSAJE
// ==========================================================

function redireccionarCargo($tipo, $texto)
{
    $url =
        BASE_URL
        . "configuracion/cargos.php?"
        . http_build_query([
            'tipo' => $tipo,
            'texto' => $texto
        ]);

    header("Location: " . $url);

    exit;
}


// ==========================================================
// FUNCIÓN PARA SUMAR MESES A UN PERÍODO
// ==========================================================

function sumarMeses($fecha, $meses)
{
    $fechaObjeto = new DateTime($fecha);

    $fechaObjeto->modify(
        '+' . (int)$meses . ' month'
    );

    return $fechaObjeto->format('Y-m-01');
}


// ==========================================================
// DISTRIBUIR EN CUOTAS
// ==========================================================

function distribuirEnCuotas(
    $valor,
    $numeroCuotas
) {

    $centavos =
        (int)round(
            ((float)$valor) * 100
        );

    $base =
        intdiv(
            $centavos,
            $numeroCuotas
        );

    $sobrante =
        $centavos
        - ($base * $numeroCuotas);

    $resultado = [];

    for (
        $i = 0;
        $i < $numeroCuotas;
        $i++
    ) {

        $valorCuota = $base;

        if ($i < $sobrante) {

            $valorCuota++;
        }

        $resultado[] =
            $valorCuota / 100;
    }

    return $resultado;
}


// ==========================================================
// DISTRIBUCIÓN PROPORCIONAL EXACTA
// ==========================================================

function distribuirProporcional(
    array $unidades,
    $valorTotal
) {

    $totalCentavos =
        (int)round(
            ((float)$valorTotal) * 100
        );

    $pesoTotal = 0;

    foreach ($unidades as $unidad) {

        $pesoTotal +=
            (float)$unidad['peso'];
    }


    if ($pesoTotal <= 0) {

        throw new Exception(
            'No existe una base válida para distribuir el cargo.'
        );
    }


    $distribucion = [];

    $centavosAsignados = 0;


    foreach (
        $unidades as $indice => $unidad
    ) {

        $proporcion =
            (float)$unidad['peso']
            / $pesoTotal;


        $valorExactoCentavos =
            $totalCentavos
            * $proporcion;


        $centavosEnteros =
            (int)floor(
                $valorExactoCentavos
            );


        $fraccion =
            $valorExactoCentavos
            - $centavosEnteros;


        $distribucion[$indice] = [

            'id_unidad' =>
                (int)$unidad['id_unidad'],

            'centavos' =>
                $centavosEnteros,

            'fraccion' =>
                $fraccion
        ];


        $centavosAsignados +=
            $centavosEnteros;
    }


    $centavosRestantes =
        $totalCentavos
        - $centavosAsignados;


    $orden = array_keys(
        $distribucion
    );


    usort(
        $orden,
        function ($a, $b) use ($distribucion) {

            if (
                $distribucion[$a]['fraccion']
                ===
                $distribucion[$b]['fraccion']
            ) {

                return
                    $distribucion[$a]['id_unidad']
                    <=>
                    $distribucion[$b]['id_unidad'];
            }

            return
                $distribucion[$a]['fraccion']
                <
                $distribucion[$b]['fraccion']
                    ? 1
                    : -1;
        }
    );


    $cantidadOrden =
        count($orden);


    for (
        $i = 0;
        $i < $centavosRestantes;
        $i++
    ) {

        $indice =
            $orden[
                $i % $cantidadOrden
            ];

        $distribucion[$indice]['centavos']++;
    }


    $resultado = [];


    foreach ($distribucion as $fila) {

        $resultado[] = [

            'id_unidad' =>
                $fila['id_unidad'],

            'valor_asignado' =>
                $fila['centavos'] / 100
        ];
    }


    return $resultado;
}


// ==========================================================
// RECIBIR DATOS
// ==========================================================

$idConcepto =
    isset($_POST['id_concepto'])
        ? (int)$_POST['id_concepto']
        : 0;


$nombre =
    trim(
        $_POST['nombre'] ?? ''
    );


$descripcion =
    trim(
        $_POST['descripcion'] ?? ''
    );


$tipoAplicacion =
    trim(
        $_POST['tipo_aplicacion'] ?? ''
    );


$idTipoConfig =
    isset($_POST['id_tipo_config'])
        && $_POST['id_tipo_config'] !== ''
            ? (int)$_POST['id_tipo_config']
            : null;


$idUnidad =
    isset($_POST['id_unidad'])
        && $_POST['id_unidad'] !== ''
            ? (int)$_POST['id_unidad']
            : null;


$tipoDistribucion =
    trim(
        $_POST['tipo_distribucion'] ?? ''
    );


$valorTotal =
    isset($_POST['valor_total'])
        ? (float)$_POST['valor_total']
        : 0;


$numeroCuotas =
    isset($_POST['numero_cuotas'])
        ? (int)$_POST['numero_cuotas']
        : 0;


$periodoInicioFormulario =
    trim(
        $_POST['periodo_inicio'] ?? ''
    );


$estado =
    trim(
        $_POST['estado'] ?? 'BORRADOR'
    );


$observaciones =
    trim(
        $_POST['observaciones'] ?? ''
    );


// ==========================================================
// VALIDACIONES BÁSICAS
// ==========================================================

if ($idConcepto <= 0) {

    redireccionarCargo(
        'error',
        'Debe seleccionar un concepto.'
    );
}


if ($nombre === '') {

    redireccionarCargo(
        'error',
        'Debe ingresar el nombre del cargo.'
    );
}


if ($valorTotal <= 0) {

    redireccionarCargo(
        'error',
        'El valor total del cargo debe ser mayor que cero.'
    );
}


if (
    $numeroCuotas < 1
    || $numeroCuotas > 120
) {

    redireccionarCargo(
        'error',
        'El número de cuotas debe estar entre 1 y 120.'
    );
}


// ==========================================================
// VALIDAR TIPO DE APLICACIÓN
// ==========================================================

$tiposAplicacionPermitidos = [

    'TODAS_UNIDADES',
    'TIPO_UNIDAD',
    'UNIDAD'
];


if (
    !in_array(
        $tipoAplicacion,
        $tiposAplicacionPermitidos,
        true
    )
) {

    redireccionarCargo(
        'error',
        'El tipo de aplicación no es válido.'
    );
}


// ==========================================================
// VALIDAR TIPO DE DISTRIBUCIÓN
// ==========================================================

$tiposDistribucionPermitidos = [

    'VALOR_FIJO',
    'METRO_CUADRADO',
    'COEFICIENTE'
];


if (
    !in_array(
        $tipoDistribucion,
        $tiposDistribucionPermitidos,
        true
    )
) {

    redireccionarCargo(
        'error',
        'La forma de distribución no es válida.'
    );
}


// ==========================================================
// VALIDAR ESTADO
// ==========================================================

$estadosPermitidos = [

    'BORRADOR',
    'ACTIVO'
];


if (
    !in_array(
        $estado,
        $estadosPermitidos,
        true
    )
) {

    $estado = 'BORRADOR';
}


// ==========================================================
// VALIDAR PERÍODO
// ==========================================================

if (
    !preg_match(
        '/^\d{4}-\d{2}$/',
        $periodoInicioFormulario
    )
) {

    redireccionarCargo(
        'error',
        'El período inicial no es válido.'
    );
}


$periodoInicio =
    $periodoInicioFormulario
    . '-01';


$fechaValidar =
    DateTime::createFromFormat(
        'Y-m-d',
        $periodoInicio
    );


if (
    !$fechaValidar
    ||
    $fechaValidar->format('Y-m-d')
        !== $periodoInicio
) {

    redireccionarCargo(
        'error',
        'El período inicial no es válido.'
    );
}


// ==========================================================
// VALIDAR CAMPOS SEGÚN APLICACIÓN
// ==========================================================

if (
    $tipoAplicacion === 'TIPO_UNIDAD'
    && (
        $idTipoConfig === null
        || $idTipoConfig <= 0
    )
) {

    redireccionarCargo(
        'error',
        'Debe seleccionar un grupo de unidades.'
    );
}


if (
    $tipoAplicacion === 'UNIDAD'
    && (
        $idUnidad === null
        || $idUnidad <= 0
    )
) {

    redireccionarCargo(
        'error',
        'Debe seleccionar una unidad.'
    );
}


// ==========================================================
// LIMPIAR IDs QUE NO CORRESPONDEN
// ==========================================================

if (
    $tipoAplicacion
    === 'TODAS_UNIDADES'
) {

    $idTipoConfig = null;
    $idUnidad = null;
}


if (
    $tipoAplicacion
    === 'TIPO_UNIDAD'
) {

    $idUnidad = null;
}


if (
    $tipoAplicacion
    === 'UNIDAD'
) {

    $idTipoConfig = null;
}


// ==========================================================
// INICIAR TRANSACCIÓN
// ==========================================================

try {

    $conexion->beginTransaction();


    // ======================================================
    // VALIDAR CONCEPTO
    // ======================================================

    $sqlConcepto = "
        SELECT
            id_concepto,
            nombre,
            estado

        FROM conceptos_facturacion

        WHERE
            id_concepto = :id_concepto
            AND estado = 1

        LIMIT 1
    ";


    $stmtConcepto =
        $conexion->prepare(
            $sqlConcepto
        );


    $stmtConcepto->execute([
        ':id_concepto' =>
            $idConcepto
    ]);


    $concepto =
        $stmtConcepto->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$concepto) {

        throw new Exception(
            'El concepto seleccionado no existe o está inactivo.'
        );
    }


    if (
        in_array(
            $idConcepto,
            [1, 4],
            true
        )
    ) {

        throw new Exception(
            'Administración e intereses de mora se generan automáticamente y no pueden crearse como cargos.'
        );
    }


    // ======================================================
    // OBTENER UNIDADES PARTICIPANTES
    // ======================================================

    $sqlUnidades = "
        SELECT
            u.id_unidad,
            u.codigo,
            u.id_tipo_config,
            u.area,
            u.coeficiente

        FROM unidades u

        INNER JOIN detalle_tipos_unidad dtu
            ON dtu.id_tipo_config =
               u.id_tipo_config

        WHERE
            u.activo = 1
            AND dtu.activo = 1
    ";


    $parametrosUnidades = [];


    if (
        $tipoAplicacion
        === 'TIPO_UNIDAD'
    ) {

        $sqlUnidades .= "
            AND u.id_tipo_config =
                :id_tipo_config
        ";

        $parametrosUnidades[
            ':id_tipo_config'
        ] = $idTipoConfig;
    }


    if (
        $tipoAplicacion
        === 'UNIDAD'
    ) {

        $sqlUnidades .= "
            AND u.id_unidad =
                :id_unidad
        ";

        $parametrosUnidades[
            ':id_unidad'
        ] = $idUnidad;
    }


    $sqlUnidades .= "
        ORDER BY
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
            'No existen unidades activas para la aplicación seleccionada.'
        );
    }


    // ======================================================
    // PREPARAR PESOS PARA DISTRIBUCIÓN
    // ======================================================

    $unidadesDistribucion = [];


    foreach ($unidades as $unidad) {

        $peso = 0;


        switch ($tipoDistribucion) {


            case 'VALOR_FIJO':

                $peso = 1;

                break;


            case 'METRO_CUADRADO':

                $peso =
                    (float)$unidad['area'];

                if ($peso <= 0) {

                    throw new Exception(
                        'La unidad '
                        . $unidad['codigo']
                        . ' no tiene un área válida para distribuir el cargo.'
                    );
                }

                break;


            case 'COEFICIENTE':

                $peso =
                    (float)$unidad[
                        'coeficiente'
                    ];

                if ($peso <= 0) {

                    throw new Exception(
                        'La unidad '
                        . $unidad['codigo']
                        . ' no tiene un coeficiente válido para distribuir el cargo.'
                    );
                }

                break;
        }


        $unidadesDistribucion[] = [

            'id_unidad' =>
                (int)$unidad['id_unidad'],

            'peso' =>
                $peso
        ];
    }


    // ======================================================
    // CALCULAR VALOR POR UNIDAD
    // ======================================================

    $distribucion =
        distribuirProporcional(
            $unidadesDistribucion,
            $valorTotal
        );


    // ======================================================
    // GUARDAR CABECERA DEL CARGO
    // ======================================================

    $sqlCargo = "
        INSERT INTO cargos_facturacion
        (
            id_concepto,
            nombre,
            descripcion,
            tipo_aplicacion,
            id_tipo_config,
            id_unidad,
            tipo_distribucion,
            valor_total,
            numero_cuotas,
            periodo_inicio,
            estado,
            observaciones
        )
        VALUES
        (
            :id_concepto,
            :nombre,
            :descripcion,
            :tipo_aplicacion,
            :id_tipo_config,
            :id_unidad,
            :tipo_distribucion,
            :valor_total,
            :numero_cuotas,
            :periodo_inicio,
            :estado,
            :observaciones
        )
    ";


    $stmtCargo =
        $conexion->prepare(
            $sqlCargo
        );


    $stmtCargo->execute([

        ':id_concepto' =>
            $idConcepto,

        ':nombre' =>
            $nombre,

        ':descripcion' =>
            $descripcion !== ''
                ? $descripcion
                : null,

        ':tipo_aplicacion' =>
            $tipoAplicacion,

        ':id_tipo_config' =>
            $idTipoConfig,

        ':id_unidad' =>
            $idUnidad,

        ':tipo_distribucion' =>
            $tipoDistribucion,

        ':valor_total' =>
            number_format(
                $valorTotal,
                2,
                '.',
                ''
            ),

        ':numero_cuotas' =>
            $numeroCuotas,

        ':periodo_inicio' =>
            $periodoInicio,

        ':estado' =>
            $estado,

        ':observaciones' =>
            $observaciones !== ''
                ? $observaciones
                : null
    ]);


    $idCargo =
        (int)$conexion->lastInsertId();


    if ($idCargo <= 0) {

        throw new Exception(
            'No fue posible obtener el ID del cargo creado.'
        );
    }


    // ======================================================
    // PREPARAR INSERT CARGO - UNIDAD
    // ======================================================

    $sqlCargoUnidad = "
        INSERT INTO cargos_facturacion_unidades
        (
            id_cargo,
            id_unidad,
            valor_asignado,
            cantidad_cuotas,
            estado
        )
        VALUES
        (
            :id_cargo,
            :id_unidad,
            :valor_asignado,
            :cantidad_cuotas,
            'ACTIVO'
        )
    ";


    $stmtCargoUnidad =
        $conexion->prepare(
            $sqlCargoUnidad
        );


    // ======================================================
    // PREPARAR INSERT CUOTA
    // ======================================================

    $sqlCuota = "
        INSERT INTO cargos_facturacion_cuotas
        (
            id_cargo_unidad,
            numero_cuota,
            periodo,
            valor,
            estado
        )
        VALUES
        (
            :id_cargo_unidad,
            :numero_cuota,
            :periodo,
            :valor,
            'PENDIENTE'
        )
    ";


    $stmtCuota =
        $conexion->prepare(
            $sqlCuota
        );


    // ======================================================
    // GUARDAR UNIDADES Y CUOTAS
    // ======================================================

    $totalDistribuido = 0;

    $cantidadCuotasGeneradas = 0;


    foreach (
        $distribucion as $fila
    ) {

        $idUnidadAsignada =
            (int)$fila['id_unidad'];


        $valorAsignado =
            (float)$fila[
                'valor_asignado'
            ];


        $stmtCargoUnidad->execute([

            ':id_cargo' =>
                $idCargo,

            ':id_unidad' =>
                $idUnidadAsignada,

            ':valor_asignado' =>
                number_format(
                    $valorAsignado,
                    2,
                    '.',
                    ''
                ),

            ':cantidad_cuotas' =>
                $numeroCuotas
        ]);


        $idCargoUnidad =
            (int)$conexion->lastInsertId();


        $cuotas =
            distribuirEnCuotas(
                $valorAsignado,
                $numeroCuotas
            );


        foreach (
            $cuotas as $indice => $valorCuota
        ) {

            $numeroCuota =
                $indice + 1;


            $periodoCuota =
                sumarMeses(
                    $periodoInicio,
                    $indice
                );


            $stmtCuota->execute([

                ':id_cargo_unidad' =>
                    $idCargoUnidad,

                ':numero_cuota' =>
                    $numeroCuota,

                ':periodo' =>
                    $periodoCuota,

                ':valor' =>
                    number_format(
                        $valorCuota,
                        2,
                        '.',
                        ''
                    )
            ]);


            $cantidadCuotasGeneradas++;
        }


        $totalDistribuido +=
            $valorAsignado;
    }


    // ======================================================
    // VALIDACIÓN FINAL
    // ======================================================

    $totalDistribuido =
        round(
            $totalDistribuido,
            2
        );


    $valorTotalValidar =
        round(
            $valorTotal,
            2
        );


    if (
        abs(
            $totalDistribuido
            - $valorTotalValidar
        ) > 0.001
    ) {

        throw new Exception(
            'La distribución del cargo no coincide con el valor total.'
        );
    }


    // ======================================================
    // CONFIRMAR TRANSACCIÓN
    // ======================================================

    $conexion->commit();


    // ======================================================
    // REDIRECCIÓN
    // ======================================================

    $texto =
        'Cargo guardado correctamente. '
        . count($distribucion)
        . ' unidad(es) y '
        . $cantidadCuotasGeneradas
        . ' cuota(s) fueron generadas.';


    $url =
        BASE_URL
        . "configuracion/cargo_detalle.php"
        . "?id=" . urlencode($idCargo)
        . "&tipo=success"
        . "&texto=" . urlencode($texto);


    header(
        "Location: " . $url
    );

    exit;


// ==========================================================
// ERROR
// ==========================================================

} catch (Throwable $e) {

    if (
        $conexion->inTransaction()
    ) {

        $conexion->rollBack();
    }


    echo "<pre>";

    echo "ERROR AL GUARDAR EL CARGO:\n\n";

    echo "Mensaje:\n";
    echo $e->getMessage();

    echo "\n\nArchivo:\n";
    echo $e->getFile();

    echo "\n\nLínea:\n";
    echo $e->getLine();

    echo "\n\n";

    echo "</pre>";

    exit;
}