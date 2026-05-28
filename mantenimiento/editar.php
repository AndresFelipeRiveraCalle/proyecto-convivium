<?php
// Conectar a la base de datos
require_once "conexion.php";

// Obtener el ID del mantenimiento a editar
$id = $_GET["id"];

// Buscar los datos del mantenimiento
$sql = "SELECT * FROM mantenimiento WHERE id = $id";
$resultado = $conn->query($sql);
$fila = $resultado->fetch_assoc();

// Si no existe, mostrar error
if(!$fila) {
    echo "Mantenimiento no encontrado";
    exit;
}

// Consultar usuarios para el SELECT
$sqlUsuarios = "SELECT id, nombre FROM usuario ORDER BY nombre";
$resultadoUsuarios = $conn->query($sqlUsuarios);

// Consultar torres para el SELECT
$sqlTorres = "SELECT id, nombre FROM conjunto ORDER BY nombre";
$resultadoTorres = $conn->query($sqlTorres);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Editar Mantenimiento</title>
</head>
<body>

<h1>Editar Mantenimiento #<?php echo $fila['id']; ?></h1>

<form action="actualizar.php" method="POST" enctype="multipart/form-data">
    
    <!-- Campo oculto para enviar el ID -->
    <input type="hidden" name="id" value="<?php echo $fila['id']; ?>">

    <!-- Campo: Descripción -->
    <div>
        <label>Descripción:</label><br>
        <textarea name="descripcion" rows="4" cols="50" required><?php echo $fila['descripcion']; ?></textarea>
    </div>

    <br>

    <!-- Campo: Prioridad -->
    <div>
        <label>Prioridad:</label><br>
        <select name="prioridad">
            <option value="baja" <?php if($fila['prioridad'] == "baja") echo "selected"; ?>>Baja</option>
            <option value="media" <?php if($fila['prioridad'] == "media") echo "selected"; ?>>Media</option>
            <option value="alta" <?php if($fila['prioridad'] == "alta") echo "selected"; ?>>Alta</option>
            <option value="critica" <?php if($fila['prioridad'] == "critica") echo "selected"; ?>>Crítica</option>
        </select>
    </div>

    <br>

    <!-- Campo: Estado -->
    <div>
        <label>Estado:</label><br>
        <select name="estado">
            <option value="pendiente" <?php if($fila['estado'] == "pendiente") echo "selected"; ?>>Pendiente</option>
            <option value="en_proceso" <?php if($fila['estado'] == "en_proceso") echo "selected"; ?>>En Proceso</option>
            <option value="finalizado" <?php if($fila['estado'] == "finalizado") echo "selected"; ?>>Finalizado</option>
        </select>
    </div>

    <br>

    <!-- Campo: Responsable -->
    <div>
        <label>Responsable:</label><br>
        <input type="text" name="responsable" size="40" value="<?php echo $fila['responsable']; ?>">
    </div>

    <br>

    <!-- Campo: Costo -->
    <div>
        <label>Costo:</label><br>
        <input type="number" step="0.01" name="costo" value="<?php echo $fila['costo']; ?>">
    </div>

    <br>

    <!-- Campo: Fecha finalización -->
    <div>
        <label>Fecha finalización:</label><br>
        <?php
        // Formatear la fecha para el input datetime-local
        $fechaFormateada = "";
        if($fila['fecha_finalizacion'] != "" && $fila['fecha_finalizacion'] != "0000-00-00 00:00:00") {
            $fechaFormateada = date('Y-m-d\TH:i', strtotime($fila['fecha_finalizacion']));
        }
        ?>
        <input type="datetime-local" name="fecha_finalizacion" value="<?php echo $fechaFormateada; ?>">
    </div>

    <br>

    <!-- Campo: Evidencia - mostrar la actual y permitir subir nueva -->
    <div>
        <label>Evidencia actual:</label><br>
        <?php
        if($fila['evidencia'] != "" && file_exists($fila['evidencia'])) {
            echo "<a href='" . $fila['evidencia'] . "' target='_blank'>📎 Ver archivo actual</a>";
        } else {
            echo "No hay evidencia guardada";
        }
        ?>
        <br><br>
        <label>Subir nueva evidencia (opcional):</label><br>
        <input type="file" name="evidencia" accept="image/*,application/pdf">
        <br><small>Si subes un archivo nuevo, reemplazará al anterior</small>
    </div>

    <br>

    <!-- Campo: Comentarios -->
    <div>
        <label>Comentarios:</label><br>
        <textarea name="comentarios" rows="3" cols="50"><?php echo $fila['comentarios']; ?></textarea>
    </div>

    <br>

    <!-- Campo: Solicitante -->
    <div>
        <label>Solicitante:</label><br>
        <select name="usuario_id">
            <?php while($usuario = $resultadoUsuarios->fetch_assoc()): ?>
                <option value="<?php echo $usuario['id']; ?>"
                    <?php if($fila['usuario_id'] == $usuario['id']) echo "selected"; ?>>
                    <?php echo $usuario['nombre']; ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <br>

    <!-- Campo: Torre -->
    <div>
        <label>Torre:</label><br>
        <select name="conjunto_id">
            <?php while($torre = $resultadoTorres->fetch_assoc()): ?>
                <option value="<?php echo $torre['id']; ?>"
                    <?php if($fila['conjunto_id'] == $torre['id']) echo "selected"; ?>>
                    <?php echo $torre['nombre']; ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <br>

    <!-- Botones -->
    <div>
        <button type="submit">Guardar Cambios</button>
        <a href="listar.php">Cancelar</a>
    </div>

</form>

</body>
</html>

<?php $conn->close(); ?>