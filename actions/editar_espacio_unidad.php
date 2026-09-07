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

$idEspacioUnidad = isset($_POST['id_espacio_unidad'])
    ? (int)$_POST['id_espacio_unidad']
    : 0;


$tipoEspacio = isset($_POST['tipo_espacio'])
    ? trim($_POST['tipo_espacio'])
    : '';


$codigo = isset($_POST['codigo'])
    ? strtoupper(trim($_POST['codigo']))
    : '';


$area = isset($_POST['area']) &&
        $_POST['area'] !== ''
    ? (float)$_POST['area']
    : null;


$observaciones = isset($_POST['observaciones'])
    ? trim($_POST['observaciones'])
    : null;


// ==========================================================
// VALIDACIONES
// ==========================================================

if ($idEspacioUnidad <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/unidades.php?tipo=error&texto=" .
        urlencode("Espacio no válido.")
    );

    exit;
}


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
        BASE_URL .
        "configuracion/unidades.php?tipo=warning&texto=" .
        urlencode("Tipo de espacio no válido.")
    );

    exit;
}


if ($codigo === '') {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/unidades.php?tipo=warning&texto=" .
        urlencode("Debe indicar el código del espacio.")
    );

    exit;
}


if (
    $area !== null &&
    $area < 0
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/unidades.php?tipo=warning&texto=" .
        urlencode("El área no puede ser negativa.")
    );

    exit;
}


try {

    // ======================================================
    // CONSULTAR REGISTRO
    // ======================================================

    $sqlActual = "
        SELECT

            id_espacio_unidad,
            id_unidad,
            tipo_espacio,
            codigo,
            fecha_hasta,
            activo

        FROM espacios_unidad

        WHERE id_espacio_unidad = :id

        LIMIT 1
    ";


    $stmtActual =
        $conexion->prepare(
            $sqlActual
        );


    $stmtActual->execute([
        ':id' => $idEspacioUnidad
    ]);


    $espacio =
        $stmtActual->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$espacio) {

        header(
            "Location: " .
            BASE_URL .
            "configuracion/unidades.php?tipo=warning&texto=" .
            urlencode("El espacio no existe.")
        );

        exit;
    }


    $idUnidad =
        !empty($espacio['id_unidad'])
            ? (int)$espacio['id_unidad']
            : 0;


    $urlRetorno =
        $idUnidad > 0
            ? BASE_URL .
              "configuracion/personas_unidad.php?id_unidad=" .
              $idUnidad
            : BASE_URL .
              "configuracion/espacios.php";


    // ======================================================
    // SOLO VIGENTE
    // ======================================================

    if (
        (int)$espacio['activo'] !== 1 ||
        $espacio['fecha_hasta'] !== null
    ) {

        header(
            "Location: " .
            $urlRetorno .
            (
                strpos($urlRetorno, '?') !== false
                    ? '&'
                    : '?'
            ) .
            "tipo=warning&texto=" .
            urlencode(
                "Solo se puede editar la vigencia actual del espacio."
            )
        );

        exit;
    }


    // ======================================================
    // VALIDAR DUPLICADO ACTUAL
    // ======================================================

    $sqlDuplicado = "
        SELECT
            id_espacio_unidad

        FROM espacios_unidad

        WHERE
            tipo_espacio = :tipo_espacio
            AND codigo = :codigo
            AND activo = 1
            AND fecha_hasta IS NULL
            AND id_espacio_unidad <> :id

        LIMIT 1
    ";


    $stmtDuplicado =
        $conexion->prepare(
            $sqlDuplicado
        );


    $stmtDuplicado->execute([

        ':tipo_espacio' =>
            $tipoEspacio,

        ':codigo' =>
            $codigo,

        ':id' =>
            $idEspacioUnidad

    ]);


    if (
        $stmtDuplicado->fetch(
            PDO::FETCH_ASSOC
        )
    ) {

        header(
            "Location: " .
            $urlRetorno .
            (
                strpos($urlRetorno, '?') !== false
                    ? '&'
                    : '?'
            ) .
            "tipo=warning&texto=" .
            urlencode(
                "Ya existe otro espacio vigente con ese tipo y código."
            )
        );

        exit;
    }


    // ======================================================
    // ACTUALIZAR
    // ======================================================

    $sqlActualizar = "
        UPDATE espacios_unidad

        SET
            tipo_espacio = :tipo_espacio,
            codigo = :codigo,
            area = :area,
            observaciones = :observaciones

        WHERE id_espacio_unidad = :id
    ";


    $stmtActualizar =
        $conexion->prepare(
            $sqlActualizar
        );


    $stmtActualizar->execute([

        ':tipo_espacio' =>
            $tipoEspacio,

        ':codigo' =>
            $codigo,

        ':area' =>
            $area,

        ':observaciones' =>
            $observaciones !== ''
                ? $observaciones
                : null,

        ':id' =>
            $idEspacioUnidad

    ]);


    header(
        "Location: " .
        $urlRetorno .
        (
            strpos($urlRetorno, '?') !== false
                ? '&'
                : '?'
        ) .
        "tipo=success&texto=" .
        urlencode(
            "Espacio actualizado correctamente."
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
            isset($urlRetorno) &&
            strpos($urlRetorno, '?') !== false
                ? '&'
                : '?'
        ) .
        "tipo=error&texto=" .
        urlencode(
            "No fue posible actualizar el espacio."
        )
    );

    exit;
}