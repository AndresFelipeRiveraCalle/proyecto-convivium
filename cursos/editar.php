<?php 
// cursos/guardar.php
session_start();
require_once "../config/permisos.php";
solo_admin(); // SOLO admin puede guardar cursos

require_once "../config/conexion.php";



if (isset($_GET["id"])) {

    $id = $_GET["id"];

    if (!empty($id)) {

        $sql = "SELECT * FROM estudiantes WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([":id" => $id]);
        $estudiante = $stmt->fetch();

    } else {
        echo "ID inválido";
        exit;
    }

} else {
    echo "No se recibió ID";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Estudiante</title>
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
        <a href="../estudiantes/listar.php" class="nav-item active">▪ Estudiantes</a>
        <a href="../cursos/listar.php" class="nav-item">▪ Cursos</a>
        <a href="../inscripciones/listar.php" class="nav-item">▪ Inscripciones</a>
    </div>
    <div style="margin-top: auto; padding: 20px;">
        <button type="button" class="btn btn-danger" style="width: 100%;" onclick="if(confirm('Seguro deseas cerrar sesion')) { window.location.href='../cerrar_sesion.php'; }">
            Cerrar Sesion
        </button>
    </div>
</div>

<div class="main">

    <div class="topbar">
        <h1>Editar Estudiante</h1>
    </div>

    <div class="form-card">
        <div class="form-title">Datos del Estudiante</div>

        <form action="actualizar.php" method="POST">

            <input type="hidden" name="id" value="<?php echo $estudiante['id']; ?>">

            <div class="form-group">
                <label for="nombre">Nombre</label>
                <input type="text" name="nombre" id="nombre" value="<?php echo $estudiante['nombre']; ?>" required>
            </div>

            <div class="form-group">
                <label for="apellido">Apellido</label>
                <input type="text" name="apellido" id="apellido" value="<?php echo $estudiante['apellido']; ?>" required>
            </div>

            <div class="form-group">
                <label for="correo">Correo</label>
                <input type="email" name="correo" id="correo" value="<?php echo $estudiante['correo']; ?>" required>
            </div>

            <div class="form-group">
                <label for="ciudad">Ciudad</label>
                <input type="text" name="ciudad" id="ciudad" value="<?php echo $estudiante['ciudad']; ?>" required>
            </div>

            <div class="form-group">
                <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" value="<?php echo $estudiante['fecha_nacimiento']; ?>" required>
            </div>

            <div class="form-footer">
                <button type="submit" class="btn btn-primary">Actualizar Estudiante</button>
                <a href="listar.php" class="btn btn-secondary">Cancelar</a>
            </div>

        </form>
    </div>

</div>

</body>
</html>