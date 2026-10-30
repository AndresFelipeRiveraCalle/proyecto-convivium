<?php
// Archivo: auth/procesar_recuperacion.php


session_start();

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";
require_once ROOT_PATH . "/auth/enviar_correo_recuperacion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit();
}

$correoRecibido = $_POST["correo_recuperacion"] ?? "";
$correo = is_string($correoRecibido) ? trim($correoRecibido) : "";

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../recuperar_contrasena.php?error=correo_invalido");
    exit();
}

try {
    $sql = "
        SELECT id, correo
        FROM usuario
        WHERE correo = :correo
          AND estado = 1
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([":correo" => $correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Respuesta genérica para no revelar si la cuenta existe.
    if (!$usuario) {
        header("Location: ../recuperar_contrasena.php?exito=correo_enviado");
        exit();
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash("sha256", $token);

    $ahora = new DateTimeImmutable(
        "now",
        new DateTimeZone("America/Bogota")
    );
    $expiracion = $ahora->modify("+1 hour")->format("Y-m-d H:i:s");

    $sqlActualizar = "
        UPDATE usuario
        SET token_recuperacion = :token,
            expiracion_token = :expiracion
        WHERE id = :id
    ";

    $stmtActualizar = $conexion->prepare($sqlActualizar);
    $stmtActualizar->execute([
        ":token" => $tokenHash,
        ":expiracion" => $expiracion,
        ":id" => $usuario["id"]
    ]);

    if (!enviarCorreoRecuperacion($usuario["correo"], $token)) {
        $sqlLimpiar = "
            UPDATE usuario
            SET token_recuperacion = NULL,
                expiracion_token = NULL
            WHERE id = :id
              AND token_recuperacion = :token
        ";

        $stmtLimpiar = $conexion->prepare($sqlLimpiar);
        $stmtLimpiar->execute([
            ":id" => $usuario["id"],
            ":token" => $tokenHash
        ]);

        header("Location: ../recuperar_contrasena.php?error=no_envio");
        exit();
    }

    header("Location: ../recuperar_contrasena.php?exito=correo_enviado");
    exit();

} catch (Throwable $error) {
    error_log("Error en recuperación de contraseña: " . $error->getMessage());
    header("Location: ../recuperar_contrasena.php?error=sistema");
    exit();
}
?>