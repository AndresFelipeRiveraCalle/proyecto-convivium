<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: " . BASE_URL . "pagos.php");

    exit;
}


try {

    // ==========================================================
    // ID PAGO
    // ==========================================================

    $id_pago = isset($_POST["id_pago"])
        ? (int) $_POST["id_pago"]
        : 0;


    // ==========================================================
    // DATOS
    // ==========================================================

    $id_unidad = isset($_POST["id_unidad"])
        ? (int) $_POST["id_unidad"]
        : 0;

    $fecha_pago = trim(
        $_POST["fecha_pago"] ?? ""
    );

    $valor = isset($_POST["valor"])
        ? (float) $_POST["valor"]
        : 0;

    $medio_pago = trim(
        $_POST["medio_pago"] ?? ""
    );

    $origen_pago = trim(
        $_POST["origen_pago"] ?? ""
    );

    $referencia = trim(
        $_POST["referencia"] ?? ""
    );

    $observaciones = trim(
        $_POST["observaciones"] ?? ""
    );


    // ==========================================================
    // VALIDACIONES
    // ==========================================================

    if ($id_unidad <= 0) {

        throw new Exception(
            "Debe seleccionar una unidad."
        );
    }


    if ($fecha_pago === "") {

        throw new Exception(
            "Debe indicar la fecha del pago."
        );
    }


    if ($valor <= 0) {

        throw new Exception(
            "El valor del pago debe ser mayor que cero."
        );
    }


    $mediosPermitidos = [
        "EFECTIVO",
        "TRANSFERENCIA",
        "CONSIGNACION",
        "PSE",
        "TARJETA",
        "OTRO"
    ];


    if (!in_array(
        $medio_pago,
        $mediosPermitidos,
        true
    )) {

        throw new Exception(
            "El medio de pago no es válido."
        );
    }


    $origenesPermitidos = [
        "MANUAL",
        "BANCO",
        "PASARELA"
    ];


    if (!in_array(
        $origen_pago,
        $origenesPermitidos,
        true
    )) {

        throw new Exception(
            "El origen del pago no es válido."
        );
    }


    // ==========================================================
    // VERIFICAR UNIDAD
    // ==========================================================

    $sqlUnidad = "
        SELECT id_unidad
        FROM unidades
        WHERE id_unidad = :id_unidad
          AND activo = 1
        LIMIT 1
    ";

    $stmtUnidad = $conexion->prepare(
        $sqlUnidad
    );

    $stmtUnidad->execute([
        ":id_unidad" => $id_unidad
    ]);

    $unidad = $stmtUnidad->fetch();


    if (!$unidad) {

        throw new Exception(
            "La unidad seleccionada no existe o está inactiva."
        );
    }


    // ==========================================================
    // VERIFICAR REFERENCIA DUPLICADA
    //
    // IMPORTANTE:
    // Si estamos editando, excluimos el mismo id_pago.
    // ==========================================================

    if ($referencia !== "") {

        $sqlDuplicado = "
            SELECT id_pago
            FROM pagos
            WHERE id_unidad = :id_unidad
              AND referencia = :referencia
              AND estado = 'REGISTRADO'
              AND id_pago <> :id_pago
            LIMIT 1
        ";

        $stmtDuplicado = $conexion->prepare(
            $sqlDuplicado
        );

        $stmtDuplicado->execute([
            ":id_unidad"  => $id_unidad,
            ":referencia" => $referencia,
            ":id_pago"    => $id_pago
        ]);

        $pagoExistente = $stmtDuplicado->fetch();


        if ($pagoExistente) {

            throw new Exception(
                "Ya existe un pago registrado para esta unidad con la referencia indicada."
            );
        }
    }


    // ==========================================================
    // ACTUALIZAR
    // ==========================================================

    if ($id_pago > 0) {


        // ------------------------------------------------------
        // Verificar que el pago exista
        // ------------------------------------------------------

        $sqlExiste = "
            SELECT id_pago
            FROM pagos
            WHERE id_pago = :id_pago
              AND estado = 'REGISTRADO'
            LIMIT 1
        ";

        $stmtExiste = $conexion->prepare(
            $sqlExiste
        );

        $stmtExiste->execute([
            ":id_pago" => $id_pago
        ]);

        $pagoExiste = $stmtExiste->fetch();


        if (!$pagoExiste) {

            throw new Exception(
                "El pago que intenta editar no existe o está anulado."
            );
        }


        // ------------------------------------------------------
        // UPDATE
        // ------------------------------------------------------

        $sql = "
            UPDATE pagos
            SET
                id_unidad = :id_unidad,
                fecha_pago = :fecha_pago,
                valor = :valor,
                medio_pago = :medio_pago,
                origen_pago = :origen_pago,
                referencia = :referencia,
                observaciones = :observaciones
            WHERE id_pago = :id_pago
        ";


        $stmt = $conexion->prepare($sql);


        $stmt->execute([

            ":id_unidad" =>
                $id_unidad,

            ":fecha_pago" =>
                $fecha_pago,

            ":valor" =>
                $valor,

            ":medio_pago" =>
                $medio_pago,

            ":origen_pago" =>
                $origen_pago,

            ":referencia" =>
                $referencia !== ""
                    ? $referencia
                    : null,

            ":observaciones" =>
                $observaciones !== ""
                    ? $observaciones
                    : null,

            ":id_pago" =>
                $id_pago
        ]);


        $mensaje =
            "Pago actualizado correctamente.";

    }


    // ==========================================================
    // NUEVO PAGO
    // ==========================================================

    else {


        $sql = "
            INSERT INTO pagos (
                id_unidad,
                fecha_pago,
                valor,
                medio_pago,
                origen_pago,
                estado_conciliacion,
                referencia,
                observaciones,
                estado
            )
            VALUES (
                :id_unidad,
                :fecha_pago,
                :valor,
                :medio_pago,
                :origen_pago,
                'PENDIENTE',
                :referencia,
                :observaciones,
                'REGISTRADO'
            )
        ";


        $stmt = $conexion->prepare(
            $sql
        );


        $stmt->execute([

            ":id_unidad" =>
                $id_unidad,

            ":fecha_pago" =>
                $fecha_pago,

            ":valor" =>
                $valor,

            ":medio_pago" =>
                $medio_pago,

            ":origen_pago" =>
                $origen_pago,

            ":referencia" =>
                $referencia !== ""
                    ? $referencia
                    : null,

            ":observaciones" =>
                $observaciones !== ""
                    ? $observaciones
                    : null
        ]);


        $mensaje =
            "Pago registrado correctamente.";
    }


    // ==========================================================
    // REDIRECCIÓN
    // ==========================================================

    $url = $_SERVER['HTTP_REFERER'] ?? BASE_URL;

    $separador =
        (strpos($url, '?') !== false)
        ? '&'
        : '?';


    header(
        "Location: " .
        $url .
        $separador .
        "tipo=success&mensaje=" .
        urlencode($mensaje)
    );

    exit;


} catch (Exception $e) {


    // ==========================================================
    // ERROR
    // ==========================================================

    $url = $_SERVER['HTTP_REFERER'] ?? BASE_URL;

    $separador =
        (strpos($url, '?') !== false)
        ? '&'
        : '?';


    header(
        "Location: " .
        $url .
        $separador .
        "tipo=error&mensaje=" .
        urlencode($e->getMessage())
    );

    exit;
}