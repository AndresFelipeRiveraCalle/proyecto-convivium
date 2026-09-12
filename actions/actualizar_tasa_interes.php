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

$fuente =
    trim($_POST['fuente'] ?? '');

$observaciones =
    trim($_POST['observaciones'] ?? '');

$activo =
    isset($_POST['activo'])
        ? (int)$_POST['activo']
        : 1;

$fechaInicio =
    trim($_POST['fecha_inicio'] ?? '');

$fechaFin =
    trim($_POST['fecha_fin'] ?? '');


// ==========================================================
// VALIDACIONES
// ==========================================================

if (
    $idTasa <= 0
    ||
    $nombre === ''
    ||
    !in_array(
        $activo,
        [0, 1],
        true
    )
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/tasas_interes.php?" .
        http_build_query([
            'tipo'  => 'warning',
            'texto' => 'Debe indicar una tasa válida y un nombre.'
        ])
    );

    exit;
}


try {

    // Verificar existencia de la tasa.
    $sqlExiste = "
        SELECT
            ti.id_tasa_interes,
            ti.fecha_inicio,
            ti.fecha_fin,

            (
                SELECT COUNT(*)
                FROM intereses_cartera ic
                WHERE ic.id_tasa_interes =
                      ti.id_tasa_interes
            ) AS cantidad_usos

        FROM tasas_interes ti

        WHERE ti.id_tasa_interes = :id_tasa_interes

        LIMIT 1
    ";

    $stmtExiste =
        $conexion->prepare($sqlExiste);

    $stmtExiste->execute([
        ':id_tasa_interes' => $idTasa
    ]);


    $tasaActual =
        $stmtExiste->fetch(PDO::FETCH_ASSOC);


    if (!$tasaActual) {

        throw new Exception(
            'La tasa seleccionada no existe.'
        );
    }


    $tasaUsada =
        (int)$tasaActual['cantidad_usos'] > 0;


    if (!$tasaUsada) {

        if ($fechaInicio === '') {

            throw new Exception(
                'La fecha de inicio es obligatoria.'
            );
        }


        if (
            $fechaFin !== ''
            &&
            $fechaFin < $fechaInicio
        ) {

            throw new Exception(
                'La fecha fin no puede ser anterior a la fecha inicio.'
            );
        }
    }


    if ($tasaUsada) {

        // Si ya fue utilizada, se preservan las fechas históricas.
        $sqlActualizar = "
            UPDATE tasas_interes

            SET
                nombre = :nombre,
                fuente = :fuente,
                observaciones = :observaciones,
                activo = :activo

            WHERE id_tasa_interes = :id_tasa_interes
        ";

        $stmtActualizar =
            $conexion->prepare($sqlActualizar);

        $stmtActualizar->execute([
            ':nombre'          => $nombre,
            ':fuente'          => $fuente !== ''
                                   ? $fuente
                                   : null,
            ':observaciones'   => $observaciones !== ''
                                   ? $observaciones
                                   : null,
            ':activo'          => $activo,
            ':id_tasa_interes' => $idTasa
        ]);

    } else {

        // Si nunca se ha utilizado, también se pueden corregir las fechas.
        $sqlActualizar = "
            UPDATE tasas_interes

            SET
                nombre = :nombre,
                fuente = :fuente,
                observaciones = :observaciones,
                activo = :activo,
                fecha_inicio = :fecha_inicio,
                fecha_fin = :fecha_fin

            WHERE id_tasa_interes = :id_tasa_interes
        ";

        $stmtActualizar =
            $conexion->prepare($sqlActualizar);

        $stmtActualizar->execute([
            ':nombre'          => $nombre,
            ':fuente'          => $fuente !== ''
                                   ? $fuente
                                   : null,
            ':observaciones'   => $observaciones !== ''
                                   ? $observaciones
                                   : null,
            ':activo'          => $activo,
            ':fecha_inicio'    => $fechaInicio,
            ':fecha_fin'       => $fechaFin !== ''
                                   ? $fechaFin
                                   : null,
            ':id_tasa_interes' => $idTasa
        ]);
    }


    header(
        "Location: " .
        BASE_URL .
        "configuracion/tasas_interes.php?" .
        http_build_query([
            'tipo'  => 'success',
            'texto' => 'Datos de la tasa actualizados correctamente.'
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
            'texto' => $e->getMessage()
        ])
    );

    exit;
}
