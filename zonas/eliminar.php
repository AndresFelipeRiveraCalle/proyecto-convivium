<?php

require_once "../config/conexion.php";

// Validar que el ID exista en la URL y no esté vacío
if (isset($_GET['id']) && !empty($_GET['id'])) {

    $id = $_GET['id'];

    // Consulta limpia sin alias
    $sql = "DELETE FROM zona_comun WHERE id = :id";
    $stmt = $conexion->prepare($sql);

    // Se ejecuta pasando la variable sanitizada
    $resultado = $stmt->execute([':id' => $id]);

    if ($resultado) {
        header("Location: index.php?mensaje=eliminado");
        exit;
    }
} else {
    // Si alguien intenta entrar a eliminar.php sin pasar un ID por la URL
    header("Location: index.php");
    exit;
}
?>