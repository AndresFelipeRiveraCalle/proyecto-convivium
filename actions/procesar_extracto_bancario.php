<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";
require_once ROOT_PATH . "/vendor/autoload.php";


// ==========================================================
// RECIBIR DOCUMENTO
// ==========================================================

$idDocumento = filter_input(
    INPUT_GET,
    "id_documento",
    FILTER_VALIDATE_INT
);

if (!$idDocumento) {

    header(
        "Location: " . BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=error&mensaje=" .
        urlencode("Documento no válido.")
    );

    exit;
}


// ==========================================================
// BUSCAR DOCUMENTO
// ==========================================================

$sql = "
    SELECT
        id_documento,
        nombre_original,
        nombre_archivo,
        ruta_archivo,
        tipo_archivo,
        metodo_extraccion,
        estado_procesamiento
    FROM documentos_bancarios
    WHERE id_documento = :id_documento
    LIMIT 1
";

$stmt = $conexion->prepare($sql);

$stmt->execute([
    ":id_documento" => $idDocumento
]);

$documento = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$documento) {

    header(
        "Location: " . BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=error&mensaje=" .
        urlencode("El documento no existe.")
    );

    exit;
}


// ==========================================================
// VALIDAR ESTADO
// ==========================================================

if (
    $documento["estado_procesamiento"] === "PROCESANDO"
) {

    header(
        "Location: " . BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=error&mensaje=" .
        urlencode(
            "El documento ya se encuentra en procesamiento."
        )
    );

    exit;
}


// ==========================================================
// RUTA FÍSICA
// ==========================================================

$rutaArchivo =
    ROOT_PATH . "/" .
    $documento["ruta_archivo"];


if (!file_exists($rutaArchivo)) {

    $sqlError = "
        UPDATE documentos_bancarios
        SET
            estado_procesamiento = 'ERROR',
            fecha_procesamiento = NOW(),
            observaciones = :observaciones
        WHERE id_documento = :id_documento
    ";

    $stmtError = $conexion->prepare($sqlError);

    $stmtError->execute([
        ":observaciones" =>
            "No se encontró físicamente el archivo.",
        ":id_documento" =>
            $idDocumento
    ]);


    header(
        "Location: " . BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=error&mensaje=" .
        urlencode(
            "No se encontró físicamente el archivo."
        )
    );

    exit;
}


// ==========================================================
// MARCAR COMO PROCESANDO
// ==========================================================

$sqlProcesando = "
    UPDATE documentos_bancarios
    SET
        estado_procesamiento = 'PROCESANDO',
        fecha_procesamiento = NOW()
    WHERE id_documento = :id_documento
";

$stmtProcesando = $conexion->prepare(
    $sqlProcesando
);

$stmtProcesando->execute([
    ":id_documento" => $idDocumento
]);


// ==========================================================
// PROCESAR PDF
// ==========================================================

try {

    $parser = new \Smalot\PdfParser\Parser();


    // ======================================================
    // INTENTAR LEER PDF
    // ======================================================

    try {

        $pdf = $parser->parseFile(
            $rutaArchivo
        );

    } catch (Throwable $e) {

        $mensajeError = $e->getMessage();


        // ==================================================
        // PDF PROTEGIDO
        // ==================================================

        if (
            stripos(
                $mensajeError,
                "Secured pdf file"
            ) !== false
        ) {

            throw new Exception(
                "El PDF está protegido con contraseña " .
                "o tiene restricciones de seguridad. " .
                "Debe cargarse una versión sin protección."
            );
        }


        // ==================================================
        // OTRO ERROR DEL PDF
        // ==================================================

        throw $e;
    }


    // ======================================================
    // EXTRAER TEXTO
    // ======================================================

    $textoExtraido = $pdf->getText();


    $textoExtraido = trim(
        preg_replace(
            '/[ \t]+/',
            ' ',
            $textoExtraido
        )
    );


    // ======================================================
    // VALIDAR TEXTO
    // ======================================================

    if ($textoExtraido === "") {

        throw new Exception(
            "El PDF no contiene texto extraíble. " .
            "Probablemente sea un documento escaneado. " .
            "Este documento deberá procesarse mediante OCR."
        );
    }


    // ======================================================
    // GUARDAR TEXTO
    // ======================================================

    $sqlActualizar = "
        UPDATE documentos_bancarios
        SET
            texto_extraido = :texto_extraido,
            estado_procesamiento = 'PROCESADO',
            metodo_extraccion = 'TEXTO',
            fecha_procesamiento = NOW(),
            observaciones = NULL
        WHERE id_documento = :id_documento
    ";

    $stmtActualizar = $conexion->prepare(
        $sqlActualizar
    );

    $stmtActualizar->execute([

        ":texto_extraido" =>
            $textoExtraido,

        ":id_documento" =>
            $idDocumento

    ]);


    // ======================================================
    // RESULTADO
    // ======================================================

    header(
        "Location: " . BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=success&mensaje=" .
        urlencode(
            "Documento procesado correctamente. " .
            "Se extrajo el texto del PDF."
        )
    );

    exit;


} catch (Throwable $e) {


    // ======================================================
    // MENSAJE DE ERROR
    // ======================================================

    $observacion = substr(
        $e->getMessage(),
        0,
        255
    );


    // ======================================================
    // MARCAR ERROR
    // ======================================================

    $sqlError = "
        UPDATE documentos_bancarios
        SET
            estado_procesamiento = 'ERROR',
            fecha_procesamiento = NOW(),
            observaciones = :observaciones
        WHERE id_documento = :id_documento
    ";

    $stmtError = $conexion->prepare(
        $sqlError
    );

    $stmtError->execute([

        ":observaciones" =>
            $observacion,

        ":id_documento" =>
            $idDocumento

    ]);


    // ======================================================
    // REDIRECCIÓN
    // ======================================================

    header(
        "Location: " . BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=error&mensaje=" .
        urlencode(
            "No fue posible procesar el documento: " .
            $observacion
        )
    );

    exit;
}