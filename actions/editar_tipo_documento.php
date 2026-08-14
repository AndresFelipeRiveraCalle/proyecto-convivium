<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

try {

    $id = (int) (
        $_POST["id_tipo_documento"] ?? 0
    );

    $codigo = trim(
        $_POST["codigo"] ?? ""
    );

    $nombre = trim(
        $_POST["nombre"] ?? ""
    );

    $estado = isset($_POST["estado"])
        ? (int) $_POST["estado"]
        : 1;


    // ==========================================
    // VALIDACIONES
    // ==========================================

    if ($id <= 0) {

        throw new Exception(
            "Tipo de documento no válido."
        );

    }


    if ($codigo === "") {

        throw new Exception(
            "El código es obligatorio."
        );

    }


    if ($nombre === "") {

        throw new Exception(
            "El nombre es obligatorio."
        );

    }


    // ==========================================
    // VERIFICAR CÓDIGO DUPLICADO
    // ==========================================

    $sql = "
        SELECT COUNT(*)
        FROM tipos_documento
        WHERE codigo = ?
        AND id_tipo_documento <> ?
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $codigo,
        $id
    ]);


    if ($stmt->fetchColumn() > 0) {

        throw new Exception(
            "Ya existe otro tipo de documento con ese código."
        );

    }


    // ==========================================
    // VERIFICAR NOMBRE DUPLICADO
    // ==========================================

    $sql = "
        SELECT COUNT(*)
        FROM tipos_documento
        WHERE nombre = ?
        AND id_tipo_documento <> ?
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $nombre,
        $id
    ]);


    if ($stmt->fetchColumn() > 0) {

        throw new Exception(
            "Ya existe otro tipo de documento con ese nombre."
        );

    }


    // ==========================================
    // ACTUALIZAR
    // ==========================================

    $sql = "
        UPDATE tipos_documento
        SET
            codigo = ?,
            nombre = ?,
            estado = ?
        WHERE id_tipo_documento = ?
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $codigo,
        $nombre,
        $estado,
        $id
    ]);


    // ==========================================
    // REGRESAR
    // ==========================================

    header(
        "Location: ../configuracion/tablas_maestras.php" .
        "?tipo=success" .
        "&texto=Tipo de documento actualizado correctamente"
    );

    exit;


} catch (Exception $e) {

    header(
        "Location: ../configuracion/tablas_maestras.php" .
        "?tipo=error" .
        "&texto=" .
        urlencode($e->getMessage())
    );

    exit;
}