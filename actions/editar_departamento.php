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

$codigo = trim(
    $_POST["codigo"] ?? ""
);

$activo = isset($_POST["estado"])
    ? (int) $_POST["estado"]
    : 1;


// ==========================================================
// VALIDAR ID
// ==========================================================

if ($id_departamento <= 0) {

    $mensaje = urlencode(
        "Departamento no válido."
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
        "Debe ingresar el nombre del departamento."
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
    // OBTENER EL PAÍS ACTUAL
    // ======================================================

    $sql = "
        SELECT id_pais
        FROM departamentos
        WHERE id_departamento = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $id_departamento
    ]);

    $id_pais = $stmt->fetchColumn();


    if (!$id_pais) {

        throw new Exception(
            "El departamento no existe."
        );

    }


    // ======================================================
    // VERIFICAR DUPLICADO
    // ======================================================

    $sql = "
        SELECT COUNT(*)
        FROM departamentos
        WHERE id_pais = ?
        AND nombre = ?
        AND id_departamento <> ?
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $id_pais,
        $nombre,
        $id_departamento
    ]);


    if ($stmt->fetchColumn() > 0) {

        throw new Exception(
            "Ya existe otro departamento con ese nombre para este país."
        );

    }


    // ======================================================
    // ACTUALIZAR
    // ======================================================

    $sql = "
        UPDATE departamentos
        SET
            nombre = ?,
            codigo = ?,
            Activo = ?
        WHERE id_departamento = ?
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([

        $nombre,

        $codigo !== ""
            ? $codigo
            : null,

        $activo,

        $id_departamento

    ]);


    // ======================================================
    // MENSAJE DE ÉXITO
    // ======================================================

    $mensaje = urlencode(
        "El departamento fue actualizado correctamente."
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