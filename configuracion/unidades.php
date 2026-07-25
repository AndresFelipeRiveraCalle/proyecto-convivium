<?php
require_once "../config/conexion.php";

$sql = "SELECT id_tipo_config, nombre_grupo, cantidad_unidades FROM detalle_tipos_unidad
WHERE activo = 1
ORDER BY id_tipo_config";

$stmt = $conexion->query($sql);

$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$idGrupoSeleccionado = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($idGrupoSeleccionado == 0 && !empty($grupos)) {
    $idGrupoSeleccionado = $grupos[0]['id_tipo_config'];
}
$idGrupoSeleccionado = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($idGrupoSeleccionado == 0 && !empty($grupos)) {
    $idGrupoSeleccionado = $grupos[0]['id_tipo_config'];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Convivium - Inicio </title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <script src="assets/js/calendar.js" defer></script>
    <script src="assets/js/modal_popup.js?v=1.0"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<?php include "includes/sidebar.php"; ?>

<body>
    <main>
        <div class="tabs-container">
            <?php foreach ($grupos as $grupo): ?>
                <a
                    href="unidades.php?id=<?= $grupo['id_tipo_config'] ?>"
                    class="tab-button <?= $grupo['id_tipo_config'] == $idGrupoSeleccionado ? 'active' : '' ?>">
                    <?= htmlspecialchars($grupo['nombre_grupo']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <table class="tabla">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Piso</th>
                    <th>Área</th>
                    <th>Coeficiente</th>
                    <th>Estado</th>
                    <th></th>
                </tr>

            </thead>
            <tbody>
                <?php foreach ($unidades as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['codigo']) ?></td>
                        <td><?= htmlspecialchars($u['piso']) ?></td>
                        <td><?= htmlspecialchars($u['area']) ?></td>
                        <td><?= htmlspecialchars($u['coeficiente']) ?></td>
                        <td><?= htmlspecialchars($u['estado']) ?></td>
                        <td>
                            <button class="btn-secondary">
                                Editar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>

            </tbody>

        </table>

    </main>
</body>