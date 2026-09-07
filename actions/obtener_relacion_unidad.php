<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

header('Content-Type: application/json; charset=utf-8');


// ==========================================================
// VALIDAR ID
// ==========================================================

$idRelacion = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


if ($idRelacion <= 0) {

    echo json_encode([
        'success' => false,
        'message' => 'Relación no válida.'
    ]);

    exit;
}


try {

    // ======================================================
    // CONSULTAR RELACIÓN
    // ======================================================

    $sql = "
        SELECT

            r.id,
            r.unidad_id,
            r.usuario_id,
            r.tipo,
            r.recibe_factura,
            r.fecha_desde,
            r.fecha_hasta,
            r.activo,

            u.nombres,
            u.apellidos,
            u.numero_documento

        FROM residente r

        INNER JOIN usuario u
            ON u.id = r.usuario_id

        WHERE r.id = :id

        LIMIT 1
    ";


    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ':id' => $idRelacion
    ]);


    $relacion = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$relacion) {

        echo json_encode([
            'success' => false,
            'message' => 'La relación no existe.'
        ]);

        exit;
    }


    echo json_encode([
        'success' => true,
        'data' => $relacion
    ]);


} catch (PDOException $e) {

    echo json_encode([
        'success' => false,
        'message' => 'No fue posible consultar la relación.'
    ]);
}