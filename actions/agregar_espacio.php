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
        "configuracion/espacios.php"
    );

    exit;
}


// ==========================================================
// DATOS
// ==========================================================

$numeroDocumento = isset($_POST['numero_documento'])
    ? trim($_POST['numero_documento'])
    : '';


$tipoEspacio = isset($_POST['tipo_espacio'])
    ? strtoupper(trim($_POST['tipo_espacio']))
    : '';


$codigo = isset($_POST['codigo'])
    ? strtoupper(trim($_POST['codigo']))
    : '';


$area = isset($_POST['area']) &&
        $_POST['area'] !== ''
    ? (float)$_POST['area']
    : null;


$idUnidad = isset($_POST['id_unidad']) &&
            $_POST['id_unidad'] !== ''
    ? (int)$_POST['id_unidad']
    : null;


$fechaDesde = isset($_POST['fecha_desde'])
    ? trim($_POST['fecha_desde'])
    : '';


$observaciones = isset($_POST['observaciones'])
    ? trim($_POST['observaciones'])
    : null;


$urlRetorno =
    BASE_URL .
    "configuracion/espacios.php";


// ==========================================================
// VALIDACIONES
// ==========================================================

$tiposPermitidos = [
    'PARQUEADERO',
    'CUARTO_UTIL',
    'DEPOSITO',
    'BODEGA',
    'OTRO'
];


if ($numeroDocumento === '') {

    header(
        "Location: " .
        $urlRetorno .
        "?tipo=warning&texto=" .
        urlencode(
            "Debe indicar el documento del propietario."
        )
    );

    exit;
}


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
        "?tipo=warning&texto=" .
        urlencode(
            "Tipo de espacio no válido."
        )
    );

    exit;
}


if ($codigo === '') {

    header(
        "Location: " .
        $urlRetorno .
        "?tipo=warning&texto=" .
        urlencode(
            "Debe indicar el código del espacio."
        )
    );

    exit;
}


if (
    $area !== null &&
    $area < 0
) {

    header(
        "Location: " .
        $urlRetorno .
        "?tipo=warning&texto=" .
        urlencode(
            "El área no puede ser negativa."
        )
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
        $urlRetorno .
        "?tipo=warning&texto=" .
        urlencode(
            "Fecha no válida."
        )
    );

    exit;
}


try {

    // ======================================================
    // TRANSACCIÓN
    // ======================================================

    $conexion->beginTransaction();


    // ======================================================
    // PROPIETARIO OBLIGATORIO
    // ======================================================

    $sqlUsuario = "
        SELECT
            id,
            nombres,
            apellidos,
            estado

        FROM usuario

        WHERE numero_documento = :numero_documento

        LIMIT 1
    ";


    $stmtUsuario =
        $conexion->prepare(
            $sqlUsuario
        );


    $stmtUsuario->execute([
        ':numero_documento' =>
            $numeroDocumento
    ]);


    $propietario =
        $stmtUsuario->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$propietario) {

        $conexion->rollBack();

        header(
            "Location: " .
            $urlRetorno .
            "?tipo=warning&texto=" .
            urlencode(
                "No existe un usuario con ese documento."
            )
        );

        exit;
    }


    if ((int)$propietario['estado'] !== 1) {

        $conexion->rollBack();

        header(
            "Location: " .
            $urlRetorno .
            "?tipo=warning&texto=" .
            urlencode(
                "El propietario está inactivo."
            )
        );

        exit;
    }


    $usuarioId =
        (int)$propietario['id'];


    // ======================================================
    // VALIDAR UNIDAD OPCIONAL
    // ======================================================

    if ($idUnidad !== null) {

        if ($idUnidad <= 0) {

            $conexion->rollBack();

            header(
                "Location: " .
                $urlRetorno .
                "?tipo=warning&texto=" .
                urlencode(
                    "Unidad no válida."
                )
            );

            exit;
        }


        $sqlUnidad = "
            SELECT
                id_unidad,
                activo

            FROM unidades

            WHERE id_unidad = :id_unidad

            LIMIT 1

            FOR UPDATE
        ";


        $stmtUnidad =
            $conexion->prepare(
                $sqlUnidad
            );


        $stmtUnidad->execute([
            ':id_unidad' =>
                $idUnidad
        ]);


        $unidad =
            $stmtUnidad->fetch(
                PDO::FETCH_ASSOC
            );


        if (
            !$unidad ||
            (int)$unidad['activo'] !== 1
        ) {

            $conexion->rollBack();

            header(
                "Location: " .
                $urlRetorno .
                "?tipo=warning&texto=" .
                urlencode(
                    "La unidad seleccionada no existe o está inactiva."
                )
            );

            exit;
        }
    }


    // ======================================================
    // VALIDAR ESPACIO VIGENTE
    // ======================================================

    $sqlVigente = "
        SELECT

            id_espacio_unidad,
            id_unidad,
            usuario_id,
            fecha_desde

        FROM espacios_unidad

        WHERE
            tipo_espacio = :tipo_espacio
            AND UPPER(TRIM(codigo)) = :codigo
            AND activo = 1
            AND fecha_hasta IS NULL

        LIMIT 1

        FOR UPDATE
    ";


    $stmtVigente =
        $conexion->prepare(
            $sqlVigente
        );


    $stmtVigente->execute([

        ':tipo_espacio' =>
            $tipoEspacio,

        ':codigo' =>
            $codigo

    ]);


    $espacioVigente =
        $stmtVigente->fetch(
            PDO::FETCH_ASSOC
        );


    if ($espacioVigente) {

        $conexion->rollBack();

        header(
            "Location: " .
            $urlRetorno .
            "?tipo=warning&texto=" .
            urlencode(
                "El espacio " .
                $codigo .
                " ya tiene un registro vigente. " .
                "Utilice Transferir para cambiar su propietario o unidad."
            )
        );

        exit;
    }


    // ======================================================
    // INSERTAR
    // ======================================================

    $sqlInsertar = "
        INSERT INTO espacios_unidad
        (
            id_unidad,
            usuario_id,
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
            :usuario_id,
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

        ':usuario_id' =>
            $usuarioId,

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


    $conexion->commit();


    header(
        "Location: " .
        $urlRetorno .
        "?tipo=success&texto=" .
        urlencode(
            "Espacio agregado correctamente."
        )
    );

    exit;


} catch (Throwable $e) {


    if ($conexion->inTransaction()) {

        $conexion->rollBack();
    }


    header(
        "Location: " .
        $urlRetorno .
        "?tipo=error&texto=" .
        urlencode(
            "No fue posible agregar el espacio."
        )
    );

    exit;
}