<?php
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION["usuario"])) {
    header("Location: dashboard/index.php");
    exit;
}

require_once "config/conexion.php";

$error = "";
$exito = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST["nombre"]);
    $apellido = trim($_POST["apellido"]);
    $usuario   = trim($_POST["usuario"]);
    $password  = $_POST["password"];
    $confirmar = $_POST["confirmar"];
    $rol       = $_POST["rol"];

    $roles_permitidos = ["admin", "usuario"];

    // Validación completa
    if (empty($nombre) || empty($apellido) || empty($usuario) || empty($password) || empty($confirmar) || empty($rol)) {
        $error = "Complete todos los campos";
    } elseif (!in_array($rol, $roles_permitidos)) {
        $error = "Rol no válido";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } elseif ($password !== $confirmar) {
        $error = "Las contraseñas no coinciden";
    } else {
        // Verificar si el correo ya existe
        $sql = "SELECT id FROM usuarios WHERE usuario = :usuario";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([":usuario" => $usuario]);

        if ($stmt->fetch()) {
            $error = "Ese correo ya está registrado";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            // ✅ INSERT CORREGIDO - Los placeholders coinciden con las columnas
            $sql  = "INSERT INTO usuarios (usuario, nombre, apellido, password_hash, rol) 
                        VALUES (:usuario, :nombre, :apellido, :hash, :rol)";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                ":usuario" => $usuario,
                ":nombre" => $nombre,
                ":apellido" => $apellido,
                ":hash"    => $hash,
                ":rol"     => $rol
            ]);

            $exito = "Cuenta creada exitosamente, ya puedes iniciar sesión";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Academia</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="form-card login-card">
            <div class="login-header">
                <h1>Academia</h1>
                <p>Crear cuenta</p>
            </div>

            <?php if ($error): ?>
                <div class="mensaje-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($exito): ?>
                <div class="mensaje-exito"><?php echo htmlspecialchars($exito); ?></div>
            <?php endif; ?>

            <?php if (!$exito): ?>
            <form action="" method="post">

                <div class="form-group">
                    <label for="nombre">Nombre(s):</label>
                    <input type="text" name="nombre" id="nombre" placeholder="Ingrese su nombre" required>
                </div>

                <div class="form-group">
                    <label for="apellido">Apellido(s):</label>
                    <input type="text" name="apellido" id="apellido" placeholder="Ingrese su apellido" required>
                </div>

                <div class="form-group">
                    <label for="usuario">Correo</label>
                    <input type="email" name="usuario" id="usuario" placeholder="ejemplo@correo.com"
                        value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="rol">Tipo de cuenta</label>
                    <select name="rol" id="rol" required style="width:100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px;">
                        <option value="">Selecciona un rol</option>
                        <option value="admin"   <?php echo (isset($_POST['rol']) && $_POST['rol'] === 'admin')   ? 'selected' : ''; ?>>Administrador</option>
                        <option value="usuario" <?php echo (isset($_POST['rol']) && $_POST['rol'] === 'usuario') ? 'selected' : ''; ?>>Usuario</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" name="password" id="password" placeholder="Mínimo 6 caracteres" required>
                </div>

                <div class="form-group">
                    <label for="confirmar">Confirmar contraseña</label>
                    <input type="password" name="confirmar" id="confirmar" placeholder="Repite la contraseña" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Crear cuenta
                </button>
            </form>
            <?php endif; ?>

            <div class="login-footer">
                <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
            </div>
        </div>
    </div>
</body>
</html>