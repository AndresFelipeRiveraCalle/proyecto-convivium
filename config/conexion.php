<?php
$host = "localhost";
$db   = "desarrollo_agil";
$user = "root";
$pass = "";

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Manejo de errores
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Devuelve arrays asociativos
    ];

    $conexion = new PDO($dsn, $user, $pass, $options);

} catch (PDOException $e) {
    // Mensaje genérico (usuario)
    echo "Error de conexión. Intenta más tarde.";

    // Mensaje real (desarrollador)
    error_log($e->getMessage());

    exit;
}
?>