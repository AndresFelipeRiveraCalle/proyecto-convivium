<?php
// Archivo: auth/restablecer_contrasena.php
session_start();

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

$mensajeError = "";
$tokenValido = false;

$tokenRecibido = $_GET["token"] ?? $_POST["token"] ?? "";
$token = is_string($tokenRecibido) ? trim($tokenRecibido) : "";

if (!preg_match('/\A[a-f0-9]{64}\z/i', $token)) {
    $mensajeError = "El enlace de recuperación es inválido o ya ha expirado.";
} else {
    $tokenHash = hash("sha256", $token);

    try {
        $ahora = new DateTimeImmutable(
            "now",
            new DateTimeZone("America/Bogota")
        );

        $sql = "
            SELECT id
            FROM usuario
            WHERE token_recuperacion = :token
              AND expiracion_token >= :ahora
              AND estado = 1
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ":token" => $tokenHash,
            ":ahora" => $ahora->format("Y-m-d H:i:s")
        ]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            $mensajeError = "El enlace de recuperación es inválido o ya ha expirado.";
        } else {
            $tokenValido = true;

            if (
                $_SERVER["REQUEST_METHOD"] === "POST"
                && isset($_POST["nueva_contrasena"])
            ) {
                $contrasenaRecibida = $_POST["nueva_contrasena"];
                $nuevaContrasena = is_string($contrasenaRecibida)
                    ? $contrasenaRecibida
                    : "";

                if (strlen($nuevaContrasena) < 6) {
                    $mensajeError = "La contraseña debe tener al menos 6 caracteres.";
                } else {
                    $hashContrasena = password_hash(
                        $nuevaContrasena,
                        PASSWORD_DEFAULT
                    );

                    $sqlActualizar = "
                        UPDATE usuario
                        SET contrasena = :contrasena,
                            token_recuperacion = NULL,
                            expiracion_token = NULL
                        WHERE id = :id
                          AND token_recuperacion = :token
                          AND expiracion_token >= :ahora
                    ";

                    $stmtActualizar = $conexion->prepare($sqlActualizar);
                    $stmtActualizar->execute([
                        ":contrasena" => $hashContrasena,
                        ":id" => $usuario["id"],
                        ":token" => $tokenHash,
                        ":ahora" => $ahora->format("Y-m-d H:i:s")
                    ]);

                    if ($stmtActualizar->rowCount() === 1) {
                        $_SESSION = [];
                        session_regenerate_id(true);
                        header("Location: ../login.php?exito=cambio_clave");
                        exit();
                    }

                    $tokenValido = false;
                    $mensajeError = "El enlace de recuperación es inválido o ya ha expirado.";
                }
            }
        }
    } catch (Throwable $error) {
        error_log("Error al restablecer contraseña: " . $error->getMessage());
        $tokenValido = false;
        $mensajeError = "No fue posible procesar el enlace. Intenta solicitar uno nuevo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva contraseña - Convivium</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body class="login-body">
    <div class="login-container">
        <h2>Restablecer contraseña</h2>
        <p class="subtitle">Ingresa tu nueva contraseña</p>

        <?php if ($mensajeError !== ""): ?>
            <div class="alerta-error">
                <strong>Atención:</strong>
                <?= htmlspecialchars($mensajeError, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <?php if ($tokenValido): ?>
            <form action="restablecer_contrasena.php" method="POST">
                <input
                    type="hidden"
                    name="token"
                    value="<?= htmlspecialchars($token, ENT_QUOTES, "UTF-8") ?>"
                >

                <div class="form-group">
                    <label for="nueva_contrasena">Nueva contraseña</label>
                    <input
                        type="password"
                        id="nueva_contrasena"
                        name="nueva_contrasena"
                        placeholder="Mínimo 6 caracteres"
                        required
                        minlength="6"
                        autocomplete="new-password"
                    >
                </div>

                <button type="submit" class="btn-submit">
                    Guardar nueva contraseña
                </button>
            </form>
        <?php else: ?>
            <div style="text-align: center; margin-top: 15px;">
                <a
                    href="../recuperar_contrasena.php"
                    style="color: #2563eb; font-size: 13px; text-decoration: none; font-weight: 600;"
                >Solicitar otro enlace</a>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 15px;">
            <a
                href="../login.php"
                style="color: #2563eb; font-size: 13px; text-decoration: none; font-weight: 600;"
            >Ir al login</a>
        </div>
    </div>
</body>
</html>