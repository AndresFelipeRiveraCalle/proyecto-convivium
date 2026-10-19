<?php
/**
 * ==============================================================
 * CONVIVIUM - GUARDAR PAGO
 * ==============================================================
 * Archivo: actions/guardar_pago.php
 *
 * Objetivo:
 * - Validar los datos enviados desde registrar_pago.php.
 * - Crear UN nuevo registro en la tabla `pagos`.
 * - Dejarlo en estado REGISTRADO y conciliación PENDIENTE.
 * - Redirigir a aplicar_pago.php para que el usuario decida dónde aplicarlo.
 *
 * MUY IMPORTANTE:
 * Este archivo registra dinero recibido, pero NO modifica `cartera` y NO crea
 * filas en `aplicaciones_pagos`. Esa responsabilidad pertenece a las acciones
 * de aplicación de pagos. Mantener esta separación permite registrar varios
 * abonos independientes sobre una misma obligación.
 * ==============================================================
 */

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

// Centraliza los mensajes de error/advertencia y conserva la unidad seleccionada.
// Así el usuario vuelve al formulario sin perder el contexto cuando algo falla.
function redireccionar($tipo, $texto, $idUnidad = 0)
{
    $params = [
        'tipo' => $tipo,
        'texto' => $texto
    ];

    if ($idUnidad > 0) {
        $params['id_unidad'] = $idUnidad;
    }

    // ======================================================
    // 5. REDIRIGIR AL DETALLE DEL NUEVO PAGO
    // ======================================================
    header(
        "Location: " .
        BASE_URL .
        "configuracion/registrar_pago.php?" .
        http_build_query($params)
    );
    exit;
}

// Esta acción solo debe ejecutarse mediante el envío del formulario.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redireccionar('error', 'Solicitud no válida.');
}

// ==========================================================
// 1. RECIBIR Y NORMALIZAR DATOS DEL FORMULARIO
// ==========================================================
// FILTER_VALIDATE_INT evita aceptar una unidad con un identificador inválido.
$idUnidad = filter_input(INPUT_POST, 'id_unidad', FILTER_VALIDATE_INT);
$fechaPago = trim($_POST['fecha_pago'] ?? '');
$valor = (float)($_POST['valor'] ?? 0);
$medioPago = trim($_POST['medio_pago'] ?? '');
$origenPago = trim($_POST['origen_pago'] ?? 'MANUAL');
$referencia = trim($_POST['referencia'] ?? '');
$referenciaExterna = trim($_POST['referencia_externa'] ?? '');
$idExterno = trim($_POST['id_externo'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');

// ==========================================================
// 2. VALIDACIONES DE NEGOCIO BÁSICAS
// ==========================================================
if (!$idUnidad) {
    redireccionar('warning', 'Debe seleccionar una unidad.');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaPago)) {
    redireccionar('warning', 'La fecha del pago no es válida.', (int)$idUnidad);
}

if ($valor <= 0) {
    redireccionar('warning', 'El valor del pago debe ser mayor que cero.', (int)$idUnidad);
}

$mediosPermitidos = [
    'EFECTIVO',
    'TRANSFERENCIA',
    'CONSIGNACION',
    'PSE',
    'TARJETA',
    'OTRO'
];

if (!in_array($medioPago, $mediosPermitidos, true)) {
    redireccionar('warning', 'El medio de pago no es válido.', (int)$idUnidad);
}

$origenesPermitidos = ['MANUAL', 'BANCO', 'PASARELA'];

if (!in_array($origenPago, $origenesPermitidos, true)) {
    redireccionar('warning', 'El origen del pago no es válido.', (int)$idUnidad);
}

// ==========================================================
// 3. VALIDAR QUE LA UNIDAD EXISTA Y ESTÉ ACTIVA
// ==========================================================
try {
    $stmtUnidad = $conexion->prepare(
        "SELECT id_unidad FROM unidades WHERE id_unidad = :id_unidad AND activo = 1 LIMIT 1"
    );
    $stmtUnidad->execute([':id_unidad' => $idUnidad]);

    if (!$stmtUnidad->fetchColumn()) {
        redireccionar('error', 'La unidad seleccionada no existe o está inactiva.');
    }

    /*
     * IMPORTANTE:
     * Este proceso SOLO registra el pago.
     * No lo aplica automáticamente a cartera.
     * Así podemos tener varios pagos/abonos independientes y decidir
     * posteriormente cómo aplicarlos desde aplicar_pago.php.
     */
    // ======================================================
    // 4. INSERTAR EL PAGO SIN APLICARLO A CARTERA
    // ======================================================
    // El valor completo queda disponible hasta que se creen aplicaciones.
    $sql = "
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

    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        ':id_unidad' => $idUnidad,
        ':fecha_pago' => $fechaPago,
        ':valor' => round($valor, 2),
        ':medio_pago' => $medioPago,
        ':origen_pago' => $origenPago,
        ':referencia' => $referencia !== '' ? $referencia : null,
        ':referencia_externa' => $referenciaExterna !== '' ? $referenciaExterna : null,
        ':id_externo' => $idExterno !== '' ? $idExterno : null,
        ':observaciones' => $observaciones !== '' ? $observaciones : null
    ]);

    // Guardamos el ID recién creado para enviarlo a la pantalla de aplicación.
    $idPago = (int)$conexion->lastInsertId();

    header(
        "Location: " .
        BASE_URL .
        "configuracion/aplicar_pago.php?" .
        http_build_query([
            'id_pago' => $idPago,
            'tipo' => 'success',
            'texto' => 'Pago registrado correctamente. Ahora puede aplicarlo a la cartera.'
        ])
    );
    exit;

} catch (Throwable $e) {
    redireccionar(
        'error',
        'No fue posible registrar el pago. ' . $e->getMessage(),
        (int)$idUnidad
    );
}
