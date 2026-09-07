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
// RECIBIR DATOS
// ==========================================================

$idEspacioUnidad = isset($_POST['id_espacio_unidad'])
    ? (int)$_POST['id_espacio_unidad']
    : 0;


$numeroDocumento = isset($_POST['numero_documento'])
    ? trim($_POST['numero_documento'])
    : '';


$idUnidadNueva = isset($_POST['id_unidad']) &&
                 $_POST['id_unidad'] !== ''
    ? (int)$_POST['id_unidad']
    : null;


$fechaTransferencia = isset($_POST['fecha_transferencia'])
    ? trim($_POST['fecha_transferencia'])
    : '';


$observacionesNuevas = isset($_POST['observaciones'])
    ? trim($_POST['observaciones'])
    : null;


// ==========================================================
// VALIDACIONES BÁSICAS
// ==========================================================

if ($idEspacioUnidad <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/unidades.php?tipo=error&texto=" .
        urlencode("Registro de espacio no válido.")
    );

    exit;
}


if ($numeroDocumento === '') {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/unidades.php?tipo=warning&texto=" .
        urlencode("Debe ingresar el documento del nuevo propietario.")
    );

    exit;
}


if ($fechaTransferencia === '') {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/unidades.php?tipo=warning&texto=" .
        urlencode("Debe ingresar la fecha de transferencia.")
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
    $fechaObj->format('Y-m-d') !== $fechaTransferencia
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/unidades.php?tipo=warning&texto=" .
        urlencode("La fecha de transferencia no es válida.")
    );

    exit;
}


