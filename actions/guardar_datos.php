<?php

require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../configuracion/datos.php");
    exit;
}

try {

    // ==============================
    // DATOS DEL FORMULARIO
    // ==============================

    $nombre          = trim($_POST["nombre_unidad"]);
    $nit             = trim($_POST["nit_unidad"]);
    $representante   = trim($_POST["representante_legal"]);
    $correo          = trim($_POST["correo_propiedad"]);
    $telefono        = trim($_POST["telefono_propiedad"]);

    // ==============================
    // CARPETAS DE DESTINO
    // ==============================

    $carpetaLogos = "../assets/logos/";
    $carpetaDocs  = "../assets/documentos/";

    // Crear carpetas si no existen
    if (!is_dir($carpetaLogos)) {
        mkdir($carpetaLogos, 0777, true);
    }

    if (!is_dir($carpetaDocs)) {
        mkdir($carpetaDocs, 0777, true);
    }

    // ==============================
    // LOGO
    // ==============================

    $logo = null;

    if (!empty($_FILES["logo"]["name"])) {

        $extension = strtolower(pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION));

        $logo = "logo_" . time() . "." . $extension;

        move_uploaded_file(
            $_FILES["logo"]["tmp_name"],
            $carpetaLogos . $logo
        );
    }

    // ==============================
    // REGLAMENTO
    // ==============================

    $reglamento = null;

    if (!empty($_FILES["reglamento"]["name"])) {

        $extension = strtolower(pathinfo($_FILES["reglamento"]["name"], PATHINFO_EXTENSION));

        $reglamento = "reglamento_" . time() . "." . $extension;

        move_uploaded_file(
            $_FILES["reglamento"]["tmp_name"],
            $carpetaDocs . $reglamento
        );
    }

    // ==============================
    // MANUAL
    // ==============================

    $manual = null;

    if (!empty($_FILES["manual"]["name"])) {

        $extension = strtolower(pathinfo($_FILES["manual"]["name"], PATHINFO_EXTENSION));

        $manual = "manual_" . time() . "." . $extension;

        move_uploaded_file(
            $_FILES["manual"]["tmp_name"],
            $carpetaDocs . $manual
        );
    }

    // ==============================
    // INSERTAR EN LA BASE DE DATOS
    // ==============================

    $sql = "INSERT INTO datos_unidad
            (
                nombre,
                nit,
                representante_legal,
                correo,
                telefono,
                logo,
                reglamento,
                manual
            )
            VALUES
            (
                :nombre,
                :nit,
                :representante,
                :correo,
                :telefono,
                :logo,
                :reglamento,
                :manual
            )";

    $stmt = $conexion->prepare($sql);

    $stmt->bindParam(":nombre", $nombre);
    $stmt->bindParam(":nit", $nit);
    $stmt->bindParam(":representante", $representante);
    $stmt->bindParam(":correo", $correo);
    $stmt->bindParam(":telefono", $telefono);
    $stmt->bindParam(":logo", $logo);
    $stmt->bindParam(":reglamento", $reglamento);
    $stmt->bindParam(":manual", $manual);

    $stmt->execute();

    // ==============================
    // MENSAJE DE ÉXITO
    // ==============================

    $mensaje = urlencode("La información de la unidad fue guardada correctamente.");

    header("Location: ../configuracion/datos.php?tipo=success&texto=$mensaje");
    exit;

} catch (PDOException $e) {

    $mensaje = urlencode("Error al guardar la información: " . $e->getMessage());

    header("Location: ../configuracion/datos.php?tipo=error&texto=$mensaje");
    exit;
}