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

$idUnidad = isset($_POST['unidad_id'])
    ? (int)$_POST['unidad_id']
    : 0;


$numeroDocumento = isset($_POST['numero_documento'])
    ? trim($_POST['numero_documento'])
    : '';


$tipo = isset($_POST['tipo'])
    ? trim($_POST['tipo'])
    : '';


$recibeFactura = isset($_POST['recibe_factura'])
    ? (int)$_POST['recibe_factura']
    : 0;


$fechaDesde = isset($_POST['fecha_desde'])
    ? trim($_POST['fecha_desde'])
    : date('Y-m-d');


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


$urlRetorno =
    BASE_URL .
    "configuracion/personas_unidad.php?id_unidad=" .
    $idUnidad;


// ==========================================================
// VALIDAR DOCUMENTO
// ==========================================================

if ($numeroDocumento === '') {

    header(
        "Location: " .
        $urlRetorno .
        "&tipo=warning&texto=" .
        urlencode(
            "Debe ingresar el número de documento."
        )
    );

    exit;
}


// ==========================================================
// VALIDAR TIPO
// ==========================================================

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
        $urlRetorno .
        "&tipo=warning&texto=" .
        urlencode(
            "Tipo de relación no válido."
        )
    );

    exit;
}


// ==========================================================
// VALIDAR RECIBE FACTURA
// ==========================================================

if (
    $recibeFactura !== 0 &&
    $recibeFactura !== 1
) {

    $recibeFactura = 0;
}


// ==========================================================
// VALIDAR FECHA
// ==========================================================

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
        "&tipo=warning&texto=" .
        urlencode(
            "Fecha desde no válida."
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
    // VALIDAR UNIDAD
    // ======================================================

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
            BASE_URL .
            "configuracion/unidades.php?tipo=warning&texto=" .
            urlencode(
                "La unidad no existe o está inactiva."
            )
        );

        exit;
    }


    // ======================================================
    // BUSCAR USUARIO
    // ======================================================

    $sqlUsuario = "
        SELECT
            id,
            nombres,
            apellidos,
            numero_documento,
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


    $usuario =
        $stmtUsuario->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$usuario) {

        $conexion->rollBack();

        header(
            "Location: " .
            $urlRetorno .
            "&tipo=warning&texto=" .
            urlencode(
                "No existe un usuario con ese documento."
            )
        );

        exit;
    }


    if ((int)$usuario['estado'] !== 1) {

        $conexion->rollBack();

        header(
            "Location: " .
            $urlRetorno .
            "&tipo=warning&texto=" .
            urlencode(
                "El usuario seleccionado está inactivo."
            )
        );

        exit;
    }


    $usuarioId =
        (int)$usuario['id'];


    // ======================================================
    // VALIDAR RELACIÓN ACTIVA DUPLICADA
    // ======================================================

    $sqlRelacionActual = "
        SELECT
            id

        FROM residente

        WHERE
            unidad_id = :unidad_id
            AND usuario_id = :usuario_id
            AND activo = 1
            AND fecha_hasta IS NULL

        LIMIT 1

        FOR UPDATE
    ";


    $stmtRelacionActual =
        $conexion->prepare(
            $sqlRelacionActual
        );


    $stmtRelacionActual->execute([

        ':unidad_id' =>
            $idUnidad,

        ':usuario_id' =>
            $usuarioId

    ]);


    if (
        $stmtRelacionActual->fetch(
            PDO::FETCH_ASSOC
        )
    ) {

        $conexion->rollBack();

        header(
            "Location: " .
            $urlRetorno .
            "&tipo=warning&texto=" .
            urlencode(
                "Esta persona ya tiene una relación activa con la unidad."
            )
        );

        exit;
    }


    // ======================================================
    // BUSCAR SI YA EXISTE RECEPTOR DE FACTURA
    // ======================================================

    $sqlReceptor = "
        SELECT
            id

        FROM residente

        WHERE
            unidad_id = :unidad_id
            AND recibe_factura = 1
            AND activo = 1
            AND fecha_hasta IS NULL

        LIMIT 1

        FOR UPDATE
    ";


    $stmtReceptor =
        $conexion->prepare(
            $sqlReceptor
        );


    $stmtReceptor->execute([
        ':unidad_id' =>
            $idUnidad
    ]);


    $receptorActual =
        $stmtReceptor->fetch(
            PDO::FETCH_ASSOC
        );


    // ======================================================
    // REGLA DE FACTURACIÓN
    // ======================================================
    //
    // Si NO existe ningún receptor vigente,
    // esta nueva relación debe recibir factura.
    //
    // ======================================================

    if (!$receptorActual) {

        $recibeFactura = 1;
    }


    // ======================================================
    // INSERTAR RELACIÓN
    // ======================================================

    $sqlInsertar = "
        INSERT INTO residente
        (
            unidad_id,
            usuario_id,
            tipo,
            recibe_factura,
            fecha_desde,
            fecha_hasta,
            activo
        )
        VALUES
        (
            :unidad_id,
            :usuario_id,
            :tipo,
            :recibe_factura,
            :fecha_desde,
            NULL,
            1
        )
    ";


    $stmtInsertar =
        $conexion->prepare(
            $sqlInsertar
        );


    $stmtInsertar->execute([

        ':unidad_id' =>
            $idUnidad,

        ':usuario_id' =>
            $usuarioId,

        ':tipo' =>
            $tipo,

        ':recibe_factura' =>
            $recibeFactura,

        ':fecha_desde' =>
            $fechaDesde . ' 00:00:00'

    ]);


    $conexion->commit();


    // ======================================================
    // MENSAJE
    // ======================================================

    $mensaje =
        "Persona agregada correctamente.";


    if (!$receptorActual) {

        $mensaje .=
            " Se marcó automáticamente para recibir factura porque la unidad no tenía otro receptor activo.";
    }


    header(
        "Location: " .
        $urlRetorno .
        "&tipo=success&texto=" .
        urlencode($mensaje)
    );

    exit;


} catch (Throwable $e) {


    if ($conexion->inTransaction()) {

        $conexion->rollBack();
    }


    header(
        "Location: " .
        $urlRetorno .
        "&tipo=error&texto=" .
        urlencode(
            "No fue posible agregar la persona."
        )
    );

    exit;
}