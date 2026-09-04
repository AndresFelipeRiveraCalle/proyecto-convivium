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

function redireccionarError($mensaje)
{
    header(
        "Location: " .
        BASE_URL .
        "configuracion/crear_calendario_financiero.php" .
        "?tipo=error" .
        "&mensaje=" .
        urlencode($mensaje)
    );

    exit;
}


// ==========================================================
// DATOS DEL FORMULARIO
// ==========================================================

$periodo = trim(
    $_POST['periodo'] ?? ''
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

$observaciones = trim(
    $_POST['observaciones'] ?? ''
);


// ==========================================================
// ESTADO INICIAL
// ==========================================================
//
// Todo período nuevo debe iniciar como ABIERTO.
//
// No confiamos en un estado enviado desde el formulario.
//

$estado = 'ABIERTO';


// ==========================================================
// VALIDAR PERÍODO
// ==========================================================

if (
    $periodo === '' ||
    !preg_match('/^\d{4}-\d{2}$/', $periodo)
) {

    redireccionarError(
        "El período financiero no es válido."
    );
}


// ==========================================================
// VALIDAR MES DEL PERÍODO
// ==========================================================

[$anioPeriodo, $mesPeriodo] = array_map(
    'intval',
    explode('-', $periodo)
);

if (
    $anioPeriodo < 2000 ||
    $anioPeriodo > 2100 ||
    $mesPeriodo < 1 ||
    $mesPeriodo > 12
) {

    redireccionarError(
        "El período financiero seleccionado no es válido."
    );
}


// ==========================================================
// CONVERTIR PERÍODO PARA BASE DE DATOS
// ==========================================================
//
// El input type="month" entrega:
//
// 2026-09
//
// calendario_financiero.periodo es DATE:
//
// 2026-09-01
//

$periodoBD = sprintf(
    '%04d-%02d-01',
    $anioPeriodo,
    $mesPeriodo
);


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
// VALIDAR FECHAS OBLIGATORIAS
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
            "La fecha de {$nombreCampo} no es válida."
        );
    }
}


// ==========================================================
// VALIDAR ORDEN DE FECHAS
// ==========================================================

// ----------------------------------------------------------
// Facturación no puede ser posterior al vencimiento
// ----------------------------------------------------------

if ($fechaFacturacion > $fechaVencimiento) {

    redireccionarError(
        "La fecha de vencimiento no puede ser anterior " .
        "a la fecha de facturación."
    );
}


// ----------------------------------------------------------
// Inicio de cierre no puede ser posterior al fin de cierre
// ----------------------------------------------------------

if ($fechaInicioCierre > $fechaFinCierre) {

    redireccionarError(
        "La fecha de inicio del cierre no puede ser posterior " .
        "a la fecha de fin del cierre."
    );
}


// ==========================================================
// VALIDAR QUE LAS FECHAS CORRESPONDAN AL PERÍODO
// ==========================================================
//
// Dejamos una validación razonable:
// las fechas principales deben pertenecer al mismo año
// del período.
//
// No obligamos todavía a que todas estén exactamente dentro
// del mismo mes porque alguna configuración podría manejar
// procesos al inicio del mes siguiente.
//

$anioPeriodoTexto = sprintf(
    '%04d',
    $anioPeriodo
);

$fechasPrincipales = [

    'fecha de facturación'
        => $fechaFacturacion,

    'fecha de vencimiento'
        => $fechaVencimiento,

    'fecha de inicio de cierre'
        => $fechaInicioCierre,

    'fecha de fin de cierre'
        => $fechaFinCierre,

    'fecha de generación de intereses'
        => $fechaGeneracionIntereses

];


foreach (
    $fechasPrincipales as $nombreFecha => $fecha
) {

    $anioFecha = substr(
        $fecha,
        0,
        4
    );

    if ($anioFecha !== $anioPeriodoTexto) {

        redireccionarError(
            "La {$nombreFecha} no corresponde al año " .
            "del período seleccionado."
        );
    }
}


// ==========================================================
// GUARDAR
// ==========================================================

try {

    // ======================================================
    // VERIFICAR QUE EL PERÍODO NO EXISTA
    // ======================================================

    $sqlExiste = "
        SELECT
            id_calendario

        FROM calendario_financiero

        WHERE periodo = :periodo

        LIMIT 1
    ";


    $stmtExiste = $conexion->prepare(
        $sqlExiste
    );


    $stmtExiste->execute([

        ':periodo'
            => $periodoBD

    ]);


    if ($stmtExiste->fetch(PDO::FETCH_ASSOC)) {

        redireccionarError(
            "El período financiero seleccionado ya existe."
        );
    }


    // ======================================================
    // INSERTAR PERÍODO
    // ======================================================

    $sql = "
        INSERT INTO calendario_financiero
        (
            periodo,
            fecha_inicio_cierre,
            fecha_fin_cierre,
            fecha_facturacion,
            fecha_generacion_intereses,
            fecha_vencimiento,
            estado,
            observaciones
        )
        VALUES
        (
            :periodo,
            :fecha_inicio_cierre,
            :fecha_fin_cierre,
            :fecha_facturacion,
            :fecha_generacion_intereses,
            :fecha_vencimiento,
            :estado,
            :observaciones
        )
    ";


    $stmt = $conexion->prepare(
        $sql
    );


    $stmt->execute([

        ':periodo'
            => $periodoBD,

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
                : null

    ]);


    // ======================================================
    // REDIRECCIÓN EXITOSA
    // ======================================================

    header(
        "Location: " .
        BASE_URL .
        "configuracion/calendario_financiero.php" .
        "?tipo=success" .
        "&mensaje=" .
        urlencode(
            "El período financiero fue creado correctamente."
        )
    );

    exit;


} catch (PDOException $e) {

    // ======================================================
    // DUPLICADO
    // ======================================================
    //
    // La columna periodo ya es UNIQUE en la base de datos.
    //

    if ($e->getCode() === '23000') {

        redireccionarError(
            "El período financiero seleccionado ya existe."
        );
    }


    // ======================================================
    // ERROR GENERAL
    // ======================================================

    error_log(
        "Error guardando calendario financiero: " .
        $e->getMessage()
    );


    header(
        "Location: " .
        BASE_URL .
        "configuracion/crear_calendario_financiero.php" .
        "?tipo=error" .
        "&mensaje=" .
        urlencode(
            "No fue posible guardar el período financiero."
        )
    );

    exit;
}