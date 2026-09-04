<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . BASE_URL . "parqueadero.php");
    exit;
}

try {

    // ==========================================================
    // DATOS
    // ==========================================================

    $codigo = trim($_POST["codigo"] ?? "");
    $tipo = $_POST["tipo"] ?? "";
    $id_unidad = !empty($_POST["id_unidad"])
        ? (int) $_POST["id_unidad"]
        : null;
    $ubicacion = trim($_POST["ubicacion"] ?? "");
    $estado = $_POST["estado"] ?? "DISPONIBLE";
    $observaciones = trim($_POST["observaciones"] ?? "");
    $activo = isset($_POST["activo"])
        ? (int) $_POST["activo"]
        : 1;


    // ==========================================================
    // VALIDAR CÓDIGO
    // ==========================================================

    if ($codigo === "") {
        throw new Exception("El código del parqueadero es obligatorio.");
    }


    // ==========================================================
    // VALIDAR TIPO
    // ==========================================================

    $tiposPermitidos = [
        "PRIVADO",
        "VISITANTES",
        "MOTOS",
        "BICICLETAS"
    ];

    if (!in_array($tipo, $tiposPermitidos, true)) {
        throw new Exception("El tipo de parqueadero no es válido.");
    }


    // ==========================================================
    // VALIDAR ESTADO
    // ==========================================================

    $estadosPermitidos = [
        "DISPONIBLE",
        "OCUPADO",
        "MANTENIMIENTO"
    ];

    if (!in_array($estado, $estadosPermitidos, true)) {
        throw new Exception("El estado del parqueadero no es válido.");
    }


    // ==========================================================
    // VALIDAR ACTIVO
    // ==========================================================

    if (!in_array($activo, [0, 1], true)) {
        $activo = 1;
    }


    // ==========================================================
    // VALIDAR CÓDIGO DUPLICADO
    // ==========================================================

    $sql = "
        SELECT id_parqueadero
        FROM parqueaderos
        WHERE codigo = :codigo
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":codigo" => $codigo
    ]);

    if ($stmt->fetch()) {
        throw new Exception(
            "Ya existe un parqueadero con el código: " . $codigo
        );
    }


    // ==========================================================
    // VALIDAR UNIDAD
    // ==========================================================

    if ($id_unidad !== null) {

        $sql = "
            SELECT id_unidad
            FROM unidades
            WHERE id_unidad = :id_unidad
              AND activo = 1
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sql);

        $stmt->execute([
            ":id_unidad" => $id_unidad
        ]);

        if (!$stmt->fetch()) {
            throw new Exception(
                "La unidad seleccionada no existe o está inactiva."
            );
        }
    }


    // ==========================================================
    // INSERTAR
    // ==========================================================

    $sql = "
        INSERT INTO parqueaderos (
            codigo,
            id_unidad,
            tipo,
            ubicacion,
            estado,
            observaciones,
            activo
        )
        VALUES (
            :codigo,
            :id_unidad,
            :tipo,
            :ubicacion,
            :estado,
            :observaciones,
            :activo
        )
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":codigo" => $codigo,
        ":id_unidad" => $id_unidad,
        ":tipo" => $tipo,
        ":ubicacion" => $ubicacion !== "" ? $ubicacion : null,
        ":estado" => $estado,
        ":observaciones" => $observaciones !== ""
            ? $observaciones
            : null,
        ":activo" => $activo
    ]);


    // ==========================================================
    // REDIRECCIÓN
    // ==========================================================

    header(
        "Location: " .
        BASE_URL .
        "configuracion/parqueaderos.php?tipo=success&mensaje=" .
        urlencode("Parqueadero registrado correctamente.")
    );

    exit;


} catch (Exception $e) {

    error_log($e->getMessage());

    header(
        "Location: " .
        BASE_URL .
        "parqueadero.php?tipo=error&mensaje=" .
        urlencode($e->getMessage())
    );

    exit;
}
?>
