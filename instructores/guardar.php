<?php
// instructores/listar.php
session_start();
require_once "../config/permisos.php";
solo_admin(); // SOLO admin puede ver instructores
require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = isset($_POST["nombre"]) ? trim($_POST["nombre"]) : "";
    $apellido = isset($_POST["apellido"]) ? trim($_POST["apellido"]) : "";
    $correo = isset($_POST["correo"]) ? trim($_POST["correo"]) : "";
    $especialidad = isset($_POST["especialidad"]) ? trim($_POST["especialidad"]) : "";

    // Validar campos obligatorios
    if (!empty($nombre) && !empty($apellido) && !empty($correo)) {
        
        // Verificar si el correo ya existe
        $sql_check = "SELECT id FROM instructores WHERE correo = :correo";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->execute([":correo" => $correo]);
        
        if ($stmt_check->fetch()) {
            echo "Error: Ya existe un instructor con ese correo electrónico";
            exit;
        }
        
        // Insertar nuevo instructor
        $sql = "INSERT INTO instructores (nombre, apellido, correo, especialidad)
                VALUES (:nombre, :apellido, :correo, :especialidad)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ":nombre" => $nombre,
            ":apellido" => $apellido,
            ":correo" => $correo,
            ":especialidad" => $especialidad
        ]);
        
        // Redirigir al listado
        header("Location: listar.php");
        exit;
        
    } else {
        echo "Error: Nombre, apellido y correo son obligatorios";
    }
} else {
    echo "Acceso no permitido";
}
?>