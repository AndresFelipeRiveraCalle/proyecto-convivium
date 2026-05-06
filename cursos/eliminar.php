<?php
// cursos/guardar.php
session_start();
require_once "../config/permisos.php";
solo_admin(); // SOLO admin puede guardar cursos

require_once "../config/conexion.php";






// verificamos que exista el id en la URL
if (isset($_GET["id"])) {

    $id = $_GET["id"];

    // validamos que el id no esté vacio
    if (!empty($id)) {

        // consulta SQL para eliminar
        $sql = "DELETE FROM cursos WHERE id = :id";

        // preparamos la consulta
        $stmt = $conexion->prepare($sql);

        // ejecutamos con el id
        $stmt->execute([
            ":id" => $id
        ]);

        // redirigimos al listado
        header("Location: listar.php");
        exit;

    } else {
        echo "ID inválido";
    }

} else {
    echo "No se recibió ningún ID";
}
?>