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
// FUNCIÓN DE REDIRECCIÓN CON ERROR
// ==========================================================

function redireccionarError($idCalendario, $mensaje)
{
    header(
        "Location: " .
        BASE_URL .
        "configuracion/editar_calendario_financiero.php?id=" .
        (int)$idCalendario .
        "&tipo=error&mensaje=" .
        urlencode($mensaje)
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
        "configuracion/calendario_financiero.php" .
        "?tipo=error&mensaje=" .
        urlencode(
            "El período financiero no es válido."
        )
    );

    exit;
}


// ==========================================================
// FUNCIÓN PARA VALIDAR FECHAS
// ==========================================================

function fechaValida($fecha)
{
    if ($fecha === '') {
        return false;
    }

    $fechaObj = DateTime::createFromFormat(
        'Y-m-d',
        $fecha
    );

    $errores = DateTime::getLastErrors();

    if (!$fechaObj) {
        return false;
    }

    if (
        $errores !== false &&
        (
            $errores['warning_count'] > 0 ||
            $errores['error_count'] > 0
        )
    ) {
        return false;
    }

    return $fechaObj->format('Y-m-d') === $fecha;
}


// ==========================================================
// VALIDAR FECHAS
// ==========================================================

$camposFecha = [

    'Inicio de cierre'
        => $fechaInicioCierre,

    'Fin de cierre'
        => $fechaFinCierre,

    'Facturación'
        => $fechaFacturacion,

    'Generación de intereses'
        => $fechaGeneracionIntereses,

    'Vencimiento'
        => $fechaVencimiento

];


foreach ($camposFecha as $nombreCampo => $fecha) {

    if (!fechaValida($fecha)) {

        redireccionarError(
            $idCalendario,
            "La fecha de {$nombreCampo} no es válida."
        );
    }
}


// ==========================================================
// VALIDAR ORDEN DE FECHAS
// ==========================================================

// ----------------------------------------------------------
// Facturación <= vencimiento
// ----------------------------------------------------------

if ($fechaFacturacion > $fechaVencimiento) {

    redireccionarError(
        $idCalendario,
        "La fecha de vencimiento no puede ser anterior " .
        "a la fecha de facturación."
    );
}


// ----------------------------------------------------------
// Inicio cierre <= fin cierre
// ----------------------------------------------------------

if ($fechaInicioCierre > $fechaFinCierre) {

    redireccionarError(
        $idCalendario,
        "La fecha de inicio del cierre no puede ser posterior " .
        "a la fecha de fin del cierre."
    );
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

    redireccionarError(
        $idCalendario,
        "El estado seleccionado no es válido."
    );
}


// ==========================================================
// ACTUALIZAR
// ==========================================================

try {

    // ======================================================
    // VERIFICAR QUE EL PERÍODO EXISTA
    // ======================================================

    $sqlExiste = "SELECT id_calendario,periodo,estado
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
            "configuracion/calendario_financiero.php" .
            "?tipo=error&mensaje=" .
            urlencode(
                "El período financiero no existe."
            )
        );

        exit;
    }


    // ======================================================
    // VALIDAR AÑO DEL PERÍODO
    // ==========================================================

    $anioPeriodo = date(
        'Y',
        strtotime($calendario['periodo'])
    );


    $fechasPeriodo = [
        'fecha de facturación'=> $fechaFacturacion,
        'fecha de vencimiento' => $fechaVencimiento,
        'fecha de inicio de cierre' => $fechaInicioCierre,
        'fecha de fin de cierre' => $fechaFinCierre,
        'fecha de generación de intereses'=> $fechaGeneracionIntereses
    ];


    foreach (
        $fechasPeriodo as $nombreFecha => $fecha
    ) {

        $anioFecha = substr(
            $fecha,
            0,
            4
        );


        if ($anioFecha !== $anioPeriodo) {

            redireccionarError(
                $idCalendario,
                "La {$nombreFecha} no corresponde al año " .
                "del período financiero."
            );
        }
    }


    // ======================================================
    // ACTUALIZAR REGISTRO
    // ======================================================

    $sql = " UPDATE calendario_financiero SET
            fecha_inicio_cierre = :fecha_inicio_cierre,
            fecha_fin_cierre =:fecha_fin_cierre,
            fecha_facturacion =:fecha_facturacion,
            fecha_generacion_intereses =:fecha_generacion_intereses,
            fecha_vencimiento =:fecha_vencimiento,
            estado =:estado,
            observaciones =:observaciones
        WHERE id_calendario =:id_calendario
    ";


    $stmt = $conexion->prepare(
        $sql
    );


    $stmt->execute([
        ':fecha_inicio_cierre' => $fechaInicioCierre,
        ':fecha_fin_cierre'=> $fechaFinCierre,
        ':fecha_facturacion'=> $fechaFacturacion,
        ':fecha_generacion_intereses'=> $fechaGeneracionIntereses,
        ':fecha_vencimiento'=> $fechaVencimiento,
        ':estado'=> $estado,
        ':observaciones'=> $observaciones !== ''
                ? $observaciones
                : null,
        ':id_calendario'=> $idCalendario
    ]);


    // ======================================================
    // CONFIRMACIÓN
    // ======================================================

    header(
        "Location: " .
        BASE_URL .
        "configuracion/calendario_financiero.php" .
        "?tipo=success&mensaje=" .
        urlencode(
            "El período financiero fue actualizado correctamente."
        )
    );

    exit;


} catch (PDOException $e) {

    // ======================================================
    // REGISTRAR ERROR
    // ======================================================

    error_log(
        "Error actualizando calendario financiero ID " .
        $idCalendario .
        ": " .
        $e->getMessage()
    );


    header(
        "Location: " .
        BASE_URL .
        "configuracion/editar_calendario_financiero.php?id=" .
        (int)$idCalendario .
        "&tipo=error&mensaje=" .
        urlencode(
            "No fue posible actualizar el período financiero."
        )
    );

    exit;
}