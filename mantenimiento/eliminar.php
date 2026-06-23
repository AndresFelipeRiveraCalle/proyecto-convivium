<?php
require_once "../config/conexion.php";
 // eliminamos con id
$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id === 0) {
    echo "ID inválido.";
    exit;
}

$stmt = $conexion->prepare("DELETE FROM mantenimiento WHERE id = :id");

if ($stmt->execute([':id' => $id])) {
    header("Location: listar.php");
    exit;
} else {
    echo "Error al eliminar el registro.";
}
?>