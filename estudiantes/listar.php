<?php 
session_start();
require_once "../config/permisos.php";
solo_admin(); // SOLO admin puede ver estudiantes

require_once("../config/conexion.php");

$busqueda = isset($_GET["buscar"]) ? trim($_GET["buscar"]) : "";
$ciudad_filtro = isset($_GET["ciudad"]) ? trim($_GET["ciudad"]) : "";

$ciudades = $conexion->query("SELECT DISTINCT ciudad FROM estudiantes ORDER BY ciudad")->fetchAll();

if (!empty($busqueda) && !empty($ciudad_filtro)) {
    $sql = "SELECT * FROM estudiantes WHERE nombre LIKE :busqueda AND ciudad = :ciudad ORDER BY nombre";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([":busqueda" => "%" . $busqueda . "%", ":ciudad" => $ciudad_filtro]);

} elseif (!empty($busqueda)) {
    $sql = "SELECT * FROM estudiantes WHERE nombre LIKE :busqueda ORDER BY nombre";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([":busqueda" => "%" . $busqueda . "%"]);

} elseif (!empty($ciudad_filtro)) {
    $sql = "SELECT * FROM estudiantes WHERE ciudad = :ciudad ORDER BY nombre";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([":ciudad" => $ciudad_filtro]);

} else {
    $sql = "SELECT * FROM estudiantes ORDER BY id DESC LIMIT 4";
    $stmt = $conexion->query($sql);
}

$estudiantes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estudiantes</title>
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
            <a href="../estudiantes/listar.php" class="nav-item active">▪ Estudiantes</a>
        <?php endif; ?>
        <a href="../cursos/listar.php" class="nav-item">▪ Cursos</a>
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
        <h1>Estudiantes</h1>
        <div class="topbar-actions">
            <a href="crear.php" class="btn btn-primary">+ Nuevo Estudiante</a>
        </div>
    </div>

    <form action="listar.php" method="GET" class="filtros">
        <input type="text" name="buscar" placeholder="Buscar por nombre..." value="<?php echo $busqueda; ?>">
        <select name="ciudad">
            <option value="">Todas las ciudades</option>
            <?php foreach($ciudades as $c): ?>
                <option value="<?php echo $c['ciudad']; ?>" <?php echo $ciudad_filtro == $c['ciudad'] ? 'selected' : ''; ?>>
                    <?php echo $c['ciudad']; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Buscar</button>
        <?php if(!empty($busqueda) || !empty($ciudad_filtro)): ?>
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
                    <th>Ciudad</th>
                    <th>Fecha Nacimiento</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($estudiantes as $estudiante): ?>
                    <tr>
                        <td><?php echo $estudiante["id"]; ?></td>
                        <td><?php echo $estudiante["nombre"]; ?></td>
                        <td><?php echo $estudiante["apellido"]; ?></td>
                        <td><?php echo $estudiante["correo"]; ?></td>
                        <td><span class="badge"><?php echo $estudiante["ciudad"]; ?></span></td>
                        <td class="fecha"><?php echo $estudiante["fecha_nacimiento"]; ?></td>
                        <td>
                            <div class="acciones">
                                <a href="editar.php?id=<?php echo $estudiante['id']; ?>" class="btn btn-secondary">Editar</a>
                                <a href="eliminar.php?id=<?php echo $estudiante['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Eliminar este estudiante?')">Eliminar</a>
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