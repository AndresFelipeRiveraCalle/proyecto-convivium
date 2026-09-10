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

$idEspacio = isset($_POST['id_espacio_unidad'])
    ? (int)$_POST['id_espacio_unidad']
    : 0;


$origen = isset($_POST['origen'])
    ? trim($_POST['origen'])
    : '';


$numeroDocumento = isset($_POST['numero_documento'])
    ? trim($_POST['numero_documento'])
    : '';


$idNuevaUnidad = isset($_POST['id_unidad']) &&
                 $_POST['id_unidad'] !== ''
    ? (int)$_POST['id_unidad']
    : null;


$fechaTransferencia =
    isset($_POST['fecha_transferencia'])
        ? trim($_POST['fecha_transferencia'])
        : '';


$observaciones = isset($_POST['observaciones'])
    ? trim($_POST['observaciones'])
    : null;


// ==========================================================
// VALIDACIONES BÁSICAS
// ==========================================================

if ($idEspacio <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/espacios.php?tipo=error&texto=" .
        urlencode(
            "Espacio no válido."
        )
    );

    exit;
}


if ($numeroDocumento === '') {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/espacios.php?tipo=warning&texto=" .
        urlencode(
            "Debe indicar el documento del nuevo propietario."
        )
    );

    exit;
}


// ==========================================================
// VALIDAR FECHA
// ==========================================================

$fechaObj =
    DateTime::createFromFormat(
        'Y-m-d',
        $fechaTransferencia
    );


