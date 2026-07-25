<?php

require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Obtener datos del formulario
    $idPais = isset($_POST["id_pais"]) ? intval($_POST["id_pais"]) : 0;
    $nombre = trim($_POST["nombreD"]);
    $codigo = isset($_POST["codigo"]) ? trim($_POST["codigo"]) : null;

    // Validar país
    if ($idPais <= 0) {

        $mensaje = urlencode("Debe seleccionar un país.");

        header("Location: ../configuracion/basico.php?tipo=warning&texto=$mensaje");
        exit;
    }

    // Validar nombre
    if ($nombre == "") {

        $mensaje = urlencode("Debe ingresar el nombre del departamento.");

        header("Location: ../configuracion/basico.php?tipo=warning&texto=$mensaje");
        exit;
    }

    try {

        // Verificar si ya existe ese departamento para el país
        $sql = "SELECT COUNT(*)
                FROM departamentos
                WHERE id_pais = :id_pais
                  AND nombre = :nombre";

        $stmt = $conexion->prepare($sql);

        $stmt->bindParam(":id_pais", $idPais, PDO::PARAM_INT);
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);

        $stmt->execute();

        if ($stmt->fetchColumn() > 0) {

            $mensaje = urlencode("El departamento ya se encuentra registrado para ese país.");

            header("Location: ../configuracion/basico.php?tipo=warning&texto=$mensaje");
            exit;
        }

        // Insertar departamento
        $sql = "INSERT INTO departamentos
                    (id_pais, nombre, codigo)
                VALUES
                    (:id_pais, :nombre, :codigo)";

        $stmt = $conexion->prepare($sql);

        $stmt->bindParam(":id_pais", $idPais, PDO::PARAM_INT);
        $stmt->bindParam(":nombre", $nombre, PDO::PARAM_STR);
        $stmt->bindParam(":codigo", $codigo, PDO::PARAM_STR);

        $stmt->execute();

        $mensaje = urlencode("El departamento fue registrado correctamente.");

        header("Location: ../configuracion/basico.php?tipo=success&texto=$mensaje");
        exit;

    } catch (PDOException $e) {

        $mensaje = urlencode("Error al guardar el departamento.");

        header("Location: ../configuracion/basico.php?tipo=error&texto=$mensaje");
        exit;
    }

} else {

    $mensaje = urlencode("Acceso no permitido.");

    header("Location: ../configuracion/basico.php?tipo=error&texto=$mensaje");
    exit;
}