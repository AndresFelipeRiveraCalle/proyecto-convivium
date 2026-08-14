<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// VALIDAR MÉTODO
// ==========================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: ../configuracion/tablas_maestras.php"
    );

    exit;
}


// ==========================================================
// DATOS
// ==========================================================

$id_ciudad = (int) (
    $_POST["id_ciudad"] ?? 0
);

$nombre = trim(
    $_POST["nombre"] ?? ""
);

$codigo_dane = trim(
    $_POST["codigo_dane"] ?? ""
);

$activo = isset($_POST["estado"])
    ? (int) $_POST["estado"]
    : 1;


// ==========================================================
// VALIDAR ID
// ==========================================================

if ($id_ciudad <= 0) {

    $mensaje = urlencode(
        "Ciudad no válida."
    );

    header(
        "Location: ../configuracion/tablas_maestras.php" .
        "?tipo=error&texto=" .
        $mensaje
    );

    exit;
}


// ==========================================================
// VALIDAR NOMBRE
// ==========================================================

if ($nombre === "") {

    $mensaje = urlencode(
        "Debe ingresar el nombre de la ciudad."
    );

    header(
        "Location: ../configuracion/tablas_maestras.php" .
        "?tipo=warning&texto=" .
        $mensaje
    );

    exit;
}


try {

    // ======================================================
    // OBTENER DEPARTAMENTO ACTUAL
    // ======================================================

    $sql = "
        SELECT id_departamento
        FROM ciudades
        WHERE id_ciudad = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $id_ciudad
    ]);

    $id_departamento =
        $stmt->fetchColumn();


    if (!$id_departamento) {

        throw new Exception(
            "La ciudad no existe."
        );

    }


    // ======================================================
    // VERIFICAR CIUDAD DUPLICADA
    // ======================================================

    $sql = "
        SELECT COUNT(*)
        FROM ciudades
        WHERE id_departamento = ?
        AND nombre = ?
        AND id_ciudad <> ?
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $id_departamento,
        $nombre,
        $id_ciudad
    ]);


    if ($stmt->fetchColumn() > 0) {

        throw new Exception(
            "Ya existe otra ciudad con ese nombre para este departamento."
        );

    }


    // ======================================================
    // ACTUALIZAR
    // ======================================================

    $sql = "
        UPDATE ciudades
        SET
            nombre = ?,
            codigo_dane = ?,
            Activo = ?
        WHERE id_ciudad = ?
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([

        $nombre,

        $codigo_dane !== ""
            ? $codigo_dane
            : null,

        $activo,

        $id_ciudad

    ]);


    // ======================================================
    // ÉXITO
    // ======================================================

    $mensaje = urlencode(
        "La ciudad fue actualizada correctamente."
    );

    header(
        "Location: ../configuracion/tablas_maestras.php" .
        "?tipo=success&texto=" .
        $mensaje
    );

    exit;


} catch (Exception $e) {

    $mensaje = urlencode(
        $e->getMessage()
    );

    header(
        "Location: ../configuracion/tablas_maestras.php" .
        "?tipo=error&texto=" .
        $mensaje
    );

    exit;
}