<?php
require_once "../config/conexion.php";

$idPertenece = isset($_GET["id_pertenece"]) ? $_GET["id_pertenece"] : null;

if (!$idPertenece) {
    echo "Falta indicar qué residente quieres editar.";
    exit;
}

// traemos los datos del residentes actuales para poder editar

$sql = "SELECT
            pertenece.id AS id_pertenece,
            pertenece.tipo,
            usuario.id AS id_usuario,
            usuario.nombre,
            usuario.apellido,
            usuario.documento,           
            usuario.tipo_documento,      
            usuario.correo,
            usuario.telefono,
            usuario.telefono_2,          
            unidad.torre,
            unidad.numero
        FROM pertenece
        INNER JOIN usuario ON usuario.id = pertenece.usuario_id
        INNER JOIN unidad ON unidad.id = pertenece.unidad_id
        WHERE pertenece.id = :id_pertenece";

$consulta = $conexion->prepare($sql);
$consulta->execute([":id_pertenece" => $idPertenece]);
$residente = $consulta->fetch();

if (!$residente) {
    echo "No se encontró ese residente.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar residente</title>
</head>
<body>
    <h1>Editar residente</h1>

    <p>Apartamento: Torre <?= htmlspecialchars($residente["torre"]) ?> - Apto <?= htmlspecialchars($residente["numero"]) ?></p>

    <form method="post" action="actualizar_residente.php">
        <input type="hidden" name="id_pertenece" value="<?= $residente["id_pertenece"] ?>">
        <input type="hidden" name="id_usuario" value="<?= $residente["id_usuario"] ?>">

        <label>Nombre:</label><br>
        <input type="text" name="nombre_residente" value="<?= htmlspecialchars($residente["nombre"]) ?>" required><br><br>

        <label>Apellido:</label><br>
        <input type="text" name="apellido_residente" value="<?= htmlspecialchars($residente["apellido"]) ?>" required><br><br>

        <!-- NUEVO: Campo Documento -->
        <label>Documento:</label><br>
        <input type="text" name="documento" value="<?= htmlspecialchars($residente["documento"]) ?>" required><br><br>

        <!-- NUEVO: Campo Tipo de documento -->
        <label>Tipo de documento:</label><br>
        <select name="tipo_documento" required>
            <option value="cedula" <?= $residente["tipo_documento"] === "cedula" ? "selected" : "" ?>>Cédula</option>
            <option value="pasaporte" <?= $residente["tipo_documento"] === "pasaporte" ? "selected" : "" ?>>Pasaporte</option>
            <option value="otro" <?= $residente["tipo_documento"] === "otro" ? "selected" : "" ?>>Otro</option>
        </select><br><br>

        <label>Correo:</label><br>
        <input type="email" name="correo_residente" value="<?= htmlspecialchars($residente["correo"]) ?>" required><br><br>

        <label>Teléfono:</label><br>
        <input type="text" name="telefono_residente" value="<?= htmlspecialchars($residente["telefono"]) ?>"><br><br>

        <label>Teléfono 2 (opcional):</label><br>
        <input type="text" name="telefono_2" value="<?= htmlspecialchars($residente["telefono_2"]) ?>"><br><br>

        <label>Tipo de residente:</label><br>
        <select name="tipo_residente">
            <option value="propietario" <?= $residente["tipo"] === "propietario" ? "selected" : "" ?>>Propietario</option>
            <option value="inquilino" <?= $residente["tipo"] === "inquilino" ? "selected" : "" ?>>Inquilino (en arriendo)</option>
        </select><br><br>

        <button type="submit">Actualizar</button>
    </form>

    <br>
    <a href="listar.php">Volver al listado</a>
</body>
</html>