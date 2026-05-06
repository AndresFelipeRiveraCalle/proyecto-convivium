<?php
session_start();
require_once "../config/permisos.php";
solo_admin(); // SOLO admin puede eliminar estudiantes

require_once "../config/conexion.php";

if (isset($_GET["id"])) {
    $id = $_GET["id"];

    if (!empty($id)) {
        $sql = "DELETE FROM estudiantes WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([":id" => $id]);

        header("Location: listar.php");
        exit;
    } else {
        echo "ID inválido";
    }
} else {
    echo "No se recibió ningún ID";
}
?>