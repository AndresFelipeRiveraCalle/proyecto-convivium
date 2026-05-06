<?php
session_start();
require_once "../config/permisos.php";
solo_admin(); // SOLO admin puede crear cursos
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Curso</title>
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
        <a href="../cursos/listar.php" class="nav-item active">▪ Cursos</a>
        <a href="../inscripciones/listar.php" class="nav-item">▪ Inscripciones</a>
        <?php if (es_admin()): ?>
            <a href="../instructores/listar.php" class="nav-item">▪ Instructores</a>
        <?php endif; ?>
    </div>
    <div style="margin-top: auto; padding: 20px;">
        <form action="../cerrar_sesion.php" method="POST" onsubmit="return confirm('¿Seguro deseas cerrar sesión?');">
            <button type="submit" class="btn btn-danger" style="width: 100%;">Cerrar Sesión</button>
        </form>
    </div>
</div>

<div class="main">

    <div class="topbar">
        <h1>Crear Curso</h1>
    </div>

    <div class="form-card">
        <div class="form-title">Datos del Curso</div>

        <form action="guardar.php" method="POST">

            <div class="form-group">
                <label for="nombre">Nombre del curso</label>
                <input type="text" name="nombre" id="nombre" placeholder="Ingresa el nombre del curso" required>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea name="descripcion" id="descripcion" rows="4" placeholder="Ingresa una descripción del curso"></textarea>
            </div>

            <div class="form-footer">
                <button type="submit" class="btn btn-primary">Guardar Curso</button>
                <a href="listar.php" class="btn btn-secondary">Cancelar</a>
            </div>

        </form>
    </div>

</div>

</body>
</html>