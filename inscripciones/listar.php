<?php 
session_start();
require_once "../config/permisos.php";  // <--- ¡ESTA LÍNEA ES LA QUE FALTA!
verificar_sesion(); // Cualquier rol puede ver inscripciones

require_once("../config/conexion.php");

// resto del código...

// recibimos fechas de filtro si existen
$fecha_inicio = isset($_GET["fecha_inicio"]) ? $_GET["fecha_inicio"] : "";
$fecha_fin = isset($_GET["fecha_fin"]) ? $_GET["fecha_fin"] : "";

// verificamos si hay filtro de fechas
if (!empty($fecha_inicio) && !empty($fecha_fin)) {

    // filtramos por rango de fechas usando BETWEEN
    // JOIN une las tres tablas para mostrar nombres en vez de IDs
    /*usa BETWEEN para filtrar inscripciones por rango de fechas y hago JOIN con estudiantes y cursos para mostrar nombres en lugar de ID */
    /*
    "si el usuario no ingresa fechas, mi codigo valida con empty y no aplica el filtro between. en ese caso,
    muestro las ultimas 10 inscripciones usando order by fecha desc y limit 10"
    */


    $sql = "SELECT i.id, 
                e.nombre AS estudiante_nombre,
                e.apellido AS estudiante_apellido,
                c.nombre AS curso_nombre,
                i.fecha_inscripcion
            FROM inscripciones i
            JOIN estudiantes e ON i.estudiante_id = e.id
            JOIN cursos c ON i.curso_id = c.id
            WHERE i.fecha_inscripcion BETWEEN :fecha_inicio AND :fecha_fin
            ORDER BY i.fecha_inscripcion DESC";

    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        ":fecha_inicio" => $fecha_inicio,
        ":fecha_fin" => $fecha_fin
    ]);

} else {

    // mostramos las Ultimas 10 inscripciones con ORDER BY y LIMIT
    $sql = "SELECT i.id,
                e.nombre AS estudiante_nombre,
                e.apellido AS estudiante_apellido,
                c.nombre AS curso_nombre,
                i.fecha_inscripcion
            FROM inscripciones i
            JOIN estudiantes e ON i.estudiante_id = e.id
            JOIN cursos c ON i.curso_id = c.id
            ORDER BY i.fecha_inscripcion DESC
            LIMIT 10"; // pusimos el limite de inscripciones

    $stmt = $conexion->query($sql);
}

$inscripciones = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Inscripciones</title>
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
        <h1>Inscripciones</h1>
        <div class="topbar-actions">
            <a href="crear.php" class="btn btn-primary">+ Nueva Inscripción</a>
        </div>
    </div>

    <!-- filtro por fechas usando BETWEEN -->
    <form action="listar.php" method="GET" class="filtros">
        <label>Desde: </label>
        <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">

        <label>Hasta: </label>
        <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>">

        <button type="submit" class="btn btn-primary">Filtrar</button>

        <!-- el boton ver ultimas solo aparece cuando hay filtro activo -->
        <?php if(!empty($fecha_inicio) && !empty($fecha_fin)): ?>
            <a href="listar.php" class="btn btn-secondary">Ver ultimas</a>
        <?php endif; ?>
    </form>

    <!-- mensaje informativo segun el estado -->
    <?php if(!empty($fecha_inicio) && !empty($fecha_fin)): ?>
        <p class="fecha">Inscripciones del <strong><?php echo $fecha_inicio; ?></strong> al <strong><?php echo $fecha_fin; ?></strong></p>
    <?php else: ?>
        <p class="fecha">Ultimas 10 inscripciones</p>
    <?php endif; ?>
    <br>

    <!-- tabla de inscripciones -->
    <div class="table-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Estudiante</th>
                    <th>Curso</th>
                    <th>Fecha Inscripción</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($inscripciones as $inscripcion): ?>
                    <tr>
                        <td><?php echo $inscripcion["id"]; ?></td>
                        <!-- mostramos nombre completo del estudiante -->
                        <td><?php echo $inscripcion["estudiante_nombre"] . " " . $inscripcion["estudiante_apellido"]; ?></td>
                        <td><?php echo $inscripcion["curso_nombre"]; ?></td>
                        <td class="fecha"><?php echo $inscripcion["fecha_inscripcion"]; ?></td>
                        <td>
                            <?php if(es_admin()): ?>
                            <!-- confirmar pregunta antes de eliminar -->
                            <div class="acciones">
                                <a href="eliminar.php?id=<?php echo $inscripcion['id']; ?>" class="btn btn-danger" onclick="return confirm('¿Eliminar esta inscripción?')">Eliminar</a>
                            </div>
                            <?php else: ?>
                            <span class="fecha">Sin acceso</span>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>