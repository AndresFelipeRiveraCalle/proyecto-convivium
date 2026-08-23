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
        "configuracion/generar_obligacion.php"
    );

    exit;
}


// ==========================================================
// DATOS DEL FORMULARIO
// ==========================================================

$idUnidad = (int)($_POST['id_unidad'] ?? 0);

$idTipoObligacion = (int)(
    $_POST['id_tipo_obligacion'] ?? 0
);

$periodo = trim(
    $_POST['periodo'] ?? ''
);

$descripcion = trim(
    $_POST['descripcion'] ?? ''
);

$valorOriginal = trim(
    $_POST['valor_original'] ?? ''
);

$observaciones = trim(
    $_POST['observaciones'] ?? ''
);


// ==========================================================
// VALIDACIONES BÁSICAS
// ==========================================================

if ($idUnidad <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "actions/generar_obligacion.php?tipo=error&mensaje=" .
        urlencode("Debe seleccionar una unidad.")
    );

    exit;
}


if ($idTipoObligacion <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/generar_obligacion.php?tipo=error&mensaje=" .
        urlencode("Debe seleccionar el tipo de obligación.")
    );

    exit;
}


if (
    empty($periodo) ||
    !preg_match('/^\d{4}-\d{2}$/', $periodo)
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/generar_obligacion.php?tipo=error&mensaje=" .
        urlencode("El período seleccionado no es válido.")
    );

    exit;
}


if (
    $valorOriginal === '' ||
    !is_numeric($valorOriginal) ||
    (float)$valorOriginal <= 0
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/generar_obligacion.php?tipo=error&mensaje=" .
        urlencode("El valor de la obligación no es válido.")
    );

    exit;
}


$valorOriginal = (float)$valorOriginal;


// ==========================================================
// CONVERTIR PERÍODO
// ==========================================================

$periodoBD = $periodo . '-01';


// ==========================================================
// VALIDAR FECHA DEL PERÍODO
// ==========================================================

$fechaPeriodo = DateTime::createFromFormat(
    'Y-m-d',
    $periodoBD
);

if (
    !$fechaPeriodo ||
    $fechaPeriodo->format('Y-m-d') !== $periodoBD
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/generar_obligacion.php?tipo=error&mensaje=" .
        urlencode("El período financiero no es válido.")
    );

    exit;
}


// ==========================================================
// BUSCAR CALENDARIO FINANCIERO DEL PERÍODO
// ==========================================================

$sqlCalendario = "

    SELECT

        id_calendario,
        periodo,
        fecha_vencimiento,
        estado

    FROM calendario_financiero

    WHERE periodo = :periodo

    LIMIT 1

";

$stmtCalendario = $conexion->prepare(
    $sqlCalendario
);

$stmtCalendario->execute([
    ':periodo' => $periodoBD
]);

$calendario = $stmtCalendario->fetch(
    PDO::FETCH_ASSOC
);


// ==========================================================
// VALIDAR CALENDARIO
// ==========================================================

if (!$calendario) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/generar_obligacion.php?tipo=error&mensaje=" .
        urlencode(
            "No existe un calendario financiero para el período seleccionado."
        )
    );

    exit;
}


// ==========================================================
// VALIDAR ESTADO DEL CALENDARIO
// ==========================================================
//
// No permitimos generar obligaciones de un período
// que ya esté cerrado.
//

if ($calendario['estado'] === 'CERRADO') {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/generar_obligacion.php?tipo=error&mensaje=" .
        urlencode(
            "El período seleccionado ya está cerrado y no permite generar nuevas obligaciones."
        )
    );

    exit;
}


$fechaVencimiento = $calendario[
    'fecha_vencimiento'
];


// ==========================================================
// VALIDAR UNIDAD
// ==========================================================

$sqlUnidad = "

    SELECT id_unidad

    FROM unidades

    WHERE id_unidad = :id_unidad

    LIMIT 1

";

$stmtUnidad = $conexion->prepare(
    $sqlUnidad
);

$stmtUnidad->execute([
    ':id_unidad' => $idUnidad
]);

if (!$stmtUnidad->fetch(PDO::FETCH_ASSOC)) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/generar_obligacion.php?tipo=error&mensaje=" .
        urlencode("La unidad seleccionada no existe.")
    );

    exit;
}


// ==========================================================
// VALIDAR TIPO DE OBLIGACIÓN
// ==========================================================

$sqlTipo = "

    SELECT

        id_tipo_obligacion,
        nombre,
        activo

    FROM tipos_obligacion

    WHERE id_tipo_obligacion = :id_tipo_obligacion

    LIMIT 1

";

$stmtTipo = $conexion->prepare(
    $sqlTipo
);

$stmtTipo->execute([
    ':id_tipo_obligacion' => $idTipoObligacion
]);

$tipoObligacion = $stmtTipo->fetch(
    PDO::FETCH_ASSOC
);


if (!$tipoObligacion) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/generar_obligacion.php?tipo=error&mensaje=" .
        urlencode("El tipo de obligación seleccionado no existe.")
    );

    exit;
}


