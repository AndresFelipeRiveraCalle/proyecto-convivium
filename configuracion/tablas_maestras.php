<?php
require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include ROOT_PATH . "/includes/head.php"; ?>
</head>

<body>

<?php include ROOT_PATH . "/includes/header.php"; ?>
<?php require_once ROOT_PATH . "/includes/mensajes.php"; ?>

<div class="contenedor">

    <?php include ROOT_PATH . "/includes/sidebar.php"; ?>

    <main class="contenido"></main>


        <h1>Tablas Maestras</h1>

        <div class="grid-configuracion">

            <!-- Tipos de documento -->
            <div class="card-configuracion">
                <h3>
                    Tipos de Documento
                </h3>

                <p>
                    Administración de documentos de identificación
                </p>

                <a href="tipos_documento.php" class="btn-primary">
                    Administrar
                </a>
            </div>


            <!-- Géneros -->
            <div class="card-configuracion">

                <h3>
                    Géneros
                </h3>

                <p>
                    Masculino, femenino, otros
                </p>

                <a href="generos.php" class="btn-primary">
                    Administrar
                </a>

            </div>



            <!-- Estados civiles -->
            <div class="card-configuracion">

                <h3>Estados Civiles</h3>

                <p>Estado civil de las personas</p>

                <button type="button" class="btn-primary"
                    id="btnNuevoEstadoCivil">
                    + Administrar
                </button>

            </div>



            <!-- Ocupaciones -->
            <div class="card-configuracion">

                <h3>
                    Ocupaciones
                </h3>

                <p>
                    Actividad laboral o profesión
                </p>

                <a href="ocupaciones.php" class="btn-primary">
                    Administrar
                </a>

            </div>


        </div>
    </main>
</div>
</body>