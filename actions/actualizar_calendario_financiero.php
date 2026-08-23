<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// VALIDAR MÉTODO
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/calendario_financiero.php"
    );

    exit;
}


// ==========================================================
// DATOS DEL FORMULARIO
// ==========================================================

$idCalendario = filter_input(
    INPUT_POST,
    'id_calendario',
    FILTER_VALIDATE_INT
);

$fechaInicioCierre = trim(
    $_POST['fecha_inicio_cierre'] ?? ''
);

$fechaFinCierre = trim(
    $_POST['fecha_fin_cierre'] ?? ''
);

$fechaFacturacion = trim(
    $_POST['fecha_facturacion'] ?? ''
);

$fechaGeneracionIntereses = trim(
    $_POST['fecha_generacion_intereses'] ?? ''
);

$fechaVencimiento = trim(
    $_POST['fecha_vencimiento'] ?? ''
);

$estado = trim(
    $_POST['estado'] ?? ''
);

$observaciones = trim(
    $_POST['observaciones'] ?? ''
);


// ==========================================================
// VALIDAR ID
// ==========================================================

if (!$idCalendario) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/calendario_financiero.php?tipo=error&mensaje=" .
        urlencode(
            "El período financiero no es válido."
        )
    );

    exit;
}


// ==========================================================
// VALIDAR FECHAS
// ==========================================================

$camposFecha = [

    'fecha_inicio_cierre'
        => $fechaInicioCierre,

    'fecha_fin_cierre'
        => $fechaFinCierre,

    'fecha_facturacion'
        => $fechaFacturacion,

    'fecha_generacion_intereses'
        => $fechaGeneracionIntereses,

    'fecha_vencimiento'
        => $fechaVencimiento

];


foreach ($camposFecha as $campo => $fecha) {

    $fechaObj = DateTime::createFromFormat(
        'Y-m-d',
        $fecha
    );

    $erroresFecha = DateTime::getLastErrors();

    if (
        !$fechaObj ||
        (
            $erroresFecha !== false &&
            (
                $erroresFecha['warning_count'] > 0 ||
                $erroresFecha['error_count'] > 0
            )
        )
    ) {

        header(
            "Location: " .
            BASE_URL .
            "configuracion/editar_calendario_financiero.php?id=" .
            $idCalendario .
            "&tipo=error&mensaje=" .
            urlencode(
                "La fecha del campo " .
                $campo .
                " no es válida."
            )
        );

        exit;
    }
}


// ==========================================================
// VALIDAR ESTADO
// ==========================================================

$estadosPermitidos = [

    'ABIERTO',
    'EN_CIERRE',
    'CERRADO'

];


if (
    !in_array(
        $estado,
        $estadosPermitidos,
        true
    )
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/editar_calendario_financiero.php?id=" .
        $idCalendario .
        "&tipo=error&mensaje=" .
        urlencode(
            "El estado seleccionado no es válido."
        )
    );

    exit;
}


// ==========================================================
// ACTUALIZAR
// ==========================================================

try {

    // ======================================================
    // VERIFICAR QUE EL PERÍODO EXISTA
    // ======================================================

    $sqlExiste = "
        SELECT
            id_calendario,
            periodo

        FROM calendario_financiero

        WHERE id_calendario = :id_calendario

        LIMIT 1
    ";

    $stmtExiste = $conexion->prepare(
        $sqlExiste
    );

    $stmtExiste->execute([

        ':id_calendario'
            => $idCalendario

    ]);


    $calendario = $stmtExiste->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$calendario) {

        header(
            "Location: " .
            BASE_URL .
            "configuracion/calendario_financiero.php?tipo=error&mensaje=" .
            urlencode(
                "El período financiero no existe."
            )
        );

        exit;
    }


    // ======================================================
    // ACTUALIZAR REGISTRO
    // ======================================================

    $sql = "
        UPDATE calendario_financiero

        SET

            fecha_inicio_cierre =
                :fecha_inicio_cierre,

            fecha_fin_cierre =
                :fecha_fin_cierre,

            fecha_facturacion =
                :fecha_facturacion,

            fecha_generacion_intereses =
                :fecha_generacion_intereses,

            fecha_vencimiento =
                :fecha_vencimiento,

            estado =
                :estado,

            observaciones =
                :observaciones

        WHERE id_calendario =
            :id_calendario
    ";


    $stmt = $conexion->prepare(
        $sql
    );


    $stmt->execute([

        ':fecha_inicio_cierre'
            => $fechaInicioCierre,

        ':fecha_fin_cierre'
            => $fechaFinCierre,

        ':fecha_facturacion'
            => $fechaFacturacion,

        ':fecha_generacion_intereses'
            => $fechaGeneracionIntereses,

        ':fecha_vencimiento'
            => $fechaVencimiento,

        ':estado'
            => $estado,

        ':observaciones'
            => $observaciones !== ''
                ? $observaciones
                : null,

        ':id_calendario'
            => $idCalendario

    ]);


    // ======================================================
    // CONFIRMACIÓN
    // ======================================================

    header(
        "Location: " .
        BASE_URL .
        "configuracion/calendario_financiero.php" .
        "?tipo=success" .
        "&mensaje=" .
        urlencode(
            "El período financiero fue actualizado correctamente."
        )
    );

    exit;


} catch (PDOException $e) {

    // ======================================================
    // MOSTRAR ERROR REAL
    // ======================================================

    echo "
    <div style='
        font-family:Arial;
        max-width:900px;
        margin:40px auto;
        padding:25px;
        border:1px solid #ddd;
        border-radius:10px;
        background:#fff;
    '>

        <h2 style='color:#b91c1c;'>
            Error al actualizar el período
        </h2>

        <p>
            <strong>Mensaje de MySQL:</strong>
        </p>

        <pre style='
            background:#f5f5f5;
            padding:15px;
            overflow:auto;
        '>" .
        htmlspecialchars(
            $e->getMessage()
        ) .
        "</pre>

        <p>
            <strong>SQLSTATE:</strong>
            " .
            htmlspecialchars(
                $e->getCode()
            ) .
        "
        </p>

        <hr>

        <p>
            <strong>ID del calendario:</strong>
            " .
            (int)$idCalendario .
        "
        </p>

        <p>
            <a href='javascript:history.back()'>
                ← Regresar
            </a>
        </p>

    </div>";
}