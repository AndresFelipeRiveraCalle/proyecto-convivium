<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

try {

    // ==========================================================
    // DATOS DEL FORMULARIO
    // ==========================================================

    $nombres            = trim($_POST["nombres"]);
    $apellidos          = trim($_POST["apellidos"]);
    $id_tipo_documento  = !empty($_POST["id_tipo_documento"]) ? $_POST["id_tipo_documento"] : null;
    $numero_documento   = trim($_POST["numero_documento"]);

    $correo             = !empty($_POST["correo"]) ? trim($_POST["correo"]) : null;

    $telefono           = !empty($_POST["telefono"]) ? trim($_POST["telefono"]) : null;
    $celular            = !empty($_POST["celular"]) ? trim($_POST["celular"]) : null;

    $fecha_nacimiento   = !empty($_POST["fecha_nacimiento"]) ? $_POST["fecha_nacimiento"] : null;

    $id_genero          = !empty($_POST["id_genero"]) ? $_POST["id_genero"] : null;

    $id_estado_civil    = !empty($_POST["id_estado_civil"]) ? $_POST["id_estado_civil"] : null;
    $id_ocupacion       = !empty($_POST["id_ocupacion"]) ? $_POST["id_ocupacion"] : null;

    $direccion          = !empty($_POST["direccion"]) ? trim($_POST["direccion"]) : null;

    $id_pais            = !empty($_POST["id_pais"]) ? $_POST["id_pais"] : null;
    $id_departamento    = !empty($_POST["id_departamento"]) ? $_POST["id_departamento"] : null;
    $id_ciudad          = !empty($_POST["id_ciudad"]) ? $_POST["id_ciudad"] : null;



    // ==========================================================
    // VALIDAR DOCUMENTO
    // ==========================================================

    $sql = "
        SELECT id
        FROM usuario
        WHERE numero_documento = ?
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([$numero_documento]);

    if ($stmt->fetch(PDO::FETCH_ASSOC)) {

        header("Location: ../configuracion/personas.php?tipo=warning&texto=El número de documento ya existe.");
        exit;

    }

    // ==========================================================
    // FOTOGRAFÍA
    // ==========================================================

    $documento = trim($_POST["numero_documento"]);

    $fotoNombre = null;

    if(isset($_FILES["foto"]) && $_FILES["foto"]["error"] == 0){

        $extension = pathinfo(
            $_FILES["foto"]["name"], 
            PATHINFO_EXTENSION
        );
        $fotoNombre = $documento . "." . strtolower($extension);

        $rutaDestino = ROOT_PATH . 
            "/uploads/personas/" . 
            $fotoNombre;

        move_uploaded_file(
            $_FILES["foto"]["tmp_name"],
            $rutaDestino
        );
    }
        $foto = "uploads/personas/" . $fotoNombre;


    // ==========================================================
    // INSERTAR
    // ==========================================================

    $sql = " INSERT INTO usuario(
                nombres,
                apellidos,
                id_tipo_documento,
                numero_documento,
                correo,
                telefono,
                celular,
                fecha_nacimiento,
                id_genero,
                id_estado_civil,
                id_ocupacion,
                direccion,
                id_pais,
                id_departamento,
                id_ciudad,
                foto
            )
            VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $nombres,
        $apellidos,
        $id_tipo_documento,
        $numero_documento,
        $correo,
        $telefono,
        $celular,
        $fecha_nacimiento,
        $id_genero,
        $id_estado_civil,
        $id_ocupacion,
        $direccion,
        $id_pais,
        $id_departamento,
        $id_ciudad,
        $foto
    ]);

    header("Location: ../configuracion/personas.php?tipo=success&texto=Persona registrada correctamente.");

    exit;

} catch (PDOException $e) {

    die("Error: " . $e->getMessage());

}