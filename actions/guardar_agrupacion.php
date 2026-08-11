<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// VALIDAR MÉTODO
// ==========================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    $mensaje = urlencode("Acceso no permitido.");

    header(
        "Location: " . BASE_URL .
        "configuracion/basico.php?tipo=error&texto=$mensaje"
    );

    exit;
}


try {

    // ==========================================================
    // DATOS DEL FORMULARIO
    // ==========================================================

    $id_tipo_agrupacion = intval($_POST["id_tipo_agrupacion"] ?? 0);
    $nombre             = trim($_POST["nombre"] ?? "");
    $descripcion        = trim($_POST["descripcion"] ?? "");


    // ==========================================================
    // VALIDAR TIPO DE AGRUPACIÓN
    // ==========================================================

    if ($id_tipo_agrupacion <= 0) {

        $mensaje = urlencode(
            "Debe seleccionar un tipo de agrupación."
        );

        header(
            "Location: " . BASE_URL .
            "configuracion/basico.php?tipo=warning&texto=$mensaje"
        );

        exit;
    }


    // ==========================================================
    // VALIDAR NOMBRE
    // ==========================================================

    if ($nombre === "") {

        $mensaje = urlencode(
            "Debe ingresar el nombre de la agrupación."
        );

        header(
            "Location: " . BASE_URL .
            "configuracion/basico.php?tipo=warning&texto=$mensaje"
        );

        exit;
    }


    // ==========================================================
    // VERIFICAR QUE EL TIPO DE AGRUPACIÓN EXISTA
    // ==========================================================

    $sql = "
        SELECT COUNT(*)
        FROM tipos_agrupacion
        WHERE id_tipo_agrupacion = :id_tipo_agrupacion
          AND activo = 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bindParam(
        ":id_tipo_agrupacion",
        $id_tipo_agrupacion,
        PDO::PARAM_INT
    );

    $stmt->execute();

    if ($stmt->fetchColumn() == 0) {

        $mensaje = urlencode(
            "El tipo de agrupación seleccionado no es válido."
        );

        header(
            "Location: " . BASE_URL .
            "configuracion/basico.php?tipo=warning&texto=$mensaje"
        );

        exit;
    }


    // ==========================================================
    // VERIFICAR DUPLICADO
    // ==========================================================

    $sql = "
        SELECT COUNT(*)
        FROM agrupaciones
        WHERE id_tipo_agrupacion = :id_tipo_agrupacion
          AND nombre = :nombre
          AND activo = 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bindParam(
        ":id_tipo_agrupacion",
        $id_tipo_agrupacion,
        PDO::PARAM_INT
    );

    $stmt->bindParam(
        ":nombre",
        $nombre,
        PDO::PARAM_STR
    );

    $stmt->execute();

    if ($stmt->fetchColumn() > 0) {

        $mensaje = urlencode(
            "Ya existe una agrupación con ese nombre."
        );

        header(
            "Location: " . BASE_URL .
            "configuracion/basico.php?tipo=warning&texto=$mensaje"
        );

        exit;
    }


    // ==========================================================
    // INSERTAR AGRUPACIÓN
    // ==========================================================

    $sql = "
        INSERT INTO agrupaciones
        (
            id_tipo_agrupacion,
            nombre,
            descripcion,
            activo
        )
        VALUES
        (
            :id_tipo_agrupacion,
            :nombre,
            :descripcion,
            1
        )
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bindParam(
        ":id_tipo_agrupacion",
        $id_tipo_agrupacion,
        PDO::PARAM_INT
    );

    $stmt->bindParam(
        ":nombre",
        $nombre,
        PDO::PARAM_STR
    );

    $stmt->bindParam(
        ":descripcion",
        $descripcion,
        PDO::PARAM_STR
    );

    $stmt->execute();


    // ==========================================================
    // MENSAJE DE ÉXITO
    // ==========================================================

    $mensaje = urlencode(
        "La agrupación fue registrada correctamente."
    );

    header(
        "Location: " . BASE_URL .
        "configuracion/basico.php?tipo=success&texto=$mensaje"
    );

    exit;


} catch (PDOException $e) {

    $mensaje = urlencode(
        "Error al guardar la agrupación: " . $e->getMessage()
    );

    header(
        "Location: " . BASE_URL .
        "configuracion/basico.php?tipo=error&texto=$mensaje"
    );

    exit;
}