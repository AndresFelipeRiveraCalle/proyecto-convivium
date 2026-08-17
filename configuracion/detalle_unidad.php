<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

$idUnidad = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($idUnidad <= 0) {
    header("Location: unidades.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| DATOS DE LA UNIDAD
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        u.id_unidad,
        u.id_tipo_config,
        u.codigo,
        u.nombre,
        u.piso,
        u.area,
        u.coeficiente,
        u.estado,
        u.observaciones,
        dt.nombre_grupo,
        tv.nombre AS tipo_vivienda

    FROM unidades u

    INNER JOIN detalle_tipos_unidad dt
        ON dt.id_tipo_config = u.id_tipo_config

    INNER JOIN tipos_vivienda tv
        ON tv.id_tipo_vivienda = dt.id_tipo_vivienda

    WHERE u.id_unidad = :id
      AND u.activo = 1

    LIMIT 1
";

$stmt = $conexion->prepare($sql);

$stmt->execute([
    ':id' => $idUnidad
]);

$unidad = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$unidad) {
    header("Location: unidades.php");
    exit;
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

        <div class="acciones-superior">

            <div>

                <h2>
                    Unidad <?= htmlspecialchars($unidad['codigo']) ?>
                </h2>

                <small>
                    <?= htmlspecialchars($unidad['nombre_grupo']) ?>
                    -
                    <?= htmlspecialchars($unidad['tipo_vivienda']) ?>
                </small>

            </div>

            <div>

                <a
                    href="unidades.php?id=<?= $unidad['id_tipo_config'] ?>"
                    class="btn-secondary">

                    ← Volver a unidades

                </a>

            </div>

        </div>


        <div class="bloque filtros">

            <div class="card">

                <h4>Información de la unidad</h4>

                <p>
                    <strong>Código:</strong>
                    <?= htmlspecialchars($unidad['codigo']) ?>
                </p>

                <p>
                    <strong>Nombre:</strong>
                    <?= htmlspecialchars($unidad['nombre'] ?? '') ?>
                </p>

                <p>
                    <strong>Piso:</strong>
                    <?= htmlspecialchars($unidad['piso'] ?? '') ?>
                </p>

            </div>


            <div class="card">

                <h4>Características</h4>

                <p>
                    <strong>Área:</strong>
                    <?= htmlspecialchars($unidad['area'] ?? '') ?> m²
                </p>

                <p>
                    <strong>Coeficiente:</strong>
                    <?= htmlspecialchars($unidad['coeficiente'] ?? '') ?>
                </p>

                <p>
                    <strong>Estado:</strong>
                    <?= htmlspecialchars($unidad['estado'] ?? '') ?>
                </p>

            </div>

        </div>


        <h3>Personas asociadas</h3>

        <div class="bloque filtros">

            <div class="card">

                <p>
                    Todavía no hay personas asociadas a esta unidad.
                </p>

            </div>

        </div>

    </main>

</div>

</body>

</html>