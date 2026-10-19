<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/configuracion_mora.php"
    );

    exit;
}


// ==========================================================
// DATOS
// ==========================================================

$nombre =
    trim($_POST['nombre'] ?? '');

$idConcepto =
    isset($_POST['id_concepto'])
        ? (int)$_POST['id_concepto']
        : 0;

$idTasaInteres =
    isset($_POST['id_tasa_interes'])
        ? (int)$_POST['id_tasa_interes']
        : 0;

$fechaInicio =
    trim($_POST['fecha_inicio'] ?? '');

$fechaFin =
    trim($_POST['fecha_fin'] ?? '');

$estado =
    isset($_POST['estado'])
        ? (int)$_POST['estado']
        : 1;

$observaciones =
    trim($_POST['observaciones'] ?? '');


// ==========================================================
// VALIDACIONES
// ==========================================================

if (
    $nombre === ''
    ||
    $idConcepto <= 0
    ||
    $idTasaInteres <= 0
    ||
    $fechaInicio === ''
    ||
    !in_array($estado, [0, 1], true)
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/configuracion_mora.php?" .
        http_build_query([
            'tipo'  => 'warning',
            'texto' => 'Complete los campos obligatorios.'
        ])
    );

    exit;
}


if (
    $fechaFin !== ''
    &&
    $fechaFin < $fechaInicio
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/configuracion_mora.php?" .
        http_build_query([
            'tipo'  => 'warning',
            'texto' => 'La fecha fin no puede ser anterior a la fecha inicio.'
        ])
    );

    exit;
}


try {

    $conexion->beginTransaction();


    // ======================================================
    // VALIDAR TASA
    // ======================================================

    $sqlTasa = "
        SELECT
            id_tasa_interes,
            tasa_mensual,
            activo

        FROM tasas_interes

        WHERE id_tasa_interes =
              :id_tasa_interes

        LIMIT 1

        FOR UPDATE
    ";

    $stmtTasa =
        $conexion->prepare($sqlTasa);

    $stmtTasa->execute([
        ':id_tasa_interes' =>
            $idTasaInteres
    ]);

    $tasa =
        $stmtTasa->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$tasa) {
        throw new Exception(
            'La tasa de interés seleccionada no existe.'
        );
    }


    if ((int)$tasa['activo'] !== 1) {
        throw new Exception(
            'La tasa de interés seleccionada está inactiva.'
        );
    }


    // ======================================================
    // VALIDAR CONCEPTO
    // ======================================================

    $sqlConcepto = "
        SELECT
            id_concepto

        FROM conceptos_facturacion

        WHERE
            id_concepto = :id_concepto
            AND estado = 1

        LIMIT 1
    ";

    $stmtConcepto =
        $conexion->prepare($sqlConcepto);

    $stmtConcepto->execute([
        ':id_concepto' => $idConcepto
    ]);


    if (!$stmtConcepto->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception(
            'El concepto seleccionado no existe o está inactivo.'
        );
    }


    // ======================================================
    // INSERTAR
    // ======================================================

    $sql = "
        INSERT INTO configuracion_mora
        (
            nombre,
            tipo_tasa,
            tasa,
            periodicidad,
            dias_gracia,
            aplicar_desde,
            id_concepto,
            id_tasa_interes,
            fecha_inicio,
            fecha_fin,
            estado,
            observaciones
        )
        VALUES
        (
            :nombre,
            'PORCENTAJE',
            :tasa,
            'MENSUAL',
            0,
            'DIA_SIGUIENTE_VENCIMIENTO',
            :id_concepto,
            :id_tasa_interes,
            :fecha_inicio,
            :fecha_fin,
            :estado,
            :observaciones
        )
    ";

    $stmt =
        $conexion->prepare($sql);

    $stmt->execute([
        ':nombre' =>
            $nombre,

        ':tasa' =>
            $tasa['tasa_mensual'],

        ':id_concepto' =>
            $idConcepto,

        ':id_tasa_interes' =>
            $idTasaInteres,

        ':fecha_inicio' =>
            $fechaInicio,

        ':fecha_fin' =>
            $fechaFin !== ''
                ? $fechaFin
                : null,

        ':estado' =>
            $estado,

        ':observaciones' =>
            $observaciones !== ''
                ? $observaciones
                : null
    ]);


    $conexion->commit();


    header(
        "Location: " .
        BASE_URL .
        "configuracion/configuracion_mora.php?" .
        http_build_query([
            'tipo'  => 'success',
            'texto' => 'Configuración de mora registrada correctamente.'
        ])
    );

    exit;


} catch (Throwable $e) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }


    header(
        "Location: " .
        BASE_URL .
        "configuracion/configuracion_mora.php?" .
        http_build_query([
            'tipo'  => 'error',
            'texto' => $e->getMessage()
        ])
    );

    exit;
}