if ((int)$tipoObligacion['activo'] !== 1) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/generar_obligacion.php?tipo=error&mensaje=" .
        urlencode("El tipo de obligación seleccionado está inactivo.")
    );

    exit;
}


// ==========================================================
// DESCRIPCIÓN AUTOMÁTICA
// ==========================================================

if ($descripcion === '') {

    $descripcion =
        $tipoObligacion['nombre'] .
        ' ' .
        $fechaPeriodo->format('m/Y');
}


// ==========================================================
// EVITAR DUPLICAR OBLIGACIONES
// ==========================================================
//
// Una unidad no debería tener dos obligaciones del mismo
// tipo para el mismo período.
//

$sqlExiste = "

    SELECT id_cartera

    FROM cartera

    WHERE id_unidad = :id_unidad

      AND id_tipo_obligacion = :id_tipo_obligacion

      AND periodo = :periodo

      AND estado <> 'ANULADA'

    LIMIT 1

";

$stmtExiste = $conexion->prepare(
    $sqlExiste
);

$stmtExiste->execute([

    ':id_unidad' =>
        $idUnidad,

    ':id_tipo_obligacion' =>
        $idTipoObligacion,

    ':periodo' =>
        $periodoBD

]);


if ($stmtExiste->fetch(PDO::FETCH_ASSOC)) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/generar_obligacion.php?tipo=error&mensaje=" .
        urlencode(
            "Ya existe una obligación de este tipo para la unidad y período seleccionados."
        )
    );

    exit;
}


// ==========================================================
// VALORES INICIALES
// ==========================================================

$valorPagado = 0.00;

$saldo = $valorOriginal;

$estado = 'PENDIENTE';


// ==========================================================
// INSERTAR OBLIGACIÓN
// ==========================================================

try {

    $conexion->beginTransaction();


    $sql = "

        INSERT INTO cartera

        (

            id_unidad,

            id_tipo_obligacion,

            periodo,

            descripcion,

            valor_original,

            valor_pagado,

            saldo,

            fecha_vencimiento,

            estado,

            observaciones

        )

        VALUES

        (

            :id_unidad,

            :id_tipo_obligacion,

            :periodo,

            :descripcion,

            :valor_original,

            :valor_pagado,

            :saldo,

            :fecha_vencimiento,

            :estado,

            :observaciones

        )

    ";


    $stmt = $conexion->prepare($sql);


    $stmt->execute([

        ':id_unidad' =>
            $idUnidad,

        ':id_tipo_obligacion' =>
            $idTipoObligacion,

        ':periodo' =>
            $periodoBD,

        ':descripcion' =>
            $descripcion,

        ':valor_original' =>
            $valorOriginal,

        ':valor_pagado' =>
            $valorPagado,

        ':saldo' =>
            $saldo,

        ':fecha_vencimiento' =>
            $fechaVencimiento,

        ':estado' =>
            $estado,

        ':observaciones' =>
            $observaciones !== ''
                ? $observaciones
                : null

    ]);


    $idCartera = $conexion->lastInsertId();


    $conexion->commit();


    // ======================================================
    // ÉXITO
    // ======================================================

    header(
        "Location: " .
        BASE_URL .
        "configuracion/generar_obligacion.php?tipo=success&mensaje=" .
        urlencode(
            "La obligación fue generada correctamente. ID: " .
            $idCartera
        )
    );

    exit;


} catch (PDOException $e) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }


    // ======================================================
    // ERROR
    // ======================================================

    echo "<div style='
        font-family: Arial;
        max-width: 900px;
        margin: 40px auto;
        padding: 25px;
        border: 1px solid #ddd;
        border-radius: 10px;
        background: #fff;
    '>";

    echo "<h2 style='color:#b91c1c;'>
        Error al guardar la obligación
    </h2>";

    echo "<p>
        <strong>Mensaje de MySQL:</strong>
    </p>";

    echo "<pre style='
        background:#f5f5f5;
        padding:15px;
        overflow:auto;
    '>";

    echo htmlspecialchars(
        $e->getMessage()
    );

    echo "</pre>";

    echo "<p>
        <strong>SQLSTATE:</strong>
        " .
        htmlspecialchars(
            $e->getCode()
        ) .
    "</p>";

    echo "<hr>";

    echo "<p>
        <strong>Datos recibidos:</strong>
    </p>";

    echo "<pre>";

    print_r([

        'id_unidad' =>
            $idUnidad,

        'id_tipo_obligacion' =>
            $idTipoObligacion,

        'periodo' =>
            $periodoBD,

        'descripcion' =>
            $descripcion,

        'valor_original' =>
            $valorOriginal,

        'valor_pagado' =>
            $valorPagado,

        'saldo' =>
            $saldo,

        'fecha_vencimiento' =>
            $fechaVencimiento,

        'estado' =>
            $estado,

        'observaciones' =>
            $observaciones

    ]);

    echo "</pre>";

    echo "<br>";

    echo "<a href='javascript:history.back()'>
        ← Regresar
    </a>";

    echo "</div>";
}