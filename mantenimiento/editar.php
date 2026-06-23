<?php
require_once "../config/conexion.php";

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
if ($id === 0) {
    echo "ID inválido.";
    exit;
}

// preparamos consulta ala base de datos
$stmt = $conexion->prepare("SELECT * FROM mantenimiento WHERE id = :id");
$stmt->execute([':id' => $id]);
$fila = $stmt->fetch();

if (!$fila) {
    echo "Mantenimiento no encontrado.";
    exit;
}

$zonas    = $conexion->query("SELECT id, nombre FROM zona_comun ORDER BY nombre")->fetchAll();
$usuarios = $conexion->query("SELECT id, nombre, apellido FROM usuario ORDER BY nombre")->fetchAll();

// formatear fecha para input datetime-local
$fechaSolucion = "";
if (!empty($fila['fecha_solucion']) && $fila['fecha_solucion'] !== "0000-00-00 00:00:00") {
    $fechaSolucion = date('Y-m-d\TH:i', strtotime($fila['fecha_solucion']));
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar mantenimiento</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/calendar.js" defer></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <?php include "../includes/sidebar.php"; ?>

    <main class="contenido">

    <h2>Editar mantenimiento con identificardor <?= $fila['id'] ?></h2>

        <div class="bloque filtros">
            <form action="actualizar.php" method="POST" enctype="multipart/form-data">

                <input type="hidden" name="id" value="<?= $fila['id'] ?>">

                <div class="bloque filtros">
                    <label>Zona comun:</label><br>
                    <select name="zona_id" required>
                        <?php foreach ($zonas as $zona): ?>
                            <option value="<?= $zona['id'] ?>"
                                <?= $fila['zona_id'] == $zona['id'] ? "selected" : "" ?>>
                                <?= htmlspecialchars($zona['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Solicitante:</label><br>
                    <select name="usuario_reporta_id" required>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id'] ?>"
                                <?= $fila['usuario_reporta_id'] == $u['id'] ? "selected" : "" ?>>
                                <?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                
                    <label>Prioridad:</label><br>
                    <select name="prioridad">
                        <option value="baja" <?= $fila['prioridad'] == "baja"  ? "selected" : "" ?>>Baja - No es urgente</option>
                        <option value="media" <?= $fila['prioridad'] == "media" ? "selected" : "" ?>>Media - Se puede esperar unos días</option>
                        <option value="alta" <?= $fila['prioridad'] == "alta"  ? "selected" : "" ?>>Alta - Urgente, hay que atender pronto</option>
                    </select>

                </div>
                <br>

                <div class="bloque filtros">
                    <h4>Descripcion:</h4>
                    <textarea class="textarea-tareas" name="descripcion" required><?= htmlspecialchars($fila['descripcion']) ?></textarea>
                
                                    <h4>Estado:</h4>
                    <select name="estado">
                        <option value="pendiente" <?= $fila['estado'] == "pendiente"  ? "selected" : "" ?>>Pendiente</option>
                        <option value="en_proceso" <?= $fila['estado'] == "en_proceso" ? "selected" : "" ?>>En Proceso</option>
                        <option value="solucionado" <?= $fila['estado'] == "solucionado" ? "selected" : "" ?>>Solucionado</option>
                    </select>
                

                    <label>Fecha de solucion (opcional):</label><br>
                    <input type="datetime-local" name="fecha_solucion" value="<?= $fechaSolucion ?>">

                
                </div>
                <br>


                <div class="bloque filtros">
                    <h4>Evidencia actual:</h4>
                    <?php if (!empty($fila['evidencia']) && file_exists($fila['evidencia'])): ?>
                        <a href="<?= $fila['evidencia'] ?>" target="_blank">Ver archivo actual</a>
                    <?php else: ?>
                        <em>Sin evidencia guardada</em>
                    <?php endif; ?>
                </div>
                <div class="bloque filtros">
                    <h4>Subir nueva evidencia (reemplaza la anterior):</h4>
                    <input type="file" name="evidencia" accept="image/*,application/pdf">
                </div>
                

                <div>
                    <button type="submit" class="btn-filtrar">Guardar Mantenimiento</button>
                    <button type="submit" class="btn-filtrar">
                        <a href="listar.php">Cancelar</a>
                    </button>

                </div>
            </form>
        </div>
    </main>

</body>

</html>