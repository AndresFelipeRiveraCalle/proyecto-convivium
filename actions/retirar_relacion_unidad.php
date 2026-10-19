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
// VALIDAR ID
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


// ==========================================================
// VALIDAR FECHA
// ==========================================================

$fechaObj = DateTime::createFromFormat(
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
    // INICIAR TRANSACCIÓN
    // ======================================================

    $conexion->beginTransaction();


    // ======================================================
    // CONSULTAR RELACIÓN
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
    // VALIDAR SI YA ESTÁ RETIRADA
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
                "La persona ya fue retirada de esta unidad."
            )
        );

        exit;
    }


    // ======================================================
    // VALIDAR FECHA DESDE
    // ======================================================

    $fechaDesde =
        date(
            'Y-m-d',
            strtotime(
                $relacion['fecha_desde']
            )
        );


    if ($fechaHasta < $fechaDesde) {

        $conexion->rollBack();

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
    // VALIDAR ÚLTIMO RECEPTOR DE FACTURA
    // ======================================================

    if (
        (int)$relacion['recibe_factura'] === 1
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
                    "No puede retirar esta persona porque la unidad quedaría sin alguien marcado para recibir la factura."
                )
            );

            exit;
        }
    }


    // ======================================================
    // RETIRAR RELACIÓN
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


    if ($stmtRetirar->rowCount() !== 1) {

        throw new Exception(
            "No fue posible cerrar la relación."
        );
    }


    // ======================================================
    // CONFIRMAR
    // ======================================================

    $conexion->commit();


    header(
        "Location: " .
        $urlRetorno .
        "&tipo=success&texto=" .
        urlencode(
            "Persona retirada de la unidad correctamente."
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
            "No fue posible retirar la persona."
        )
    );

    exit;
}