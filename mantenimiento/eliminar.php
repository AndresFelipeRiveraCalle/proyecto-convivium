<?php

require_once "conexion.php";

if (isset($_GET["id"])) {
    $id = $_GET["id"];

    if (!empty($id)) {
        $sql = "DELETE FROM mantenimiento WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([":id" => $id]);

        header("Location: mantenimientos_pendientes.php");
        exit;
    } else {
        echo "ID inválido";
    }
} else {
    echo "No se recibió ningún ID";
}
?>