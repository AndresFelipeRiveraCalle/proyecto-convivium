<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

try {

    $id = (int) ($_POST["id_pais"] ?? 0);

    $nombre = trim(
        $_POST["nombre"] ?? ""
    );

    $estado = isset($_POST["Activo"])
        ? (int) $_POST["Activo"]
        : 1;


    // ==========================================
    // VALIDACIONES
    // ==========================================

    if ($id <= 0) {

        throw new Exception(
            "País no válido."
        );

    }

    if ($nombre === "") {

        throw new Exception(
            "El nombre del país es obligatorio."
        );

    }


    // ==========================================
    // VERIFICAR DUPLICADO
    // ==========================================

    $sql = "
        SELECT COUNT(*)
        FROM paises
        WHERE nombre = ?
        AND id_pais <> ?
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $nombre,
        $id
    ]);


    if ($stmt->fetchColumn() > 0) {

        throw new Exception(
            "Ya existe otro país con ese nombre."
        );

    }


    // ==========================================
    // ACTUALIZAR
    // ==========================================

    $sql = "
        UPDATE paises
        SET
            nombre = ?,
            Activo = ?
        WHERE id_pais = ?
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
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
        "&texto=País actualizado correctamente"
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