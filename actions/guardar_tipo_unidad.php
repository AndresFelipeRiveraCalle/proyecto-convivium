<?php

require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre      = trim($_POST["nombre"]);
    $cantidad    = intval($_POST["cantidad_unidades"]);
    $area        = floatval($_POST["area_total"]);
    $coeficiente = floatval($_POST["coeficiente_total"]);
    $observacion = trim($_POST["observaciones"]);

    // Validaciones
    if ($nombre == "") {

        $mensaje = urlencode("Debe ingresar el nombre del tipo de unidad.");

        header("Location: ../configuracion/basico.php?tipo=warning&texto=$mensaje");
        exit;
    }

    try {

        // Verificar si ya existe
        $sql = "SELECT COUNT(*)
                FROM tipos_unidad_config
                WHERE nombre = :nombre";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(":nombre", $nombre);
        $stmt->execute();

        if ($stmt->fetchColumn() > 0) {

            $mensaje = urlencode("Ya existe un tipo de unidad con ese nombre.");

            header("Location: ../configuracion/basico.php?tipo=warning&texto=$mensaje");
            exit;
        }

        // Insertar
        $sql = "INSERT INTO tipos_unidad_config
                (
                    nombre,
                    cantidad_unidades,
                    area_total,
                    coeficiente_total,
                    observaciones
                )
                VALUES
                (
                    :nombre,
                    :cantidad,
                    :area,
                    :coeficiente,
                    :observacion
                )";

        $stmt = $conexion->prepare($sql);

        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":cantidad", $cantidad);
        $stmt->bindParam(":area", $area);
        $stmt->bindParam(":coeficiente", $coeficiente);
        $stmt->bindParam(":observacion", $observacion);

        $stmt->execute();

        $mensaje = urlencode("Tipo de unidad creado correctamente.");

        header("Location: ../configuracion/basico.php?tipo=success&texto=$mensaje");
        exit;

    } catch (PDOException $e) {

        $mensaje = urlencode("Error: " . $e->getMessage());

        header("Location: ../configuracion/basico.php?tipo=error&texto=$mensaje");
        exit;
    }

} else {

    header("Location: ../configuracion/basico.php");
    exit;
}