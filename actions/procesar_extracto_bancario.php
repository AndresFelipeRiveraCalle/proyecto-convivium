<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

// ==========================================================
// CONFIGURACIÓN EXTERNA
// ==========================================================

$tesseract = 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe';
$pdftoppm  = 'C:\\poppler\\Library\\bin\\pdftoppm.exe';

// ==========================================================
// VALIDAR INSTALACIONES
// ==========================================================

if (!file_exists($tesseract)) {
    die("No se encontró Tesseract en: " . $tesseract);
}

if (!file_exists($pdftoppm)) {
    die("No se encontró pdftoppm en: " . $pdftoppm);
}


// ==========================================================
// VALIDAR ID
// ==========================================================

$idDocumento = filter_input( INPUT_GET, 'id_documento',FILTER_VALIDATE_INT);

if (!$idDocumento) {
    header(
        "Location: " . BASE_URL .
            "configuracion/extractos_bancarios.php" .
            "?tipo=error&mensaje=" .
            urlencode("Documento bancario no válido.")
    );

    exit;
}


// ==========================================================
// FUNCIONES
// ==========================================================

function ejecutarComando($comando, &$salida = null, &$codigo = null)
{
    $salida = [];
    $codigo = 0;

    exec($comando . " 2>&1", $salida, $codigo);

    return implode("\n", $salida);
}

/*------------------------------------------------------------------*/
// ==========================================================
// PARSER DE MOVIMIENTOS BANCARIOS
// ==========================================================

function detectarMovimientosBancarios(
    string $texto,
    int $anio = 2026
): array {

    $movimientos = [];

    // Normalizar saltos de línea
    $texto = str_replace(
        ["\r\n", "\r"],
        "\n",
        $texto
    );

    $lineas = explode("\n", $texto);

    foreach ($lineas as $linea) {

        $linea = trim($linea);

        if ($linea === '') {
            continue;
        }

        /*
         * Formato esperado:
         *
         * 22 01 $ 9,000.00- 1943 Compra A Comercio...
         *
         * También:
         *
         * 30 01 $ 100,000.00+ 1055 Abono...
         */

        $patron = '/^
            (\d{1,2})              # día
            \s+
            (\d{1,2})              # mes
            \s+
            \$?\s*
            ([\d.,]+)              # valor
            \s*
            ([+-])                 # signo
            \s+
            (\d{3,10})             # documento
            \s+
            (.+)                   # descripción
        $/x';

        if (
            !preg_match(
                $patron,
                $linea,
                $coincidencias
            )
        ) {
            continue;
        }

        $dia = (int) $coincidencias[1];
        $mes = (int) $coincidencias[2];

        $valorTexto = $coincidencias[3];

        $signo = $coincidencias[4];

        $numeroDocumento =
            trim($coincidencias[5]);

        $descripcion =
            trim($coincidencias[6]);


        // ==================================================
        // VALIDAR FECHA
        // ==================================================

        if (
            !checkdate(
                $mes,
                $dia,
                $anio
            )
        ) {
            continue;
        }


        // ==================================================
        // CONVERTIR VALOR
        // ==================================================

        $valorTexto =
            str_replace(
                ',',
                '',
                $valorTexto
            );

        $valorTexto =
            str_replace(
                '$',
                '',
                $valorTexto
            );

        $valor = (float) $valorTexto;


        if ($valor <= 0) {
            continue;
        }


        // ==================================================
        // TIPO DE MOVIMIENTO
        // ==================================================

        $tipoMovimiento =
            $signo === '+'
            ? 'INGRESO'
            : 'EGRESO';


        // ==================================================
        // FECHA
        // ==================================================

        $fechaMovimiento =
            sprintf(
                '%04d-%02d-%02d',
                $anio,
                $mes,
                $dia
            );


        // ==================================================
        // GUARDAR MOVIMIENTO DETECTADO
        // ==================================================

        $movimientos[] = [

            'fecha_movimiento' =>
            $fechaMovimiento,

            'fecha_valor' =>
            $fechaMovimiento,

            'numero_documento' =>
            $numeroDocumento,

            'valor' =>
            $valor,

            'tipo_movimiento' =>
            $tipoMovimiento,

            'descripcion' =>
            $descripcion,

            'referencia' =>
            $numeroDocumento

        ];
    }


    return $movimientos;
}