try {


    // ======================================================
    // INICIAR TRANSACCIÓN
    // ======================================================

    $conexion->beginTransaction();


    // ======================================================
    // OBTENER REGISTRO ACTUAL DEL ESPACIO
    // ======================================================

    $sqlEspacioActual = "
        SELECT

            eu.id_espacio_unidad,
            eu.id_unidad,
            eu.usuario_id,
            eu.tipo_espacio,
            eu.codigo,
            eu.area,
            eu.fecha_desde,
            eu.fecha_hasta,
            eu.activo,
            eu.observaciones

        FROM espacios_unidad eu

        WHERE
            eu.id_espacio_unidad = :id_espacio_unidad

        LIMIT 1

        FOR UPDATE
    ";


    $stmtEspacioActual =
        $conexion->prepare(
            $sqlEspacioActual
        );


    $stmtEspacioActual->execute([

        ':id_espacio_unidad'
            => $idEspacioUnidad

    ]);


    $espacioActual =
        $stmtEspacioActual->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$espacioActual) {

        $conexion->rollBack();

        header(
            "Location: " .
            BASE_URL .
            "configuracion/unidades.php?tipo=warning&texto=" .
            urlencode("El espacio no existe.")
        );

        exit;
    }


    // ======================================================
    // VALIDAR QUE SEA REGISTRO VIGENTE
    // ======================================================

    if (
        (int)$espacioActual['activo'] !== 1 ||
        $espacioActual['fecha_hasta'] !== null
    ) {

        $conexion->rollBack();

        header(
            "Location: " .
            BASE_URL .
            "configuracion/unidades.php?tipo=warning&texto=" .
            urlencode(
                "El espacio seleccionado ya no tiene una vigencia activa."
            )
        );

        exit;
    }


    // ======================================================
    // URL DE RETORNO
    // ======================================================

    $idUnidadRetorno =
        !empty($espacioActual['id_unidad'])
            ? (int)$espacioActual['id_unidad']
            : 0;


    if ($idUnidadRetorno > 0) {

        $urlRetorno =
            BASE_URL .
            "configuracion/personas_unidad.php?id_unidad=" .
            $idUnidadRetorno;

    } elseif ($idUnidadNueva !== null) {

        $urlRetorno =
            BASE_URL .
            "configuracion/personas_unidad.php?id_unidad=" .
            $idUnidadNueva;

    } else {

        $urlRetorno =
            BASE_URL .
            "configuracion/unidades.php";
    }


    // ======================================================
    // VALIDAR FECHA VS FECHA DESDE ACTUAL
    // ======================================================

    $fechaDesdeActual =
        $espacioActual['fecha_desde'];


    if (
        $fechaTransferencia <= $fechaDesdeActual
    ) {

        $conexion->rollBack();

        header(
            "Location: " .
            $urlRetorno .
            "&tipo=warning&texto=" .
            urlencode(
                "La fecha de transferencia debe ser posterior a la fecha desde actual (" .
                date(
                    'd/m/Y',
                    strtotime($fechaDesdeActual)
                ) .
                ")."
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

        ':numero_documento'
            => $numeroDocumento

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
            "&tipo=warning&texto=" .
            urlencode(
                "No existe un usuario con el documento " .
                $numeroDocumento .
                "."
            )
        );

        exit;
    }


    // ======================================================
    // VALIDAR USUARIO ACTIVO
    // ======================================================

    if (
        isset($nuevoPropietario['estado']) &&
        (int)$nuevoPropietario['estado'] !== 1
    ) {

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


    $nuevoUsuarioId =
        (int)$nuevoPropietario['id'];


    // ======================================================
    // VALIDAR UNIDAD NUEVA SI FUE SELECCIONADA
    // ======================================================

    if ($idUnidadNueva !== null) {


        $sqlUnidadNueva = "
            SELECT
                id_unidad,
                codigo,
                activo

            FROM unidades

            WHERE id_unidad = :id_unidad

            LIMIT 1
        ";


        $stmtUnidadNueva =
            $conexion->prepare(
                $sqlUnidadNueva
            );


        $stmtUnidadNueva->execute([

            ':id_unidad'
                => $idUnidadNueva

        ]);


        $unidadNueva =
            $stmtUnidadNueva->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$unidadNueva) {

            $conexion->rollBack();

            header(
                "Location: " .
                $urlRetorno .
                "&tipo=warning&texto=" .
                urlencode(
                    "La nueva unidad seleccionada no existe."
                )
            );

            exit;
        }


        if (
            (int)$unidadNueva['activo'] !== 1
        ) {

            $conexion->rollBack();

            header(
                "Location: " .
                $urlRetorno .
                "&tipo=warning&texto=" .
                urlencode(
                    "La nueva unidad seleccionada está inactiva."
                )
            );

            exit;
        }
    }


    // ======================================================
    // VALIDAR QUE NO EXISTA OTRA VIGENCIA ACTUAL
    // DEL MISMO ESPACIO
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
            AND id_espacio_unidad <> :id_actual

        LIMIT 1
    ";


    $stmtDuplicado =
        $conexion->prepare(
            $sqlDuplicado
        );


    $stmtDuplicado->execute([

        ':tipo_espacio'
            => $espacioActual['tipo_espacio'],

        ':codigo'
            => $espacioActual['codigo'],

        ':id_actual'
            => $idEspacioUnidad

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
            "&tipo=error&texto=" .
            urlencode(
                "Existe otra vigencia activa para este espacio. Revise el histórico."
            )
        );

        exit;
    }


    // ======================================================
    // CALCULAR FECHA HASTA DEL REGISTRO ANTERIOR
    // ======================================================

    $fechaHastaAnterior =
        date(
            'Y-m-d',
            strtotime(
                $fechaTransferencia .
                ' -1 day'
            )
        );


    // ======================================================
    // CERRAR REGISTRO ACTUAL
    // ======================================================

    $sqlCerrar = "
        UPDATE espacios_unidad

        SET
            fecha_hasta = :fecha_hasta

        WHERE
            id_espacio_unidad = :id_espacio_unidad
            AND activo = 1
            AND fecha_hasta IS NULL
    ";


    $stmtCerrar =
        $conexion->prepare(
            $sqlCerrar
        );


    $stmtCerrar->execute([

        ':fecha_hasta'
            => $fechaHastaAnterior,

        ':id_espacio_unidad'
            => $idEspacioUnidad

    ]);


    if ($stmtCerrar->rowCount() !== 1) {

        throw new Exception(
            "No fue posible cerrar la vigencia anterior."
        );
    }


    // ======================================================
    // PREPARAR OBSERVACIONES
    // ======================================================

    $observacionesFinales = null;


    if (
        $observacionesNuevas !== null &&
        $observacionesNuevas !== ''
    ) {

        $observacionesFinales =
            $observacionesNuevas;

    } elseif (
        !empty($espacioActual['observaciones'])
    ) {

        $observacionesFinales =
            $espacioActual['observaciones'];
    }


    // ======================================================
    // CREAR NUEVO REGISTRO HISTÓRICO
    // ======================================================

    $sqlNuevo = "
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


    $stmtNuevo =
        $conexion->prepare(
            $sqlNuevo
        );


    $stmtNuevo->execute([

        ':id_unidad'
            => $idUnidadNueva,

        ':usuario_id'
            => $nuevoUsuarioId,

        ':tipo_espacio'
            => $espacioActual['tipo_espacio'],

        ':codigo'
            => $espacioActual['codigo'],

        ':area'
            => $espacioActual['area'],

        ':fecha_desde'
            => $fechaTransferencia,

        ':observaciones'
            => $observacionesFinales

    ]);


    // ======================================================
    // CONFIRMAR TRANSACCIÓN
    // ======================================================

    $conexion->commit();


    // ======================================================
    // REDIRECCIÓN FINAL
    // ======================================================

    $nombrePropietario =
        trim(
            ($nuevoPropietario['nombres'] ?? '') .
            ' ' .
            ($nuevoPropietario['apellidos'] ?? '')
        );


    header(
        "Location: " .
        $urlRetorno .
        "&tipo=success&texto=" .
        urlencode(
            "El espacio " .
            $espacioActual['codigo'] .
            " fue transferido correctamente a " .
            $nombrePropietario .
            "."
        )
    );

    exit;


} catch (Throwable $e) {


    // ======================================================
    // ROLLBACK
    // ======================================================

    if ($conexion->inTransaction()) {

        $conexion->rollBack();
    }


    // ======================================================
    // RETORNO
    // ======================================================

    $urlError =
        BASE_URL .
        "configuracion/unidades.php";


    if (
        isset($urlRetorno) &&
        !empty($urlRetorno)
    ) {

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
            "No fue posible transferir el espacio."
        )
    );

    exit;
}