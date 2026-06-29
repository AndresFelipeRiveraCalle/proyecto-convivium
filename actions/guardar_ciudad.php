<?php

require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $idDepartamento = isset($_POST["id_departamento"])
        ? intval($_POST["id_departamento"])
        : 0;

    $nombre = trim($_POST["nombreC"]);

    $codigo = isset($_POST["codigo"])
        ? trim($_POST["codigo"])
        : null;

    // Validar departamento
    if ($idDepartamento <= 0) {

        $mensaje = urlencode("Debe seleccionar un departamento.");

        header("Location: ../configuracion/datos.php?tipo=warning&texto=$mensaje");
        exit;
    }

    // Validar nombre
    if ($nombre == "") {

        $mensaje = urlencode("Debe ingresar el nombre de la ciudad.");

        header("Location: ../configuracion/datos.php?tipo=warning&texto=$mensaje");
        exit;
    }

    try {

        // Verificar si existe
        $sql = "SELECT COUNT(*)
                FROM ciudades
                WHERE id_departamento = :id_departamento
                AND nombre = :nombre";

        $stmt = $conexion->prepare($sql);

        $stmt->bindParam(":id_departamento", $idDepartamento, PDO::PARAM_INT);
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);

        $stmt->execute();

        if ($stmt->fetchColumn() > 0) {

            $mensaje = urlencode("La ciudad ya se encuentra registrada.");

            header("Location: ../configuracion/datos.php?tipo=warning&texto=$mensaje");
            exit;
        }

        // Insertar ciudad
        $sql = "INSERT INTO ciudades
                    (id_departamento, nombre, codigo)
                VALUES
                    (:id_departamento, :nombre, :codigo)";

        $stmt = $conexion->prepare($sql);

        $stmt->bindParam(":id_departamento", $idDepartamento, PDO::PARAM_INT);
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        $stmt->bindParam(":codigo", $codigo, PDO::PARAM_STR);

        $stmt->execute();

        $mensaje = urlencode("La ciudad fue registrada correctamente.");

        header("Location: ../configuracion/datos.php?tipo=success&texto=$mensaje");
        exit;

    } catch (PDOException $e) {

        $mensaje = urlencode("Error al guardar la ciudad.");

        header("Location: ../configuracion/datos.php?tipo=error&texto=$mensaje");
        exit;
    }

} else {

    $mensaje = urlencode("Acceso no permitido.");

    header("Location: ../configuracion/datos.php?tipo=error&texto=$mensaje");
    exit;
}