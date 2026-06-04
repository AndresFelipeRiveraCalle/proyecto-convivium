<?php

// Importa la conexión a la base de datos
require_once "../config/conexion.php";

// Recibe datos del formulario
$nombre = $_POST['nombre'];
$descripcion = $_POST['descripcion'];
$capacidad = $_POST['capacidad'];
$horario = $_POST['horario_disponible'];

// Consulta sql
$validar_nombre = "SELECT * FROM zona_comun WHERE nombre = :nombre";

// Preparar consulta
$stmt_validar = $conexion->prepare($validar_nombre);

// Ejecutar consulta
$stmt_validar->execute([
    ':nombre' => $nombre
]);

// Obtener el resultado
$zona_existente = $stmt_validar->fetch();

// Valida que el nombre no exista en la base de datos
if(!$zona_existente){

    $sql = "INSERT INTO zona_comun
    (nombre, descripcion, capacidad, horario_disponible)
    VALUES
    (:nombre, :descripcion, :capacidad, :horario)";

    // Preparar consulta
    $stmt = $conexion->prepare($sql);

    // Ejecutar consulta
    $stmt->execute([
        ':nombre' => $nombre,
        ':descripcion' => $descripcion,
        ':capacidad' => $capacidad,
        ':horario' => $horario
    ]);

    // Redireccionar con mensaje éxito
    header("Location: index.php?mensaje=registrado");
    exit;
} else {

    // Redireccionar con mensaje existencia
    header("Location: index.php?mensaje=existe");
    exit;
}

?>