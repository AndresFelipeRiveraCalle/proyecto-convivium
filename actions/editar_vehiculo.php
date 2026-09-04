<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// VERIFICAR MÉTODO
// ==========================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: " . BASE_URL . "vehiculos.php?tipo=warning&texto=" .
        urlencode("Solicitud no válida.")
    );

    exit;
}


try {

    // ======================================================
    // DATOS DEL FORMULARIO
    // ======================================================

    $id_vehiculo = isset($_POST["id_vehiculo"])
        ? (int) $_POST["id_vehiculo"]
        : 0;

    $placa = isset($_POST["placa"])
        ? strtoupper(trim($_POST["placa"]))
        : "";

    $tipo = isset($_POST["tipo"])
        ? strtoupper(trim($_POST["tipo"]))
        : "";

    $marca = isset($_POST["marca"])
        ? trim($_POST["marca"])
        : "";

    $modelo = isset($_POST["modelo"])
        ? trim($_POST["modelo"])
        : "";

    $color = isset($_POST["color"])
        ? trim($_POST["color"])
        : "";

    $id_unidad = isset($_POST["id_unidad"]) &&
                 $_POST["id_unidad"] !== ""
        ? (int) $_POST["id_unidad"]
        : null;

    $id_residente = isset($_POST["id_residente"]) &&
                    $_POST["id_residente"] !== ""
        ? (int) $_POST["id_residente"]
        : null;

    $fecha_desde = isset($_POST["fecha_desde"]) &&
                   $_POST["fecha_desde"] !== ""
        ? $_POST["fecha_desde"]
        : null;

    $fecha_hasta = isset($_POST["fecha_hasta"]) &&
                   $_POST["fecha_hasta"] !== ""
        ? $_POST["fecha_hasta"]
        : null;

    $observaciones = isset($_POST["observaciones"])
        ? trim($_POST["observaciones"])
        : "";

    $estado = isset($_POST["estado"])
        ? (int) $_POST["estado"]
        : 1;


    // ======================================================
    // VALIDACIONES BÁSICAS
    // ======================================================

    if ($id_vehiculo <= 0) {

        throw new Exception(
            "El vehículo no es válido."
        );
    }


    if ($placa === "") {

        throw new Exception(
            "La placa es obligatoria."
        );
    }


    if ($tipo === "") {

        throw new Exception(
            "El tipo de vehículo es obligatorio."
        );
    }


    // ======================================================
    // VERIFICAR QUE EL VEHÍCULO EXISTA
    // ======================================================

    $sql = "
        SELECT id_vehiculo
        FROM vehiculos
        WHERE id_vehiculo = :id_vehiculo
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":id_vehiculo" => $id_vehiculo
    ]);

    $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$vehiculo) {

        throw new Exception(
            "El vehículo no existe."
        );
    }


    // ======================================================
    // VERIFICAR PLACA DUPLICADA
    // ======================================================

    $sql = "
        SELECT id_vehiculo
        FROM vehiculos
        WHERE placa = :placa
        AND id_vehiculo <> :id_vehiculo
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":placa"       => $placa,
        ":id_vehiculo" => $id_vehiculo
    ]);

    $placaExiste = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($placaExiste) {

        throw new Exception(
            "La placa ya está registrada en otro vehículo."
        );
    }


    // ======================================================
    // VALIDAR UNIDAD
    // ======================================================

    if ($id_unidad !== null) {

        $sql = "
            SELECT id_unidad
            FROM unidades
            WHERE id_unidad = :id_unidad
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sql);

        $stmt->execute([
            ":id_unidad" => $id_unidad
        ]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {

            throw new Exception(
                "La unidad seleccionada no existe."
            );
        }
    }


    // ======================================================
    // VALIDAR RESIDENTE
    // ======================================================

    if ($id_residente !== null) {

        $sql = "
            SELECT id_residente
            FROM residentes
            WHERE id_residente = :id_residente
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sql);

        $stmt->execute([
            ":id_residente" => $id_residente
        ]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {

            throw new Exception(
                "El residente seleccionado no existe."
            );
        }
    }


    // ======================================================
    // VALIDAR ESTADO
    // ======================================================

    if ($estado !== 0 && $estado !== 1) {

        $estado = 1;
    }


    // ======================================================
    // ACTUALIZAR VEHÍCULO
    // ======================================================

    $sql = "
        UPDATE vehiculos
        SET
            placa = :placa,
            tipo = :tipo,
            marca = :marca,
            modelo = :modelo,
            color = :color,
            id_residente = :id_residente,
            id_unidad = :id_unidad,
            estado = :estado,
            fecha_desde = :fecha_desde,
            fecha_hasta = :fecha_hasta,
            observaciones = :observaciones
        WHERE id_vehiculo = :id_vehiculo
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->bindValue(
        ":placa",
        $placa,
        PDO::PARAM_STR
    );

    $stmt->bindValue(
        ":tipo",
        $tipo,
        PDO::PARAM_STR
    );

    $stmt->bindValue(
        ":marca",
        $marca !== "" ? $marca : null,
        $marca !== "" ? PDO::PARAM_STR : PDO::PARAM_NULL
    );

    $stmt->bindValue(
        ":modelo",
        $modelo !== "" ? $modelo : null,
        $modelo !== "" ? PDO::PARAM_STR : PDO::PARAM_NULL
    );

    $stmt->bindValue(
        ":color",
        $color !== "" ? $color : null,
        $color !== "" ? PDO::PARAM_STR : PDO::PARAM_NULL
    );

    $stmt->bindValue(
        ":id_residente",
        $id_residente,
        $id_residente !== null
            ? PDO::PARAM_INT
            : PDO::PARAM_NULL
    );

    $stmt->bindValue(
        ":id_unidad",
        $id_unidad,
        $id_unidad !== null
            ? PDO::PARAM_INT
            : PDO::PARAM_NULL
    );

    $stmt->bindValue(
        ":estado",
        $estado,
        PDO::PARAM_INT
    );

    $stmt->bindValue(
        ":fecha_desde",
        $fecha_desde,
        $fecha_desde !== null
            ? PDO::PARAM_STR
            : PDO::PARAM_NULL
    );

    $stmt->bindValue(
        ":fecha_hasta",
        $fecha_hasta,
        $fecha_hasta !== null
            ? PDO::PARAM_STR
            : PDO::PARAM_NULL
    );

    $stmt->bindValue(
        ":observaciones",
        $observaciones !== "" ? $observaciones : null,
        $observaciones !== ""
            ? PDO::PARAM_STR
            : PDO::PARAM_NULL
    );

    $stmt->bindValue(
        ":id_vehiculo",
        $id_vehiculo,
        PDO::PARAM_INT
    );


    $stmt->execute();


    // ======================================================
    // REDIRECCIÓN ÉXITO
    // ======================================================

    header(
        "Location: " . BASE_URL .
        "/configuracion/vehiculos.php?tipo=success&texto=" .
        urlencode("Vehículo actualizado correctamente.")
    );

    exit;


} catch (Exception $e) {

    // ======================================================
    // ERROR
    // ======================================================

    header(
        "Location: " . BASE_URL .
        "/configuracion/vehiculos.php?tipo=error&texto=" .
        urlencode($e->getMessage())
    );

    exit;

}