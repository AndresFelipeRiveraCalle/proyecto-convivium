<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// VALIDAR MÉTODO
// ==========================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: ../configuracion/basico.php"
    );

    exit;
}


// ==========================================================
// DETERMINAR ORIGEN
// ==========================================================

$origen = $_POST["origen"] ?? "basico";


// ==========================================================
// DETERMINAR DATOS SEGÚN EL FORMULARIO
// ==========================================================

if ($origen === "tablas_maestras") {

    // Formulario de Tablas Maestras

    $nombre = trim(
        $_POST["nombre"] ?? ""
    );

    $activo = isset($_POST["estado"])
        ? (int) $_POST["estado"]
        : 1;

} else {

    // Formulario antiguo de basico.php

    $nombre = trim(
        $_POST["nombreP"] ?? ""
    );

    $activo = 1;
}


// ==========================================================
// DEFINIR PÁGINA DE RETORNO
// ==========================================================

if ($origen === "tablas_maestras") {

    $paginaRetorno =
        "../configuracion/tablas_maestras.php";

} else {

    $paginaRetorno =
        "../configuracion/basico.php";
}


// ==========================================================
// VALIDAR NOMBRE
// ==========================================================

if ($nombre === "") {

    $mensaje = urlencode(
        "Debe ingresar el nombre del país."
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
    // VERIFICAR SI EL PAÍS YA EXISTE
    // ======================================================

    $sql = "
        SELECT COUNT(*)
        FROM paises
        WHERE nombre = ?
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $nombre
    ]);

    if ($stmt->fetchColumn() > 0) {

        $mensaje = urlencode(
            "El país ya se encuentra registrado."
        );

        header(
            "Location: " .
            $paginaRetorno .
            "?tipo=warning&texto=" .
            $mensaje
        );

        exit;
    }


    // ======================================================
    // INSERTAR PAÍS
    // ======================================================

    $sql = "
        INSERT INTO paises (
            nombre,
            Activo
        )
        VALUES (
            ?,
            ?
        )
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $nombre,
        $activo
    ]);


    // ======================================================
    // MENSAJE DE ÉXITO
    // ======================================================

    $mensaje = urlencode(
        "El país fue registrado correctamente."
    );

    header(
        "Location: " .
        $paginaRetorno .
        "?tipo=success&texto=" .
        $mensaje
    );

    exit;


} catch (PDOException $e) {

    $mensaje = urlencode(
        "Error al guardar el país: " .
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