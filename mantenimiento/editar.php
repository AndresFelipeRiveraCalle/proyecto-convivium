<?php
require_once "../config/conexion.php";

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
if ($id === 0) { echo "ID inválido."; exit; }

// preparamos consulta ala base de datos
$stmt = $conexion->prepare("SELECT * FROM mantenimiento WHERE id = :id");
$stmt->execute([':id' => $id]);
$fila = $stmt->fetch();

if (!$fila) { echo "Mantenimiento no encontrado."; exit; }

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
    <title>Editar Mantenimiento #<?= $fila['id'] ?></title>
</head>
<body>

<h1>Editar Mantenimiento #<?= $fila['id'] ?></h1>

<form action="actualizar.php" method="POST" enctype="multipart/form-data">

    <input type="hidden" name="id" value="<?= $fila['id'] ?>">

    <div>
        <label>Zona comun:</label><br>
        <select name="zona_id" required>
            <?php foreach ($zonas as $zona): ?>
                <option value="<?= $zona['id'] ?>"
                    <?= $fila['zona_id'] == $zona['id'] ? "selected" : "" ?>>
                    <?= htmlspecialchars($zona['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <br>

    <div>
        <label>Solicitante:</label><br>
        <select name="usuario_reporta_id" required>
            <?php foreach ($usuarios as $u): ?>
                <option value="<?= $u['id'] ?>"
                    <?= $fila['usuario_reporta_id'] == $u['id'] ? "selected" : "" ?>>
                    <?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <br>

    <div>
        <label>Descripcion:</label><br>
        <textarea name="descripcion" rows="4" cols="50" required><?= htmlspecialchars($fila['descripcion']) ?></textarea>
    </div>
    <br>

    <div>
        <label>Prioridad:</label><br>
        <select name="prioridad">
            <option value="baja"  <?= $fila['prioridad'] == "baja"  ? "selected" : "" ?>>Baja</option>
            <option value="media" <?= $fila['prioridad'] == "media" ? "selected" : "" ?>>Media</option>
            <option value="alta"  <?= $fila['prioridad'] == "alta"  ? "selected" : "" ?>>Alta</option>
        </select>
    </div>
    <br>

    <div>
        <label>Estado:</label><br>
        <select name="estado">
            <option value="pendiente"  <?= $fila['estado'] == "pendiente"  ? "selected" : "" ?>>Pendiente</option>
            <option value="en_proceso" <?= $fila['estado'] == "en_proceso" ? "selected" : "" ?>>En Proceso</option>
            <option value="solucionado"<?= $fila['estado'] == "solucionado"? "selected" : "" ?>>Solucionado</option>
        </select>
    </div>
    <br>

    <div>
        <label>Fecha de solucion (opcional):</label><br>
        <input type="datetime-local" name="fecha_solucion" value="<?= $fechaSolucion ?>">
    </div>
    <br>

    <div>
        <label>Evidencia actual:</label><br>
        <?php if (!empty($fila['evidencia']) && file_exists($fila['evidencia'])): ?>
            <a href="<?= $fila['evidencia'] ?>" target="_blank">Ver archivo actual</a>
        <?php else: ?>
            <em>Sin evidencia guardada</em>
        <?php endif; ?>
        <br><br>
        <label>Subir nueva evidencia (reemplaza la anterior):</label><br>
        <input type="file" name="evidencia" accept="image/*,application/pdf">
    </div>
    <br>

    <div>
        <button type="submit">Guardar Cambios</button>
        <a href="listar.php">Cancelar</a>
    </div>

</form>

</body>
</html>