<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// SOLO POST
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: " . BASE_URL .
        "vehiculos.php?tipo=error&mensaje=" .
        urlencode("Solicitud no válida.")
    );

    exit;
}


try {

    // ======================================================
    // DATOS DEL FORMULARIO
    // ======================================================

    $placa = strtoupper(
        trim($_POST['placa'] ?? '')
    );

    $tipo = strtoupper(
        trim($_POST['tipo'] ?? '')
    );

    $marca = trim(
        $_POST['marca'] ?? ''
    );

    $modelo = trim(
        $_POST['modelo'] ?? ''
    );

    $color = trim(
        $_POST['color'] ?? ''
    );

    $id_unidad = !empty($_POST['id_unidad'])
        ? (int)$_POST['id_unidad']
        : null;

    $id_residente = !empty($_POST['id_residente'])
        ? (int)$_POST['id_residente']
        : null;

    $fecha_desde = !empty($_POST['fecha_desde'])
        ? $_POST['fecha_desde']
        : date('Y-m-d H:i:s');

    $fecha_hasta = !empty($_POST['fecha_hasta'])
        ? $_POST['fecha_hasta']
        : null;

    $observaciones = trim(
        $_POST['observaciones'] ?? ''
    );

    $estado = isset($_POST['estado'])
        ? (int)$_POST['estado']
        : 1;


    // ======================================================
    // VALIDACIONES
    // ======================================================

    if ($placa === '') {

        throw new Exception(
            "La placa es obligatoria."
        );

    }


    $tiposPermitidos = [
        'AUTOMOVIL',
        'MOTOCICLETA',
        'BICICLETA',
        'OTRO'
    ];

    if (!in_array($tipo, $tiposPermitidos, true)) {

        throw new Exception(
            "El tipo de vehículo no es válido."
        );

    }


    if (!in_array($estado, [0, 1], true)) {

        throw new Exception(
            "El estado del vehículo no es válido."
        );

    }


    // ======================================================
    // VALIDAR PLACA DUPLICADA
    // ======================================================

    $stmt = $conexion->prepare("
        SELECT id_vehiculo
        FROM vehiculos
        WHERE placa = ?
        LIMIT 1
    ");

    $stmt->execute([
        $placa
    ]);

    if ($stmt->fetch()) {

        throw new Exception(
            "Ya existe un vehículo registrado con la placa " .
            $placa . "."
        );

    }


    // ======================================================
    // VALIDAR UNIDAD
    // ======================================================

    if ($id_unidad !== null) {

        $stmt = $conexion->prepare("
            SELECT id_unidad
            FROM unidades
            WHERE id_unidad = ?
              AND activo = 1
            LIMIT 1
        ");

        $stmt->execute([
            $id_unidad
        ]);

        if (!$stmt->fetch()) {

            throw new Exception(
                "La unidad seleccionada no es válida."
            );

        }

    }


    // ======================================================
    // VALIDAR RESIDENTE
    // ======================================================

    if ($id_residente !== null) {

        $stmt = $conexion->prepare("
            SELECT
                id,
                unidad_id
            FROM residente
            WHERE id = ?
              AND activo = 1
            LIMIT 1
        ");

        $stmt->execute([
            $id_residente
        ]);

        $residente = $stmt->fetch();

        if (!$residente) {

            throw new Exception(
                "El residente seleccionado no es válido."
            );

        }


        // Si se seleccionaron unidad y residente,
        // verificamos que pertenezcan a la misma unidad.

        if (
            $id_unidad !== null &&
            (int)$residente['unidad_id'] !== $id_unidad
        ) {

            throw new Exception(
                "El residente seleccionado no pertenece a la unidad indicada."
            );

        }


        // Si no se seleccionó unidad pero sí residente,
        // tomamos automáticamente la unidad del residente.

        if ($id_unidad === null) {

            $id_unidad = !empty($residente['unidad_id'])
                ? (int)$residente['unidad_id']
                : null;

        }

    }


    // ======================================================
    // INSERTAR VEHÍCULO
    // ======================================================

    $sql = "
        INSERT INTO vehiculos (
            placa,
            tipo,
            marca,
            modelo,
            color,
            id_residente,
            id_unidad,
            estado,
            fecha_desde,
            fecha_hasta,
            observaciones
        )
        VALUES (
            :placa,
            :tipo,
            :marca,
            :modelo,
            :color,
            :id_residente,
            :id_unidad,
            :estado,
            :fecha_desde,
            :fecha_hasta,
            :observaciones
        )
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([

        ':placa' =>
            $placa,

        ':tipo' =>
            $tipo,

        ':marca' =>
            $marca !== ''
                ? $marca
                : null,

        ':modelo' =>
            $modelo !== ''
                ? $modelo
                : null,

        ':color' =>
            $color !== ''
                ? $color
                : null,

        ':id_residente' =>
            $id_residente,

        ':id_unidad' =>
            $id_unidad,

        ':estado' =>
            $estado,

        ':fecha_desde' =>
            $fecha_desde,

        ':fecha_hasta' =>
            $fecha_hasta,

        ':observaciones' =>
            $observaciones !== ''
                ? $observaciones
                : null
    ]);


    // ======================================================
    // REDIRECCIÓN EXITOSA
    // ======================================================

    header(
        "Location: " . BASE_URL .
        "/configuracion/vehiculos.php?tipo=success&mensaje=" .
        urlencode("Vehículo registrado correctamente.")
    );

    exit;


} catch (Exception $e) {

    error_log(
        "Error guardar_vehiculo.php: " .
        $e->getMessage()
    );

    header(
        "Location: " . BASE_URL .
        "/configuracion/vehiculos.php?tipo=error&mensaje=" .
        urlencode($e->getMessage())
    );

    exit;

}
?>
