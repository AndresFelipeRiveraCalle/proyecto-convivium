<?php
session_start();
require_once "../config/permisos.php";
verificar_sesion(); // cualquier rol logueado puede ver cursos

require_once("../config/conexion.php");

$busqueda = isset($_GET["buscar"]) ? trim($_GET["buscar"]) : "";

if (!empty($busqueda)) {
    $sql = "SELECT * FROM cursos WHERE nombre LIKE :busqueda ORDER BY nombre ";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([":busqueda" => "%" . $busqueda . "%"]);
} else {
    $sql = "SELECT * FROM cursos ORDER BY nombre LIMIT 3"; // lo ordenamos por nombre 
    $stmt = $conexion->query($sql);
}

$cursos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursos</title>
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
        <h1>Cursos</h1>
        <?php if (es_admin()): ?>
        <div class="topbar-actions">
            <a href="crear.php" class="btn btn-primary">+ Nuevo Curso</a> <!-- solo el amnistrador puede crear cursos -->
        </div>
        <?php endif; ?>
    </div>

    <form action="listar.php" method="GET" class="filtros">
        <input type="text" name="buscar" placeholder="Buscar por nombre..." value="<?php echo $busqueda; ?>">
        <button type="submit" class="btn btn-primary">Buscar</button>
        <?php if(!empty($busqueda)): ?>
            <a href="listar.php" class="btn btn-secondary">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <?php if (es_admin()): ?>
                        <th>Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($cursos as $curso): ?>
                <tr>
                    <td><?php echo $curso["id"]; ?></td>
                    <td><?php echo $curso["nombre"]; ?></td>
                    <td><?php echo $curso["descripcion"]; ?></td>
                    <?php if (es_admin()): ?>
                    <td>
                        <div class="acciones">
                            <a href="editar.php?id=<?php echo $curso['id']; ?>" class="btn btn-secondary">Editar</a>
                            <a href="eliminar.php?id=<?php echo $curso['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Eliminar este curso?')">Eliminar</a>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>