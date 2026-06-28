<?php
$host = "localhost";
$db   = "convivium";
$user = "root";
$pass = "";

try {

    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $conexion = new PDO($dsn, $user, $pass, $options);

} catch (PDOException $e) {

    echo "Error de conexión. Intenta más tarde.";

    error_log($e->getMessage());

    exit;
}
?>