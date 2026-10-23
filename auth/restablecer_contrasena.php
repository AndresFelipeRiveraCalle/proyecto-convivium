<?php
// Archivo: auth/restablecer_contrasena.php
session_start();
require_once "../config/conexion.php";

$mensaje_error = "";
$token_valido = false;
$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (!empty($token)) {
    try {
        // 1. Verificamos si el token existe y si aún no ha expirado
        date_default_timezone_set('America/Bogota');
        $ahora = date('Y-m-d H:i:s');

        $sql = "SELECT * FROM usuario WHERE token_recuperacion = :token AND expiracion_token >= :ahora LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':token' => $token,
            ':ahora' => $ahora
        ]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $token_valido = true;

            // 2. Si el usuario envió el formulario con la nueva contraseña
            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nueva_contrasena'])) {
                $nueva_clave = trim($_POST['nueva_contrasena']);
                
                // Encriptamos la nueva contraseña con BCRYPT
                $hash_nuevo = password_hash($nueva_clave, PASSWORD_DEFAULT);

                // Actualizamos la contraseña y limpiamos el token (usando el correo del usuario)
                $sql_update = "UPDATE usuario SET contrasena = :contrasena, token_recuperacion = NULL, expiracion_token = NULL WHERE correo = :correo";
                $stmt_update = $conexion->prepare($sql_update);
                $stmt_update->execute([
                    ':contrasena' => $hash_nuevo,
                    ':correo' => $usuario['correo']
                ]);

                // Redirigimos al login con éxito
                header("Location: ../login.php?exito=cambio_clave");
                exit();
            }

        } else {
            $mensaje_error = "El enlace de recuperación es inválido o ya ha expirado (su duración es de 1 hora).";
        }

    } catch(PDOException $e) {
        $mensaje_error = "Error de sistema: " . $e->getMessage();
    }
} else {
    $mensaje_error = "No se ha proporcionado ningún token de seguridad.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - Convivium</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body class="login-body">

    <div class="login-container">
        <h2>Restablecer</h2>
        <p class="subtitle">Ingresa tu nueva contraseña</p>

        <?php if (!empty($mensaje_error)): ?>
            <div class="alerta-error">
                <strong>Atención:</strong> <?= $mensaje_error ?>
            </div>
            <div style="text-align: center; margin-top: 15px;">
                <a href="../login.php" style="color: #2563eb; font-size: 13px; text-decoration: none; font-weight: 600;">Ir al Login</a>
            </div>
        <?php elseif ($token_valido): ?>

            <form action="restablecer_contrasena.php" method="POST">
                <!-- Pasamos el token oculto para validarlo al enviar -->
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="form-group">
                    <label for="nueva_contrasena">Nueva Contraseña</label>
                    <input type="password" id="nueva_contrasena" name="nueva_contrasena" placeholder="Mínimo 6 caracteres" required minlength="6">
                </div>

                <button type="submit" class="btn-submit" style="background-color: #16a34a;">Guardar Nueva Contraseña</button>
            </form>

        <?php endif; ?>
    </div>

</body>
</html>