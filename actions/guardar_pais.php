<?php

require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Obtener el nombre del país
    $nombre = trim($_POST["nombreP"]);

    // Validar que no esté vacío
    if ($nombre == "") {

        $mensaje = urlencode("Debe ingresar el nombre del país.");

        header("Location: ../configuracion/datos.php?tipo=warning&texto=$mensaje");
        exit;
    }

    try {

        // Verificar si el país ya existe
        $sql = "SELECT COUNT(*) FROM paises WHERE nombre = :nombre";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->fetchColumn() > 0) {

            $mensaje = urlencode("El país ya se encuentra registrado.");

            header("Location: ../configuracion/datos.php?tipo=warning&texto=$mensaje");
            exit;
        }

        // Insertar el nuevo país
        $sql = "INSERT INTO paises (nombre) VALUES (:nombre)";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        $stmt->execute();

        $mensaje = urlencode("El país fue registrado correctamente.");

        header("Location: ../configuracion/datos.php?tipo=success&texto=$mensaje");
        exit;

    } catch (PDOException $e) {

        $mensaje = urlencode("Error al guardar el país: " . $e->getMessage());

        header("Location: ../configuracion/datos.php?tipo=error&texto=$mensaje");
        exit;

    }

} else {

    // Si alguien intenta acceder directamente al archivo
    $mensaje = urlencode("Acceso no permitido.");

    header("Location: ../configuracion/datos.php?tipo=error&texto=$mensaje");
    exit;
}