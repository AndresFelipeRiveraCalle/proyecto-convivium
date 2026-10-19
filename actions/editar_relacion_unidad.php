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
// VALIDACIONES BÁSICAS
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
    // INICIAR TRANSACCIÓN
    // ======================================================

    $conexion->beginTransaction();


    // ======================================================
    // CONSULTAR RELACIÓN ACTUAL
    // ======================================================

    $sqlRelacion = "
        SELECT

            id,
            unidad_id,
            usuario_id,
            tipo,
            recibe_factura,
            fecha_desde,
            fecha_hasta,
            activo

        FROM residente

        WHERE id = :id

        LIMIT 1

        FOR UPDATE
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

        $conexion->rollBack();

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

        $conexion->rollBack();

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
    // VALIDAR ÚLTIMO RECEPTOR DE FACTURA
    // ======================================================
    //
    // Solo aplica cuando:
    //
    // Antes: recibe_factura = 1
    // Ahora: recibe_factura = 0
    //
    // ======================================================

    if (
        (int)$relacion['recibe_factura'] === 1 &&
        $recibeFactura === 0
    ) {

        $sqlOtroReceptor = "
            SELECT
                id

            FROM residente

            WHERE
                unidad_id = :unidad_id
                AND id <> :id_actual
                AND recibe_factura = 1
                AND activo = 1
                AND fecha_hasta IS NULL

            LIMIT 1
        ";


        $stmtOtroReceptor =
            $conexion->prepare(
                $sqlOtroReceptor
            );


        $stmtOtroReceptor->execute([

            ':unidad_id' =>
                $idUnidad,

            ':id_actual' =>
                $idRelacion

        ]);


        $otroReceptor =
            $stmtOtroReceptor->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$otroReceptor) {

            $conexion->rollBack();

            header(
                "Location: " .
                $urlRetorno .
                "&tipo=warning&texto=" .
                urlencode(
                    "No puede quitar la opción de recibir factura porque esta persona es actualmente el único receptor de facturación de la unidad."
                )
            );

            exit;
        }
    }


    // ======================================================
    // ACTUALIZAR RELACIÓN
    // ======================================================

    $sqlActualizar = "
        UPDATE residente

        SET
            tipo = :tipo,
            recibe_factura = :recibe_factura,
            fecha_desde = :fecha_desde

        WHERE
            id = :id
            AND activo = 1
            AND fecha_hasta IS NULL
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


    // ======================================================
    // CONFIRMAR
    // ======================================================

    $conexion->commit();


    header(
        "Location: " .
        $urlRetorno .
        "&tipo=success&texto=" .
        urlencode(
            "Relación actualizada correctamente."
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
    // RETORNO ERROR
    // ======================================================

    $urlError =
        isset($urlRetorno)
            ? $urlRetorno
            : BASE_URL .
              "configuracion/unidades.php";


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