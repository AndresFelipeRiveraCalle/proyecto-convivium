<?php
session_start();
require_once "../config/permisos.php";
solo_admin(); // SOLO admin puede actualizar estudiantes

require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = isset($_POST["id"]) ? $_POST["id"] : "";
    $nombre = isset($_POST["nombre"]) ? trim($_POST["nombre"]) : "";
    $apellido = isset($_POST["apellido"]) ? trim($_POST["apellido"]) : "";
    $correo = isset($_POST["correo"]) ? trim($_POST["correo"]) : "";
    $ciudad = isset($_POST["ciudad"]) ? trim($_POST["ciudad"]) : ""; 
    $fecha_nacimiento = isset($_POST["fecha_nacimiento"]) ? $_POST["fecha_nacimiento"] : "";

    if (!empty($id) && !empty($nombre) && !empty($apellido) && !empty($correo) && !empty($ciudad) && !empty($fecha_nacimiento)) {

        // Verificar si el correo ya existe en OTRO estudiante
        $sql_check = "SELECT id FROM estudiantes WHERE correo = :correo AND id != :id";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->execute([":correo" => $correo, ":id" => $id]);
        
        if ($stmt_check->fetch()) {
            echo "Error: Ya existe otro estudiante con ese correo electrónico";
            exit;
        }

        $sql = "UPDATE estudiantes 
                SET nombre = :nombre,
                    apellido = :apellido,
                    correo = :correo,
                    ciudad = :ciudad,
                    fecha_nacimiento = :fecha_nacimiento
                WHERE id = :id";

        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ":id" => $id,
            ":nombre" => $nombre,
            ":apellido" => $apellido,
            ":correo" => $correo,
            ":ciudad" => $ciudad,
            ":fecha_nacimiento" => $fecha_nacimiento
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