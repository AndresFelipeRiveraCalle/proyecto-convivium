<?php
require_once "../config/conexion.php";

$stmtZonas    = $conexion->query("SELECT id, nombre FROM zona_comun ORDER BY nombre");
$zonas        = $stmtZonas->fetchAll();

$stmtUsuarios = $conexion->query("SELECT id, nombre, apellido FROM usuario ORDER BY nombre");
$usuarios     = $stmtUsuarios->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Crear Mantenimiento</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/css/script.js" defer></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <?php include "sidebar.php"; ?>

    <main class="contenido">
        <h1>Crear Nuevo Mantenimiento</h1>

        <div class="form-card">  
            <form action="guardar.php" method="POST" enctype="multipart/form-data">

                <div>
                    <label>Zona comun:</label><br>
                    <select name="zona_id" required>
                        <option value="">--- Seleccione una zona ---</option>
                        <?php foreach ($zonas as $zona): ?>
                            <option value="<?= $zona['id'] ?>"><?= htmlspecialchars($zona['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>


                <div>
                    <label>Solicitante:</label><br>
                    <select name="usuario_reporta_id" required>
                        <option value="">--- Seleccione un usuario ---</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id'] ?>">
                                <?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>


                <div>
                    <label>Descripcion del problema:</label><br>
                    <textarea name="descripcion" rows="4" cols="50" required
                        placeholder="Describa el problema"></textarea>
                </div>


                <div>
                    <label>Prioridad:</label><br>
                    <select name="prioridad">
                        <option value="baja">Baja - No es urgente</option>
                        <option value="media" selected>Media - Se puede esperar unos días</option>
                        <option value="alta">Alta - Urgente, hay que atender pronto</option>
                    </select>
                </div>


                <div>
                    <label>Estado actual:</label><br>
                    <select name="estado">
                        <option value="pendiente" selected>Pendiente</option>
                        <option value="en_proceso">En Proceso</option>
                        <option value="solucionado">Solucionado</option>
                    </select>
                </div>


                <div>
                    <label>Fecha promedio solucion (F.solucionado):</label><br>
                    <input type="datetime-local" name="fecha_solucion">
                    <br><small>Solo si ya está resuelto</small>
                </div>


                <div>
                    <label>Evidencia foto o PDF:</label><br>
                    <input type="file" name="evidencia" accept="image/*,application/pdf">
                </div>


                <div>
                    <button type="submit">Guardar Mantenimiento</button>
                    <a href="listar.php">Cancelar</a>
                </div>

            </form>
        </div>
    </main>

</body>
</html>