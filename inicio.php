<?php
// iniciamos la sesion para leer la caja fuerte de php
session_start();
// conexion ala configuraciones del proyecto
require_once "config/config.php";

// Traemos la conexión a la base de datos Para poder consultar datos del dashboard
require_once "config/conexion.php";

// verificamos si no existe la variable de sesion que creamos en el procesar login
if (!isset($_SESSION['id_usuario_autenticado'])) {
    // si no existe significa que el usuario es un intruso y lo mandamos al login
    header("Location: login.php");
    exit(); // bloqueamos la ejecucion del resto del codigo por seguridad
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <?php include ROOT_PATH . "/includes/head.php"; ?>
</head>

<body>

<?php include ROOT_PATH . "/includes/header.php"; ?>
<div class="contenedor">
    <?php include ROOT_PATH . "/includes/sidebar.php"; ?>
    <main class="contenido">
        <img
            src="<?= BASE_URL ?>assets/background/Convivium-Background_4.png"
            class="img-background">
    </main>
</div>
</body>

</html>