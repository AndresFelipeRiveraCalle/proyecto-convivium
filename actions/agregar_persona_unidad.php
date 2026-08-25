<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

try {

    // ==========================================================
    // DATOS DEL FORMULARIO
    // ==========================================================

    $unidad_id = isset($_POST['unidad_id'])
        ? (int) $_POST['unidad_id']
        : 0;

    $numero_documento = isset($_POST['numero_documento'])
        ? trim($_POST['numero_documento'])
        : '';

    $tipo = isset($_POST['tipo'])
        ? trim($_POST['tipo'])
        : '';

    $recibe_factura = isset($_POST['recibe_factura'])
        ? (int) $_POST['recibe_factura']
        : 0;

    $fecha_desde = !empty($_POST['fecha_desde'])
        ? $_POST['fecha_desde']
        : date('Y-m-d');


    // ==========================================================
    // VALIDAR UNIDAD
    // ==========================================================

    if ($unidad_id <= 0) {

        header(
            "Location: ../configuracion/unidades.php?tipo=warning&texto="
            . urlencode("La unidad no es válida.")
        );

        exit;
    }


    // ==========================================================
    // VALIDAR DOCUMENTO
    // ==========================================================

    if ($numero_documento === '') {

        header(
            "Location: ../configuracion/personas_unidad.php?id_unidad="
            . $unidad_id
            . "&tipo=warning&texto="
            . urlencode("Debe ingresar el número de documento.")
        );

        exit;
    }


    // ==========================================================
    // VALIDAR TIPO
    // ==========================================================

if (!in_array($tipo, ['propietario', 'inquilino', 'residente'], true)) {
    header(
        "Location: ../configuracion/personas_unidad.php?id_unidad="
        . $unidad_id
        . "&tipo=warning&texto=El tipo de relación no es válido."
    );

    exit;
}


    // ==========================================================
    // VALIDAR RECIBE FACTURA
    // ==========================================================

    if (!in_array($recibe_factura, [0, 1], true)) {
        $recibe_factura = 0;
    }


    // ==========================================================
    // VERIFICAR QUE LA UNIDAD EXISTA
    // ==========================================================

    $sql = "
        SELECT id_unidad
        FROM unidades
        WHERE id_unidad = :id_unidad
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ':id_unidad' => $unidad_id
    ]);

    $unidad = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$unidad) {

        header(
            "Location: ../configuracion/unidades.php?tipo=warning&texto="
            . urlencode("La unidad no existe.")
        );

        exit;
    }


    // ==========================================================
    // BUSCAR USUARIO POR DOCUMENTO
    // ==========================================================

    $sql = "SELECT id, nombres,apellidos,numero_documento,estado
        FROM usuario
        WHERE numero_documento = :numero_documento
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([':numero_documento' => $numero_documento]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);


    // ==========================================================
    // VALIDAR QUE EXISTA EL USUARIO
    // ==========================================================

    if (!$usuario) {

        header(
            "Location: ../configuracion/personas_unidad.php?id_unidad="
            . $unidad_id
            . "&tipo=warning&texto="
            . urlencode(
                "La persona no está registrada en el módulo de Usuarios."
            )
        );

        exit;
    }


    // ==========================================================
    // VALIDAR QUE EL USUARIO ESTÉ ACTIVO
    // ==========================================================

    if ((int) $usuario['estado'] !== 1) {

        header(
            "Location: ../configuracion/personas_unidad.php?id_unidad="
            . $unidad_id
            . "&tipo=warning&texto="
            . urlencode(
                "La persona está inactiva en el módulo de Usuarios."
            )
        );

        exit;
    }
    $usuario_id = (int) $usuario['id'];

    // ==========================================================
    // VERIFICAR SI YA ESTÁ VINCULADO
    // ==========================================================

    $sql = " SELECT id, tipo
        FROM residente
        WHERE unidad_id = :unidad_id
            AND usuario_id = :usuario_id
            AND activo = 1
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ':unidad_id' => $unidad_id,
        ':usuario_id' => $usuario_id
    ]);

    $relacionExistente = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($relacionExistente) {

        $tipoExistente = ucfirst(
            $relacionExistente['tipo']
        );

        header(
            "Location: ../configuracion/personas_unidad.php?id_unidad="
            . $unidad_id
            . "&tipo=warning&texto="
            . urlencode(
                "La persona ya está vinculada a esta unidad como "
                . $tipoExistente
                . "."
            )
        );

        exit;
    }


    // ==========================================================
    // INSERTAR RELACIÓN
    // ==========================================================


    $sql = "INSERT INTO residente (unidad_id,usuario_id,tipo,recibe_factura,fecha_desde,fecha_hasta,activo        )
        VALUES (:unidad_id,
        :usuario_id,
        :tipo,
        :recibe_factura,
        :fecha_desde,
        NULL,1)";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ':unidad_id'      => $unidad_id,
        ':usuario_id'     => $usuario_id,
        ':tipo'           => $tipo,
        ':recibe_factura' => $recibe_factura,
        ':fecha_desde'    => $fecha_desde
    ]);

    // ==========================================================
    // MENSAJE DE ÉXITO
    // ==========================================================

    $nombreCompleto =
        $usuario['nombres'] . ' ' . $usuario['apellidos'];

    header(
        "Location: ../configuracion/personas_unidad.php?id_unidad="
        . $unidad_id
        . "&tipo=success&texto="
        . urlencode(
            "La persona "
            . $nombreCompleto
            . " fue agregada correctamente a la unidad."
        )
    );

    exit;


} catch (PDOException $e) {

    die(
        "Error al agregar la persona a la unidad: "
        . $e->getMessage()
    );
}