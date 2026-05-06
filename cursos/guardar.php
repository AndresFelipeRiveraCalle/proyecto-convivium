<?php
// cursos/guardar.php
session_start();
require_once "../config/permisos.php";
solo_admin(); // SOLO admin puede guardar cursos

require_once "../config/conexion.php";




// verificamos que venga por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // recibimos los datos del formulario
    $nombre = isset($_POST["nombre"]) ? trim($_POST["nombre"]) : "";
    $descripcion = isset($_POST["descripcion"]) ? trim($_POST["descripcion"]) : "";

    // validamos que el nombre no este vacio
    // la descripcion no es obligatoria
    if (!empty($nombre)) {

        // consulta SQL las columna que seamos trabajar 
        $sql = "INSERT INTO cursos (nombre, descripcion) 
                VALUES (:nombre, :descripcion)";

        // preparamos la consulta
        $stmt = $conexion->prepare($sql);

        // ejecutamos con los datos
        $stmt->execute([
            ":nombre" => $nombre,
            ":descripcion" => $descripcion
        ]);

        // redirigimos al listado
        header("Location: listar.php");
        exit;

    } else {
        echo "El nombre del curso es obligatorio";
    }

} else {
    echo "Acceso no permitido";
}
?>