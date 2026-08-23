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

$periodo = trim($_POST['periodo'] ?? '');

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
    $_POST['estado'] ?? 'ABIERTO'
);

$observaciones = trim(
    $_POST['observaciones'] ?? ''
);


// ==========================================================
// VALIDAR PERÍODO
// ==========================================================

if (
    empty($periodo) ||
    !preg_match('/^\d{4}-\d{2}$/', $periodo)
) {

    die("
        <h2>Error</h2>

        <p>
            El período no es válido.
        </p>

        <p>
            Valor recibido:
            <strong>" .
            htmlspecialchars($periodo) .
            "</strong>
        </p>

        <a href='javascript:history.back()'>
            Regresar
        </a>
    ");
}


// ==========================================================
// CONVERTIR PERÍODO
// ==========================================================
//
// El formulario entrega:
//
// 2026-08
//
// La tabla utiliza DATE:
//
// 2026-08-01
//
// ==========================================================

$periodoBD = $periodo . '-01';


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

        die("
            <h2>Error</h2>

            <p>
                La fecha del campo
                <strong>" .
                htmlspecialchars($campo) .
                "</strong>
                no es válida.
            </p>

            <p>
                Valor recibido:
                <strong>" .
                htmlspecialchars($fecha) .
                "</strong>
            </p>

            <a href='javascript:history.back()'>
                Regresar
            </a>
        ");
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

    die("
        <h2>Error</h2>

        <p>
            Estado no válido:
            <strong>" .
            htmlspecialchars($estado) .
            "</strong>
        </p>

        <a href='javascript:history.back()'>
            Regresar
        </a>
    ");
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

        ':periodo' => $periodoBD

    ]);


    if ($stmtExiste->fetch(PDO::FETCH_ASSOC)) {

        die("
            <h2>Período existente</h2>

            <p>
                El período
                <strong>" .
                htmlspecialchars($periodo) .
                "</strong>
                ya existe.
            </p>

            <a href='javascript:history.back()'>
                Regresar
            </a>
        ");
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
    // REDIRECCIÓN
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
    // MOSTRAR ERROR
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
            Error al guardar el período
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
            <strong>Datos recibidos:</strong>
        </p>

        <pre>";

    print_r([

        'periodo'
            => $periodoBD,

        'fecha_inicio_cierre'
            => $fechaInicioCierre,

        'fecha_fin_cierre'
            => $fechaFinCierre,

        'fecha_facturacion'
            => $fechaFacturacion,

        'fecha_generacion_intereses'
            => $fechaGeneracionIntereses,

        'fecha_vencimiento'
            => $fechaVencimiento,

        'estado'
            => $estado,

        'observaciones'
            => $observaciones

    ]);

    echo "
        </pre>

        <br>

        <a href='javascript:history.back()'>
            ← Regresar
        </a>

    </div>";
}