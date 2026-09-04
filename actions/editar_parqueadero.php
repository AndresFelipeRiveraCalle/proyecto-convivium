<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "parqueadero.php");
    exit;
}

try {

    // ==========================================================
    // DATOS DEL FORMULARIO
    // ==========================================================

    $id_parqueadero = (int)($_POST['id_parqueadero'] ?? 0);

    $codigo = trim($_POST['codigo'] ?? '');

    $tipo = $_POST['tipo'] ?? '';

    $id_unidad = !empty($_POST['id_unidad'])
        ? (int)$_POST['id_unidad']
        : null;

    $ubicacion = trim($_POST['ubicacion'] ?? '');

    $estado = $_POST['estado'] ?? '';

    $observaciones = trim($_POST['observaciones'] ?? '');

    $activo = isset($_POST['activo'])
        ? (int)$_POST['activo']
        : 1;


    // ==========================================================
    // VALIDACIONES
    // ==========================================================

    if ($id_parqueadero <= 0) {
        throw new Exception("Parqueadero no válido.");
    }

    if ($codigo === '') {
        throw new Exception("El código del parqueadero es obligatorio.");
    }

    $tiposPermitidos = [
        'PRIVADO',
        'VISITANTES',
        'MOTOS',
        'BICICLETAS'
    ];

    if (!in_array($tipo, $tiposPermitidos, true)) {
        throw new Exception("Tipo de parqueadero no válido.");
    }


    $estadosPermitidos = [
        'DISPONIBLE',
        'OCUPADO',
        'MANTENIMIENTO'
    ];

    if (!in_array($estado, $estadosPermitidos, true)) {
        throw new Exception("Estado del parqueadero no válido.");
    }


    if ($activo !== 0 && $activo !== 1) {
        $activo = 1;
    }


    // ==========================================================
    // VERIFICAR QUE EXISTA
    // ==========================================================

    $stmt = $conexion->prepare("
        SELECT id_parqueadero
        FROM parqueaderos
        WHERE id_parqueadero = ?
        LIMIT 1
    ");

    $stmt->execute([
        $id_parqueadero
    ]);

    if (!$stmt->fetch()) {
        throw new Exception("El parqueadero no existe.");
    }


    // ==========================================================
    // VERIFICAR CÓDIGO DUPLICADO
    // ==========================================================

    $stmt = $conexion->prepare("
        SELECT id_parqueadero
        FROM parqueaderos
        WHERE codigo = ?
        AND id_parqueadero <> ?
        LIMIT 1
    ");

    $stmt->execute([
        $codigo,
        $id_parqueadero
    ]);

    if ($stmt->fetch()) {
        throw new Exception(
            "Ya existe otro parqueadero con el código indicado."
        );
    }


    // ==========================================================
    // ACTUALIZAR
    // ==========================================================

    $sql = "
        UPDATE parqueaderos
        SET
            codigo = :codigo,
            id_unidad = :id_unidad,
            tipo = :tipo,
            ubicacion = :ubicacion,
            estado = :estado,
            observaciones = :observaciones,
            activo = :activo
        WHERE id_parqueadero = :id_parqueadero
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bindValue(
        ':codigo',
        $codigo,
        PDO::PARAM_STR
    );

    if ($id_unidad === null) {

        $stmt->bindValue(
            ':id_unidad',
            null,
            PDO::PARAM_NULL
        );

    } else {

        $stmt->bindValue(
            ':id_unidad',
            $id_unidad,
            PDO::PARAM_INT
        );

    }

    $stmt->bindValue(
        ':tipo',
        $tipo,
        PDO::PARAM_STR
    );

    $stmt->bindValue(
        ':ubicacion',
        $ubicacion !== '' ? $ubicacion : null,
        $ubicacion !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
    );

    $stmt->bindValue(
        ':estado',
        $estado,
        PDO::PARAM_STR
    );

    $stmt->bindValue(
        ':observaciones',
        $observaciones !== '' ? $observaciones : null,
        $observaciones !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL
    );

    $stmt->bindValue(
        ':activo',
        $activo,
        PDO::PARAM_INT
    );

    $stmt->bindValue(
        ':id_parqueadero',
        $id_parqueadero,
        PDO::PARAM_INT
    );

    $stmt->execute();


    // ==========================================================
    // REDIRECCIÓN
    // ==========================================================

    header(
        "Location: " .
        BASE_URL .
        "configuracion/parqueaderos.php?tipo=success&mensaje=" .
        urlencode("Parqueadero actualizado correctamente.")
    );

    exit;


} catch (PDOException $e) {

    error_log($e->getMessage());

    header(
        "Location: " .
        BASE_URL .
        "parqueadero.php?tipo=error&mensaje=" .
        urlencode("No fue posible actualizar el parqueadero.")
    );

    exit;


} catch (Exception $e) {

    header(
        "Location: " .
        BASE_URL .
        "parqueadero.php?tipo=error&mensaje=" .
        urlencode($e->getMessage())
    );

    exit;
}
?>
