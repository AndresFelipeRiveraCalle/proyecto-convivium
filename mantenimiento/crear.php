<?php
// Conectar a la base de datos
require_once "conexion.php";

// Consultar lista de usuarios para el SELECT
$sqlUsuarios = "SELECT id, nombre FROM usuario ORDER BY nombre";
$resultadoUsuarios = $conn->query($sqlUsuarios);

// Consultar lista de torres para el SELECT
$sqlTorres = "SELECT id, nombre FROM conjunto ORDER BY nombre";
$resultadoTorres = $conn->query($sqlTorres);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Crear Mantenimiento</title>
</head>
<body>

<h1>Crear Nuevo Mantenimiento</h1>

<!-- 
    ENCTYPE="multipart/form-data" es necesario para poder subir archivos (fotos/facturas)
-->
<form action="guardar.php" method="POST" enctype="multipart/form-data">

    <!-- Campo: Descripción (obligatorio) -->
    <div>
        <label>Descripción del problema:</label><br>
        <textarea name="descripcion" rows="4" cols="50" required placeholder="Ej: Fuga de agua en el pasillo..."></textarea>
    </div>

    <br>

    <!-- Campo: Prioridad (NUEVO) - Qué tan urgente es -->
    <div>
        <label>Prioridad:</label><br>
        <select name="prioridad">
            <option value="baja">Baja - No es urgente</option>
            <option value="media" selected>Media - Se puede esperar unos dias</option>
            <option value="alta">Alta - Urgente, hay que atender pronto</option>
            <option value="critica">Critica - Emergencia, atencion inmediata</option>
        </select>
        <br><small>¿Que tan importante es el mantenimiento?</small>
    </div>

    <br>

    <!-- Campo: Estado -->
    <div>
        <label>Estado actual:</label><br>
        <select name="estado">
            <option value="pendiente">Pendiente - Aun no se ha iniciado</option>
            <option value="en_proceso">En Proceso - Ya se esta trabajando</option>
            <option value="finalizado">Finalizado - Ya esta terminado</option>
        </select>
    </div>

    <br>

    <!-- Campo: Responsable (NUEVO) - Quién hace el trabajo -->
    <div>
        <label>Responsable (Interno/Tecnico/Empresa):</label><br>
        <input type="text" name="responsable" size="40" placeholder="Ej: cristian castrillon, Ascensores S.A.S">
        <br><small>¿Quien va a realizar el trabajo?</small>
    </div>

    <br>

    <!-- Campo: Costo (NUEVO) - Cuánto cuesta -->
    <div>
        <label>Costo estimado o final:</label><br>
        <input type="number" step="0.01" name="costo" placeholder="0.00">
        <br><small>¿Cuanto cuesta o costara este mantenimiento?</small>
    </div>

    <br>

    <!-- Campo: Fecha de finalización -->
    <div>
        <label>Fecha y hora de finalización:</label><br>
        <input type="datetime-local" name="fecha_finalizacion">
        <br><small>Si ya esta terminado, selecciona cuando se completo</small>
    </div>

    <br>

    <!-- Campo: Evidencia (NUEVO) - Foto o factura -->
    <div>
        <label>Evidencia (Foto del daño o Factura):</label><br>
        <input type="file" name="evidencia" accept="image/*,application/pdf">
        <br><small>Puedes subir una foto del problema o la factura del trabajo</small>
    </div>

    <br>

    <!-- Campo: Comentarios (NUEVO) - Notas adicionales -->
    <div>
        <label>Comentarios o notas:</label><br>
        <textarea name="comentarios" rows="3" cols="50" placeholder="Ej: Se necesita comprar un repuesto, el tecnico vendra el lunes..."></textarea>
        <br><small>Información adicional sobre el proceso</small>
    </div>

    <br>

    <!-- Campo: Solicitante (quién pide el mantenimiento) -->
    <div>
        <label>Solicitante (quien reporta):</label><br>
        <select name="usuario_id" required>
            <option value="">--- Seleccione un usuario ---</option>
            <?php while($usuario = $resultadoUsuarios->fetch_assoc()): ?>
                <option value="<?php echo $usuario['id']; ?>">
                    <?php echo $usuario['nombre']; ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <br>

    <!-- Campo: Torre/Conjunto -->
    <div>
        <label>Torre o Conjunto:</label><br>
        <select name="conjunto_id" required>
            <option value="">--- Seleccione una torre ---</option>
            <?php while($torre = $resultadoTorres->fetch_assoc()): ?>
                <option value="<?php echo $torre['id']; ?>">
                    <?php echo $torre['nombre']; ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <br>

    <!-- Botones -->
    <div>
        <button type="submit">Guardar Mantenimiento</button>
        <a href="listar.php">Cancelar</a>
    </div>

</form>

</body>
</html>

<?php $conn->close(); ?>