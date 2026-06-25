<?php
require_once "../config/conexion.php";


$textoBusqueda      = isset($_GET["buscar_nombre_apto"]) ? trim($_GET["buscar_nombre_apto"]) : "";
$torreSeleccionada   = isset($_GET["seleccion_torre"])    ? trim($_GET["seleccion_torre"])    : "";
$estadoSeleccionado  = isset($_GET["estado"])              ? trim($_GET["estado"])              : "";

$sql = "SELECT
            unidad.id AS id_unidad,
            unidad.torre,
            unidad.numero,
            unidad.estado AS estado_unidad,
            pertenece.id AS id_pertenece,
            pertenece.tipo,
            usuario.nombre,
            usuario.apellido,
            usuario.documento,
            usuario.tipo_documento,
            usuario.correo,
            usuario.telefono,
            usuario.telefono_2
        FROM unidad
        LEFT JOIN pertenece ON pertenece.unidad_id = unidad.id AND pertenece.activo = 1
        LEFT JOIN usuario ON usuario.id = pertenece.usuario_id
        WHERE 1 = 1";

$parametros = [];

// Filtro de búsqueda: por nombre, apellido, número de apartamento o documento
if ($textoBusqueda !== "") {
    $sql .= " AND (usuario.nombre LIKE :texto 
                    OR usuario.apellido LIKE :texto 
                    OR unidad.numero LIKE :texto
                    OR usuario.documento LIKE :texto)";
    $parametros[":texto"] = "%" . $textoBusqueda . "%";
}

// Filtro de torre
if ($torreSeleccionada !== "") {
    $sql .= " AND unidad.torre = :torre";
    $parametros[":torre"] = $torreSeleccionada;
}

// Filtro de estado: ocupado, desocupado o en arriendo
if ($estadoSeleccionado === "desocupado") {
    $sql .= " AND unidad.estado = 'desocupado'";
} elseif ($estadoSeleccionado === "ocupado") {
    $sql .= " AND unidad.estado = 'ocupado' AND pertenece.tipo = 'propietario'";
} elseif ($estadoSeleccionado === "arriendo") {
    $sql .= " AND unidad.estado = 'ocupado' AND pertenece.tipo = 'inquilino'";
}

$sql .= " ORDER BY unidad.torre, unidad.numero";

$consulta = $conexion->prepare($sql);
$consulta->execute($parametros);
$listaApartamentos = $consulta->fetchAll();

// Traemos las torres que existen en la base de datos para llenar el select de torres
$consultaTorres = $conexion->query("SELECT DISTINCT torre FROM unidad ORDER BY torre");
$listaTorres = $consultaTorres->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Residentes e Inmuebles</title>
</head>
<body>
    <h1>Gestion de Residentes e Inmuebles</h1>
    <br>
    <a href="agregar_residente.php">+ Agregar nuevo residente</a>
    <br><br>

    <form method="get">
        <input type="text" name="buscar_nombre_apto" placeholder="Buscar por nombre, apto o documento"
                value="<?= htmlspecialchars($textoBusqueda) ?>">

        <select name="seleccion_torre">
            <option value="">Torre: Todas</option>
            <?php foreach ($listaTorres as $torre): ?>
                <option value="<?= htmlspecialchars($torre["torre"]) ?>"
                    <?= $torreSeleccionada === $torre["torre"] ? "selected" : "" ?>>
                    Torre <?= htmlspecialchars($torre["torre"]) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="estado">
            <option value="">Estado: Todos</option>
            <option value="ocupado"    <?= $estadoSeleccionado === "ocupado"    ? "selected" : "" ?>>Ocupado</option>
            <option value="desocupado" <?= $estadoSeleccionado === "desocupado" ? "selected" : "" ?>>Desocupado</option>
            <option value="arriendo"   <?= $estadoSeleccionado === "arriendo"   ? "selected" : "" ?>>En arriendo</option>
        </select>

        <button type="submit">Filtrar</button>
        <?php if ($textoBusqueda || $torreSeleccionada || $estadoSeleccionado): ?>
            <a href="listar.php">Limpiar filtros</a>
            <?php endif; ?>
    </form>

    <br>

    <table border="1" cellpadding="8">
        <tr>
            <th>Torre</th>
            <th>Apartamento</th>
            <th>Residente</th>
            <th>Documento</th>
            <th>Telefono</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>

        <?php foreach ($listaApartamentos as $apartamento): ?>
            <?php
                
                if ($apartamento["estado_unidad"] === "desocupado") {
                    $estadoMostrado = "Desocupado";
                } elseif ($apartamento["tipo"] === "inquilino") {
                    $estadoMostrado = "En arriendo";
                } else {
                    $estadoMostrado = "Ocupado";
                }
            ?>
            <tr>
                <td><?= htmlspecialchars($apartamento["torre"]) ?></td>
                <td><?= htmlspecialchars($apartamento["numero"]) ?></td>
                <td>
                    <?= $apartamento["nombre"]
                        ? htmlspecialchars($apartamento["nombre"] . " " . $apartamento["apellido"])
                        : "—" ?>
                </td>
                <td><?= htmlspecialchars($apartamento["documento"] ?? "—") ?></td>
                <td><?= htmlspecialchars($apartamento["telefono"] ?? "—") ?></td>
                <td><?= $apartamento["tipo"] ? htmlspecialchars(ucfirst($apartamento["tipo"])) : "—" ?></td>
                <td><?= $estadoMostrado ?></td>
                <td>
                    <?php if ($apartamento["id_pertenece"]): ?>
                        
                        <a href="editar.php?id_pertenece=<?= $apartamento["id_pertenece"] ?>">Editar</a>
                        |
                        <a href="eliminar_residente.php?id_pertenece=<?= $apartamento["id_pertenece"] ?>"
                            onclick="return confirm('¿Seguro que quieres eliminar este residente?');">Eliminar</a>
                        |
                        <a href="detalle_residente.php?id_pertenece=<?= $apartamento["id_pertenece"] ?>">Ver más</a>
                    <?php else: ?>
                        <a href="agregar_residente.php?id_unidad=<?= $apartamento["id_unidad"] ?>">Asignar residente</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>