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
// RECIBIR DATOS
// ==========================================================

$idUnidad = isset($_POST['id_unidad'])
    ? (int)$_POST['id_unidad']
    : 0;


$tipoEspacio = isset($_POST['tipo_espacio'])
    ? trim($_POST['tipo_espacio'])
    : '';


$codigo = isset($_POST['codigo'])
    ? strtoupper(trim($_POST['codigo']))
    : '';


$area = isset($_POST['area']) && $_POST['area'] !== ''
    ? (float)$_POST['area']
    : null;


$fechaDesde = isset($_POST['fecha_desde'])
    ? trim($_POST['fecha_desde'])
    : '';


$observaciones = isset($_POST['observaciones'])
    ? trim($_POST['observaciones'])
    : null;


// ==========================================================
// URL RETORNO
// ==========================================================

$urlRetorno =
    BASE_URL .
    "configuracion/personas_unidad.php?id_unidad=" .
    $idUnidad;


// ==========================================================
// VALIDAR UNIDAD
// ==========================================================

if ($idUnidad <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/unidades.php?tipo=error&texto=" .
        urlencode("Unidad no válida.")
    );

    exit;
}


// ==========================================================
// VALIDAR TIPO
// ==========================================================

$tiposPermitidos = [
    'PARQUEADERO',
    'CUARTO_UTIL',
    'DEPOSITO',
    'BODEGA',
    'OTRO'
];


if (
    !in_array(
        $tipoEspacio,
        $tiposPermitidos,
        true
    )
) {

    header(
        "Location: " .
        $urlRetorno .
        "&tipo=warning&texto=" .
        urlencode("Tipo de espacio no válido.")
    );

    exit;
}


// ==========================================================
// VALIDAR CÓDIGO
// ==========================================================

if ($codigo === '') {

    header(
        "Location: " .
        $urlRetorno .
        "&tipo=warning&texto=" .
        urlencode("Debe ingresar el código del espacio.")
    );

    exit;
}


// ==========================================================
// VALIDAR ÁREA
// ==========================================================

if (
    $area !== null &&
    $area < 0
) {

    header(
        "Location: " .
        $urlRetorno .
        "&tipo=warning&texto=" .
        urlencode("El área no puede ser negativa.")
    );

    exit;
}


// ==========================================================
// VALIDAR FECHA
// ==========================================================

if ($fechaDesde === '') {

    header(
        "Location: " .
        $urlRetorno .
        "&tipo=warning&texto=" .
        urlencode("Debe ingresar la fecha desde.")
    );

    exit;
}


$fechaValidada =
    DateTime::createFromFormat(
        'Y-m-d',
        $fechaDesde
    );


if (
    !$fechaValidada ||
    $fechaValidada->format('Y-m-d') !== $fechaDesde
) {

    header(
        "Location: " .
        $urlRetorno .
        "&tipo=warning&texto=" .
        urlencode("La fecha ingresada no es válida.")
    );

    exit;
}


// ==========================================================
// VALIDAR OBSERVACIONES
// ==========================================================

if (
    $observaciones !== null &&
    strlen($observaciones) > 255
) {

    header(
        "Location: " .
        $urlRetorno .
        "&tipo=warning&texto=" .
        urlencode(
            "Las observaciones no pueden superar los 255 caracteres."
        )
    );

    exit;
}


try {


    // ======================================================
    // VALIDAR QUE EXISTA LA UNIDAD
    // ======================================================

    $sqlUnidad = "
        SELECT
            id_unidad

        FROM unidades

        WHERE
            id_unidad = :id_unidad
            AND activo = 1

        LIMIT 1
    ";


    $stmtUnidad =
        $conexion->prepare(
            $sqlUnidad
        );


    $stmtUnidad->execute([
        ':id_unidad' => $idUnidad
    ]);


    $unidad =
        $stmtUnidad->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$unidad) {

        header(
            "Location: " .
            BASE_URL .
            "configuracion/unidades.php?tipo=warning&texto=" .
            urlencode(
                "La unidad no existe o está inactiva."
            )
        );

        exit;
    }


    // ======================================================
    // VALIDAR QUE EL ESPACIO NO ESTÉ ASIGNADO ACTUALMENTE
    // ======================================================

    $sqlExiste = "
        SELECT
            eu.id_espacio_unidad,
            eu.id_unidad,
            u.codigo AS codigo_unidad

        FROM espacios_unidad eu

        INNER JOIN unidades u
            ON u.id_unidad = eu.id_unidad

        WHERE
            eu.tipo_espacio = :tipo_espacio
            AND eu.codigo = :codigo
            AND eu.activo = 1
            AND eu.fecha_hasta IS NULL

        LIMIT 1
    ";


    $stmtExiste =
        $conexion->prepare(
            $sqlExiste
        );


    $stmtExiste->execute([

        ':tipo_espacio' =>
            $tipoEspacio,

        ':codigo' =>
            $codigo

    ]);


    $espacioExistente =
        $stmtExiste->fetch(
            PDO::FETCH_ASSOC
        );


    if ($espacioExistente) {

        header(
            "Location: " .
            $urlRetorno .
            "&tipo=warning&texto=" .
            urlencode(
                "El espacio " .
                $codigo .
                " ya está asociado actualmente a la unidad " .
                $espacioExistente['codigo_unidad'] .
                "."
            )
        );

        exit;
    }


    // ======================================================
    // INSERTAR ESPACIO
    // ======================================================

    $sqlInsertar = "
        INSERT INTO espacios_unidad
        (
            id_unidad,
            tipo_espacio,
            codigo,
            area,
            fecha_desde,
            fecha_hasta,
            activo,
            observaciones
        )
        VALUES
        (
            :id_unidad,
            :tipo_espacio,
            :codigo,
            :area,
            :fecha_desde,
            NULL,
            1,
            :observaciones
        )
    ";


    $stmtInsertar =
        $conexion->prepare(
            $sqlInsertar
        );


    $stmtInsertar->execute([

        ':id_unidad' =>
            $idUnidad,

        ':tipo_espacio' =>
            $tipoEspacio,

        ':codigo' =>
            $codigo,

        ':area' =>
            $area,

        ':fecha_desde' =>
            $fechaDesde,

        ':observaciones' =>
            $observaciones !== ''
                ? $observaciones
                : null

    ]);


    // ======================================================
    // ÉXITO
    // ======================================================

    header(
        "Location: " .
        $urlRetorno .
        "&tipo=success&texto=" .
        urlencode(
            "Espacio agregado correctamente."
        )
    );

    exit;


} catch (PDOException $e) {


    header(
        "Location: " .
        $urlRetorno .
        "&tipo=error&texto=" .
        urlencode(
            "No fue posible guardar el espacio."
        )
    );

    exit;
}