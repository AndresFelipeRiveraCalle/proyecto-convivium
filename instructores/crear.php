<?php
// instructores/listar.php
session_start();
require_once "../config/permisos.php";
solo_admin(); // SOLO admin puede ver instructores
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Instructor</title>  <!-- CORREGIDO -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<!-- sidebar de navegación -->
<div class="sidebar">
    <div class="sidebar-logo">
        <h2>Academia</h2>
        <p>Sistema de Gestión</p>
    </div>
    <div class="nav-menu">
        <a href="../dashboard/index.php" class="nav-item">▪ Dashboard</a>
        <a href="../estudiantes/listar.php" class="nav-item">▪ Estudiantes</a>
        <a href="../cursos/listar.php" class="nav-item">▪ Cursos</a>
        <a href="../inscripciones/listar.php" class="nav-item">▪ Inscripciones</a>
        <a href="../instructores/listar.php" class="nav-item active">▪ Instructores</a>  <!-- AGREGADO -->
    </div>
    <div style="margin-top: auto; padding: 20px;">
        <button type="button" class="btn btn-danger" style="width: 100%;" onclick="if(confirm('Seguro deseas cerrar sesion')) { window.location.href='../cerrar_sesion.php'; }">
            Cerrar Sesion
        </button>
    </div>
</div>

<div class="main">

    <div class="topbar">
        <h1>Registrar Instructor</h1>  <!-- CORREGIDO -->
    </div>

    <div class="form-card">
        <div class="form-title">Datos del Instructor</div>  <!-- CORREGIDO -->

        <form action="guardar.php" method="POST">

            <div class="form-group">
                <label for="nombre">Nombre: </label>
                <input type="text" name="nombre" id="nombre" placeholder="Ingresa el nombre" required>
            </div>

            <div class="form-group">
                <label for="apellido">Apellido: </label>
                <input type="text" name="apellido" id="apellido" placeholder="Ingresa el apellido" required>
            </div>

            <div class="form-group">
                <label for="correo">Correo: </label>
                <input type="email" name="correo" id="correo" placeholder="Ingresa el correo" required>
            </div>

            <div class="form-group">
                <label for="especialidad">Especialidad: </label>  <!-- CORREGIDO -->
                <!-- CORREGIDO: input text en lugar de textarea -->
                <input type="text" name="especialidad" id="especialidad" placeholder="Ej: Desarrollo Web, Base de Datos, etc.">
            </div>

            <div class="form-footer">
                <button type="submit" class="btn btn-primary">Registrar Instructor</button>
                <a href="listar.php" class="btn btn-secondary">Cancelar</a>
            </div>

        </form>
    </div>

</div>

</body>
</html>