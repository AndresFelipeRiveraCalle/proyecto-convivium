<?php
session_start();
require_once "../config/permisos.php";
verificar_sesion(); // cualquier rol puede ver el dashboard

require_once("../config/conexion.php");

$total_estudiantes = $conexion->query("SELECT COUNT(*) AS total FROM estudiantes")->fetch()["total"];
$total_cursos      = $conexion->query("SELECT COUNT(*) AS total FROM cursos")->fetch()["total"];
$total_instructores = $conexion->query("SELECT COUNT(*) AS total FROM instructores")->fetch()["total"];

$sql_promedio = "SELECT AVG(total) AS promedio FROM (
                    SELECT COUNT(*) AS total 
                    FROM inscripciones 
                    GROUP BY estudiante_id
                ) AS subconsulta";
$promedio = $conexion->query($sql_promedio)->fetch()["promedio"];

// ciudades solo para admin
if (es_admin()) {
    $sql_ciudades = "SELECT ciudad, COUNT(*) AS total 
                    FROM estudiantes 
                    GROUP BY ciudad 
                    ORDER BY total DESC";
    $ciudades = $conexion->query($sql_ciudades)->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Academia</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="sidebar">
    <div class="sidebar-logo">
        <h2>Academia</h2>
        <p>Sistema de Gestión</p>
    </div>
    <div class="nav-menu">
        <a href="../dashboard/index.php" class="nav-item active">▪ Dashboard</a>

        <?php if (es_admin()): ?>
            <a href="../estudiantes/listar.php" class="nav-item">▪ Estudiantes</a>
        <?php endif; ?>

        <a href="../cursos/listar.php" class="nav-item">▪ Cursos</a>
        <a href="../inscripciones/listar.php" class="nav-item">▪ Inscripciones</a>

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
        <div>
            <?php if (es_admin()): ?>
            <h1>Dashboard</h1>
                <span>
                Bienvenido <strong><?php echo htmlspecialchars($_SESSION["nombre"] ." ". $_SESSION["apellido"]); ?></strong>
                tienes privilegios de <strong><?php echo htmlspecialchars($_SESSION["rol"]); ?></strong>
                </span>
            <?php else: ?>
            <h1>INICIO</h1>
            <span>
                Bienvenido <strong><?php echo htmlspecialchars($_SESSION["nombre"] ." ". $_SESSION["apellido"]); ?></strong>
                tienes privilegios de usuario
            </span>
            <?php endif; ?>
        </div>
    </div>


<span></span>




    <div class="cards-grid">
        <div class="card">
            <div class="card-label">Total Cursos</div>
            <div class="card-value"><?php echo $total_cursos; ?></div>
            <div class="card-sub">Cursos disponibles</div>
        </div>

        <div class="card">
            <div class="card-label">Promedio Inscripciones</div>
            <div class="card-value"><?php echo number_format($promedio, 1); ?></div>
            <div class="card-sub">Por estudiante</div>
        </div>

        <?php if (es_admin()): ?>
        <div class="card">
            <div class="card-label">Total Estudiantes</div>
            <div class="card-value"><?php echo $total_estudiantes; ?></div>
            <div class="card-sub">Registrados en el sistema</div>
        </div>

        <div class="card">
            <div class="card-label">Total Instructores</div>
            <div class="card-value"><?php echo $total_instructores; ?></div>
            <div class="card-sub">Profesores registrados</div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (es_admin()): ?>
    <div class="section-title">Estudiantes por Ciudad</div>
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>Ciudad</th>
                    <th>Estudiantes</th>
                    <th>Distribución</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($ciudades as $ciudad): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ciudad["ciudad"]); ?></td>
                        <td><span class="badge"><?php echo $ciudad["total"]; ?></span></td>
                        <td>
                            <div class="bar-wrap">
                                <div class="bar-bg">
                                    <div class="bar-fill" style="width: <?php echo ($ciudad["total"] / $total_estudiantes) * 100; ?>%"></div>
                                </div>
                                <span style="font-size: 12px; color: #888;">
                                    <?php echo number_format(($ciudad["total"] / $total_estudiantes) * 100, 0); ?>%
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>

</body>
</html>