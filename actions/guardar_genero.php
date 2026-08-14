<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

try {

    $nombre = trim($_POST["nombre"] ?? "");
    $estado = isset($_POST["estado"]) ? (int) $_POST["estado"] : 1;

    if ($nombre === "") {
        throw new Exception("El nombre del género es obligatorio.");
    }

    // Verificar duplicado
    $sql = "
        SELECT COUNT(*)
        FROM generos
        WHERE nombre = ?
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([$nombre]);

    if ($stmt->fetchColumn() > 0) {
        throw new Exception("El género ya existe.");
    }

    // Guardar
    $sql = "
        INSERT INTO generos
        (
            nombre,
            estado
        )
        VALUES
        (
            ?,
            ?
        )
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $nombre,
        $estado
    ]);

    header(
        "Location: ../configuracion/tablas_maestras.php?tipo=success&texto=Género creado correctamente"
    );

    exit;

} catch (Exception $e) {

    header(
        "Location: ../configuracion/tablas_maestras.php?tipo=error&texto=" .
        urlencode($e->getMessage())
    );

    exit;
}