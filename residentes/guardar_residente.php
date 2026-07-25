<?php
require_once "../config/conexion.php";

//solo se prosede si el formulario se envia por post
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: agregar_residente.php");
    exit;
}

// resibimos datos del formulario
$nombreResidente       = trim($_POST["nombre_residente"]);
$apellidoResidente     = trim($_POST["apellido_residente"]);
$documento             = trim($_POST["documento"]);
$tipoDocumento         = $_POST["tipo_documento"];
$correoResidente       = trim($_POST["correo_residente"]);
$telefonoResidente     = trim($_POST["telefono_residente"]);
$telefono2             = trim($_POST["telefono_2"]);
$contrasenaResidente   = $_POST["contrasena_residente"];
$idUnidadSeleccionada  = $_POST["id_unidad_seleccionada"];
$tipoResidente         = $_POST["tipo_residente"];

// Encriptamos la contraseña
$contrasenaEncriptada = password_hash($contrasenaResidente, PASSWORD_DEFAULT);

try {
    $conexion->beginTransaction();

    // ============================================
    // 1. CREAMOS EL USUARIO
    // ============================================
    $sqlUsuario = "INSERT INTO usuario (
                        nombre, 
                        apellido, 
                        documento,
                        tipo_documento,
                        correo, 
                        telefono, 
                        telefono_2,
                        contrasena, 
                        rol_id, 
                        estado
                    ) VALUES (
                        :nombre, 
                        :apellido, 
                        :documento,
                        :tipo_documento,
                        :correo, 
                        :telefono, 
                        :telefono_2,
                        :contrasena, 
                        2,
                        1
                    )";
    
    $consultaUsuario = $conexion->prepare($sqlUsuario);
    $consultaUsuario->execute([
        ":nombre"        => $nombreResidente,
        ":apellido"      => $apellidoResidente,
        ":documento"     => $documento,
        ":tipo_documento"=> $tipoDocumento,
        ":correo"        => $correoResidente,
        ":telefono"      => $telefonoResidente,
        ":telefono_2"    => $telefono2,
        ":contrasena"    => $contrasenaEncriptada
    ]);

    $idUsuarioNuevo = $conexion->lastInsertId();

    // ============================================
    // 2. RELACIONAMOS AL USUARIO CON SU APARTAMENTO
    // ============================================
    $sqlPertenece = "INSERT INTO pertenece (unidad_id, usuario_id, tipo, activo)
                        VALUES (:unidad_id, :usuario_id, :tipo, 1)";
    $consultaPertenece = $conexion->prepare($sqlPertenece);
    $consultaPertenece->execute([
        ":unidad_id"  => $idUnidadSeleccionada,
        ":usuario_id" => $idUsuarioNuevo,
        ":tipo"       => $tipoResidente
    ]);

    // ============================================
    // 3. MARCAMOS EL APARTAMENTO COMO OCUPADO
    // ============================================
    $sqlUnidad = "UPDATE unidad SET estado = 'ocupado' WHERE id = :unidad_id";
    $consultaUnidad = $conexion->prepare($sqlUnidad);
    $consultaUnidad->execute([":unidad_id" => $idUnidadSeleccionada]);

    // ============================================
    // 4. SI ES PROPIETARIO, GUARDAMOS SU ID EN LA UNIDAD
    // ============================================
    if ($tipoResidente === 'propietario') {
        $sqlPropietario = "UPDATE unidad 
                            SET propietario_id = :propietario_id 
                            WHERE id = :unidad_id";
        $consultaPropietario = $conexion->prepare($sqlPropietario);
        $consultaPropietario->execute([
            ":propietario_id" => $idUsuarioNuevo,
            ":unidad_id"      => $idUnidadSeleccionada
        ]);
    }

    $conexion->commit();

    header("Location: listar.php");
    exit;

} catch (PDOException $e) {
    $conexion->rollBack();
    error_log($e->getMessage());
    echo "Ocurrió un error al guardar el residente. Intenta más tarde.";
}
?>