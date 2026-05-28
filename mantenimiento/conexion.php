<?php
// Datos para conectar a la base de datos
$servidor = "localhost";
$usuario = "root";
$clave = "";
$basedatos = "convivium";

// Crear la conexión
$conn = new mysqli($servidor, $usuario, $clave, $basedatos);

// Verificar si hay error
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>