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
// DATOS DEL FORMULARIO
// ==========================================================

$id_departamento = (int) (
    $_POST["id_departamento"] ?? 0
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
// PÁGINA DE RETORNO
// ==========================================================

$paginaRetorno =
    "../configuracion/tablas_maestras.php";


// ==========================================================
// VALIDAR DEPARTAMENTO
// ==========================================================

if ($id_departamento <= 0) {

    $mensaje = urlencode(
        "Debe seleccionar un departamento."
    );

    header(
        "Location: " .
        $paginaRetorno .
        "?tipo=warning&texto=" .
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
        "Location: " .
        $paginaRetorno .
        "?tipo=warning&texto=" .
        $mensaje
    );

    exit;
}


try {

    // ======================================================
    // VERIFICAR DEPARTAMENTO
    // ======================================================

    $sql = "
        SELECT COUNT(*)
        FROM departamentos
        WHERE id_departamento = ?
        AND Activo = 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $id_departamento
    ]);


    if ($stmt->fetchColumn() == 0) {

        throw new Exception(
            "El departamento seleccionado no existe o está inactivo."
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
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $id_departamento,
        $nombre
    ]);


    if ($stmt->fetchColumn() > 0) {

        throw new Exception(
            "Esa ciudad ya existe para el departamento seleccionado."
        );

    }


    // ======================================================
    // INSERTAR CIUDAD
    // ======================================================

    $sql = "
        INSERT INTO ciudades (
            id_departamento,
            nombre,
            codigo_dane,
            Activo
        )
        VALUES (
            ?,
            ?,
            ?,
            ?
        )
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([

        $id_departamento,

        $nombre,

        $codigo_dane !== ""
            ? $codigo_dane
            : null,

        $activo

    ]);


    // ======================================================
    // ÉXITO
    // ======================================================

    $mensaje = urlencode(
        "La ciudad fue registrada correctamente."
    );

    header(
        "Location: " .
        $paginaRetorno .
        "?tipo=success&texto=" .
        $mensaje
    );

    exit;


} catch (Exception $e) {

    $mensaje = urlencode(
        $e->getMessage()
    );

    header(
        "Location: " .
        $paginaRetorno .
        "?tipo=error&texto=" .
        $mensaje
    );

    exit;
}