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


$tipo = isset($_POST['tipo'])
    ? trim($_POST['tipo'])
    : '';


$recibeFactura = isset($_POST['recibe_factura'])
    ? (int)$_POST['recibe_factura']
    : 0;


$fechaDesde = isset($_POST['fecha_desde'])
    ? trim($_POST['fecha_desde'])
    : '';


// ==========================================================
// VALIDACIONES
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


$tiposPermitidos = [
    'propietario',
    'inquilino',
    'residente'
];


if (
    !in_array(
        $tipo,
        $tiposPermitidos,
        true
    )
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/unidades.php?tipo=warning&texto=" .
        urlencode("Tipo de relación no válido.")
    );

    exit;
}


if (
    $recibeFactura !== 0 &&
    $recibeFactura !== 1
) {

    $recibeFactura = 0;
}


// ==========================================================
// VALIDAR FECHA
// ==========================================================

if ($fechaDesde === '') {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/unidades.php?tipo=warning&texto=" .
        urlencode("Debe indicar la fecha desde.")
    );

    exit;
}


$fechaObj =
    DateTime::createFromFormat(
        'Y-m-d',
        $fechaDesde
    );


if (
    !$fechaObj ||
    $fechaObj->format('Y-m-d') !== $fechaDesde
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/unidades.php?tipo=warning&texto=" .
        urlencode("La fecha ingresada no es válida.")
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
            usuario_id,
            activo,
            fecha_hasta

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
    // VALIDAR QUE SIGA ACTIVA
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
                "La relación ya no está activa."
            )
        );

        exit;
    }


    // ======================================================
    // ACTUALIZAR
    // ======================================================

    $sqlActualizar = "
        UPDATE residente

        SET
            tipo = :tipo,
            recibe_factura = :recibe_factura,
            fecha_desde = :fecha_desde

        WHERE id = :id
    ";


    $stmtActualizar =
        $conexion->prepare(
            $sqlActualizar
        );


    $stmtActualizar->execute([

        ':tipo' =>
            $tipo,

        ':recibe_factura' =>
            $recibeFactura,

        ':fecha_desde' =>
            $fechaDesde . ' 00:00:00',

        ':id' =>
            $idRelacion

    ]);


    header(
        "Location: " .
        $urlRetorno .
        "&tipo=success&texto=" .
        urlencode(
            "Relación actualizada correctamente."
        )
    );

    exit;


} catch (PDOException $e) {

    $urlError =
        BASE_URL .
        "configuracion/unidades.php";


    if (isset($urlRetorno)) {

        $urlError =
            $urlRetorno;
    }


    header(
        "Location: " .
        $urlError .
        (
            strpos($urlError, '?') !== false
                ? '&'
                : '?'
        ) .
        "tipo=error&texto=" .
        urlencode(
            "No fue posible actualizar la relación."
        )
    );

    exit;
}