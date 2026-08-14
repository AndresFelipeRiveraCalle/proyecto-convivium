<?php
require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

// recibimos los datos del residente 
$idPertenece       = $_POST["id_pertenece"];
$idUsuario         = $_POST["id_usuario"];
$nombreResidente   = trim($_POST["nombre_residente"]);
$apellidoResidente = trim($_POST["apellido_residente"]);
$documento         = trim($_POST["documento"]);           
$tipoDocumento     = $_POST["tipo_documento"];            
$correoResidente   = trim($_POST["correo_residente"]);
$telefonoResidente = trim($_POST["telefono_residente"]);
$telefono2         = trim($_POST["telefono_2"]);         
$tipoResidente     = $_POST["tipo_residente"];

try {
    // actualizar los datos personales del usuario 
    $sqlUsuario = "UPDATE usuario 
                    SET 
                        nombre = :nombre, 
                        apellido = :apellido,
                        documento = :documento,          
                        tipo_documento = :tipo_documento, 
                        correo = :correo, 
                        telefono = :telefono,
                        telefono_2 = :telefono_2          
                    WHERE id = :id_usuario";
    
    $consultaUsuario = $conexion->prepare($sqlUsuario);
    $consultaUsuario->execute([
        ":nombre"        => $nombreResidente,
        ":apellido"      => $apellidoResidente,
        ":documento"     => $documento,              
        ":tipo_documento"=> $tipoDocumento,          
        ":correo"        => $correoResidente,
        ":telefono"      => $telefonoResidente,
        ":telefono_2"    => $telefono2,              
        ":id_usuario"    => $idUsuario
    ]);

    // actualizar el tipo de residente ya sea inquilino o propietario

    $sqlPertenece = "UPDATE pertenece SET tipo = :tipo WHERE id = :id_pertenece";
    $consultaPertenece = $conexion->prepare($sqlPertenece);
    $consultaPertenece->execute([
        ":tipo"         => $tipoResidente,
        ":id_pertenece" => $idPertenece
    ]);

    header("Location: listar.php");
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());
    echo "Ocurrio un error al actualizar el residente. Intenta mas tarde.";
}
?>