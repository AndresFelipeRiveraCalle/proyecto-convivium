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
    <title>Crear mantenimiento</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/css/script.js" defer></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <?php include "sidebar.php"; ?>

    <main class="contenido">
        <h2 align="center">Crear nuevo mantenimiento</h2>

        <div class="bloque filtros">

            <form action="guardar.php" method="POST" enctype="multipart/form-data">

                <div class="bloque filtros">
                    <h4>Zona comun:</h4>
                    <select name="zona_id" required>
                        <option value="">--- Seleccione una zona ---</option>
                        <?php foreach ($zonas as $zona): ?>
                            <option value="<?= $zona['id'] ?>"><?= htmlspecialchars($zona['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    

                    <h4>Solicitante:</h4>
                    <select name="usuario_reporta_id" required>
                        <option value="">--- Seleccione un usuario ---</option>
                        <?php foreach ($usuarios as $u): ?>
                            <option value="<?= $u['id'] ?>">
                                <?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>


                    <h4>Prioridad:</h4 >
                    <select name="prioridad">
                        <option value="baja">Baja - No es urgente</option>
                        <option value="media" selected>Media - Se puede esperar unos días</option>
                        <option value="alta">Alta - Urgente, hay que atender pronto</option>
                    </select>
                    
                </div>
                <div class="bloque filtros">
                    <h4>Descripcion del problema:</h4>
                    
                <textarea class="textarea-tareas" name="descripcion" required placeholder="Describa el problema">

                </textarea>
                            

                    <h4>Estado actual:</h4>
                    <select name="estado">
                        <option value="pendiente" selected>Pendiente</option>
                        <option value="en_proceso">En Proceso</option>
                        <option value="solucionado">Solucionado</option>
                    </select>

                    <h4>Fecha de solucion (Fecha tentativa):</h4>
                    <input type="datetime-local" name="fecha_solucion">

                </div>
                <h3>Si ya está resuelto:</H3>                
                <div class="bloque filtros">
                    Por favor ingrese la evidencia de la solución (foto o PDF) y cambie el estado a "solucionado". Esto nos ayuda a mantener un registro claro de los mantenimientos realizados y su resolución.
                    <h4> Foto o PDF:</h4>
                    <input type="file" name="evidencia" accept="image/*,application/pdf">
                </div>

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