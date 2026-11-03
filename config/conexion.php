<?php

$host = getenv('DB_HOST');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');
$port = getenv('DB_PORT');

$host = ($host === false || trim($host) === '')
    ? 'localhost'
    : trim($host);

$db = ($db === false || trim($db) === '')
    ? 'convivium'
    : trim($db);

$user = ($user === false || trim($user) === '')
    ? 'root'
    : trim($user);

$pass = ($pass === false)
    ? ''
    : $pass;

$port = ($port === false || trim($port) === '')
    ? 3306
    : (int)$port;

if ($port < 1 || $port > 65535) {
    throw new RuntimeException(
        'DB_PORT debe ser un puerto válido.'
    );
}

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $conexion = new PDO($dsn, $user, $pass, $options);

} catch (PDOException $e) {
    error_log($e->getMessage());

    http_response_code(500);
    exit('Error de conexión. Intenta más tarde.');
}