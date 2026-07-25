<?php
require_once "../config/conexion.php";

// recibe el id del residemte 

$idPertenece = isset($_GET["id_pertenece"]) ? $_GET["id_pertenece"] : null;

if (!$idPertenece) {
    echo "No se especificó qué residente ver.";
    exit;
}
// hacemos la consulta de datos del residente

$sql = "SELECT
            usuario.id AS id_usuario,
            usuario.nombre,
            usuario.apellido,
            usuario.documento,
            usuario.tipo_documento,
            usuario.correo,
            usuario.telefono,
            usuario.telefono_2,
            usuario.fecha_registro,
            pertenece.id AS id_pertenece,
            pertenece.tipo AS tipo_residencia,
            pertenece.fecha_desde,
            pertenece.fecha_hasta,
            unidad.id AS id_unidad,
            unidad.torre,
            unidad.numero,
            unidad.estado AS estado_unidad,
            unidad.metros_cuadrados,
            unidad.propietario_id,
            conjunto.nombre AS nombre_conjunto
        FROM pertenece
        INNER JOIN usuario ON usuario.id = pertenece.usuario_id
        INNER JOIN unidad ON unidad.id = pertenece.unidad_id
        INNER JOIN conjunto ON conjunto.id = unidad.conjunto_id
        WHERE pertenece.id = :id_pertenece";

$consulta = $conexion->prepare($sql);
$consulta->execute([":id_pertenece" => $idPertenece]);
$residente = $consulta->fetch();

if (!$residente) {
    echo "No se encontró el residente solicitado.";
    exit;
}
// si el reidente es inquilino buscamos la infomacion del propietario

$propietario = null;
if ($residente["tipo_residencia"] === "inquilino" && $residente["propietario_id"]) {
    $sqlPropietario = "SELECT 
                            nombre,
                            apellido,
                            documento,
                            tipo_documento,
                            telefono,
                            telefono_2,
                            correo
                        FROM usuario 
                        WHERE id = :propietario_id";
    
    $consultaPropietario = $conexion->prepare($sqlPropietario);
    $consultaPropietario->execute([":propietario_id" => $residente["propietario_id"]]);
    $propietario = $consultaPropietario->fetch();
}

// calculamo el estado de unidad

if ($residente["estado_unidad"] === "desocupado") {
    $estadoMostrado = "Desocupado";
} elseif ($residente["tipo_residencia"] === "inquilino") {
    $estadoMostrado = "En arriendo";
} else {
    $estadoMostrado = "Ocupado (Propietario)";
}


$tiposDocumento = [
    "cedula" => "Cédula",
    "pasaporte" => "Pasaporte",
    "otro" => "Otro"
];
$tipoDocumentoMostrado = $tiposDocumento[$residente["tipo_documento"]] ?? "No especificado";

$tiposResidencia = [
    "propietario" => "Propietario",
    "inquilino" => "Inquilino (en arriendo)"
];
$tipoResidenciaMostrado = $tiposResidencia[$residente["tipo_residencia"]] ?? "No especificado";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del residente</title>
</head>
<body>
    <h1>Detalle del residente</h1>

    <h2>Informacion del apartamento</h2>
    <p><strong>Conjunto:</strong> <?= htmlspecialchars($residente["nombre_conjunto"]) ?></p>
    <p><strong>Torre:</strong> <?= htmlspecialchars($residente["torre"]) ?></p>
    <p><strong>Numero:</strong> <?= htmlspecialchars($residente["numero"]) ?></p>
    <p><strong>Metros cuadrados:</strong> <?= htmlspecialchars($residente["metros_cuadrados"]) ?> m²</p>
    <p><strong>Estado:</strong> <?= $estadoMostrado ?></p>

    



    <h2>Datos del residente</h2>
    <p><strong>Nombre completo:</strong> <?= htmlspecialchars($residente["nombre"] . " " . $residente["apellido"]) ?></p>
    <p><strong>Documento:</strong> <?= htmlspecialchars($residente["documento"]) ?></p>
    <p><strong>Tipo de documento:</strong> <?= $tipoDocumentoMostrado ?></p>



    <h2>Datos de contacto</h2>
    <p><strong>Correo electrónico:</strong> <?= htmlspecialchars($residente["correo"]) ?></p>
    <p><strong>Teléfono principal:</strong> <?= htmlspecialchars($residente["telefono"] ?? "No registrado") ?></p>
    <p><strong>Teléfono secundario:</strong> <?= htmlspecialchars($residente["telefono_2"] ?? "No registrado") ?></p>

    <!--condicion si es inquilino mostrar informacion de propetario -->

    <?php if ($residente["tipo_residencia"] === "inquilino" && $propietario): ?>
        
        <h2>Informacion del propietario</h2>
        <p><strong>Nombre completo:</strong> <?= htmlspecialchars($propietario["nombre"] . " " . $propietario["apellido"]) ?></p>
        <p><strong>Documento:</strong> <?= htmlspecialchars($propietario["documento"]) ?></p>
        <p><strong>Tipo de documento:</strong> <?= $tiposDocumento[$propietario["tipo_documento"]] ?? "No especificado" ?></p>
        <p><strong>Correo electronico:</strong> <?= htmlspecialchars($propietario["correo"]) ?></p>
        <p><strong>Telefono principal:</strong> <?= htmlspecialchars($propietario["telefono"] ?? "No registrado") ?></p>
        <p><strong>Teléfono segundario:</strong> <?= htmlspecialchars($propietario["telefono_2"] ?? "No registrado") ?></p>
    <?php endif; ?>




    <!-- la informacion del residente -->
    
    <h2>Informacion de residencia</h2>
    <p><strong>Tipo:</strong> <?= $tipoResidenciaMostrado ?></p>
    <p><strong>Fecha de ingreso:</strong> <?= date("d/m/Y", strtotime($residente["fecha_desde"])) ?></p>
    
    <?php if ($residente["fecha_hasta"]): ?>
        <p><strong>Fecha de salida:</strong> <?= date("d/m/Y", strtotime($residente["fecha_hasta"])) ?></p>
    <?php else: ?>
        <p><strong>Fecha de salida:</strong> Actualmente reside</p>
    <?php endif; ?>

    <p><strong>Fecha de registro en el sistema:</strong> <?= date("d/m/Y H:i", strtotime($residente["fecha_registro"])) ?></p>



    <p>
        <a href="editar.php?id_pertenece=<?= $residente["id_pertenece"] ?>">Editar residente</a>
        |
        <a href="eliminar_residente.php?id_pertenece=<?= $residente["id_pertenece"] ?>"
            onclick="return confirm('¿Seguro que quieres eliminar este residente?');">Eliminar residente</a>
        |
        <a href="listar.php">Volver al listado</a>
    </p>
</body>
</html>