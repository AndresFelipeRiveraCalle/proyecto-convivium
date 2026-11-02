<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/auth/requerir_sesion.php";
require_once ROOT_PATH . "/config/conexion.php";


//
// VALIDAR MÉTODO
//

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "configuracion/pagos.php");
    exit;
}


//
// FUNCIÓN DE REDIRECCIÓN
//

function redirigirPago(
    string $tipo,
    string $mensaje,
    int $idUnidad = 0
): void {
    $parametros = [
        'tipo' => $tipo,
        'mensaje' => $mensaje
    ];

    if ($idUnidad > 0) {
        $parametros['id_unidad'] = $idUnidad;
    }

    header(
        "Location: " .
        BASE_URL .
        "configuracion/registrar_pago.php?" .
        http_build_query($parametros)
    );

    exit;
}


//
// PROCESAR
//

$idUnidad = 0;

try {
    $idPago = isset($_POST['id_pago'])
        ? (int)$_POST['id_pago']
        : 0;

    $idUnidad = isset($_POST['id_unidad'])
        ? (int)$_POST['id_unidad']
        : 0;

    $fechaPago = trim($_POST['fecha_pago'] ?? '');
    $valorTexto = trim((string)($_POST['valor'] ?? ''));

    $medioPago = strtoupper(trim($_POST['medio_pago'] ?? ''));
    $origenPago = strtoupper(trim($_POST['origen_pago'] ?? ''));

    $referencia = trim($_POST['referencia'] ?? '');
    $referenciaExterna = trim($_POST['referencia_externa'] ?? '');
    $idExterno = trim($_POST['id_externo'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');


    //
    // VALIDACIONES GENERALES
    //

    if ($idUnidad <= 0) {
        throw new Exception("Debe seleccionar una unidad.");
    }

    if ($fechaPago === '') {
        throw new Exception("Debe indicar la fecha del pago.");
    }

    $fechaObjeto = DateTime::createFromFormat('Y-m-d', $fechaPago);

    if (
        !$fechaObjeto
        || $fechaObjeto->format('Y-m-d') !== $fechaPago
    ) {
        throw new Exception("La fecha del pago no es válida.");
    }

    if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $valorTexto)) {
        throw new Exception("El valor del pago no es válido.");
    }

    $valor = round((float)$valorTexto, 2);

    if ($valor <= 0) {
        throw new Exception("El valor del pago debe ser mayor que cero.");
    }


    //
    // VALIDAR MEDIO Y ORIGEN
    //

    $mediosPermitidos = [
        'EFECTIVO',
        'TRANSFERENCIA',
        'CONSIGNACION',
        'PSE',
        'TARJETA',
        'OTRO'
    ];

    if (!in_array($medioPago, $mediosPermitidos, true)) {
        throw new Exception("El medio de pago no es válido.");
    }

    $origenesPermitidos = [
        'MANUAL',
        'BANCO',
        'PASARELA'
    ];

    if (!in_array($origenPago, $origenesPermitidos, true)) {
        throw new Exception("El origen del pago no es válido.");
    }


    //
    // VALIDAR LONGITUDES
    //

    if (mb_strlen($referencia, 'UTF-8') > 100) {
        throw new Exception(
            "La referencia no puede superar los 100 caracteres."
        );
    }

    if (mb_strlen($referenciaExterna, 'UTF-8') > 150) {
        throw new Exception(
            "La referencia externa no puede superar los 150 caracteres."
        );
    }

    if (mb_strlen($idExterno, 'UTF-8') > 150) {
        throw new Exception(
            "El ID externo no puede superar los 150 caracteres."
        );
    }

    if (mb_strlen($observaciones, 'UTF-8') > 255) {
        throw new Exception(
            "Las observaciones no pueden superar los 255 caracteres."
        );
    }


    //
    // TRANSACCIÓN
    //

    $conexion->beginTransaction();


    //
    // VERIFICAR Y BLOQUEAR UNIDAD
    //

    $sqlUnidad = "
        SELECT id_unidad
        FROM unidades
        WHERE id_unidad = :id_unidad
          AND activo = 1
        LIMIT 1
        FOR UPDATE
    ";

    $stmtUnidad = $conexion->prepare($sqlUnidad);
    $stmtUnidad->execute([
        ':id_unidad' => $idUnidad
    ]);

    if (!$stmtUnidad->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception(
            "La unidad seleccionada no existe o está inactiva."
        );
    }


    //
    // VERIFICAR REFERENCIA DUPLICADA
    //

    if ($referencia !== '') {
        $sqlDuplicado = "
            SELECT id_pago
            FROM pagos
            WHERE id_unidad = :id_unidad
              AND referencia = :referencia
              AND estado = 'REGISTRADO'
              AND id_pago <> :id_pago
            LIMIT 1
            FOR UPDATE
        ";

        $stmtDuplicado = $conexion->prepare($sqlDuplicado);
        $stmtDuplicado->execute([
            ':id_unidad' => $idUnidad,
            ':referencia' => $referencia,
            ':id_pago' => $idPago
        ]);

        if ($stmtDuplicado->fetch(PDO::FETCH_ASSOC)) {
            throw new Exception(
                "Ya existe un pago registrado para esta unidad con la referencia indicada."
            );
        }
    }


    //
    // EDITAR PAGO
    //

    if ($idPago > 0) {
        $sqlPagoActual = "
            SELECT
                p.id_pago,
                p.id_unidad,
                p.valor,
                p.estado,
                p.estado_conciliacion,
                p.id_extracto,

                COALESCE(
                    (
                        SELECT SUM(ap.valor_aplicado)
                        FROM aplicaciones_pagos ap
                        WHERE ap.id_pago = p.id_pago
                    ),
                    0
                ) AS valor_aplicado,

                COALESCE(
                    (
                        SELECT SUM(sf.valor_original)
                        FROM saldo_favor sf
                        WHERE sf.id_pago = p.id_pago
                          AND sf.estado <> 'ANULADO'
                    ),
                    0
                ) AS saldo_favor_generado

            FROM pagos p
            WHERE p.id_pago = :id_pago
            LIMIT 1
            FOR UPDATE
        ";

        $stmtPagoActual = $conexion->prepare($sqlPagoActual);
        $stmtPagoActual->execute([
            ':id_pago' => $idPago
        ]);

        $pagoActual = $stmtPagoActual->fetch(PDO::FETCH_ASSOC);

        if (!$pagoActual) {
            throw new Exception("El pago que intenta editar no existe.");
        }

        if ($pagoActual['estado'] !== 'REGISTRADO') {
            throw new Exception("El pago está anulado y no puede ser editado.");
        }

        if (
            !empty($pagoActual['id_extracto'])
            || $pagoActual['estado_conciliacion'] !== 'PENDIENTE'
        ) {
            throw new Exception(
                "No puede editar un pago conciliado o vinculado a un movimiento bancario."
            );
        }

        $valorAplicado = (float)$pagoActual['valor_aplicado'];
        $saldoFavorGenerado = (float)$pagoActual['saldo_favor_generado'];

        $tieneMovimientos =
            $valorAplicado > 0.009
            || $saldoFavorGenerado > 0.009;

        if ($tieneMovimientos) {
            if ($idUnidad !== (int)$pagoActual['id_unidad']) {
                throw new Exception(
                    "No puede cambiar la unidad porque el pago ya tiene aplicaciones o saldo a favor."
                );
            }

            if (abs($valor - (float)$pagoActual['valor']) > 0.009) {
                throw new Exception(
                    "No puede cambiar el valor porque el pago ya tiene aplicaciones o saldo a favor."
                );
            }
        }

        $sqlActualizar = "
            UPDATE pagos
            SET
                id_unidad = :id_unidad,
                fecha_pago = :fecha_pago,
                valor = :valor,
                medio_pago = :medio_pago,
                origen_pago = :origen_pago,
                referencia = :referencia,
                referencia_externa = :referencia_externa,
                id_externo = :id_externo,
                observaciones = :observaciones
            WHERE id_pago = :id_pago
              AND estado = 'REGISTRADO'
              AND estado_conciliacion = 'PENDIENTE'
              AND id_extracto IS NULL
        ";

        $stmtActualizar = $conexion->prepare($sqlActualizar);
        $stmtActualizar->execute([
            ':id_unidad' => $idUnidad,
            ':fecha_pago' => $fechaPago,
            ':valor' => $valor,
            ':medio_pago' => $medioPago,
            ':origen_pago' => $origenPago,
            ':referencia' => $referencia !== '' ? $referencia : null,
            ':referencia_externa' =>
                $referenciaExterna !== '' ? $referenciaExterna : null,
            ':id_externo' => $idExterno !== '' ? $idExterno : null,
            ':observaciones' => $observaciones !== '' ? $observaciones : null,
            ':id_pago' => $idPago
        ]);


        $conexion->commit();

        redirigirPago(
            'success',
            'Pago actualizado correctamente.',
            $idUnidad
        );
    }


    //
    // REGISTRAR NUEVO PAGO
    //

    $sqlInsertar = "
        INSERT INTO pagos
        (
            id_unidad,
            fecha_pago,
            valor,
            medio_pago,
            origen_pago,
            estado_conciliacion,
            referencia,
            referencia_externa,
            id_externo,
            observaciones,
            estado
        )
        VALUES
        (
            :id_unidad,
            :fecha_pago,
            :valor,
            :medio_pago,
            :origen_pago,
            'PENDIENTE',
            :referencia,
            :referencia_externa,
            :id_externo,
            :observaciones,
            'REGISTRADO'
        )
    ";

    $stmtInsertar = $conexion->prepare($sqlInsertar);
    $stmtInsertar->execute([
        ':id_unidad' => $idUnidad,
        ':fecha_pago' => $fechaPago,
        ':valor' => $valor,
        ':medio_pago' => $medioPago,
        ':origen_pago' => $origenPago,
        ':referencia' => $referencia !== '' ? $referencia : null,
        ':referencia_externa' =>
            $referenciaExterna !== '' ? $referenciaExterna : null,
        ':id_externo' => $idExterno !== '' ? $idExterno : null,
        ':observaciones' => $observaciones !== '' ? $observaciones : null
    ]);

    $nuevoIdPago = (int)$conexion->lastInsertId();

    if ($nuevoIdPago <= 0) {
        throw new Exception("No fue posible registrar el pago.");
    }

    $conexion->commit();

    redirigirPago(
        'success',
        'Pago registrado correctamente. ID de pago: #' . $nuevoIdPago,
        $idUnidad
    );


} catch (Throwable $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    redirigirPago(
        'error',
        $e->getMessage(),
        $idUnidad
    );
}