// ==========================================================
// PROCESAMIENTO
// ==========================================================

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

    $documento = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$documento) {

        throw new Exception(
            "El documento bancario no existe."
        );
    }


    // ======================================================
    // EVITAR PROCESAMIENTO SIMULTÁNEO
    // ======================================================

    if (
        $documento['estado_procesamiento'] === 'PROCESANDO'
    ) {

        throw new Exception(
            "El documento ya se encuentra en proceso."
        );
    }


    // ======================================================
    // RUTA DEL ARCHIVO
    // ======================================================

    $rutaArchivo = $documento['ruta_archivo'];

    if (empty($rutaArchivo)) {

        throw new Exception(
            "El documento no tiene una ruta registrada."
        );
    }


    if (
        !preg_match(
            '/^[A-Za-z]:[\\\\\/]/',
            $rutaArchivo
        )
    ) {

        $rutaArchivo =
            ROOT_PATH . "/" .
            ltrim($rutaArchivo, "/\\");
    }


    $rutaArchivo = str_replace(
        ['/', '\\'],
        DIRECTORY_SEPARATOR,
        $rutaArchivo
    );


    if (!file_exists($rutaArchivo)) {

        throw new Exception(
            "No se encontró el archivo físico: " .
                $rutaArchivo
        );
    }


    // ======================================================
    // MARCAR COMO PROCESANDO
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
    // EXTENSIÓN
    // ======================================================

    $extension = strtolower(
        pathinfo(
            $documento['nombre_original'],
            PATHINFO_EXTENSION
        )
    );


    $textoExtraido = '';
    $metodoExtraccion = '';


    // ======================================================
    // IMAGEN
    // ======================================================

    if (
        in_array(
            $extension,
            ['jpg', 'jpeg', 'png'],
            true
        )
    ) {

        $archivoSalida =
            sys_get_temp_dir() .
            DIRECTORY_SEPARATOR .
            'ocr_' .
            $idDocumento;

        $comando =
            '"' . $tesseract . '"' .
            ' "' . $rutaArchivo . '"' .
            ' "' . $archivoSalida . '"' .
            ' --psm 6';

        ejecutarComando(
            $comando,
            $salida,
            $codigo
        );


        if ($codigo !== 0) {

            throw new Exception(
                "Tesseract no pudo procesar la imagen."
            );
        }


        $archivoTexto =
            $archivoSalida . '.txt';


        if (!file_exists($archivoTexto)) {

            throw new Exception(
                "Tesseract no generó el archivo de texto."
            );
        }


        $textoExtraido =
            file_get_contents(
                $archivoTexto
            );


        @unlink($archivoTexto);

        $metodoExtraccion = 'TESSERACT';
    }


    // ======================================================
    // PDF
    // ======================================================

    elseif ($extension === 'pdf') {

        // ==================================================
        // BUSCAR PDftotext
        // ==================================================

        $pdftotext =
            'C:\\poppler\\Library\\bin\\pdftotext.exe';


        // ==================================================
        // INTENTAR TEXTO DIRECTO
        // ==================================================

        if (file_exists($pdftotext)) {

            $archivoTexto =
                sys_get_temp_dir() .
                DIRECTORY_SEPARATOR .
                'pdftext_' .
                $idDocumento .
                '.txt';


            $comando =
                '"' . $pdftotext . '"' .
                ' -layout "' .
                $rutaArchivo .
                '" "' .
                $archivoTexto .
                '"';


            ejecutarComando(
                $comando,
                $salida,
                $codigo
            );


            if (
                file_exists($archivoTexto)
            ) {

                $textoDirecto =
                    file_get_contents(
                        $archivoTexto
                    );

                @unlink($archivoTexto);

                if (
                    trim($textoDirecto) !== ''
                ) {

                    $textoExtraido =
                        $textoDirecto;

                    $metodoExtraccion =
                        'PDF_TEXTO';
                }
            }
        }


        // ==================================================
        // SI NO HAY TEXTO -> OCR
        // ==================================================

        if (
            trim($textoExtraido) === ''
        ) {

            $metodoExtraccion =
                'TESSERACT';

            // ==============================================
            // DIRECTORIO TEMPORAL
            // ==============================================

            $directorioTemp =
                sys_get_temp_dir() .
                DIRECTORY_SEPARATOR .
                'extracto_' .
                $idDocumento .
                '_' .
                uniqid();


            if (
                !mkdir(
                    $directorioTemp,
                    0777,
                    true
                )
            ) {

                throw new Exception(
                    "No fue posible crear el directorio temporal."
                );
            }


            // ==============================================
            // PREFIJO DE IMÁGENES
            // ==============================================

            $prefijo =
                $directorioTemp .
                DIRECTORY_SEPARATOR .
                'pagina';


            // ==============================================
            // PDF -> PNG
            // ==============================================

            $comando =
                '"' . $pdftoppm . '"' .
                ' -png -r 300 "' .
                $rutaArchivo .
                '" "' .
                $prefijo .
                '"';


            ejecutarComando(
                $comando,
                $salida,
                $codigo
            );


            if ($codigo !== 0) {

                throw new Exception(
                    "No fue posible convertir el PDF a imágenes. " .
                        implode(" ", $salida)
                );
            }


            // ==============================================
            // BUSCAR PÁGINAS
            // ==============================================

            $imagenes =
                glob(
                    $prefijo . '-*.png'
                );


            if (
                empty($imagenes)
            ) {

                throw new Exception(
                    "pdftoppm no generó imágenes del PDF."
                );
            }


            natsort($imagenes);


            // ==============================================
            // OCR DE CADA PÁGINA
            // ==============================================

            $textoOCR = '';


            foreach ($imagenes as $numero => $imagen) {

                $salidaOCR =
                    $directorioTemp .
                    DIRECTORY_SEPARATOR .
                    'ocr_' .
                    ($numero + 1);


                $comando =
                    '"' . $tesseract . '"' .
                    ' "' .
                    $imagen .
                    '" "' .
                    $salidaOCR .
                    '" --psm 6';


                ejecutarComando(
                    $comando,
                    $salidaTesseract,
                    $codigoTesseract
                );


                $archivoOCR =
                    $salidaOCR . '.txt';


                if (
                    $codigoTesseract === 0 &&
                    file_exists($archivoOCR)
                ) {

                    $contenido =
                        file_get_contents(
                            $archivoOCR
                        );


                    $textoOCR .=
                        "\n\n===== PÁGINA " .
                        ($numero + 1) .
                        " =====\n\n" .
                        $contenido;


                    @unlink($archivoOCR);
                }


                @unlink($imagen);
            }


            // ==============================================
            // ELIMINAR TEMPORAL
            // ==============================================

            @rmdir($directorioTemp);


            $textoExtraido =
                trim($textoOCR);
        }
    }


    // ======================================================
    // FORMATO NO SOPORTADO
    // ======================================================

    else {

        throw new Exception(
            "Formato no soportado: ." .
                $extension
        );
    }


    // ======================================================
    // VALIDAR OCR
    // ======================================================

    if (
        trim($textoExtraido) === ''
    ) {

        throw new Exception(
            "No fue posible extraer texto del documento."
        );
    }

    // ==========================================================
    // DETECTAR AÑO DEL EXTRACTO
    // ==========================================================

    $anioExtracto = null;

    if (
        preg_match(
            '/\b(20\d{2})\b/',
            $textoExtraido,
            $coincidenciaAnio
        )
    ) {

        $anioExtracto =
            (int) $coincidenciaAnio[1];
    }


    // ==========================================================
    // SI NO SE ENCUENTRA AÑO
    // ==========================================================

    if (!$anioExtracto) {

        $anioExtracto =
            (int) date('Y');
    }


    // ==========================================================
    // PARSEAR MOVIMIENTOS
    // ==========================================================

    $movimientosDetectados =
        detectarMovimientosBancarios(
            $textoExtraido,
            $anioExtracto
        );


    // ==========================================================
    // VALIDAR RESULTADO
    // ==========================================================

    if (
        empty($movimientosDetectados)
    ) {

        throw new Exception(
            "El OCR fue realizado correctamente, " .
                "pero no se encontraron movimientos bancarios " .
                "con el formato esperado."
        );
    }


    // ======================================================
    // GUARDAR TEXTO EN ARCHIVO TEMPORAL
    // ======================================================

    /*
     * Por ahora guardamos el OCR en un archivo temporal
     * para poder revisar exactamente qué está leyendo
     * Tesseract antes de construir el parser definitivo.
     */

    $archivoDebug =
        ROOT_PATH .
        DIRECTORY_SEPARATOR .
        'uploads' .
        DIRECTORY_SEPARATOR .
        'documentos_bancarios' .
        DIRECTORY_SEPARATOR .
        'ocr_debug_' .
        $idDocumento .
        '.txt';


    file_put_contents(
        $archivoDebug,
        $textoExtraido
    );


    // ======================================================
    // MOSTRAR RESULTADO TEMPORAL
    // ======================================================

    /*
     * TODAVÍA NO marcamos el documento como PROCESADO.
     *
     * Primero vamos a comprobar que el OCR está llegando
     * correctamente y después conectaremos el parser de
     * movimientos.
     */

    $sqlActualizar = "
        UPDATE documentos_bancarios
        SET
            metodo_extraccion = :metodo,
            estado_procesamiento = 'PROCESANDO',
            observaciones = :observaciones
        WHERE id_documento = :id_documento
    ";


    $stmtActualizar =
        $conexion->prepare(
            $sqlActualizar
        );


    $stmtActualizar->execute([

        ':metodo' =>
        $metodoExtraccion,

        ':observaciones' =>
        'OCR realizado correctamente. Pendiente de interpretar movimientos.',

        ':id_documento' =>
        $idDocumento

    ]);


    // ======================================================
    // MOSTRAR RESULTADO DEL PARSER
    // ======================================================

    echo '<!DOCTYPE html>';
    echo '<html lang="es">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Confirmar movimientos bancarios</title>';

    // ======================================================
    // CSS
    // ======================================================

    echo '<link rel="stylesheet" href="' . BASE_URL . 'assets/css/style.css">';

    echo '</head>';
    echo '<body>';

    echo '<div class="contenido">';


    // ======================================================
    // TÍTULO
    // ======================================================

    echo '<h2>Confirmar movimientos bancarios</h2>';


    // ======================================================
    // MENSAJE
    // ======================================================

    echo '<div class="alert alert-success">';

    echo '<strong>OCR realizado correctamente.</strong><br>';

    echo 'Método: ' .
        htmlspecialchars($metodoExtraccion);

    echo '<br>';

    echo 'Movimientos detectados: ' .
        count($movimientosDetectados);

    echo '</div>';


    // ======================================================
    // FORMULARIO
    // ======================================================

    echo '<form method="POST" action="' .
        BASE_URL .
        'actions/guardar_movimientos_extracto.php">';


    // ======================================================
    // ID DOCUMENTO
    // ======================================================

    echo '<input
        type="hidden"
        name="id_documento"
        value="' . $idDocumento . '"
    >';


    // ======================================================
    // ID CUENTA
    // ======================================================

    echo '<input
        type="hidden"
        name="id_cuenta_bancaria"
        value="' .
        (int)$documento['id_cuenta_bancaria'] .
        '"
    >';


    // ======================================================
    // TABLA
    // ======================================================

    echo '<div class="tabla-responsive">';

    echo '<table class="tabla">';

    echo '<thead>';

    echo '<tr>';

    echo '<th>#</th>';
    echo '<th>Fecha</th>';
    echo '<th>Documento</th>';
    echo '<th>Descripción</th>';
    echo '<th>Valor</th>';
    echo '<th>Tipo</th>';

    echo '</tr>';

    echo '</thead>';

    echo '<tbody>';


    // ======================================================
    // MOVIMIENTOS
    // ======================================================

    foreach (
        $movimientosDetectados
        as $indice => $movimiento
    ) {

        echo '<tr>';

        // --------------------------------------------------
        // NÚMERO
        // --------------------------------------------------

        echo '<td>';

        echo ($indice + 1);

        echo '</td>';


        // --------------------------------------------------
        // FECHA
        // --------------------------------------------------

        echo '<td>';

        echo '<input
            type="date"
            name="movimientos[' . $indice . '][fecha_movimiento]"
            value="' .
            htmlspecialchars(
                $movimiento['fecha_movimiento']
            ) .
            '"
            required
        >';

        echo '</td>';


        // --------------------------------------------------
        // DOCUMENTO
        // --------------------------------------------------

        echo '<td>';

        echo '<input
            type="text"
            name="movimientos[' . $indice . '][numero_documento]"
            value="' .
            htmlspecialchars(
                $movimiento['numero_documento']
            ) .
            '"
        >';

        echo '</td>';


        // --------------------------------------------------
        // DESCRIPCIÓN
        // --------------------------------------------------

        echo '<td>';

        echo '<input
            type="text"
            name="movimientos[' . $indice . '][descripcion]"
            value="' .
            htmlspecialchars(
                $movimiento['descripcion']
            ) .
            '"
            maxlength="255"
            required
        >';

        echo '</td>';


        // --------------------------------------------------
        // VALOR
        // --------------------------------------------------

        echo '<td>';

        echo '<input
            type="number"
            step="0.01"
            min="0"
            name="movimientos[' . $indice . '][valor]"
            value="' .
            number_format(
                $movimiento['valor'],
                2,
                '.',
                ''
            ) .
            '"
            required
        >';

        echo '</td>';


        // --------------------------------------------------
        // TIPO
        // --------------------------------------------------

        echo '<td>';

        echo '<select
            name="movimientos[' . $indice . '][tipo_movimiento]"
            required
        >';

        echo '<option value="INGRESO" ' .
            (
                $movimiento['tipo_movimiento'] === 'INGRESO'
                ? 'selected'
                : ''
            ) .
            '>INGRESO</option>';

        echo '<option value="EGRESO" ' .
            (
                $movimiento['tipo_movimiento'] === 'EGRESO'
                ? 'selected'
                : ''
            ) .
            '>EGRESO</option>';

        echo '</select>';

        echo '</td>';

        echo '</tr>';
    }

    echo '</tbody>';

    echo '</table>';

    echo '</div>';


    // ======================================================
    // BOTONES
    // ======================================================

    echo '<div class="acciones-formulario">';

    echo '<a href="' . BASE_URL . 'configuracion/extractos_bancarios.php" class="btn-secondary">
        Cancelar
    </a>';

    echo '<button type="submit" class="btn-primary">
        Confirmar y guardar movimientos
        </button>';

    echo '</div>';

    echo '</form>';


    // ======================================================
    // OCR COMPLETO
    // ======================================================

    echo '<details class="ocr">';

    echo '<summary>';

    echo 'Ver texto OCR completo';

    echo '</summary>';

    echo '<pre>';

    echo htmlspecialchars(
        $textoExtraido
    );

    echo '</pre>';

    echo '</details>';

    echo '</div>';

    echo '</body>';

    echo '</html>';


    exit;
} catch (Throwable $e) {

    // ======================================================
    // GUARDAR ERROR
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


    header(
        "Location: " . BASE_URL .
            "configuracion/extractos_bancarios.php" .
            "?tipo=error&mensaje=" .
            urlencode(
                $e->getMessage()
            )
    );

    exit;
}
