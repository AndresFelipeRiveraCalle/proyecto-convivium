<?php

/**
 * registro.php - Controlador de registro de usuarios para el sistema Convivium
 * 
 * Funcionalidad:
 * 1. Muestra formulario de registro con roles disponibles
 * 2. Valida datos: campos vacíos, formato de correo, longitud de contraseña, coincidencia
 * 3. Verifica que el correo no esté registrado previamente
 * 4. Hashea la contraseña y guarda el usuario en BD
 * 5. Redirige a login.php con flag ?registro=exitoso
 * 
 * Dependencias:
 * - config/conexion.php: Debe definir $conexion como instancia PDO
 * - Tabla 'usuario': campos id, nombre, apellido, correo, telefono, contrasena, rol_id, estado
 * - Tabla 'rol': campos id, nombre
 * 
 * @author Andrés Felipe Rivera Calle
 * @version 1.0
 * @since 2026-05-10
 */

/* 
 * BLOQUE COMENTADO: Redirección si ya está logueado
 * Descomentar en producción para evitar que usuarios logueados se registren de nuevo
 * if (isset($_SESSION["usuario_id"])) {
 *     header("Location: dashboard/index1.php");
 *     exit;
 * }
 */

// Incluir archivo de conexión PDO
require_once "config/conexion.php";

// Variables para mensajes al usuario en la vista
$error = ""; // Mensajes de error de validación
$exito = ""; // No se usa porque redirige, pero se deja por si se cambia el flujo

// 1. CARGAR ROLES: Consulta roles permitidos para registro
// Se excluye 'Administrador' para que no se pueda crear desde el formulario público
$sqlRoles = "SELECT * FROM rol WHERE nombre != 'Administrador'";
$stmtRoles = $conexion->query($sqlRoles);
$roles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);

// 2. PROCESAMIENTO DEL FORMULARIO: Solo si el método es POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2.1 Capturar y limpiar datos del formulario
    // trim() elimina espacios al inicio/final. No se aplica a password para permitir espacios intencionales
    $nombre = trim($_POST["nombre"] ?? '');
    $apellido = trim($_POST["apellido"] ?? '');
    $correo = trim($_POST["correo"] ?? '');
    $telefono = trim($_POST["telefono"] ?? '');
    $password  = $_POST["password"] ?? '';
    $confirmar = $_POST["confirmar"] ?? '';
    $rol_id = $_POST["rol_id"] ?? '';

    // 2.2 Validaciones en cascada
    if (empty($nombre) || empty($apellido) || empty($correo) || empty($telefono) || empty($password) || empty($confirmar) || empty($rol_id)) {
        $error = "Complete todos los campos";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } elseif ($password !== $confirmar) {
        $error = "Las contraseñas no coinciden";
    } else {
        // 2.3 Verificar si el correo ya existe en BD
        $sql = "SELECT id FROM usuario WHERE correo = :correo";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([":correo" => $correo]);
        $usuarioExistente = $stmt->fetch();

        if ($usuarioExistente) {
            $error = "El correo ya está registrado";
        } else {
            // 2.4 Hashear contraseña con bcrypt/argon2i. PASSWORD_DEFAULT usa el algoritmo más seguro disponible
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // 2.5 INSERT preparado para evitar SQL Injection
            // La columna 'estado' debería tener DEFAULT TRUE en la BD
            $sql  = "INSERT INTO usuario (nombre, apellido, correo, telefono, contrasena, rol_id) 
                     VALUES (:nombre, :apellido, :correo, :telefono, :contrasena, :rol_id)";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                ":nombre" => $nombre,
                ":apellido" => $apellido,
                ":correo" => $correo,
                ":telefono" => $telefono,
                ":contrasena" => $hash,
                ":rol_id" => $rol_id
            ]);

            // 2.6 Redirigir a login con parámetro GET para mostrar mensaje de éxito
            // El exit es obligatorio para detener la ejecución después del header
            header("Location: login.php?registro=exitoso");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Convivium</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="login-container">
        <div class="form-card login-card">
            <div class="login-header">
                <h1>Convivium</h1>
                <p>Crear cuenta</p>
            </div>

            <!-- Mensaje de error: solo se muestra si $error tiene contenido -->
            <?php if ($error): ?>
                <div class="mensaje-error" id="mensaje-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Mensaje de éxito: actualmente no se usa porque redirige -->
            <?php if ($exito): ?>
                <div class="mensaje-exito"><?php echo htmlspecialchars($exito); ?></div>
            <?php endif; ?>

            <!-- Formulario envía a sí mismo con method POST -->
            <form action="" method="post">

                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" name="nombre" id="nombre" placeholder="Ingrese su nombre" value="<?= htmlspecialchars($nombre ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="apellido">Apellido</label>
                    <input type="text" name="apellido" id="apellido" placeholder="Ingrese su apellido" value="<?= htmlspecialchars($apellido ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="corrreo">Correo</label>
                    <input type="email" name="correo" id="correo" placeholder="ejemplo@correo.com" value="<?= htmlspecialchars($correo ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="text" name="telefono" id="telefono" placeholder="Ingrese su teléfono" value="<?= htmlspecialchars($telefono ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="rol">Tipo de cuenta</label>
                    <select name="rol_id" id="rol_id" required style="width:100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px;">
                        <option value="">Seleccione un rol</option>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?= $rol['id'] ?>" <?= (isset($rol_id) && $rol_id == $rol['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rol['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
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

                <div class="login-footer">
                    <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Oculta el mensaje de error después de 4 segundos
        setTimeout(() => {
            const error = document.getElementById("mensaje-error");
            if (error) error.style.display = "none";
        }, 4000);
    </script>
</body>

</html>