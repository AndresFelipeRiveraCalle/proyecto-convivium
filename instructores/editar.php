<?php
// instructores/listar.php
session_start();
require_once "../config/permisos.php";
solo_admin(); // SOLO admin puede ver instructores

require_once "../config/conexion.php";

if (isset($_GET["id"])) {
    $id = $_GET["id"];
    
    if (!empty($id)) {
        $sql = "SELECT * FROM instructores WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([":id" => $id]);
        $instructor = $stmt->fetch();
        
        if(!$instructor) {
            echo "Instructor no encontrado";
            exit;
        }
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
    <title>Editar Instructor</title>
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
        <a href="../estudiantes/listar.php" class="nav-item">▪ Estudiantes</a>
        <a href="../cursos/listar.php" class="nav-item">▪ Cursos</a>
        <a href="../inscripciones/listar.php" class="nav-item">▪ Inscripciones</a>
        <a href="../instructores/listar.php" class="nav-item active">▪ Instructores</a>
    </div>
    <div style="margin-top: auto; padding: 20px;">
        <button type="button" class="btn btn-danger" style="width: 100%;" onclick="if(confirm('Seguro deseas cerrar sesion')) { window.location.href='../cerrar_sesion.php'; }">
            Cerrar Sesion
        </button>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <h1>Editar Instructor</h1>
    </div>

    <div class="form-card">
        <div class="form-title">Datos del Instructor</div>

        <form action="actualizar.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $instructor['id']; ?>">
            
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" value="<?php echo $instructor['nombre']; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Apellido</label>
                <input type="text" name="apellido" value="<?php echo $instructor['apellido']; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Correo</label>
                <input type="email" name="correo" value="<?php echo $instructor['correo']; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Especialidad</label>
                <input type="text" name="especialidad" value="<?php echo $instructor['especialidad']; ?>">
            </div>
            
            <div class="form-footer">
                <button type="submit" class="btn btn-primary">Actualizar Instructor</button>
                <a href="listar.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>