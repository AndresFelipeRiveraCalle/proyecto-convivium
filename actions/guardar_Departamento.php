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

$origen = $_POST["origen"] ?? "tablas_maestras";

$id_pais = (int) (
    $_POST["id_pais"] ?? 0
);

$nombre = trim(
    $_POST["nombre"] ?? ""
);

$codigo = trim(
    $_POST["codigo"] ?? ""
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
// VALIDAR PAÍS
// ==========================================================

if ($id_pais <= 0) {

    $mensaje = urlencode(
        "Debe seleccionar un país."
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
        "Debe ingresar el nombre del departamento."
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
    // VERIFICAR QUE EL PAÍS EXISTA Y ESTÉ ACTIVO
    // ======================================================

    $sql = "
        SELECT COUNT(*)
        FROM paises
        WHERE id_pais = ?
        AND Activo = 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $id_pais
    ]);

    if ($stmt->fetchColumn() == 0) {

        throw new Exception(
            "El país seleccionado no existe o está inactivo."
        );
    }


    // ======================================================
    // VERIFICAR DEPARTAMENTO DUPLICADO
    // ======================================================

    $sql = "
        SELECT COUNT(*)
        FROM departamentos
        WHERE id_pais = ?
        AND nombre = ?
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $id_pais,
        $nombre
    ]);

    if ($stmt->fetchColumn() > 0) {

        throw new Exception(
            "Ese departamento ya existe para el país seleccionado."
        );
    }


    // ======================================================
    // INSERTAR DEPARTAMENTO
    // ======================================================

    $sql = "
        INSERT INTO departamentos (
            id_pais,
            nombre,
            codigo,
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
        $id_pais,
        $nombre,
        $codigo !== "" ? $codigo : null,
        $activo
    ]);


    // ======================================================
    // MENSAJE DE ÉXITO
    // ======================================================

    $mensaje = urlencode(
        "El departamento fue registrado correctamente."
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