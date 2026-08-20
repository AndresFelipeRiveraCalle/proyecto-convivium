<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// CONFIGURACIÓN
// ==========================================================

$tesseract = 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe';

$pdftoppm = 'C:\\poppler\\Library\\bin\\pdftoppm.exe';


// ==========================================================
// FUNCIÓN DE REDIRECCIÓN
// ==========================================================

function redireccionar($tipo, $mensaje)
{
    header(
        "Location: " . BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=" . urlencode($tipo) .
        "&mensaje=" . urlencode($mensaje)
    );

    exit;
}


// ==========================================================
// VALIDAR INSTALACIONES
// ==========================================================

if (!file_exists($tesseract)) {

    redireccionar(
        "error",
        "No se encontró Tesseract en: " . $tesseract
    );
}


if (!file_exists($pdftoppm)) {

    redireccionar(
        "error",
        "No se encontró pdftoppm en: " . $pdftoppm
    );
}


// ==========================================================
// VALIDAR ID DEL DOCUMENTO
// ==========================================================

$idDocumento = filter_input(
    INPUT_GET,
    'id_documento',
    FILTER_VALIDATE_INT
);


if (!$idDocumento) {

    redireccionar(
        "error",
        "Documento bancario no válido."
    );
}


try {

    // ======================================================
    // BUSCAR DOCUMENTO
    // ======================================================

    $sql = "
        SELECT
            db.id_documento,
            db.id_cuenta_bancaria,
            db.nombre_archivo,
            db.nombre_original,
            db.ruta_archivo,
            db.tipo_archivo,
            db.estado_procesamiento
        FROM documentos_bancarios db
        WHERE db.id_documento = :id_documento
        LIMIT 1
    ";


    $stmt = $conexion->prepare($sql);


    $stmt->execute([
        ':id_documento' => $idDocumento
    ]);


    $documento = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$documento) {

        throw new Exception(
            "El documento bancario no existe."
        );
    }


    // ======================================================
    // EVITAR PROCESAMIENTO SIMULTÁNEO
    // ======================================================

    if (
        $documento['estado_procesamiento']
        === 'PROCESANDO'
    ) {

        throw new Exception(
            "El documento ya se encuentra en proceso."
        );
    }


    // ======================================================
    // VERIFICAR RUTA
    // ======================================================

    $rutaArchivo = $documento['ruta_archivo'];


    if (empty($rutaArchivo)) {

        throw new Exception(
            "El documento no tiene una ruta de archivo registrada."
        );
    }


    // ======================================================
    // CONVERTIR RUTA RELATIVA EN ABSOLUTA
    // ======================================================

    if (
        !preg_match(
            '/^[A-Za-z]:[\\\\\/]/',
            $rutaArchivo
        )
    ) {

        $rutaArchivo =
            ROOT_PATH . "/" .
            ltrim(
                $rutaArchivo,
                "/\\"
            );
    }


    $rutaArchivo = str_replace(
        ['/', '\\'],
        DIRECTORY_SEPARATOR,
        $rutaArchivo
    );


    // ======================================================
    // VERIFICAR ARCHIVO FÍSICO
    // ======================================================

    if (!file_exists($rutaArchivo)) {

        throw new Exception(
            "No se encontró el archivo físico: " .
            $rutaArchivo
        );
    }


    // ======================================================
    // MARCAR DOCUMENTO COMO PROCESANDO
    // ======================================================

    $sqlEstado = "
        UPDATE documentos_bancarios
        SET
            estado_procesamiento = 'PROCESANDO',
            fecha_procesamiento = NULL,
            observaciones = NULL
        WHERE id_documento = :id_documento
    ";


    $stmtEstado =
        $conexion->prepare($sqlEstado);


    $stmtEstado->execute([
        ':id_documento' => $idDocumento
    ]);


    // ======================================================
    // DETERMINAR EXTENSIÓN
    // ======================================================

    $extension = strtolower(
        pathinfo(
            $documento['nombre_original'],
            PATHINFO_EXTENSION
        )
    );


    $textoExtraido = '';

    $metodoExtraccion = '';

    $archivosTemporales = [];


    // ======================================================
    // IMÁGENES
    // ======================================================

    if (
        in_array(
            $extension,
            ['jpg', 'jpeg', 'png'],
            true
        )
    ) {

        $metodoExtraccion = 'TESSERACT';


        // --------------------------------------------------
        // PREFIJO TEMPORAL
        // --------------------------------------------------

        $directorioTemporal =
            ROOT_PATH .
            "/uploads/documentos_bancarios/temp_ocr";


        if (
            !is_dir($directorioTemporal) &&
            !mkdir(
                $directorioTemporal,
                0755,
                true
            )
        ) {

            throw new Exception(
                "No fue posible crear la carpeta temporal de OCR."
            );
        }


        $nombreBase =
            $directorioTemporal .
            DIRECTORY_SEPARATOR .
            "ocr_" .
            $idDocumento .
            "_" .
            bin2hex(
                random_bytes(5)
            );


        $archivoSalida =
            $nombreBase .
            ".txt";


        // --------------------------------------------------
        // EJECUTAR TESSERACT
        // --------------------------------------------------

        $comando =
            '"' . $tesseract . '"' .
            ' "' . $rutaArchivo . '"' .
            ' "' . $nombreBase . '"' .
            ' -l spa';


        $salida = [];

        $codigoRetorno = 0;


        exec(
            $comando .
            " 2>&1",
            $salida,
            $codigoRetorno
        );


        if (
            $codigoRetorno !== 0
        ) {

            throw new Exception(
                "Tesseract no pudo procesar la imagen. " .
                implode(
                    " ",
                    $salida
                )
            );
        }


        if (
            !file_exists(
                $archivoSalida
            )
        ) {

            throw new Exception(
                "Tesseract terminó pero no generó el archivo de texto."
            );
        }


        $textoExtraido =
            file_get_contents(
                $archivoSalida
            );


        $archivosTemporales[] =
            $archivoSalida;
    }


    // ======================================================
    // PDF
    // ======================================================

    elseif ($extension === 'pdf') {

        $metodoExtraccion = 'TESSERACT';


        // --------------------------------------------------
        // CARPETA TEMPORAL
        // --------------------------------------------------

        $directorioTemporal =
            ROOT_PATH .
            "/uploads/documentos_bancarios/temp_ocr";


        if (
            !is_dir($directorioTemporal) &&
            !mkdir(
                $directorioTemporal,
                0755,
                true
            )
        ) {

            throw new Exception(
                "No fue posible crear la carpeta temporal de OCR."
            );
        }


        // --------------------------------------------------
        // NOMBRE BASE PARA LAS PÁGINAS
        // --------------------------------------------------

        $nombreBase =
            $directorioTemporal .
            DIRECTORY_SEPARATOR .
            "pdf_" .
            $idDocumento .
            "_" .
            bin2hex(
                random_bytes(5)
            );


        // --------------------------------------------------
        // CONVERTIR PDF → PNG
        // --------------------------------------------------

        $comandoPDF =
            '"' . $pdftoppm . '"' .
            ' -png' .
            ' -r 300' .
            ' "' . $rutaArchivo . '"' .
            ' "' . $nombreBase . '"';


        $salidaPDF = [];

        $codigoPDF = 0;


        exec(
            $comandoPDF .
            " 2>&1",
            $salidaPDF,
            $codigoPDF
        );


        if (
            $codigoPDF !== 0
        ) {

            throw new Exception(
                "No fue posible convertir el PDF a imágenes. " .
                implode(
                    " ",
                    $salidaPDF
                )
            );
        }


        // --------------------------------------------------
        // BUSCAR PÁGINAS GENERADAS
        // --------------------------------------------------

        $paginas = glob(
            $nombreBase . "-*.png"
        );


        if (
            empty($paginas)
        ) {

            throw new Exception(
                "Poppler no generó imágenes a partir del PDF."
            );
        }


        // --------------------------------------------------
        // ORDENAR PÁGINAS
        // --------------------------------------------------

        natsort($paginas);


        // --------------------------------------------------
        // PROCESAR CADA PÁGINA
        // --------------------------------------------------

        foreach ($paginas as $numero => $pagina) {


            $baseOCR =
                $directorioTemporal .
                DIRECTORY_SEPARATOR .
                "ocr_" .
                $idDocumento .
                "_" .
                bin2hex(
                    random_bytes(5)
                );


            $archivoTXT =
                $baseOCR .
                ".txt";


            // ----------------------------------------------
            // TESSERACT
            // ----------------------------------------------

            $comandoOCR =
                '"' . $tesseract . '"' .
                ' "' . $pagina . '"' .
                ' "' . $baseOCR . '"' .
                ' -l spa';


            $salidaOCR = [];

            $codigoOCR = 0;


            exec(
                $comandoOCR .
                " 2>&1",
                $salidaOCR,
                $codigoOCR
            );


            if (
                $codigoOCR !== 0
            ) {

                throw new Exception(
                    "Tesseract no pudo procesar " .
                    basename($pagina) .
                    ". " .
                    implode(
                        " ",
                        $salidaOCR
                    )
                );
            }


            if (
                file_exists(
                    $archivoTXT
                )
            ) {

                $textoPagina =
                    file_get_contents(
                        $archivoTXT
                    );


                $textoExtraido .=
                    "\n\n" .
                    "===== PÁGINA " .
                    ($numero + 1) .
                    " =====\n\n" .
                    $textoPagina;


                $archivosTemporales[] =
                    $archivoTXT;
            }


            // ----------------------------------------------
            // GUARDAR PNG PARA ELIMINARLO DESPUÉS
            // ----------------------------------------------

            $archivosTemporales[] =
                $pagina;
        }
    }


    // ======================================================
    // VALIDAR TEXTO
    // ======================================================

    $textoExtraido =
        trim(
            $textoExtraido
        );


    if (
        $textoExtraido === ''
    ) {

        throw new Exception(
            "No fue posible extraer texto del documento."
        );
    }


    // ======================================================
    // GUARDAR OCR TEMPORAL PARA PRUEBA
    // ======================================================

    $archivoPruebaOCR =
        ROOT_PATH .
        "/uploads/documentos_bancarios/" .
        "ocr_prueba_" .
        $idDocumento .
        ".txt";


    file_put_contents(
        $archivoPruebaOCR,
        $textoExtraido
    );


    // ======================================================
    // ACTUALIZAR DOCUMENTO
    // ======================================================

    $sqlActualizar = "
        UPDATE documentos_bancarios
        SET
            metodo_extraccion = :metodo,
            estado_procesamiento = 'PROCESADO',
            fecha_procesamiento = NOW(),
            observaciones = NULL
        WHERE id_documento = :id_documento
    ";


    $stmtActualizar =
        $conexion->prepare(
            $sqlActualizar
        );


    $stmtActualizar->execute([
        ':metodo' =>
            $metodoExtraccion,

        ':id_documento' =>
            $idDocumento
    ]);


    // ======================================================
    // ELIMINAR ARCHIVOS TEMPORALES
    // ======================================================

    foreach (
        $archivosTemporales
        as $archivoTemporal
    ) {

        if (
            file_exists(
                $archivoTemporal
            )
        ) {

            @unlink(
                $archivoTemporal
            );
        }
    }


    // ======================================================
    // ÉXITO
    // ======================================================

    redireccionar(
        "success",
        "Documento procesado correctamente. " .
        "OCR realizado con Tesseract."
    );


} catch (Throwable $e) {


    // ======================================================
    // ELIMINAR TEMPORALES SI EXISTEN
    // ======================================================

    if (
        !empty($archivosTemporales)
    ) {

        foreach (
            $archivosTemporales
            as $archivoTemporal
        ) {

            if (
                file_exists(
                    $archivoTemporal
                )
            ) {

                @unlink(
                    $archivoTemporal
                );
            }
        }
    }


    // ======================================================
    // GUARDAR ERROR EN BASE DE DATOS
    // ======================================================

    try {

        $sqlError = "
            UPDATE documentos_bancarios
            SET
                estado_procesamiento = 'ERROR',
                fecha_procesamiento = NOW(),
                observaciones = :observaciones
            WHERE id_documento = :id_documento
        ";


        $stmtError =
            $conexion->prepare(
                $sqlError
            );


        $stmtError->execute([

            ':observaciones' =>
                mb_substr(
                    $e->getMessage(),
                    0,
                    255
                ),

            ':id_documento' =>
                $idDocumento
        ]);

    } catch (Throwable $errorBD) {

        // No hacemos nada adicional.
    }


    // ======================================================
    // REDIRECCIÓN ERROR
    // ======================================================

    redireccionar(
        "error",
        $e->getMessage()
    );
}