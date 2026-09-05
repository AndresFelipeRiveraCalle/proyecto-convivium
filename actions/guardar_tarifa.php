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
        "configuracion/tarifas.php"
    );

    exit;
}


// ==========================================================
// REDIRECCIÓN
// ==========================================================

function redireccionarTarifas(
    $tipo,
    $mensaje
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/tarifas.php" .
        "?tipo=" .
        urlencode($tipo) .
        "&texto=" .
        urlencode($mensaje)
    );

    exit;
}


// ==========================================================
// VALIDAR FECHA
// ==========================================================

function fechaValida($fecha)
{
    if ($fecha === null || $fecha === '') {
        return true;
    }

    $obj = DateTime::createFromFormat(
        'Y-m-d',
        $fecha
    );

    return $obj &&
        $obj->format('Y-m-d') === $fecha;
}


// ==========================================================
// DATOS RECIBIDOS
// ==========================================================

$idConcepto = isset($_POST['id_concepto'])
    ? (int)$_POST['id_concepto']
    : 0;


$tipoConfigRecibido =
    isset($_POST['id_tipo_config'])
        ? trim((string)$_POST['id_tipo_config'])
        : '';


$aplicarATodas =
    strtoupper($tipoConfigRecibido) === 'TODAS';


$idTipoConfig =
    $aplicarATodas
        ? 0
        : (int)$tipoConfigRecibido;


$nombre = isset($_POST['nombre'])
    ? trim($_POST['nombre'])
    : '';


$valor = isset($_POST['valor'])
    ? trim($_POST['valor'])
    : '';


$fechaInicio = isset($_POST['fecha_inicio'])
    ? trim($_POST['fecha_inicio'])
    : '';


$fechaFin = isset($_POST['fecha_fin'])
    ? trim($_POST['fecha_fin'])
    : '';


$estado = isset($_POST['estado'])
    ? (int)$_POST['estado']
    : 1;


$observaciones = isset($_POST['observaciones'])
    ? trim($_POST['observaciones'])
    : '';


// ==========================================================
// NORMALIZAR OPCIONALES
// ==========================================================

$nombre =
    $nombre !== ''
        ? $nombre
        : null;


$fechaFin =
    $fechaFin !== ''
        ? $fechaFin
        : null;


$observaciones =
    $observaciones !== ''
        ? $observaciones
        : null;


// ==========================================================
// VALIDACIONES BÁSICAS
// ==========================================================

if ($idConcepto <= 0) {

    redireccionarTarifas(
        'warning',
        'Debe seleccionar un concepto.'
    );
}


if (
    !$aplicarATodas &&
    $idTipoConfig <= 0
) {

    redireccionarTarifas(
        'warning',
        'Debe seleccionar un tipo de unidad.'
    );
}


if (
    $nombre !== null &&
    mb_strlen($nombre) > 150
) {

    redireccionarTarifas(
        'warning',
        'El nombre de la tarifa no puede superar los 150 caracteres.'
    );
}


if (
    $valor === '' ||
    !is_numeric($valor)
) {

    redireccionarTarifas(
        'warning',
        'Debe ingresar un valor válido.'
    );
}


$valor = (float)$valor;


if ($valor < 0) {

    redireccionarTarifas(
        'warning',
        'El valor de la tarifa no puede ser negativo.'
    );
}


if (
    $fechaInicio === '' ||
    !fechaValida($fechaInicio)
) {

    redireccionarTarifas(
        'warning',
        'Debe ingresar una fecha de inicio válida.'
    );
}


if (
    $fechaFin !== null &&
    !fechaValida($fechaFin)
) {

    redireccionarTarifas(
        'warning',
        'La fecha de finalización no es válida.'
    );
}


if (
    $fechaFin !== null &&
    $fechaFin < $fechaInicio
) {

    redireccionarTarifas(
        'warning',
        'La fecha de finalización no puede ser anterior a la fecha de inicio.'
    );
}


if (!in_array($estado, [0, 1], true)) {

    redireccionarTarifas(
        'warning',
        'El estado seleccionado no es válido.'
    );
}


