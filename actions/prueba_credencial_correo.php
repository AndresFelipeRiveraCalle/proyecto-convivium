<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";
require_once ROOT_PATH . "/config/seguridad_correo.php";


// ==========================================================
// CONFIGURACIÓN ACTIVA
// Obtiene únicamente los datos necesarios para la prueba.
// ==========================================================

$sql = "
    SELECT
        id_configuracion_correo,
        smtp_usuario,
        smtp_password

    FROM configuracion_correo

    WHERE activo = 1

    ORDER BY id_configuracion_correo DESC

    LIMIT 1
";


$stmt =
    $conexion->query($sql);


$configuracion =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


if (!$configuracion) {

    exit(
        'No existe configuración activa.'
    );
}


// ==========================================================
// DESCIFRAR
// No muestra la contraseña real.
// ==========================================================

try {

    $password =
        descifrarCredencialCorreo(
            $configuracion[
                'smtp_password'
            ]
        );


    echo '<pre>';

    echo "CONFIGURACIÓN SMTP\n";
    echo "==================\n\n";

    echo "Usuario: "
        . htmlspecialchars(
            $configuracion[
                'smtp_usuario'
            ]
        )
        . "\n";


    echo "Contraseña descifrada: OK\n";


    echo "Longitud contraseña: "
        . strlen($password)
        . " caracteres\n";


    echo "Primer carácter disponible: "
        . ($password !== ''
            ? 'Sí'
            : 'No'
        )
        . "\n";


    echo '</pre>';


} catch (Throwable $e) {

    echo '<pre>';

    echo "ERROR\n";
    echo "=====\n\n";

    echo htmlspecialchars(
        $e->getMessage()
    );

    echo '</pre>';
}