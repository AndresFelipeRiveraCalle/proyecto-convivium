<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

try {

    $nombre = trim($_POST["nombre"] ?? "");
    $estado = isset($_POST["estado"]) ? (int) $_POST["estado"] : 1;

    // Validar nombre
    if ($nombre === "") {
        throw new Exception("El nombre del estado civil es obligatorio.");
    }

    // Verificar duplicado
    $sql = "
        SELECT COUNT(*)
        FROM estados_civiles
        WHERE nombre = ?
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([$nombre]);

    if ($stmt->fetchColumn() > 0) {
        throw new Exception("El estado civil ya existe.");
    }

    // Guardar
    $sql = "
        INSERT INTO estados_civiles
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
        "Location: ../configuracion/tablas_maestras.php?tipo=success&texto=Estado civil creado correctamente"
    );

    exit;

} catch (Exception $e) {

    header(
        "Location: ../configuracion/tablas_maestras.php?tipo=error&texto=" .
        urlencode($e->getMessage())
    );

    exit;
}