if (
    $observaciones !== null &&
    mb_strlen($observaciones) > 255
) {

    redireccionarTarifas(
        'warning',
        'Las observaciones no pueden superar los 255 caracteres.'
    );
}


try {

    // ======================================================
    // INICIAR TRANSACCIÓN
    // ======================================================

    $conexion->beginTransaction();


    // ======================================================
    // VALIDAR CONCEPTO
    // ======================================================

    $sqlConcepto = "
        SELECT
            id_concepto,
            nombre,
            tipo_calculo

        FROM conceptos_facturacion

        WHERE id_concepto = :id_concepto
        AND estado = 1

        LIMIT 1
    ";


    $stmtConcepto = $conexion->prepare(
        $sqlConcepto
    );


    $stmtConcepto->execute([

        ':id_concepto'
            => $idConcepto

    ]);


    $concepto = $stmtConcepto->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$concepto) {

        throw new RuntimeException(
            'El concepto seleccionado no existe o se encuentra inactivo.'
        );
    }


    // ======================================================
    // DETERMINAR TIPOS DE UNIDAD DESTINO
    // ======================================================

    $tiposDestino = [];


    if ($aplicarATodas) {

        $sqlTipos = "
            SELECT
                id_tipo_config,
                nombre_grupo

            FROM detalle_tipos_unidad

            WHERE activo = 1

            ORDER BY id_tipo_config
        ";


        $stmtTipos = $conexion->query(
            $sqlTipos
        );


        $tiposDestino = $stmtTipos->fetchAll(
            PDO::FETCH_ASSOC
        );


        if (empty($tiposDestino)) {

            throw new RuntimeException(
                'No existen tipos de unidad activos para asignar la tarifa.'
            );
        }

    } else {

        // ==================================================
        // VALIDAR TIPO DE UNIDAD INDIVIDUAL
        // ==================================================

        $sqlTipo = "
            SELECT
                id_tipo_config,
                nombre_grupo

            FROM detalle_tipos_unidad

            WHERE id_tipo_config =
                :id_tipo_config

            AND activo = 1

            LIMIT 1
        ";


        $stmtTipo = $conexion->prepare(
            $sqlTipo
        );


        $stmtTipo->execute([

            ':id_tipo_config'
                => $idTipoConfig

        ]);


        $tipoUnidad = $stmtTipo->fetch(
            PDO::FETCH_ASSOC
        );


        if (!$tipoUnidad) {

            throw new RuntimeException(
                'El tipo de unidad seleccionado no existe o se encuentra inactivo.'
            );
        }


        $tiposDestino[] =
            $tipoUnidad;
    }


    // ======================================================
    // PREPARAR CONSULTA DE SOLAPAMIENTO
    // ======================================================
    //
    // Usamos parámetros diferentes para evitar HY093.
    // ======================================================

    $sqlSolape = "
        SELECT
            id_tarifa,
            fecha_inicio,
            fecha_fin

        FROM tarifas_facturacion

        WHERE id_concepto =
            :id_concepto

        AND id_tipo_config =
            :id_tipo_config

        AND estado = 1

        AND fecha_inicio <=
            :fecha_fin_comparacion

        AND (
            fecha_fin IS NULL
            OR fecha_fin >=
               :fecha_inicio_comparacion
        )

        LIMIT 1
    ";


    $stmtSolape = $conexion->prepare(
        $sqlSolape
    );


    // ======================================================
    // PREPARAR INSERT
    // ======================================================

    $sqlInsert = "
        INSERT INTO tarifas_facturacion
        (
            id_concepto,
            id_tipo_config,
            nombre,
            valor,
            fecha_inicio,
            fecha_fin,
            estado,
            observaciones
        )
        VALUES
        (
            :id_concepto,
            :id_tipo_config,
            :nombre,
            :valor,
            :fecha_inicio,
            :fecha_fin,
            :estado,
            :observaciones
        )
    ";


    $stmtInsert = $conexion->prepare(
        $sqlInsert
    );


    // ======================================================
    // FECHA FINAL PARA COMPARACIÓN
    // ======================================================

    $fechaFinComparacion =
        $fechaFin !== null
            ? $fechaFin
            : '9999-12-31';


    // ======================================================
    // PRIMER RECORRIDO:
    // VALIDAR TODAS ANTES DE INSERTAR
    // ======================================================

    foreach ($tiposDestino as $tipoDestino) {

        $idTipoDestino =
            (int)$tipoDestino['id_tipo_config'];


        // ==================================================
        // SOLO VALIDAR SOLAPE SI LA NUEVA QUEDA ACTIVA
        // ==================================================

        if ($estado === 1) {

            $stmtSolape->execute([

                ':id_concepto'
                    => $idConcepto,

                ':id_tipo_config'
                    => $idTipoDestino,

                ':fecha_fin_comparacion'
                    => $fechaFinComparacion,

                ':fecha_inicio_comparacion'
                    => $fechaInicio

            ]);


            $solape = $stmtSolape->fetch(
                PDO::FETCH_ASSOC
            );


            if ($solape) {

                $nombreGrupo =
                    $tipoDestino['nombre_grupo']
                    ?? 'tipo de unidad';


                $vigencia =
                    $solape['fecha_inicio'];


                if (!empty($solape['fecha_fin'])) {

                    $vigencia .=
                        ' hasta ' .
                        $solape['fecha_fin'];

                } else {

                    $vigencia .=
                        ' en adelante';
                }


                throw new RuntimeException(
                    'No se puede crear la tarifa. ' .
                    'El grupo "' .
                    $nombreGrupo .
                    '" ya tiene una tarifa activa para "' .
                    $concepto['nombre'] .
                    '" cuya vigencia se superpone (' .
                    $vigencia .
                    ').'
                );
            }
        }
    }


    // ======================================================
    // SEGUNDO RECORRIDO:
    // INSERTAR
    // ======================================================

    $cantidadCreadas = 0;


    foreach ($tiposDestino as $tipoDestino) {

        $idTipoDestino =
            (int)$tipoDestino['id_tipo_config'];


        $stmtInsert->execute([

            ':id_concepto'
                => $idConcepto,

            ':id_tipo_config'
                => $idTipoDestino,

            ':nombre'
                => $nombre,

            ':valor'
                => $valor,

            ':fecha_inicio'
                => $fechaInicio,

            ':fecha_fin'
                => $fechaFin,

            ':estado'
                => $estado,

            ':observaciones'
                => $observaciones

        ]);


        $cantidadCreadas++;
    }


    // ======================================================
    // CONFIRMAR
    // ======================================================

    $conexion->commit();


    // ======================================================
    // MENSAJE
    // ======================================================

    if ($aplicarATodas) {

        redireccionarTarifas(
            'success',
            'La tarifa fue creada correctamente para ' .
            $cantidadCreadas .
            ' tipos de unidad.'
        );
    }


    redireccionarTarifas(
        'success',
        'La tarifa fue creada correctamente.'
    );


} catch (RuntimeException $e) {

    // ======================================================
    // ROLLBACK ERROR DE NEGOCIO
    // ======================================================

    if ($conexion->inTransaction()) {

        $conexion->rollBack();
    }


    redireccionarTarifas(
        'warning',
        $e->getMessage()
    );


} catch (PDOException $e) {

    // ======================================================
    // ROLLBACK ERROR BD
    // ======================================================

    if ($conexion->inTransaction()) {

        $conexion->rollBack();
    }


    error_log(
        'Error guardando tarifa: ' .
        $e->getMessage()
    );


    redireccionarTarifas(
        'error',
        'No fue posible guardar la tarifa.'
    );


} catch (Throwable $e) {

    // ======================================================
    // OTRO ERROR
    // ======================================================

    if ($conexion->inTransaction()) {

        $conexion->rollBack();
    }


    error_log(
        'Error inesperado guardando tarifa: ' .
        $e->getMessage()
    );


    redireccionarTarifas(
        'error',
        'Ocurrió un error inesperado al guardar la tarifa.'
    );
}