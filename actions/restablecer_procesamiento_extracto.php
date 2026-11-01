<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/auth/requerir_sesion.php";
require_once ROOT_PATH . "/config/conexion.php";

function redirigirRestablecerExtracto(
    string $tipo,
    string $mensaje
): void {
    header(
        "Location: " . BASE_URL .
        "configuracion/extractos_bancarios.php?" .
        http_build_query([
            'tipo' => $tipo,
            'mensaje' => $mensaje
        ])
    );
    exit;
}


// VALIDAR MÉTODO

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigirRestablecerExtracto(
        'error',
        'Solicitud no válida.'
    );
}


// VALIDAR DATOS

$idDocumento = filter_input(
    INPUT_POST,
    'id_documento',
    FILTER_VALIDATE_INT
);

$reprocesarRecibido = $_POST['reprocesar'] ?? '0';

if (!$idDocumento || $idDocumento <= 0) {
    redirigirRestablecerExtracto(
        'error',
        'Documento bancario no válido.'
    );
}

if (!in_array($reprocesarRecibido, ['0', '1'], true)) {
    redirigirRestablecerExtracto(
        'error',
        'La opción de procesamiento no es válida.'
    );
}

$reprocesar = $reprocesarRecibido === '1';


try {
    $conexion->beginTransaction();

    // BLOQUEAR DOCUMENTO

    $sqlDocumento = "
        SELECT
            id_documento,
            estado_procesamiento
        FROM documentos_bancarios
        WHERE id_documento = :id_documento
        LIMIT 1
        FOR UPDATE
    ";

    $stmtDocumento = $conexion->prepare($sqlDocumento);
    $stmtDocumento->execute([
        ':id_documento' => $idDocumento
    ]);

    $documento = $stmtDocumento->fetch(PDO::FETCH_ASSOC);

    if (!$documento) {
        throw new Exception(
            'El documento bancario no existe.'
        );
    }

    if ($documento['estado_procesamiento'] !== 'PROCESANDO') {
        throw new Exception(
            'El documento ya no está en proceso.'
        );
    }

    // BLOQUEAR Y CONTAR MOVIMIENTOS
    // En un primer procesamiento no debe haber movimientos previos.
    // En un reproceso se conservan los registros existentes al cancelar.

    $sqlMovimientos = "
        SELECT id_extracto
        FROM extractos_bancarios
        WHERE id_documento = :id_documento
        FOR UPDATE
    ";

    $stmtMovimientos = $conexion->prepare($sqlMovimientos);
    $stmtMovimientos->execute([
        ':id_documento' => $idDocumento
    ]);

    $movimientosExistentes =
        $stmtMovimientos->fetchAll(PDO::FETCH_COLUMN);

    if (!$reprocesar && !empty($movimientosExistentes)) {
        throw new Exception(
            'El documento tiene movimientos guardados. No se restableció para evitar inconsistencias.'
        );
    }

    $estadoRestaurado = $reprocesar
        ? 'PROCESADO'
        : 'PENDIENTE';

    $observaciones = $reprocesar
        ? 'Reproceso cancelado. Se conservaron los movimientos anteriores.'
        : 'Proceso OCR cancelado o liberado para reintento.';

    $sqlActualizar = "
        UPDATE documentos_bancarios
        SET
            estado_procesamiento = :estado_restaurado,
            fecha_procesamiento = CASE
                WHEN :modo_reproceso = '1'
                    THEN fecha_procesamiento
                ELSE NULL
            END,
            observaciones = :observaciones
        WHERE
            id_documento = :id_documento
            AND estado_procesamiento = 'PROCESANDO'
    ";

    $stmtActualizar = $conexion->prepare($sqlActualizar);
    $stmtActualizar->execute([
        ':estado_restaurado' => $estadoRestaurado,
        ':modo_reproceso' => $reprocesarRecibido,
        ':observaciones' => $observaciones,
        ':id_documento' => $idDocumento
    ]);

    if ($stmtActualizar->rowCount() !== 1) {
        throw new Exception(
            'No fue posible restablecer el documento.'
        );
    }

    $conexion->commit();

    $mensaje = $reprocesar
        ? 'Reproceso cancelado. El documento conserva sus movimientos anteriores.'
        : 'Documento restablecido. Puedes procesarlo nuevamente.';

    redirigirRestablecerExtracto(
        'success',
        $mensaje
    );

} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    redirigirRestablecerExtracto(
        'error',
        $e->getMessage()
    );
}

