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

$idTasa =
    isset($_POST['id_tasa_interes'])
        ? (int)$_POST['id_tasa_interes']
        : 0;

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

$fuente =
    trim($_POST['fuente'] ?? '');

$observaciones =
    trim($_POST['observaciones'] ?? '');


// ==========================================================
// VALIDACIONES
// ==========================================================

if (
    $idTasa <= 0
    ||
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
            'texto' => 'Datos incompletos para crear la nueva vigencia.'
        ])
    );

    exit;
}


try {

    $conexion->beginTransaction();


    // ======================================================
    // TASA ACTUAL
    // ======================================================

    $sqlActual = "
        SELECT
            id_tasa_interes,
            nombre,
            fecha_inicio,
            fecha_fin,
            activo

        FROM tasas_interes

        WHERE id_tasa_interes =
              :id_tasa_interes

        FOR UPDATE
    ";

    $stmtActual =
        $conexion->prepare($sqlActual);

    $stmtActual->execute([
        ':id_tasa_interes' => $idTasa
    ]);

    $actual =
        $stmtActual->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$actual) {

        throw new Exception(
            'La tasa seleccionada no existe.'
        );
    }


    if (
        (int)$actual['activo'] !== 1
        ||
        !empty($actual['fecha_fin'])
    ) {

        throw new Exception(
            'La tasa seleccionada ya no tiene una vigencia abierta.'
        );
    }


    if (
        $fechaInicio <=
        $actual['fecha_inicio']
    ) {

        throw new Exception(
            'La nueva vigencia debe iniciar después de la vigencia actual.'
        );
    }


    // ======================================================
    // CERRAR VIGENCIA ANTERIOR
    // ======================================================

    $fechaFinAnterior =
        date(
            'Y-m-d',
            strtotime(
                $fechaInicio .
                ' -1 day'
            )
        );


    $sqlCerrar = "
        UPDATE tasas_interes

        SET
            fecha_fin = :fecha_fin

        WHERE id_tasa_interes =
              :id_tasa_interes
    ";

    $stmtCerrar =
        $conexion->prepare($sqlCerrar);

    $stmtCerrar->execute([
        ':fecha_fin'        => $fechaFinAnterior,
        ':id_tasa_interes'  => $idTasa
    ]);


    // ======================================================
    // CREAR NUEVA VIGENCIA
    // ======================================================

    $sqlNueva = "
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
            NULL,
            :fuente,
            1,
            :observaciones
        )
    ";

    $stmtNueva =
        $conexion->prepare($sqlNueva);

    $stmtNueva->execute([
        ':nombre'        => $nombre,
        ':tasa_anual'    => $tasaAnual,
        ':tasa_mensual'  => $tasaMensual,
        ':fecha_inicio'  => $fechaInicio,
        ':fuente'        => $fuente !== ''
                                ? $fuente
                                : null,
        ':observaciones' => $observaciones !== ''
                                ? $observaciones
                                : null
    ]);


    $conexion->commit();


    header(
        "Location: " .
        BASE_URL .
        "configuracion/tasas_interes.php?" .
        http_build_query([
            'tipo'  => 'success',
            'texto' => 'Nueva vigencia creada correctamente. La vigencia anterior fue cerrada al ' .
                       $fechaFinAnterior .
                       '.'
        ])
    );

    exit;


} catch (Throwable $e) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }


    header(
        "Location: " .
        BASE_URL .
        "configuracion/tasas_interes.php?" .
        http_build_query([
            'tipo'  => 'error',
            'texto' => $e->getMessage()
        ])
    );

    exit;
}
