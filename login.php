<?php
session_start(); // inicio de sesion

// cuando este logueado nos dirige al dashboard
if (isset($_SESSION["usuario"])) {
    header("Location: dashboard/index.php");
    exit;
}

// incluir la conexion de la base de datos
require_once "config/conexion.php";

$error = "";

// obtener informacion post del formulario login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST["usuario"];
    $nombre = $_POST["nombre"];
    $apellido = $_POST["apellido"];
    $password = $_POST["password"];

    if (!empty($usuario) && !empty($password)) {
        // busca al usuario por su identificacion unica
        $sql = "SELECT * FROM usuarios WHERE usuario = :usuario";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([":usuario" => $usuario]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password_hash"])) {
            $_SESSION["usuario"] = $user["usuario"];
            $_SESSION["nombre"] = $user["nombre"];
            $_SESSION["apellido"] = $user["apellido"];
            $_SESSION["rol"] = $user["rol"];
            header("Location: dashboard/index.php");
            exit;
        } else {
            $error = "Usuario o contraseña incorrectos";
        }
    } else {
        $error = "Complete todos los campos";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Academia</title>
    <!-- Enlazamos tu archivo CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="form-card login-card">
            <div class="login-header">
                <h1>Academia</h1>
                <p>Sistema de Gestión</p>
            </div>

            <!-- mostramos mensaje de error si lo hay-->
            <?php if($error): ?>
                <div class="mensaje-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <div class="login-image">
                <img src="assets/css/img/login.jpg" alt="Imagen de bienvenida Academia">
            </div>
            
            <form action="" method="post">
                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <input type="email" name="usuario" id="usuario" placeholder="ejemplo@correo.com" autocomplete="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" name="password" id="password" placeholder="Ingrese su contraseña" autocomplete="current-password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="width: 100%;">Iniciar sesión</button>
            </form>

            <div class="login-footer">
                <a href="registro.php">¿No tiene una cuenta? Registrese</a>
            </div>
        </div>
    </div>
</body>
</html>