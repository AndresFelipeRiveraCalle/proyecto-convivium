<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

try {

    $codigo = trim($_POST["codigo"] ?? "");
    $nombre = trim($_POST["nombre"] ?? "");
    $estado = isset($_POST["estado"]) ? (int) $_POST["estado"] : 1;


    // ==========================================
    // VALIDACIONES
    // ==========================================

    if ($codigo === "") {
        throw new Exception("El código es obligatorio.");
    }

    if ($nombre === "") {
        throw new Exception("El nombre es obligatorio.");
    }


    // ==========================================
    // VERIFICAR CÓDIGO DUPLICADO
    // ==========================================

    $sql = "
        SELECT COUNT(*)
        FROM tipos_documento
        WHERE codigo = ?
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([$codigo]);

    if ($stmt->fetchColumn() > 0) {
        throw new Exception("El código del documento ya existe.");
    }


    // ==========================================
    // VERIFICAR NOMBRE DUPLICADO
    // ==========================================

    $sql = "
        SELECT COUNT(*)
        FROM tipos_documento
        WHERE nombre = ?
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([$nombre]);

    if ($stmt->fetchColumn() > 0) {
        throw new Exception("El tipo de documento ya existe.");
    }


    // ==========================================
    // GUARDAR
    // ==========================================

    $sql = "
        INSERT INTO tipos_documento
        (
            codigo,
            nombre,
            estado
        )
        VALUES
        (
            ?,
            ?,
            ?
        )
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $codigo,
        $nombre,
        $estado
    ]);


    // ==========================================
    // REGRESAR
    // ==========================================

    header(
        "Location: ../configuracion/tablas_maestras.php?tipo=success&texto=Tipo de documento creado correctamente"
    );

    exit;


} catch (Exception $e) {

    header(
        "Location: ../configuracion/tablas_maestras.php?tipo=error&texto=" .
        urlencode($e->getMessage())
    );

    exit;
}