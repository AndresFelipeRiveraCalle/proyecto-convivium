<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// SOLO POST
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/unidades.php"
    );

    exit;
}


// ==========================================================
// DATOS
// ==========================================================

$idRelacion = isset($_POST['id_relacion'])
    ? (int)$_POST['id_relacion']
    : 0;


$fechaHasta = isset($_POST['fecha_hasta'])
    ? trim($_POST['fecha_hasta'])
    : date('Y-m-d');


// ==========================================================
// VALIDAR
// ==========================================================

if ($idRelacion <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/unidades.php?tipo=error&texto=" .
        urlencode("Relación no válida.")
    );

    exit;
}


$fechaObj =
    DateTime::createFromFormat(
        'Y-m-d',
        $fechaHasta
    );


if (
    !$fechaObj ||
    $fechaObj->format('Y-m-d') !== $fechaHasta
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/unidades.php?tipo=warning&texto=" .
        urlencode("Fecha de retiro no válida.")
    );

    exit;
}


try {

    // ======================================================
    // CONSULTAR RELACIÓN
    // ======================================================

    $sqlRelacion = "
        SELECT
            id,
            unidad_id,
            fecha_desde,
            fecha_hasta,
            activo

        FROM residente

        WHERE id = :id

        LIMIT 1
    ";


    $stmtRelacion =
        $conexion->prepare(
            $sqlRelacion
        );


    $stmtRelacion->execute([
        ':id' => $idRelacion
    ]);


    $relacion =
        $stmtRelacion->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$relacion) {

        header(
            "Location: " .
            BASE_URL .
            "configuracion/unidades.php?tipo=warning&texto=" .
            urlencode("La relación no existe.")
        );

        exit;
    }


    $idUnidad =
        (int)$relacion['unidad_id'];


    $urlRetorno =
        BASE_URL .
        "configuracion/personas_unidad.php?id_unidad=" .
        $idUnidad;


    // ======================================================
    // YA RETIRADA
    // ======================================================

    if (
        (int)$relacion['activo'] !== 1 ||
        $relacion['fecha_hasta'] !== null
    ) {

        header(
            "Location: " .
            $urlRetorno .
            "&tipo=warning&texto=" .
            urlencode(
                "La persona ya fue retirada de esta unidad."
            )
        );

        exit;
    }


    // ======================================================
    // VALIDAR FECHA
    // ======================================================

    $fechaDesde =
        date(
            'Y-m-d',
            strtotime(
                $relacion['fecha_desde']
            )
        );


    if ($fechaHasta < $fechaDesde) {

        header(
            "Location: " .
            $urlRetorno .
            "&tipo=warning&texto=" .
            urlencode(
                "La fecha de retiro no puede ser anterior a la fecha desde."
            )
        );

        exit;
    }


    // ======================================================
    // RETIRAR
    // ======================================================

    $sqlRetirar = "
        UPDATE residente

        SET
            fecha_hasta = :fecha_hasta,
            activo = 0

        WHERE
            id = :id
            AND activo = 1
            AND fecha_hasta IS NULL
    ";


    $stmtRetirar =
        $conexion->prepare(
            $sqlRetirar
        );


    $stmtRetirar->execute([

        ':fecha_hasta' =>
            $fechaHasta . ' 23:59:59',

        ':id' =>
            $idRelacion

    ]);


    header(
        "Location: " .
        $urlRetorno .
        "&tipo=success&texto=" .
        urlencode(
            "Persona retirada de la unidad correctamente."
        )
    );

    exit;


} catch (PDOException $e) {

    header(
        "Location: " .
        (
            isset($urlRetorno)
                ? $urlRetorno
                : BASE_URL . "configuracion/unidades.php"
        ) .
        (
            isset($urlRetorno)
                ? '&'
                : '?'
        ) .
        "tipo=error&texto=" .
        urlencode(
            "No fue posible retirar la persona."
        )
    );

    exit;
}