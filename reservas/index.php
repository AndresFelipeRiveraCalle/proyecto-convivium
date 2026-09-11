<?php
/**
 * =========================================================================
 * MÓDULO: Reservas de Zonas Comunes - Convivium
 * ARCHIVO: index.php
 * DESCRIPCIÓN: Permite consultar y registrar reservas de zonas comunes.
 * AUTOR: Andrés Felipe Rivera Calle
 * FECHA: 2026-09-11
 * HOJA DE ESTILOS: assets/css/reservas.css
 * =========================================================================
 */

// Incluir la ruta de la conexión a la base de datos y config.php
require_once "../config/config.php";
require_once "../config/conexion.php";

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include "../includes/head.php"; ?>
    <title>Reservas de Zonas Comunes</title>
    <!-- Estilos exclusivos del módulo -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/reservas.css?v=<?= time() ?>">
    <!-- JavaScript exclusivo del módulo -->
    <script src="<?= BASE_URL ?>assets/js/reservas.js?v=<?= time() ?>" defer></script>
</head>

<body>
    <?php include "../includes/header.php"; ?>

    <div class="contenedor">

        <?php include "../includes/sidebar.php"; ?>

        <main class="rz-contenido">
            <h1>Reservas de Zonas Comunes</h1>
            <section class="rz-card">

                <!-- Aquí construiremos el formulario en el siguiente bloque -->

            </section>
        </main>

    </div>
</body>

</html>