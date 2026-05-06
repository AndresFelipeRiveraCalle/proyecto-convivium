<?php 
session_start();
require_once "../config/permisos.php";
solo_admin(); // SOLO admin puede guardar estudiantes

require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST"){ 

    $nombre = isset($_POST["nombre"]) ? trim($_POST["nombre"]) : "";
    $apellido = isset($_POST["apellido"]) ? trim($_POST["apellido"]) : "";
    $correo = isset($_POST["correo"]) ? trim($_POST["correo"]) : "";
    $ciudad = isset($_POST["ciudad"]) ? trim($_POST["ciudad"]) : "";
    $fecha_nacimiento = isset($_POST["fecha_nacimiento"]) ? $_POST["fecha_nacimiento"] : "";

    if (!empty($nombre) && !empty($apellido) && !empty($correo) && !empty($ciudad) && !empty($fecha_nacimiento)){

        // Verificar si el correo ya existe
        $sql_check = "SELECT id FROM estudiantes WHERE correo = :correo";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->execute([":correo" => $correo]);
        
        if ($stmt_check->fetch()) {
            echo "Error: Ya existe un estudiante con ese correo electrónico";
            exit;
        }

        $sql = "INSERT INTO estudiantes(nombre, apellido, correo, ciudad, fecha_nacimiento)
                VALUES (:nombre, :apellido, :correo, :ciudad, :fecha_nacimiento)";

        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ":nombre"=> $nombre,
            ":apellido"=> $apellido,
            ":correo"=> $correo,
            ":ciudad"=> $ciudad,
            ":fecha_nacimiento"=> $fecha_nacimiento
        ]);

        header("Location: listar.php");
        exit;

    } else {
        echo "Todos los campos son obligatorios";
    }

} else {
    echo "Acceso no permitido";
}
?>