<?php
session_start();
if(!isset($_SESSION["usuario"])) {
    header("Location: ../login.php");
    exit;
}

require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = isset($_POST["id"]) ? $_POST["id"] : "";
    $nombre = isset($_POST["nombre"]) ? trim($_POST["nombre"]) : "";
    $apellido = isset($_POST["apellido"]) ? trim($_POST["apellido"]) : "";
    $correo = isset($_POST["correo"]) ? trim($_POST["correo"]) : "";
    $especialidad = isset($_POST["especialidad"]) ? trim($_POST["especialidad"]) : "";

    if (!empty($id) && !empty($nombre) && !empty($apellido) && !empty($correo)) {
        
        // Verificar si el correo ya existe en OTRO instructor
        $sql_check = "SELECT id FROM instructores WHERE correo = :correo AND id != :id";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->execute([":correo" => $correo, ":id" => $id]);
        
        if ($stmt_check->fetch()) {
            echo "Error: Ya existe otro instructor con ese correo electrónico";
            exit;
        }
        
        $sql = "UPDATE instructores 
                SET nombre = :nombre, 
                    apellido = :apellido, 
                    correo = :correo, 
                    especialidad = :especialidad 
                WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ":id" => $id,
            ":nombre" => $nombre,
            ":apellido" => $apellido,
            ":correo" => $correo,
            ":especialidad" => $especialidad
        ]);
        
        header("Location: listar.php");
        exit;
        
    } else {
        echo "Error: Nombre, apellido y correo son obligatorios";
    }
} else {
    echo "Acceso no permitido";
}
?>