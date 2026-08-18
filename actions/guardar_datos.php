<?php

require_once "../config/conexion.php";

/*
|--------------------------------------------------------------------------
| VALIDAR MÉTODO
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../configuracion/datos.php");
    exit;
}


try {

    /*
    |--------------------------------------------------------------------------
    | DATOS DEL FORMULARIO
    |--------------------------------------------------------------------------
    */

    $id = isset($_POST["id"]) && $_POST["id"] !== ""
        ? (int) $_POST["id"]
        : null;


    $nombre = trim($_POST["nombre_unidad"] ?? "");

    $nit = trim($_POST["nit_unidad"] ?? "");

    $representante = trim(
        $_POST["representante_legal"] ?? ""
    );

    $correo = trim(
        $_POST["correo_propiedad"] ?? ""
    );

    $telefono = trim(
        $_POST["telefono_propiedad"] ?? ""
    );

$id_tipo_copropiedad = !empty($_POST["tipo_copropiedad"])
    ? (int) $_POST["tipo_copropiedad"]
    : null;

$cantidad_unidades = !empty($_POST["cantidad_unidades"])
    ? (int) $_POST["cantidad_unidades"]
    : null;

    // Ubicación

    $id_pais = !empty($_POST["id_pais"])
        ? (int) $_POST["id_pais"]
        : null;

    $id_departamento = !empty($_POST["id_departamento"])
        ? (int) $_POST["id_departamento"]
        : null;

    $id_ciudad = !empty($_POST["id_ciudad"])
        ? (int) $_POST["id_ciudad"]
        : null;

    $direccion = trim(
        $_POST["direccion"] ?? ""
    );

    $sector = trim(
        $_POST["sector"] ?? ""
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDACIONES
    |--------------------------------------------------------------------------
    */

    if ($nombre === "") {

        $mensaje = urlencode(
            "Debe ingresar el nombre de la copropiedad."
        );

        header(
            "Location: ../configuracion/datos.php" .
            "?tipo=warning&texto=" . $mensaje
        );

        exit;
    }


    if (!$id_pais) {

        $mensaje = urlencode(
            "Debe seleccionar un país."
        );

        header(
            "Location: ../configuracion/datos.php" .
            "?tipo=warning&texto=" . $mensaje
        );

        exit;
    }


    if (!$id_departamento) {

        $mensaje = urlencode(
            "Debe seleccionar un departamento."
        );

        header(
            "Location: ../configuracion/datos.php" .
            "?tipo=warning&texto=" . $mensaje
        );

        exit;
    }


    if (!$id_ciudad) {

        $mensaje = urlencode(
            "Debe seleccionar una ciudad."
        );

        header(
            "Location: ../configuracion/datos.php" .
            "?tipo=warning&texto=" . $mensaje
        );

        exit;
    }


    if ($direccion === "") {

        $mensaje = urlencode(
            "Debe ingresar la dirección de la copropiedad."
        );

        header(
            "Location: ../configuracion/datos.php" .
            "?tipo=warning&texto=" . $mensaje
        );

        exit;
    }

    if (!$id_tipo_copropiedad) {
        $mensaje = urlencode(
            "Debe seleccionar un tipo de copropiedad."
        );
        header(
            "Location: ../configuracion/datos.php" .
            "?tipo=warning&texto=" . $mensaje
        );
        exit;
    }


    if (!$cantidad_unidades || $cantidad_unidades < 1) {
        $mensaje = urlencode(
            "Debe ingresar una cantidad válida de unidades."
        );
        header(
            "Location: ../configuracion/datos.php" .
            "?tipo=warning&texto=" . $mensaje
        );
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | CARPETAS
    |--------------------------------------------------------------------------
    */

    $carpetaLogos = "../assets/logos/";
    $carpetaDocs  = "../assets/documentos/";


    if (!is_dir($carpetaLogos)) {

        mkdir(
            $carpetaLogos,
            0777,
            true
        );
    }


    if (!is_dir($carpetaDocs)) {

        mkdir(
            $carpetaDocs,
            0777,
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ARCHIVOS ACTUALES
    |--------------------------------------------------------------------------
    */

    $logo = null;
    $reglamento = null;
    $manual = null;
    $versionActual = null;


    /*
    |--------------------------------------------------------------------------
    | SI ES EDICIÓN
    |--------------------------------------------------------------------------
    */

    if ($id !== null) {

        $sql = "
            SELECT *
            FROM datos_unidad
            WHERE id = :id
            LIMIT 1
        ";


        $stmt = $conexion->prepare($sql);

        $stmt->execute([
            ":id" => $id
        ]);


        $versionActual =
            $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$versionActual) {

            $mensaje = urlencode(
                "No se encontró el registro que desea editar."
            );

            header(
                "Location: ../configuracion/datos.php" .
                "?tipo=error&texto=" . $mensaje
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | CONSERVAR ARCHIVOS ANTERIORES
        |--------------------------------------------------------------------------
        */

        $logo =
            $versionActual["logo"] ?? null;

        $reglamento =
            $versionActual["reglamento"] ?? null;

        $manual =
            $versionActual["manual"] ?? null;

    }


    /*
    |--------------------------------------------------------------------------
    | LOGO NUEVO
    |--------------------------------------------------------------------------
    */

    if (
        isset($_FILES["logo"]) &&
        $_FILES["logo"]["error"] === UPLOAD_ERR_OK
    ) {

        $extension = strtolower(
            pathinfo(
                $_FILES["logo"]["name"],
                PATHINFO_EXTENSION
            )
        );


        $logo =
            "logo_" .
            time() .
            "_" .
            uniqid() .
            "." .
            $extension;


        if (!move_uploaded_file(
            $_FILES["logo"]["tmp_name"],
            $carpetaLogos . $logo
        )) {

            throw new Exception(
                "No fue posible guardar el logo."
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | REGLAMENTO NUEVO
    |--------------------------------------------------------------------------
    */

    if (
        isset($_FILES["reglamento"]) &&
        $_FILES["reglamento"]["error"] === UPLOAD_ERR_OK
    ) {

        $extension = strtolower(
            pathinfo(
                $_FILES["reglamento"]["name"],
                PATHINFO_EXTENSION
            )
        );


        $reglamento =
            "reglamento_" .
            time() .
            "_" .
            uniqid() .
            "." .
            $extension;


        if (!move_uploaded_file(
            $_FILES["reglamento"]["tmp_name"],
            $carpetaDocs . $reglamento
        )) {

            throw new Exception(
                "No fue posible guardar el reglamento."
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | MANUAL NUEVO
    |--------------------------------------------------------------------------
    */

    if (
        isset($_FILES["manual"]) &&
        $_FILES["manual"]["error"] === UPLOAD_ERR_OK
    ) {

        $extension = strtolower(
            pathinfo(
                $_FILES["manual"]["name"],
                PATHINFO_EXTENSION
            )
        );


        $manual =
            "manual_" .
            time() .
            "_" .
            uniqid() .
            "." .
            $extension;


        if (!move_uploaded_file(
            $_FILES["manual"]["tmp_name"],
            $carpetaDocs . $manual
        )) {

            throw new Exception(
                "No fue posible guardar el manual."
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DETERMINAR VERSIÓN
    |--------------------------------------------------------------------------
    */

    if ($versionActual) {

        $version =
            ((int) $versionActual["version"]) + 1;

    } else {

        $version = 1;
    }


    /*
    |--------------------------------------------------------------------------
    | INICIAR TRANSACCIÓN
    |--------------------------------------------------------------------------
    */

    $conexion->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | MARCAR VERSIÓN ANTERIOR COMO NO ACTUAL
    |--------------------------------------------------------------------------
    */

    if ($versionActual) {

        $sql = "
            UPDATE datos_unidad
            SET es_actual = 0
            WHERE id = :id
        ";


        $stmt = $conexion->prepare($sql);

        $stmt->execute([
            ":id" => $id
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CREAR NUEVA VERSIÓN
    |--------------------------------------------------------------------------
    */

    $sql = "
        INSERT INTO datos_unidad
        (
            version,
            es_actual,
            nombre,
            nit,
            representante_legal,
            id_pais,
            id_departamento,
            id_ciudad,
            direccion,
            sector,
            id_tipo_copropiedad,
            cantidad_unidades,
            correo,
            telefono,
            logo,
            reglamento,
            manual,
            activo
        )
        VALUES
        (
            :version,
            :es_actual,
            :nombre,
            :nit,
            :representante,
            :id_pais,
            :id_departamento,
            :id_ciudad,
            :direccion,
            :sector,
            :id_tipo_copropiedad,
            :cantidad_unidades,
            :correo,
            :telefono,
            :logo,
            :reglamento,
            :manual,
            :activo
        )
    ";


    $stmt = $conexion->prepare($sql);


    $stmt->execute([

        ":version" =>
            $version,

        ":es_actual" =>
            1,

        ":nombre" =>
            $nombre,

        ":nit" =>
            $nit,

        ":representante" =>
            $representante,

        ":id_pais" =>
            $id_pais,

        ":id_departamento" =>
            $id_departamento,

        ":id_ciudad" =>
            $id_ciudad,

        ":direccion" =>
            $direccion,

        ":sector" =>
            $sector,

        ":id_tipo_copropiedad" =>
            $id_tipo_copropiedad,

        ":cantidad_unidades" =>
            $cantidad_unidades,
            
        ":correo" =>
            $correo,

        ":telefono" =>
            $telefono,

        ":logo" =>
            $logo,

        ":reglamento" =>
            $reglamento,

        ":manual" =>
            $manual,

        ":activo" =>
            1

    ]);


    /*
    |--------------------------------------------------------------------------
    | CONFIRMAR
    |--------------------------------------------------------------------------
    */

    $conexion->commit();


    /*
    |--------------------------------------------------------------------------
    | MENSAJE
    |--------------------------------------------------------------------------
    */

    if ($versionActual) {

        $mensaje = urlencode(
            "La información fue actualizada correctamente. " .
            "Se creó la versión " . $version . "."
        );

    } else {

        $mensaje = urlencode(
            "La información de la copropiedad fue guardada correctamente."
        );
    }


    header(
        "Location: ../configuracion/datos.php" .
        "?tipo=success&texto=" . $mensaje
    );

    exit;


} catch (Exception $e) {


    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    if ($conexion->inTransaction()) {

        $conexion->rollBack();
    }


    $mensaje = urlencode(
        "Error al guardar la información: " .
        $e->getMessage()
    );


    header(
        "Location: ../configuracion/datos.php" .
        "?tipo=error&texto=" . $mensaje
    );

    exit;
}