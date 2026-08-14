<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

try {

    $id = (int) ($_POST["id_genero"] ?? 0);

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
            "Género no válido."
        );
    }

    if ($nombre === "") {
        throw new Exception(
            "El nombre del género es obligatorio."
        );
    }


    // ==========================================
    // VERIFICAR DUPLICADO
    // ==========================================

    $sql = "
        SELECT COUNT(*)
        FROM generos
        WHERE nombre = ?
        AND id_genero <> ?
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $nombre,
        $id
    ]);


    if ($stmt->fetchColumn() > 0) {

        throw new Exception(
            "Ya existe otro género con ese nombre."
        );

    }


    // ==========================================
    // ACTUALIZAR
    // ==========================================

    $sql = "
        UPDATE generos
        SET
            nombre = ?,
            estado = ?
        WHERE id_genero = ?
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
        "&texto=Género actualizado correctamente"
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