if (
    !$fechaObj ||
    $fechaObj->format('Y-m-d') !==
        $fechaTransferencia
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/espacios.php?tipo=warning&texto=" .
        urlencode(
            "Fecha de transferencia no válida."
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
    // CONSULTAR ESPACIO VIGENTE
    // ======================================================

    $sqlEspacio = "
        SELECT

            id_espacio_unidad,
            id_unidad,
            usuario_id,
            tipo_espacio,
            codigo,
            area,
            fecha_desde,
            fecha_hasta,
            activo,
            observaciones

        FROM espacios_unidad

        WHERE id_espacio_unidad = :id

        LIMIT 1

        FOR UPDATE
    ";


    $stmtEspacio =
        $conexion->prepare(
            $sqlEspacio
        );


    $stmtEspacio->execute([
        ':id' => $idEspacio
    ]);


    $espacio =
        $stmtEspacio->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$espacio) {

        $conexion->rollBack();

        header(
            "Location: " .
            BASE_URL .
            "configuracion/espacios.php?tipo=warning&texto=" .
            urlencode(
                "El espacio no existe."
            )
        );

        exit;
    }


    // ======================================================
    // URL RETORNO ORIGINAL
    // ======================================================

    if ($origen === 'espacios') {

        $urlRetorno =
            BASE_URL .
            "configuracion/espacios.php";

    } elseif (
        !empty($espacio['id_unidad'])
    ) {

        $urlRetorno =
            BASE_URL .
            "configuracion/personas_unidad.php?id_unidad=" .
            (int)$espacio['id_unidad'];

    } else {

        $urlRetorno =
            BASE_URL .
            "configuracion/espacios.php";
    }


    // ======================================================
    // VALIDAR VIGENCIA
    // ======================================================

    if (
        (int)$espacio['activo'] !== 1 ||
        $espacio['fecha_hasta'] !== null
    ) {

        $conexion->rollBack();

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
                "Este espacio ya no corresponde al registro vigente."
            )
        );

        exit;
    }


    // ======================================================
    // VALIDAR FECHA CONTRA FECHA DESDE
    // ======================================================

    $fechaDesde =
        date(
            'Y-m-d',
            strtotime(
                $espacio['fecha_desde']
            )
        );


    if (
        $fechaTransferencia <=
        $fechaDesde
    ) {

        $conexion->rollBack();

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
                "La fecha de transferencia debe ser posterior a la fecha desde del registro actual."
            )
        );

        exit;
    }


    // ======================================================
    // BUSCAR NUEVO PROPIETARIO
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


    $nuevoPropietario =
        $stmtUsuario->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$nuevoPropietario) {

        $conexion->rollBack();

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
                "No existe un usuario con el documento " .
                $numeroDocumento .
                "."
            )
        );

        exit;
    }


    if (
        isset($nuevoPropietario['estado']) &&
        (int)$nuevoPropietario['estado'] !== 1
    ) {

        $conexion->rollBack();

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
                "El nuevo propietario está inactivo."
            )
        );

        exit;
    }


    $nuevoUsuarioId =
        (int)$nuevoPropietario['id'];


    // ======================================================
    // VALIDAR NUEVA UNIDAD
    // ======================================================

    if ($idNuevaUnidad !== null) {

        $sqlUnidad = "
            SELECT
                id_unidad,
                activo

            FROM unidades

            WHERE id_unidad = :id_unidad

            LIMIT 1
        ";


        $stmtUnidad =
            $conexion->prepare(
                $sqlUnidad
            );


        $stmtUnidad->execute([
            ':id_unidad' =>
                $idNuevaUnidad
        ]);


        $nuevaUnidad =
            $stmtUnidad->fetch(
                PDO::FETCH_ASSOC
            );


        if (
            !$nuevaUnidad ||
            (int)$nuevaUnidad['activo'] !== 1
        ) {

            $conexion->rollBack();

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
                    "La unidad seleccionada no existe o está inactiva."
                )
            );

            exit;
        }
    }


    // ======================================================
    // VALIDAR DUPLICADO VIGENTE
    // ======================================================

    $sqlDuplicado = "
        SELECT
            id_espacio_unidad

        FROM espacios_unidad

        WHERE
            tipo_espacio = :tipo
            AND codigo = :codigo
            AND activo = 1
            AND fecha_hasta IS NULL
            AND id_espacio_unidad <> :id_actual

        LIMIT 1
    ";


    $stmtDuplicado =
        $conexion->prepare(
            $sqlDuplicado
        );


    $stmtDuplicado->execute([

        ':tipo' =>
            $espacio['tipo_espacio'],

        ':codigo' =>
            $espacio['codigo'],

        ':id_actual' =>
            $idEspacio

    ]);


    if (
        $stmtDuplicado->fetch(
            PDO::FETCH_ASSOC
        )
    ) {

        $conexion->rollBack();

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
                "Ya existe otro registro vigente para este espacio."
            )
        );

        exit;
    }


    // ======================================================
    // FECHA HASTA DEL PROPIETARIO ANTERIOR
    // ======================================================

    $fechaHastaAnteriorObj =
        new DateTime(
            $fechaTransferencia
        );


    $fechaHastaAnteriorObj->modify(
        '-1 day'
    );


    $fechaHastaAnterior =
        $fechaHastaAnteriorObj->format(
            'Y-m-d'
        );


    // ======================================================
    // CERRAR REGISTRO ANTERIOR
    // ======================================================

    $sqlCerrar = "
        UPDATE espacios_unidad

        SET
            fecha_hasta = :fecha_hasta

        WHERE
            id_espacio_unidad = :id
            AND activo = 1
            AND fecha_hasta IS NULL
    ";


    $stmtCerrar =
        $conexion->prepare(
            $sqlCerrar
        );


    $stmtCerrar->execute([

        ':fecha_hasta' =>
            $fechaHastaAnterior,

        ':id' =>
            $idEspacio

    ]);


    if ($stmtCerrar->rowCount() !== 1) {

        throw new Exception(
            "No fue posible cerrar el registro anterior."
        );
    }


    // ======================================================
    // CREAR NUEVO REGISTRO VIGENTE
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
            $idNuevaUnidad,

        ':usuario_id' =>
            $nuevoUsuarioId,

        ':tipo_espacio' =>
            $espacio['tipo_espacio'],

        ':codigo' =>
            $espacio['codigo'],

        ':area' =>
            $espacio['area'],

        ':fecha_desde' =>
            $fechaTransferencia,

        ':observaciones' =>
            $observaciones !== ''
                ? $observaciones
                : null

    ]);


    // ======================================================
    // COMMIT
    // ======================================================

    $conexion->commit();


    // ======================================================
    // REDIRECCIÓN
    // ======================================================

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
            "Espacio transferido correctamente."
        )
    );

    exit;


} catch (Throwable $e) {


    if ($conexion->inTransaction()) {

        $conexion->rollBack();
    }


    $urlError =
        isset($urlRetorno)
            ? $urlRetorno
            : BASE_URL .
              "configuracion/espacios.php";


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
            "No fue posible transferir el espacio."
        )
    );

    exit;
}