<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// VALIDAR MÉTODO
// ==========================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: ../configuracion/usuarios.php"
    );

    exit;
}


try {

    // ==========================================================
    // ID
    // ==========================================================

    $id = !empty($_POST["id"])
        ? (int) $_POST["id"]
        : 0;


    if ($id <= 0) {

        throw new Exception(
            "El usuario seleccionado no es válido."
        );
    }


    // ==========================================================
    // DATOS DEL FORMULARIO
    // ==========================================================

    $nombres = trim(
        $_POST["nombres"] ?? ""
    );

    $apellidos = trim(
        $_POST["apellidos"] ?? ""
    );

    $id_tipo_documento =
        !empty($_POST["id_tipo_documento"])
        ? $_POST["id_tipo_documento"]
        : null;

    $numero_documento = trim(
        $_POST["numero_documento"] ?? ""
    );

    $correo =
        !empty($_POST["correo"])
        ? trim($_POST["correo"])
        : null;

    $telefono =
        !empty($_POST["telefono"])
        ? trim($_POST["telefono"])
        : null;

    $celular =
        !empty($_POST["celular"])
        ? trim($_POST["celular"])
        : null;

    $fecha_nacimiento =
        !empty($_POST["fecha_nacimiento"])
        ? $_POST["fecha_nacimiento"]
        : null;

    $id_genero =
        !empty($_POST["id_genero"])
        ? $_POST["id_genero"]
        : null;

    $id_estado_civil =
        !empty($_POST["id_estado_civil"])
        ? $_POST["id_estado_civil"]
        : null;

    $id_ocupacion =
        !empty($_POST["id_ocupacion"])
        ? $_POST["id_ocupacion"]
        : null;

    $direccion =
        !empty($_POST["direccion"])
        ? trim($_POST["direccion"])
        : null;

    $estado =
        isset($_POST["estado"])
        ? (int) $_POST["estado"]
        : 1;


    // ==========================================================
    // VALIDACIONES
    // ==========================================================

    if ($nombres === "") {

        throw new Exception(
            "Debe ingresar los nombres."
        );
    }


    if ($apellidos === "") {

        throw new Exception(
            "Debe ingresar los apellidos."
        );
    }


    if (!$id_tipo_documento) {

        throw new Exception(
            "Debe seleccionar el tipo de documento."
        );
    }


    if ($numero_documento === "") {

        throw new Exception(
            "Debe ingresar el número de documento."
        );
    }


    // ==========================================================
    // VERIFICAR QUE EL USUARIO EXISTA
    // ==========================================================

    $sql = "
        SELECT
            id,
            foto,
            id_pais,
            id_departamento,
            id_ciudad

        FROM usuario

        WHERE id = :id

        LIMIT 1
    ";


    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":id" => $id
    ]);


    $usuarioActual =
        $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$usuarioActual) {

        throw new Exception(
            "El usuario no existe."
        );
    }


    // ==========================================================
    // VALIDAR DOCUMENTO DUPLICADO
    // ==========================================================

    $sql = "
        SELECT id
        FROM usuario

        WHERE numero_documento = :numero_documento
        AND id <> :id

        LIMIT 1
    ";


    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":numero_documento" =>
            $numero_documento,

        ":id" =>
            $id
    ]);


    if ($stmt->fetch(PDO::FETCH_ASSOC)) {

        throw new Exception(
            "El número de documento ya pertenece a otro usuario."
        );
    }


    // ==========================================================
    // CONSERVAR FOTO ACTUAL
    // ==========================================================

    $foto =
        $usuarioActual["foto"] ?? null;


    // ==========================================================
    // FOTOGRAFÍA NUEVA
    // ==========================================================

    if (
        isset($_FILES["foto"]) &&
        $_FILES["foto"]["error"] === UPLOAD_ERR_OK
    ) {

        $extension = strtolower(
            pathinfo(
                $_FILES["foto"]["name"],
                PATHINFO_EXTENSION
            )
        );


        // Extensiones permitidas

        $extensionesPermitidas = [
            "jpg",
            "jpeg",
            "png",
            "webp"
        ];


        if (!in_array(
            $extension,
            $extensionesPermitidas
        )) {

            throw new Exception(
                "El formato de la fotografía no es válido."
            );
        }


        // ======================================================
        // NOMBRE DE LA FOTO
        // ======================================================

        $fotoNombre =
            $numero_documento .
            "." .
            $extension;


        // ======================================================
        // RUTA FÍSICA
        // ======================================================

        $rutaDestino =
            ROOT_PATH .
            "/uploads/personas/" .
            $fotoNombre;


        // ======================================================
        // GUARDAR FOTO
        // ======================================================

        if (!move_uploaded_file(
            $_FILES["foto"]["tmp_name"],
            $rutaDestino
        )) {

            throw new Exception(
                "No fue posible guardar la fotografía."
            );
        }


        // ======================================================
        // RUTA QUE SE GUARDA EN BD
        // ======================================================

        $foto =
            "uploads/personas/" .
            $fotoNombre;
    }


    // ==========================================================
    // ACTUALIZAR
    // ==========================================================

    $sql = "
        UPDATE usuario

        SET
            nombres = :nombres,
            apellidos = :apellidos,
            id_tipo_documento = :id_tipo_documento,
            numero_documento = :numero_documento,
            correo = :correo,
            telefono = :telefono,
            celular = :celular,
            fecha_nacimiento = :fecha_nacimiento,
            id_genero = :id_genero,
            id_estado_civil = :id_estado_civil,
            id_ocupacion = :id_ocupacion,
            direccion = :direccion,
            foto = :foto,
            estado = :estado

        WHERE id = :id
    ";


    $stmt = $conexion->prepare($sql);


    $stmt->execute([

        ":nombres" =>
            $nombres,

        ":apellidos" =>
            $apellidos,

        ":id_tipo_documento" =>
            $id_tipo_documento,

        ":numero_documento" =>
            $numero_documento,

        ":correo" =>
            $correo,

        ":telefono" =>
            $telefono,

        ":celular" =>
            $celular,

        ":fecha_nacimiento" =>
            $fecha_nacimiento,

        ":id_genero" =>
            $id_genero,

        ":id_estado_civil" =>
            $id_estado_civil,

        ":id_ocupacion" =>
            $id_ocupacion,

        ":direccion" =>
            $direccion,

        ":foto" =>
            $foto,

        ":estado" =>
            $estado,

        ":id" =>
            $id

    ]);


    // ==========================================================
    // ÉXITO
    // ==========================================================

    $mensaje = urlencode(
        "Usuario actualizado correctamente."
    );


    header(
        "Location: ../configuracion/usuarios.php" .
        "?tipo=success&texto=" .
        $mensaje
    );

    exit;


} catch (Exception $e) {

    // ==========================================================
    // ERROR
    // ==========================================================

    $mensaje = urlencode(
        $e->getMessage()
    );


    header(
        "Location: ../configuracion/usuarios.php" .
        "?tipo=error&texto=" .
        $mensaje
    );

    exit;
}