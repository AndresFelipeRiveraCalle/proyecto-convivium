<?php

require_once "config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

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