<?php

// cursos/guardar.php
session_start();
require_once "../config/permisos.php";
solo_admin(); // SOLO admin puede guardar cursos

require_once "../config/conexion.php";




// verificamos que venga por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // recibimos los datos del formulario
    $id = isset($_POST["id"]) ? $_POST["id"] : "";
    $nombre = isset($_POST["nombre"]) ? trim($_POST["nombre"]) : "";
    $descripcion = isset($_POST["descripcion"]) ? trim($_POST["descripcion"]) : "";

    // validamos que el id y el nombre no estén vacios
    if (!empty($id) && !empty($nombre)) {

        // consulta SQL UPDATE
        $sql = "UPDATE cursos 
                SET nombre = :nombre,
                    descripcion = :descripcion
                WHERE id = :id";

        // preparamos la consulta
        $stmt = $conexion->prepare($sql);

        // ejecutamos con los datos
        $stmt->execute([
            ":id" => $id,
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