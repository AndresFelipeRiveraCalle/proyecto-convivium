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

$idTarifa = isset($_POST['id_tarifa'])
    ? (int)$_POST['id_tarifa']
    : 0;


$idConcepto = isset($_POST['id_concepto'])
    ? (int)$_POST['id_concepto']
    : 0;


$idTipoConfig = isset($_POST['id_tipo_config'])
    ? (int)$_POST['id_tipo_config']
    : 0;


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

$nombre = $nombre !== ''
    ? $nombre
    : null;


$fechaFin = $fechaFin !== ''
    ? $fechaFin
    : null;


$observaciones = $observaciones !== ''
    ? $observaciones
    : null;


// ==========================================================
// VALIDACIONES BÁSICAS
// ==========================================================

if ($idTarifa <= 0) {

    redireccionarTarifas(
        'warning',
        'La tarifa seleccionada no es válida.'
    );
}


if ($idConcepto <= 0) {

    redireccionarTarifas(
        'warning',
        'Debe seleccionar un concepto.'
    );
}


if ($idTipoConfig <= 0) {

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
    // VALIDAR QUE LA TARIFA EXISTA
    // ======================================================

    $sqlTarifa = "
        SELECT
            id_tarifa

        FROM tarifas_facturacion

        WHERE id_tarifa = :id_tarifa

        LIMIT 1
    ";


    $stmtTarifa = $conexion->prepare(
        $sqlTarifa
    );


    $stmtTarifa->execute([

        ':id_tarifa'
            => $idTarifa

    ]);


    if (!$stmtTarifa->fetch(PDO::FETCH_ASSOC)) {

        redireccionarTarifas(
            'warning',
            'La tarifa que intenta editar no existe.'
        );
    }


    // ======================================================
    // VALIDAR CONCEPTO ACTIVO
    // ======================================================

    $sqlConcepto = "
        SELECT
            id_concepto,
            nombre,
            tipo_calculo

        FROM conceptos_facturacion

        WHERE id_concepto =
            :id_concepto

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

        redireccionarTarifas(
            'warning',
            'El concepto seleccionado no existe o se encuentra inactivo.'
        );
    }


    // ======================================================
    // VALIDAR TIPO DE UNIDAD
    // ======================================================

    $sqlTipoUnidad = "
        SELECT
            id_tipo_config,
            nombre_grupo

        FROM detalle_tipos_unidad

        WHERE id_tipo_config =
            :id_tipo_config

        AND activo = 1

        LIMIT 1
    ";


    $stmtTipoUnidad = $conexion->prepare(
        $sqlTipoUnidad
    );


    $stmtTipoUnidad->execute([

        ':id_tipo_config'
            => $idTipoConfig

    ]);


    $tipoUnidad = $stmtTipoUnidad->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$tipoUnidad) {

        redireccionarTarifas(
            'warning',
            'El tipo de unidad seleccionado no existe o se encuentra inactivo.'
        );
    }


    // ======================================================
    // VALIDAR SOLAPAMIENTO
    // ======================================================
    //
    // Solo verificamos solapamiento si la tarifa que estamos
    // editando quedará ACTIVA.
    //
    // Debemos excluir el registro actual.
    // ======================================================

    if ($estado === 1) {

        $sqlSolape = "
            SELECT
                id_tarifa,
                nombre,
                fecha_inicio,
                fecha_fin

            FROM tarifas_facturacion

            WHERE id_concepto =
                :id_concepto

            AND id_tipo_config =
                :id_tipo_config

            AND estado = 1

            AND id_tarifa <>
                :id_tarifa

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


        $fechaFinComparacion =
            $fechaFin !== null
                ? $fechaFin
                : '9999-12-31';


        $stmtSolape->execute([

            ':id_concepto'
                => $idConcepto,

            ':id_tipo_config'
                => $idTipoConfig,

            ':id_tarifa'
                => $idTarifa,

            ':fecha_fin_comparacion'
                => $fechaFinComparacion,

            ':fecha_inicio_comparacion'
                => $fechaInicio

        ]);


        $tarifaSolapada =
            $stmtSolape->fetch(
                PDO::FETCH_ASSOC
            );


        if ($tarifaSolapada) {

            $vigenciaExistente =
                $tarifaSolapada['fecha_inicio'];


            if (
                !empty(
                    $tarifaSolapada['fecha_fin']
                )
            ) {

                $vigenciaExistente .=
                    ' hasta ' .
                    $tarifaSolapada['fecha_fin'];

            } else {

                $vigenciaExistente .=
                    ' en adelante';
            }


            redireccionarTarifas(
                'warning',
                'Ya existe otra tarifa activa para este concepto y tipo de unidad cuya vigencia se superpone (' .
                $vigenciaExistente .
                ').'
            );
        }
    }


    // ======================================================
    // ACTUALIZAR TARIFA
    // ======================================================

    $sqlActualizar = "
        UPDATE tarifas_facturacion

        SET
            id_concepto =
                :id_concepto,

            id_tipo_config =
                :id_tipo_config,

            nombre =
                :nombre,

            valor =
                :valor,

            fecha_inicio =
                :fecha_inicio,

            fecha_fin =
                :fecha_fin,

            estado =
                :estado,

            observaciones =
                :observaciones

        WHERE id_tarifa =
            :id_tarifa
    ";


    $stmtActualizar = $conexion->prepare(
        $sqlActualizar
    );


    $stmtActualizar->execute([

        ':id_concepto'
            => $idConcepto,

        ':id_tipo_config'
            => $idTipoConfig,

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
            => $observaciones,

        ':id_tarifa'
            => $idTarifa

    ]);


    // ======================================================
    // MENSAJE
    // ======================================================

    redireccionarTarifas(
        'success',
        'La tarifa fue actualizada correctamente.'
    );


} catch (PDOException $e) {

    error_log(
        "Error actualizando tarifa: " .
        $e->getMessage()
    );


    redireccionarTarifas(
        'error',
        'No fue posible actualizar la tarifa.'
    );
}