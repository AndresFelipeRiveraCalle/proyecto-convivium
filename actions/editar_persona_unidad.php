<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

try {

    // ==========================================================
    // DATOS DEL FORMULARIO
    // ==========================================================

    $id_relacion = isset($_POST['id_relacion'])
        ? (int) $_POST['id_relacion']
        : 0;

    $tipo = isset($_POST['tipo'])
        ? trim($_POST['tipo'])
        : '';

    $recibe_factura = isset($_POST['recibe_factura'])
        ? (int) $_POST['recibe_factura']
        : 0;

    $fecha_desde = !empty($_POST['fecha_desde'])
        ? $_POST['fecha_desde']
        : null;


    // ==========================================================
    // VALIDAR ID DE RELACIÓN
    // ==========================================================

    if ($id_relacion <= 0) {

        header(
            "Location: ../configuracion/unidades.php?tipo=warning&texto=La relación no es válida."
        );

        exit;
    }


    // ==========================================================
    // VALIDAR TIPO
    // ==========================================================

    if (!in_array(
        $tipo,
        ['propietario', 'inquilino', 'residente'],
        true
    )) {

        header(
            "Location: ../configuracion/personas_unidad.php?tipo=warning&texto=El tipo de relación no es válido."
        );

        exit;
    }


    // ==========================================================
    // VALIDAR RECIBE FACTURA
    // ==========================================================

    if (!in_array($recibe_factura, [0, 1], true)) {

        $recibe_factura = 0;
    }


    // ==========================================================
    // BUSCAR RELACIÓN
    // ==========================================================

    $sql = "
        SELECT
            r.id,
            r.unidad_id,
            r.usuario_id,
            u.nombres,
            u.apellidos

        FROM residente r

        INNER JOIN usuario u
            ON u.id = r.usuario_id

        WHERE
            r.id = :id_relacion

            AND r.activo = 1

        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ':id_relacion' => $id_relacion
    ]);

    $relacion = $stmt->fetch(PDO::FETCH_ASSOC);


    // ==========================================================
    // VALIDAR QUE EXISTA
    // ==========================================================

    if (!$relacion) {

        header(
            "Location: ../configuracion/unidades.php?tipo=warning&texto=La relación no existe."
        );

        exit;
    }


    $unidad_id = (int) $relacion['unidad_id'];


    // ==========================================================
    // ACTUALIZAR RELACIÓN
    // ==========================================================

    $sql = "
        UPDATE residente

        SET
            tipo = :tipo,
            recibe_factura = :recibe_factura,
            fecha_desde = :fecha_desde

        WHERE id = :id_relacion
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ':tipo'           => $tipo,
        ':recibe_factura' => $recibe_factura,
        ':fecha_desde'    => $fecha_desde,
        ':id_relacion'    => $id_relacion
    ]);


    // ==========================================================
    // MENSAJE DE ÉXITO
    // ==========================================================

    $nombreCompleto =
        $relacion['nombres'] . ' ' . $relacion['apellidos'];

    header(
        "Location: ../configuracion/personas_unidad.php?id_unidad="
        . $unidad_id
        . "&tipo=success&texto="
        . urlencode(
            "La relación de "
            . $nombreCompleto
            . " fue actualizada correctamente."
        )
    );

    exit;


} catch (PDOException $e) {

    die(
        "Error al editar la relación: "
        . $e->getMessage()
    );
}