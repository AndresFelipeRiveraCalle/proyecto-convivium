<?php

/**
 * login.php - Controlador de autenticación para el sistema Convivium
 * 
 * Funcionalidad:
 * 1. Inicia sesión del usuario validando correo y contraseña contra la BD
 * 2. Crea variables de sesión si las credenciales son correctas
 * 3. Redirige al dashboard según el resultado
 * 
 * Dependencias:
 * - config/conexion.php: Debe definir $conexion como instancia PDO
 * - Tabla 'usuario': campos id, nombre, apellido, correo, contrasena, estado, rol_id
 * - Tabla 'rol': campos id, nombre
 * 
 * @author Andrés Felipe Rivera Calle
 * @version 1.0
 * @since 2026-05-10
 */

session_start(); // Inicia o reanuda la sesión PHP. Debe ir antes de cualquier salida HTML

/* 
 * BLOQUE COMENTADO: Redirección si ya está logueado
 * Descomentar en producción para evitar que usuarios logueados vean el login
 * if (isset($_SESSION["usuario_id"])) { 
 *     header("Location: dashboard/index.php"); 
 *     exit; 
 * } 
 */

// Incluir archivo de conexión PDO. Ruta relativa desde este archivo
require_once "config/conexion.php";

// Variables para mensajes al usuario en la vista
$mensaje = ""; // Mensajes de éxito, ej: registro completado
$error = "";   // Mensajes de error de validación/login

// Mostrar mensaje si viene de registro.php?registro=1
if (isset($_GET["registro"])) {
    $mensaje = "Cuenta creada exitosamente. Ahora puedes iniciar sesión.";
}

// PROCESAMIENTO DEL FORMULARIO: Solo si el método es POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Capturar datos del formulario. No hacer trim() aquí si la contraseña puede tener espacios
    $correo = $_POST["correo"] ?? '';
    $contrasena = $_POST["password"] ?? '';

    // 2. Validar que no estén vacíos
    if (!empty($correo) && !empty($contrasena)) {

        // 3. Consulta preparada para evitar SQL Injection
        // Se hace JOIN con 'rol' para guardar el nombre del rol en sesión
        // u.estado = TRUE filtra usuarios desactivados
        $sql = "SELECT u.*, r.nombre AS nombre_rol 
                FROM usuario u 
                JOIN rol r ON u.rol_id = r.id 
                WHERE u.correo = :correo AND u.estado = TRUE";

        $stmt = $conexion->prepare($sql);
        $stmt->execute([":correo" => $correo]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC); // Devuelve array asociativo o false

        // 4. Verificar usuario y contraseña
        // password_verify compara texto plano vs hash bcrypt/argon2i generado con password_hash
        if ($user && password_verify($contrasena, $user['contrasena'])) {

            // 5. Guardar datos mínimos necesarios en $_SESSION
            $_SESSION["usuario_id"] = $user["id"];
            $_SESSION["nombre"] = $user["nombre"];
            $_SESSION["apellido"] = $user["apellido"];
            $_SESSION["rol"] = $user["nombre_rol"]; // 'Administrador', 'Cliente', etc

            // 6. Redirigir al dashboard y detener ejecución
            header("Location: dashboard/index.php");
            exit;
        } else {
            // Mensaje genérico por seguridad. No decir si falló correo o contraseña
            $error = "Correo o contraseña incorrectos";
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
    <title>Login - Convivium</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>

<body class="auth-body">
    <div class="auth-card">
        <img src="assets/img/logo_2.png" alt="Convivium" class="auth-logo">
        <p class="auth-subtitle">Sistema de Gestión</p>

        <?php if ($mensaje): ?>
            <div class="mensaje-exito" id="mensaje-exito">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mensaje-error" id="mensaje-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="" method="post">
            <div class="form-group">
                <label for="correo" class="form-label">Correo</label>
                <input type="email" class="form-control" name="correo" id="correo"
                    placeholder="ejemplo@correo.com" autocomplete="email"
                    value="<?= htmlspecialchars($correo ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Contraseña</label>
                <div class="input-group">
                    <input type="password" class="form-control" name="password" id="password"
                        placeholder="Ingrese su contraseña" autocomplete="current-password" required>
                    <button class="btn-toggle-pass" type="button" id="togglePass">👁️</button>
                </div>
            </div>

            <button type="submit" class="btn-primary">Iniciar sesión</button>
        </form>

        <div class="auth-footer">
            <a href="recuperar_contraseña/solicitar_recuperacion.php">¿Olvidaste tu contraseña?</a>
            <a href="registro.php">¿No tiene una cuenta? Regístrese</a>
        </div>
    </div>

    <script src="assets/js/auth.js"></script>
</body>

</html>