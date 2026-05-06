<?php
// instructores/listar.php
session_start();
require_once "../config/permisos.php";
solo_admin(); // SOLO admin puede ver instructores

require_once("../config/conexion.php");

$busqueda = isset($_GET["buscar"]) ? trim($_GET["buscar"]) : "";
$especialidad_filtro = isset($_GET["especialidad"]) ? trim($_GET["especialidad"]) : "";


$especialidades = $conexion->query("SELECT DISTINCT especialidad FROM instructores WHERE especialidad IS NOT NULL AND especialidad != '' ORDER BY especialidad")->fetchAll();

if (!empty($busqueda) && !empty($especialidad_filtro)) {
    $sql = "SELECT * FROM instructores 
            WHERE (nombre LIKE :busqueda OR apellido LIKE :busqueda) 
            AND especialidad = :especialidad 
            ORDER BY nombre";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        ":busqueda" => "%" . $busqueda . "%", 
        ":especialidad" => $especialidad_filtro
    ]);

} elseif (!empty($busqueda)) {
    $sql = "SELECT * FROM instructores 
            WHERE nombre LIKE :busqueda OR apellido LIKE :busqueda 
            ORDER BY nombre";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([":busqueda" => "%" . $busqueda . "%"]);

} elseif (!empty($especialidad_filtro)) {  
    $sql = "SELECT * FROM instructores 
            WHERE especialidad = :especialidad 
            ORDER BY nombre";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([":especialidad" => $especialidad_filtro]); 

} else {
    $sql = "SELECT * FROM instructores ORDER BY id DESC LIMIT 10"; // mostramos las ultimas 10 escriciones 
    $stmt = $conexion->query($sql);
}

$instructores = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Instructores</title>
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
        <h1>Instructores</h1>
        <div class="topbar-actions">
            <!-- CORREGIDO: ruta correcta sin barra al inicio -->
            <a href="crear.php" class="btn btn-primary">+ Nuevo Instructor</a>
        </div>
    </div>

    <form action="listar.php" method="GET" class="filtros">
        <input type="text" name="buscar" placeholder="Buscar por nombre o apellido..." value="<?php echo $busqueda; ?>">
        
        <select name="especialidad">
            <option value="">Todas las especialidades</option>
            <?php foreach($especialidades as $e): ?>
                <option value="<?php echo $e['especialidad']; ?>" <?php echo $especialidad_filtro == $e['especialidad'] ? 'selected' : ''; ?>>
                    <?php echo $e['especialidad']; ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit" class="btn btn-primary">Buscar</button>
        
        <?php if(!empty($busqueda) || !empty($especialidad_filtro)): ?>
            <a href="listar.php" class="btn btn-secondary">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Correo</th>
                    <th>Especialidad</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($instructores as $instructor): ?>
                <tr>
                    <td><?php echo $instructor["id"]; ?></td>
                    <td><?php echo $instructor["nombre"]; ?></td>
                    <td><?php echo $instructor["apellido"]; ?></td>
                    <td><?php echo $instructor["correo"]; ?></td>
                    <td><span class="badge"><?php echo $instructor["especialidad"] ?? 'No especificada'; ?></span></td>
                    <td>
                        <div class="acciones">
                            <a href="editar.php?id=<?php echo $instructor['id']; ?>" class="btn btn-secondary">Editar</a>
                            <a href="eliminar.php?id=<?php echo $instructor['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Eliminar este instructor?')">Eliminar</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>