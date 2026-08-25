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
        "configuracion/extractos_bancarios.php" .
        "?tipo=error&mensaje=" .
        urlencode("Solicitud no válida.")
    );

    exit;
}


// ==========================================================
// DATOS RECIBIDOS
// ==========================================================

$idDocumento =
    filter_input(
        INPUT_POST,
        'id_documento',
        FILTER_VALIDATE_INT
    );

$idCuenta =
    filter_input(
        INPUT_POST,
        'id_cuenta_bancaria',
        FILTER_VALIDATE_INT
    );

$movimientos =
    $_POST['movimientos'] ?? [];


// ==========================================================
// VALIDAR DOCUMENTO
// ==========================================================

if (!$idDocumento) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=error&mensaje=" .
        urlencode("Documento bancario no válido.")
    );

    exit;
}


// ==========================================================
// VALIDAR MOVIMIENTOS
// ==========================================================

if (
    !is_array($movimientos) ||
    empty($movimientos)
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=error&mensaje=" .
        urlencode("No se recibieron movimientos para guardar.")
    );

    exit;
}


// ==========================================================
// BUSCAR DOCUMENTO
// ==========================================================

$sqlDocumento = "
    SELECT
        id_documento,
        id_cuenta_bancaria,
        nombre_original
    FROM documentos_bancarios
    WHERE id_documento = :id_documento
    LIMIT 1
";

$stmtDocumento =
    $conexion->prepare(
        $sqlDocumento
    );

$stmtDocumento->execute([
    ':id_documento' => $idDocumento
]);

$documento =
    $stmtDocumento->fetch(
        PDO::FETCH_ASSOC
    );


if (!$documento) {

    throw new Exception(
        "El documento bancario no existe."
    );
}


// ==========================================================
// CUENTA BANCARIA
// ==========================================================

$idCuenta =
    (int)$documento['id_cuenta_bancaria'];


// ==========================================================
// TRANSACCIÓN
// ==========================================================

try {

    $conexion->beginTransaction();


    // ======================================================
    // PREPARAR INSERT
    // ======================================================

    $sqlInsert = "
        INSERT INTO extractos_bancarios (
            id_documento,
            id_cuenta_bancaria,
            fecha_movimiento,
            fecha_valor,
            descripcion,
            referencia,
            numero_documento,
            valor,
            tipo_movimiento,
            estado_conciliacion,
            archivo_origen,
            observaciones
        )
        VALUES (
            :id_documento,
            :id_cuenta_bancaria,
            :fecha_movimiento,
            :fecha_valor,
            :descripcion,
            :referencia,
            :numero_documento,
            :valor,
            :tipo_movimiento,
            'PENDIENTE',
            :archivo_origen,
            :observaciones
        )
    ";


    $stmtInsert =
        $conexion->prepare(
            $sqlInsert
        );


    $cantidadGuardada = 0;


    // ======================================================
    // RECORRER MOVIMIENTOS
    // ======================================================

    foreach ($movimientos as $movimiento) {

        $fecha =
            trim(
                $movimiento['fecha_movimiento']
                ?? ''
            );

        $numeroDocumento =
            trim(
                $movimiento['numero_documento']
                ?? ''
            );

        $descripcion =
            trim(
                $movimiento['descripcion']
                ?? ''
            );

        $valor =
            (float)(
                $movimiento['valor']
                ?? 0
            );

        $tipoMovimiento =
            strtoupper(
                trim(
                    $movimiento['tipo_movimiento']
                    ?? ''
                )
            );


        // ==================================================
        // VALIDACIONES
        // ==================================================

        if (!$fecha) {
            continue;
        }

        if (!$descripcion) {
            continue;
        }

        if ($valor <= 0) {
            continue;
        }

        if (
            !in_array(
                $tipoMovimiento,
                ['INGRESO', 'EGRESO'],
                true
            )
        ) {
            continue;
        }


        // ==================================================
        // INSERTAR
        // ==================================================

        $stmtInsert->execute([

            ':id_documento' =>
                $idDocumento,

            ':id_cuenta_bancaria' =>
                $idCuenta,

            ':fecha_movimiento' =>
                $fecha,

            ':fecha_valor' =>
                $fecha,

            ':descripcion' =>
                mb_substr(
                    $descripcion,
                    0,
                    255
                ),

            ':referencia' =>
                mb_substr(
                    $numeroDocumento,
                    0,
                    150
                ),

            ':numero_documento' =>
                mb_substr(
                    $numeroDocumento,
                    0,
                    100
                ),

            ':valor' =>
                $valor,

            ':tipo_movimiento' =>
                $tipoMovimiento,

            ':archivo_origen' =>
                mb_substr(
                    $documento['nombre_original'],
                    0,
                    255
                ),

            ':observaciones' =>
                'Movimiento importado desde extracto bancario.'

        ]);


        $cantidadGuardada++;
    }


    // ======================================================
    // VALIDAR INSERTS
    // ======================================================

    if ($cantidadGuardada === 0) {

        throw new Exception(
            "No fue posible guardar ningún movimiento."
        );
    }


    // ======================================================
    // MARCAR DOCUMENTO COMO PROCESADO
    // ======================================================

    $sqlActualizarDocumento = "
        UPDATE documentos_bancarios
        SET
            estado_procesamiento = 'PROCESADO',
            fecha_procesamiento = NOW(),
            observaciones = :observaciones
        WHERE id_documento = :id_documento
    ";

    $stmtActualizarDocumento =
        $conexion->prepare(
            $sqlActualizarDocumento
        );

    $stmtActualizarDocumento->execute([

        ':observaciones' =>
            'Movimientos confirmados y guardados correctamente: ' .
            $cantidadGuardada,

        ':id_documento' =>
            $idDocumento

    ]);


    // ======================================================
    // CONFIRMAR TRANSACCIÓN
    // ======================================================

    $conexion->commit();


    // ======================================================
    // REGRESAR
    // ======================================================

    header(
        "Location: " .
        BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=success&mensaje=" .
        urlencode(
            "Se guardaron correctamente " .
            $cantidadGuardada .
            " movimiento(s) bancario(s)."
        )
    );

    exit;


} catch (Throwable $e) {

    // ======================================================
    // DESHACER TRANSACCIÓN
    // ======================================================

    if (
        $conexion->inTransaction()
    ) {

        $conexion->rollBack();
    }


    // ======================================================
    // ERROR
    // ======================================================

    header(
        "Location: " .
        BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=error&mensaje=" .
        urlencode(
            $e->getMessage()
        )
    );

    exit;
}