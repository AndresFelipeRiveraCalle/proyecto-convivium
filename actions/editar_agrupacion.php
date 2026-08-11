<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: " . BASE_URL .
        "configuracion/basico.php"
    );

    exit;
}

try {

    // ==========================================================
    // DATOS
    // ==========================================================

    $id_agrupacion = intval($_POST["id_agrupacion"] ?? 0);
    $id_tipo_agrupacion =intval($_POST["id_tipo_agrupacion"] ?? 0);
    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion =trim($_POST["descripcion"] ?? "");


    // ==========================================================
    // VALIDACIONES
    // ==========================================================

    if ($id_agrupacion <= 0) {
        throw new Exception(
            "La agrupación no es válida."
        );
    }

    if ($id_tipo_agrupacion <= 0) {
        throw new Exception(
            "Debe seleccionar un tipo de agrupación."
        );
    }

    if ($nombre === "") {
        throw new Exception(
            "Debe ingresar el nombre de la agrupación."
        );
    }


    // ==========================================================
    // VERIFICAR DUPLICADO
    // ==========================================================

    $sql = " SELECT COUNT(*) FROM agrupaciones WHERE id_tipo_agrupacion = :tipo AND nombre = :nombre
          AND activo = 1 AND id_agrupacion <> :id";


$stmt = $conexion->prepare($sql);
    $stmt->bindParam(":tipo",$id_tipo_agrupacion,PDO::PARAM_INT);
    $stmt->bindParam(":nombre",$nombre,PDO::PARAM_STR);
    $stmt->bindParam(":id", $id_agrupacion,PDO::PARAM_INT);

    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        $mensaje = urlencode(
            "Ya existe otra agrupación con ese nombre."
        );

        header(
            "Location: " . BASE_URL .
            "configuracion/basico.php?tipo=warning&texto=$mensaje"
        );

        exit;
    }


    // ==========================================================
    // ACTUALIZAR
    // ==========================================================

    $sql = "
        UPDATE agrupaciones
        SET
            id_tipo_agrupacion = :tipo,
            nombre = :nombre,
            descripcion = :descripcion
        WHERE id_agrupacion = :id
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bindParam(
        ":tipo",
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

    $stmt->bindParam(
        ":id",
        $id_agrupacion,
        PDO::PARAM_INT
    );

    $stmt->execute();

    // ==========================================================
    // ÉXITO
    // ==========================================================

    $mensaje = urlencode("La agrupación fue actualizada correctamente.");

    header(
        "Location: " . BASE_URL . "configuracion/basico.php?tipo=success&texto=$mensaje"
    );
    exit;


} catch (Exception $e) {
    $mensaje = urlencode(
        $e->getMessage()
    );

    header(
        "Location: " . BASE_URL .
        "configuracion/basico.php?tipo=error&texto=$mensaje"
    );

    exit;
}