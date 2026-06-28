<?php
require_once "../config/conexion.php";

$idPertenece = isset($_GET["id_pertenece"]) ? $_GET["id_pertenece"] : null;

if (!$idPertenece) {
    echo "Falta indicar qué residente quieres eliminar.";
    exit;
}

try {
    // primero averiguamos a que apartamento pertenecia, para poder actualizar su estado despues
    $consultaUnidad = $conexion->prepare("SELECT unidad_id FROM pertenece WHERE id = :id_pertenece");
    $consultaUnidad->execute([":id_pertenece" => $idPertenece]);
    $fila = $consultaUnidad->fetch();

    if ($fila) {
        $idUnidad = $fila["unidad_id"];

        // Eliminamos la relación del residente con el apartamento
        // (no borramos su cuenta de usuario, solo lo desvinculamos del apartamento)
        $consultaEliminar = $conexion->prepare("DELETE FROM pertenece WHERE id = :id_pertenece");
        $consultaEliminar->execute([":id_pertenece" => $idPertenece]);

        // Revisamos si quedan otros residentes activos en ese apartamento
        $consultaRestantes = $conexion->prepare("SELECT COUNT(*) AS total FROM pertenece WHERE unidad_id = :unidad_id");
        $consultaRestantes->execute([":unidad_id" => $idUnidad]);
        $restantes = $consultaRestantes->fetch();

        // Si no queda nadie, marcamos el apartamento como desocupado
        if ($restantes["total"] == 0) {
            $consultaActualizarUnidad = $conexion->prepare("UPDATE unidad SET estado = 'desocupado' WHERE id = :unidad_id");
            $consultaActualizarUnidad->execute([":unidad_id" => $idUnidad]);
        }
    }

    header("Location: listar.php");
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());
    echo "Ocurrió un error al eliminar el residente. Intenta más tarde.";
}
