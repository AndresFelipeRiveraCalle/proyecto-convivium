<?php
session_start();
require_once "../config/permisos.php";
verificar_sesion(); // cualquier rol puede crear inscripciones

require_once "../config/conexion.php";

// Traemos todos los estudiantes y cursos para los selects
$estudiantes = $conexion->query("SELECT id, nombre, apellido FROM estudiantes ORDER BY nombre")->fetchAll();
$cursos      = $conexion->query("SELECT id, nombre FROM cursos ORDER BY nombre")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Inscripción - Academia</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <h2>Academia</h2>
        <p>Sistema de Gestión</p>
    </div>
    <div class="nav-menu">
        <a href="../dashboard/index.php" class="nav-item">▪ Dashboard</a>
        <?php if (es_admin()): ?>
            <a href="../estudiantes/listar.php" class="nav-item">▪ Estudiantes</a>
        <?php endif; ?>
        <a href="../cursos/listar.php" class="nav-item">▪ Cursos</a>
        <a href="../inscripciones/listar.php" class="nav-item active">▪ Inscripciones</a>
        <?php if (es_admin()): ?>
            <a href="../instructores/listar.php" class="nav-item">▪ Instructores</a>
        <?php endif; ?>
    </div>
    <div style="margin-top: auto; padding: 20px;">
        <button type="button" class="btn btn-danger" style="width: 100%;"
            onclick="if(confirm('¿Seguro deseas cerrar sesión?')) { window.location.href='../cerrar_sesion.php'; }">
            Cerrar Sesión
        </button>
    </div>
</div>

<div class="main">

    <div class="topbar">
        <h1>Nueva Inscripción</h1>
    </div>

    <div class="form-card">
        <div class="form-title">Datos de la Inscripción</div>

        <form action="guardar.php" method="POST">

            <div class="form-group">
                <label for="estudiante_id">Estudiante</label>
                <select name="estudiante_id" id="estudiante_id" required
                    style="width:100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
                    <option value="">Selecciona un estudiante</option>
                    <?php foreach($estudiantes as $e): ?>
                        <option value="<?php echo $e['id']; ?>">
                            <?php echo htmlspecialchars($e['nombre'] . " " . $e['apellido']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="curso_id">Curso</label>
                <select name="curso_id" id="curso_id" required
                    style="width:100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
                    <option value="">Selecciona un curso</option>
                    <?php foreach($cursos as $c): ?>
                        <option value="<?php echo $c['id']; ?>">
                            <?php echo htmlspecialchars($c['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="fecha_inscripcion">Fecha de inscripción</label>
                <input type="date" name="fecha_inscripcion" id="fecha_inscripcion"
                    value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-footer">
                <button type="submit" class="btn btn-primary">Guardar Inscripción</button>
                <a href="listar.php" class="btn btn-secondary">Cancelar</a>
            </div>

        </form>
    </div>

</div>
</body>
</html>