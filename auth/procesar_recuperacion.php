<?php
// Archivo: auth/procesar_recuperacion.php
session_start();
require_once "../config/conexion.php";
require_once "../config/seguridad_correo.php"; // <-- Cargamos el motor de correos

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $correo = trim($_POST['correo_recuperacion']);

    try {
        $sql = "SELECT * FROM usuario WHERE correo = :correo LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':correo' => $correo]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $token = bin2hex(random_bytes(32));
            
            date_default_timezone_set('America/Bogota');
            $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $sql_update = "UPDATE usuario SET token_recuperacion = :token, expiracion_token = :expiracion WHERE correo = :correo";
            $stmt_update = $conexion->prepare($sql_update);
            $stmt_update->execute([
                ':token' => $token,
                ':expiracion' => $expiracion,
                ':correo' => $correo
            ]);

            // Intentamos enviar el correo real
            if (enviarCorreoRecuperacion($correo, $token)) {
                // Redirigimos con mensaje de éxito (sin mostrar el token en la URL)
                header("Location: ../recuperar_contrasena.php?exito=correo_enviado");
                exit();
            } else {
                // Si PHPMailer falla (por credenciales o internet)
                header("Location: ../recuperar_contrasena.php?error=no_envio");
                exit();
            }

        } else {
            header("Location: ../recuperar_contrasena.php?error=no_existe");
            exit();
        }

    } catch(PDOException $e) {
        error_log("Error en recuperación: " . $e->getMessage());
        header("Location: ../recuperar_contrasena.php?error=sistema");
        exit();
    }
} else {
    header("Location: ../login.php");
    exit();
}
?>