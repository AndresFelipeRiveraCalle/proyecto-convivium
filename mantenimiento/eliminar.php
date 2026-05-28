<?php
// Conectar a la base de datos
require_once "conexion.php";

// Obtener el ID a eliminar
$id = $_GET["id"];

// Validar que el ID no esté vacío
if($id != "") {
    
    // Consulta SQL para eliminar
    $sql = "DELETE FROM mantenimiento WHERE id = $id";
    
    // Ejecutar la consulta
    if($conn->query($sql)) {
        // Si todo bien, ir al listado
        header("Location: listar.php");
    } else {
        echo "Error al eliminar: " . $conn->error;
    }
    
} else {
    echo "ID inválido";
}

$conn->close();
?>