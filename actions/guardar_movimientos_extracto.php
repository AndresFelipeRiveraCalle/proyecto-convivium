<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/auth/requerir_sesion.php";
require_once ROOT_PATH . "/config/conexion.php";

function redirigirGuardarMovimientos(
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
    redirigirGuardarMovimientos(
        'error',
        'Solicitud no válida.'
    );
}


// DATOS RECIBIDOS

$idDocumento = filter_input(
    INPUT_POST,
    'id_documento',
    FILTER_VALIDATE_INT
);

$reprocesarRecibido = $_POST['reprocesar'] ?? '0';

$movimientos = $_POST['movimientos'] ?? [];

if (
    !$idDocumento
    || $idDocumento <= 0
) {
    redirigirGuardarMovimientos(
        'error',
        'Documento bancario no válido.'
    );
}

if (!in_array($reprocesarRecibido, ['0', '1'], true)) {
    redirigirGuardarMovimientos(
        'error',
        'La opción de procesamiento no es válida.'
    );
}

$reprocesar = $reprocesarRecibido === '1';

if (
    !is_array($movimientos)
    || empty($movimientos)
) {
    redirigirGuardarMovimientos(
        'error',
        'No se recibieron movimientos para guardar.'
    );
}


try {
    $conexion->beginTransaction();

    // BLOQUEAR Y VALIDAR DOCUMENTO

    $sqlDocumento = "
        SELECT
            id_documento,
            id_cuenta_bancaria,
            nombre_original,
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
            'El documento ya fue procesado o no está listo para guardar.'
        );
    }

    $idCuenta = (int)$documento['id_cuenta_bancaria'];

    // BLOQUEAR MOVIMIENTOS ANTERIORES Y DETECTAR PROTECCIONES

    $sqlMovimientosAnteriores = "
        SELECT
            e.id_extracto,
            e.estado_conciliacion,
            EXISTS (
                SELECT 1
                FROM pagos p
                WHERE p.id_extracto = e.id_extracto
            ) AS tiene_pago
        FROM extractos_bancarios e
        WHERE e.id_documento = :id_documento
        FOR UPDATE
    ";

    $stmtMovimientosAnteriores =
        $conexion->prepare($sqlMovimientosAnteriores);

    $stmtMovimientosAnteriores->execute([
        ':id_documento' => $idDocumento
    ]);

    $movimientosAnteriores =
        $stmtMovimientosAnteriores->fetchAll(PDO::FETCH_ASSOC);

    $hayMovimientosProtegidos = false;

    foreach ($movimientosAnteriores as $movimientoAnterior) {
        if (
            $movimientoAnterior['estado_conciliacion'] === 'CONCILIADO'
            || (int)$movimientoAnterior['tiene_pago'] > 0
        ) {
            $hayMovimientosProtegidos = true;
            break;
        }
    }

    if ($hayMovimientosProtegidos) {
        throw new Exception(
            'No se pueden reemplazar los movimientos: hay movimientos conciliados o vinculados a pagos.'
        );
    }

    if (
        !$reprocesar
        && !empty($movimientosAnteriores)
    ) {
        throw new Exception(
            'El documento ya tiene movimientos. Debes iniciar el reproceso para reemplazarlos.'
        );
    }

    // ELIMINAR LOS MOVIMIENTOS ANTERIORES SOLO EN MODO REPROCESO
    // Esta operación se revertirá si falla cualquier inserción posterior.

    if ($reprocesar && !empty($movimientosAnteriores)) {
        $sqlEliminarAnteriores = "
            DELETE FROM extractos_bancarios
            WHERE id_documento = :id_documento
        ";

        $stmtEliminarAnteriores =
            $conexion->prepare($sqlEliminarAnteriores);

        $stmtEliminarAnteriores->execute([
            ':id_documento' => $idDocumento
        ]);
    }

    // PREPARAR INSERCIÓN

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

    $stmtInsert = $conexion->prepare($sqlInsert);
    $cantidadGuardada = 0;

    // GUARDAR MOVIMIENTOS RECIBIDOS

    foreach ($movimientos as $movimiento) {
        if (!is_array($movimiento)) {
            continue;
        }

        $fecha =
            trim((string)($movimiento['fecha_movimiento'] ?? ''));

        $fechaValor =
            trim((string)($movimiento['fecha_valor'] ?? $fecha));

        $numeroDocumento =
            trim((string)($movimiento['numero_documento'] ?? ''));

        $descripcion =
            trim((string)($movimiento['descripcion'] ?? ''));

        $valorRecibido = $movimiento['valor'] ?? null;

        $tipoMovimiento = strtoupper(
            trim((string)($movimiento['tipo_movimiento'] ?? ''))
        );

        if (
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)
            || !checkdate(
                (int)substr($fecha, 5, 2),
                (int)substr($fecha, 8, 2),
                (int)substr($fecha, 0, 4)
            )
        ) {
            continue;
        }

        if (
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaValor)
            || !checkdate(
                (int)substr($fechaValor, 5, 2),
                (int)substr($fechaValor, 8, 2),
                (int)substr($fechaValor, 0, 4)
            )
        ) {
            $fechaValor = $fecha;
        }

        if ($descripcion === '' || !is_numeric($valorRecibido)) {
            continue;
        }

        $valor = round((float)$valorRecibido, 2);

        if (
            !is_finite($valor)
            || $valor <= 0
            || !in_array(
                $tipoMovimiento,
                ['INGRESO', 'EGRESO'],
                true
            )
        ) {
            continue;
        }

        $stmtInsert->execute([
            ':id_documento' => $idDocumento,
            ':id_cuenta_bancaria' => $idCuenta,
            ':fecha_movimiento' => $fecha,
            ':fecha_valor' => $fechaValor,
            ':descripcion' => mb_substr($descripcion, 0, 255),
            ':referencia' => mb_substr($numeroDocumento, 0, 150),
            ':numero_documento' => mb_substr($numeroDocumento, 0, 100),
            ':valor' => $valor,
            ':tipo_movimiento' => $tipoMovimiento,
            ':archivo_origen' => mb_substr(
                (string)$documento['nombre_original'],
                0,
                255
            ),
            ':observaciones' =>
                'Movimiento importado desde extracto bancario.'
        ]);

        $cantidadGuardada++;
    }

    if ($cantidadGuardada === 0) {
        throw new Exception(
            'No fue posible guardar ningún movimiento válido.'
        );
    }

    // MARCAR DOCUMENTO COMO PROCESADO

    $sqlActualizarDocumento = "
        UPDATE documentos_bancarios
        SET
            estado_procesamiento = 'PROCESADO',
            fecha_procesamiento = NOW(),
            observaciones = :observaciones
        WHERE
            id_documento = :id_documento
            AND estado_procesamiento = 'PROCESANDO'
    ";

    $stmtActualizarDocumento =
        $conexion->prepare($sqlActualizarDocumento);

    $stmtActualizarDocumento->execute([
        ':observaciones' =>
            'Movimientos confirmados y guardados correctamente: ' .
            $cantidadGuardada,
        ':id_documento' => $idDocumento
    ]);

    if ($stmtActualizarDocumento->rowCount() !== 1) {
        throw new Exception(
            'No fue posible confirmar el procesamiento del documento.'
        );
    }

    // CONFIRMAR
    $conexion->commit();

    $mensaje = $reprocesar
        ? 'Se reemplazaron correctamente los movimientos. Total: ' .
            $cantidadGuardada . '.'
        : 'Se guardaron correctamente ' .
            $cantidadGuardada . ' movimiento(s) bancario(s).';

    redirigirGuardarMovimientos(
        'success',
        $mensaje
    );

} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    redirigirGuardarMovimientos(
        'error',
        $e->getMessage()
    );
}

