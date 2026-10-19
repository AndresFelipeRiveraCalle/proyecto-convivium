<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/tasas_interes.php"
    );

    exit;
}


// ==========================================================
// DATOS
// ==========================================================

$nombre =
    trim($_POST['nombre'] ?? '');

$tasaAnual =
    isset($_POST['tasa_anual'])
        ? (float)$_POST['tasa_anual']
        : -1;

$tasaMensual =
    isset($_POST['tasa_mensual'])
        ? (float)$_POST['tasa_mensual']
        : -1;

$fechaInicio =
    trim($_POST['fecha_inicio'] ?? '');

$fechaFin =
    trim($_POST['fecha_fin'] ?? '');

$fuente =
    trim($_POST['fuente'] ?? '');

$observaciones =
    trim($_POST['observaciones'] ?? '');


// ==========================================================
// VALIDACIONES
// ==========================================================

if (
    $nombre === ''
    ||
    $fechaInicio === ''
    ||
    $tasaAnual < 0
    ||
    $tasaMensual < 0
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/tasas_interes.php?" .
        http_build_query([
            'tipo'  => 'warning',
            'texto' => 'Complete los campos obligatorios.'
        ])
    );

    exit;
}


if (
    $fechaFin !== ''
    &&
    $fechaFin < $fechaInicio
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/tasas_interes.php?" .
        http_build_query([
            'tipo'  => 'warning',
            'texto' => 'La fecha fin no puede ser anterior a la fecha inicio.'
        ])
    );

    exit;
}


try {

    $sql = "
        INSERT INTO tasas_interes
        (
            nombre,
            tasa_anual,
            tasa_mensual,
            fecha_inicio,
            fecha_fin,
            fuente,
            activo,
            observaciones
        )
        VALUES
        (
            :nombre,
            :tasa_anual,
            :tasa_mensual,
            :fecha_inicio,
            :fecha_fin,
            :fuente,
            1,
            :observaciones
        )
    ";

    $stmt =
        $conexion->prepare($sql);

    $stmt->execute([
        ':nombre'        => $nombre,
        ':tasa_anual'    => $tasaAnual,
        ':tasa_mensual'  => $tasaMensual,
        ':fecha_inicio'  => $fechaInicio,
        ':fecha_fin'     => $fechaFin !== ''
                                ? $fechaFin
                                : null,
        ':fuente'        => $fuente !== ''
                                ? $fuente
                                : null,
        ':observaciones' => $observaciones !== ''
                                ? $observaciones
                                : null
    ]);


    header(
        "Location: " .
        BASE_URL .
        "configuracion/tasas_interes.php?" .
        http_build_query([
            'tipo'  => 'success',
            'texto' => 'Tasa de interés registrada correctamente.'
        ])
    );

    exit;


} catch (Throwable $e) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/tasas_interes.php?" .
        http_build_query([
            'tipo'  => 'error',
            'texto' => 'No fue posible guardar la tasa: ' .
                       $e->getMessage()
        ])
    );

    exit;
}
