<?php

require_once "../config/conexion.php";

// Recibe datos del formulario
$id = $_POST['id'];
$nombre = $_POST['nombre'];
$descripcion = $_POST['descripcion'];
$capacidad = $_POST['capacidad'];
$horario = $_POST['horario_disponible'];

$sql = "UPDATE zona_comun 
        SET nombre = :nombre, 
            descripcion = :descripcion, 
            capacidad = :capacidad, 
            horario_disponible = :horario
        WHERE id = :id";

// Preparar consulta
$stmt = $conexion->prepare($sql);

// Ejecutar consulta
$stmt->execute([
    'id' => $id,
    ':nombre' => $nombre,
    ':descripcion' => $descripcion,
    ':capacidad' => $capacidad,
    ':horario' => $horario
]);

// Redireccionar con mensaje éxito
header("Location: index.php?mensaje=actualizado");
exit;

?>