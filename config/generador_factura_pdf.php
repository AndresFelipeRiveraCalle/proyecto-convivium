<?php

require_once ROOT_PATH . "/config/conexion.php";
require_once ROOT_PATH . "/vendor/autoload.php";

use Dompdf\Dompdf;
use Dompdf\Options;


// ==========================================================
// GENERAR PDF DE FACTURA
// Devuelve el PDF como contenido binario.
// ==========================================================

function generarPdfFactura(
    int $idFactura
): string {

    global $conexion;
    if ($idFactura <= 0) {

        throw new RuntimeException(
            'Debe indicar una factura válida.'
        );
    }


    // ======================================================
    // CONSERVAR GET ORIGINAL
    // ======================================================

    $getOriginal =
        $_GET;


    $_GET['id'] =
        $idFactura;


    // ======================================================
    // GENERAR HTML
    // Reutiliza factura_detalle.php.
    // ======================================================

    ob_start();


    require ROOT_PATH .
        "/configuracion/factura_detalle.php";


    $htmlCompleto =
        ob_get_clean();


    $_GET =
        $getOriginal;


    if (
        $htmlCompleto === false
        ||
        trim($htmlCompleto) === ''
    ) {

        throw new RuntimeException(
            'No fue posible generar el contenido de la factura.'
        );
    }


    // ======================================================
    // EXTRAER DOCUMENTO
    // ======================================================

    libxml_use_internal_errors(
        true
    );


    $dom =
        new DOMDocument(
            '1.0',
            'UTF-8'
        );


    $dom->loadHTML(
        '<?xml encoding="UTF-8">' .
        $htmlCompleto,
        LIBXML_HTML_NOIMPLIED |
        LIBXML_HTML_NODEFDTD
    );


    libxml_clear_errors();


    $xpath =
        new DOMXPath(
            $dom
        );


    $nodos =
        $xpath->query(
            "//*[contains(
                concat(
                    ' ',
                    normalize-space(@class),
                    ' '
                ),
                ' factura-documento '
            )]"
        );


    if (
        $nodos === false
        ||
        $nodos->length === 0
    ) {

        throw new RuntimeException(
            'No fue posible localizar el documento de la factura.'
        );
    }


    $nodoFactura =
        $nodos->item(0);


    // ======================================================
    // CONVERTIR IMÁGENES
    // Incluye el logo directamente dentro del PDF.
    // ======================================================

    $imagenes =
        $xpath->query(
            ".//img",
            $nodoFactura
        );


    if ($imagenes !== false) {

        foreach (
            $imagenes
            as $imagen
        ) {

            if (
                !($imagen instanceof DOMElement)
            ) {
                continue;
            }


            $src =
                trim(
                    $imagen->getAttribute(
                        'src'
                    )
                );


            if ($src === '') {
                continue;
            }


            if (
                strpos(
                    $src,
                    BASE_URL
                ) !== 0
            ) {
                continue;
            }


            $rutaRelativa =
                substr(
                    $src,
                    strlen(BASE_URL)
                );


            $rutaFisica =
                ROOT_PATH .
                DIRECTORY_SEPARATOR .
                str_replace(
                    '/',
                    DIRECTORY_SEPARATOR,
                    $rutaRelativa
                );


            if (
                !is_file(
                    $rutaFisica
                )
            ) {
                continue;
            }


            $extension =
                strtolower(
                    pathinfo(
                        $rutaFisica,
                        PATHINFO_EXTENSION
                    )
                );


            $mime = null;


            switch ($extension) {

                case 'png':

                    $mime =
                        'image/png';

                    break;


                case 'jpg':

                case 'jpeg':

                    $mime =
                        'image/jpeg';

                    break;


                case 'gif':

                    $mime =
                        'image/gif';

                    break;


                case 'webp':

                    $mime =
                        'image/webp';

                    break;
            }


            if ($mime === null) {
                continue;
            }


            $contenidoImagen =
                file_get_contents(
                    $rutaFisica
                );


            if (
                $contenidoImagen === false
            ) {
                continue;
            }


            $imagen->setAttribute(
                'src',
                'data:' .
                $mime .
                ';base64,' .
                base64_encode(
                    $contenidoImagen
                )
            );
        }
    }


    // ======================================================
    // HTML DE LA FACTURA
    // ======================================================

    $htmlFactura =
        $dom->saveHTML(
            $nodoFactura
        );


    if (
        $htmlFactura === false
        ||
        trim($htmlFactura) === ''
    ) {

        throw new RuntimeException(
            'No fue posible preparar la factura para PDF.'
        );
    }


    // ======================================================
    // ESTILOS PDF
    // Usa la versión que ya dejamos aprobada.
    // ======================================================

    $cssPdf = '

        @page {

            size:
                A4 portrait;

            margin:
                5mm;
        }


        * {

            box-sizing:
                border-box;
        }


        html,
        body {

            margin:
                0;

            padding:
                0;

            font-family:
                DejaVu Sans,
                sans-serif;

            font-size:
                7.5pt;

            color:
                #132238;

            background:
                #ffffff;
        }


        .factura-documento {

            width:
                194mm;

            margin:
                4mm auto 0 auto;

            padding:
                2.5mm;

            box-sizing:
                border-box;

            background:
                #ffffff;

            border:
                0.35mm solid
                #b8c7d9;

            border-radius:
                2mm;
        }


        /* ==================================================
           CABECERA
        ================================================== */

        .factura-cabecera {

            width:
                100%;

            display:
                table;

            table-layout:
                fixed;

            margin-bottom:
                3mm;
        }


        .factura-logo,
        .factura-empresa,
        .factura-periodo {

            display:
                table-cell;

            vertical-align:
                middle;
        }


        .factura-logo {

            width:
                20%;

            text-align:
                center;
        }


        .factura-empresa {

            width:
                55%;

            text-align:
                center;
        }


        .factura-periodo {

            width:
                25%;

            text-align:
                center;

            background:
                #eaf4fb;

            padding:
                3mm;

            border-radius:
                4px;
        }


        .factura-logo img {

            max-width:
                25mm;

            max-height:
                18mm;
        }


        .factura-logo-placeholder {

            font-size:
                15pt;

            font-weight:
                bold;
        }


        .factura-empresa h2 {

            margin:
                0 0 1mm 0;

            font-size:
                12pt;
        }


        .factura-empresa p {

            margin:
                0.4mm 0;

            font-size:
                6.5pt;
        }


        .factura-periodo small {

            display:
                block;

            font-size:
                5.5pt;

            font-weight:
                bold;

            margin-bottom:
                1mm;
        }


        .factura-periodo strong {

            display:
                block;

            font-size:
                10pt;
        }


        /* ==================================================
           DATOS PRINCIPALES
        ================================================== */

        .factura-meta {

            display:
                table;

            width:
                100%;

            max-width:
                100%;

            table-layout:
                fixed;

            border:
                1px solid #cfddeb;

            margin-top:
                2mm;
        }


        .factura-meta-item {

            display:
                table-cell;

            width:
                25%;

            padding:
                2mm;

            text-align:
                center;

            border-right:
                1px solid #cfddeb;
        }


        .factura-meta-item:last-child {

            border-right:
                none;
        }


        .factura-meta-item span {

            display:
                block;

            font-size:
                5.5pt;

            color:
                #64748b;

            margin-bottom:
                0.5mm;
        }


        .factura-meta-item strong {

            font-size:
                7.5pt;
        }


        /* ==================================================
           CLIENTE / UNIDAD
        ================================================== */

        .factura-cliente {

            display:
                table;

            width:
                100%;

            max-width:
                100%;

            table-layout:
                fixed;

            border:
                1px solid #cfddeb;

            margin-top:
                2mm;
        }


        .factura-cliente-bloque {

            display:
                table-cell;

            width:
                50%;

            vertical-align:
                top;

            padding:
                2.5mm;
        }


        .factura-cliente-bloque
        +
        .factura-cliente-bloque {

            border-left:
                1px solid #cfddeb;
        }


        .factura-titulo-campo {

            font-size:
                5.5pt;

            color:
                #64748b;

            margin-bottom:
                0.5mm;
        }


        .factura-persona-nombre {

            font-size:
                8pt;

            font-weight:
                bold;

            margin-bottom:
                0.8mm;
        }


        .factura-datos-unidad {

            display:
                table;

            width:
                100%;

            table-layout:
                fixed;

            margin-top:
                2mm;
        }


        .factura-unidad-item {

            display:
                table-cell;

            width:
                25%;

            vertical-align:
                top;

            padding:
                1.5mm 1mm 0 1mm;

            border-top:
                1px solid #e2e8f0;
        }


        /* ==================================================
           RESUMEN MORA
        ================================================== */

        .factura-resumen-mora {

            margin-top:
                2mm;

            padding:
                1.8mm 2mm;

            border:
                1px solid #ead8a5;

            background:
                #fff9e8;

            color:
                #624611;

            font-size:
                6pt;

            line-height:
                1.2;
        }


        /* ==================================================
           TABLA
        ================================================== */

        .factura-tabla {

            width:
                100%;

            max-width:
                100%;

            border-collapse:
                collapse;

            table-layout:
                fixed;

            margin-top:
                2mm;

            font-size:
                6.3pt;
        }


        .factura-tabla th {

            background:
                #dceefb;

            color:
                #10243e;

            padding:
                1.4mm;

            border:
                1px solid #c5d9e8;

            text-align:
                left;
        }


        .factura-tabla td {

            padding:
                1.2mm 1.3mm;

            border:
                1px solid #d8e2ec;

            vertical-align:
                top;
        }


        .factura-tabla .numero {

            text-align:
                right;

            white-space:
                nowrap;
        }


        .factura-tabla th:nth-child(1),
        .factura-tabla td:nth-child(1) {

            width:
                37%;
        }


        .factura-tabla th:nth-child(2),
        .factura-tabla td:nth-child(2) {

            width:
                19%;
        }


        .factura-tabla th:nth-child(3),
        .factura-tabla td:nth-child(3) {

            width:
                14%;
        }


        .factura-tabla th:nth-child(4),
        .factura-tabla td:nth-child(4) {

            width:
                14%;
        }


        .factura-tabla th:nth-child(5),
        .factura-tabla td:nth-child(5) {

            width:
                16%;
        }


        .factura-concepto-aclaracion {

            display:
                block;

            margin-top:
                0.5mm;

            font-size:
                5.4pt;

            color:
                #64748b;
        }


        .factura-periodo-estado {

            text-align:
                center;

            font-size:
                5.5pt;

            line-height:
                1.15;
        }


        .estado-mora-texto {

            margin-top:
                0.5mm;

            color:
                #9a4d00;

            font-weight:
                bold;
        }


        .estado-periodo-actual {

            margin-top:
                0.5mm;

            color:
                #64748b;

            font-weight:
                bold;
        }


        .fila-saldo-anterior td {

            background:
                #f8fafc;
        }


        .fila-interes td {

            background:
                #fff9e8;
        }


        .factura-mora {

            display:
                block;

            margin-top:
                0.7mm;

            padding:
                1mm;

            border:
                1px solid #ead8a5;

            background:
                #fffdf4;

            font-size:
                5.2pt;

            line-height:
                1.2;
        }


        .total-row th,
        .total-row td {

            background:
                #dceefb;

            font-size:
                7pt;

            font-weight:
                bold;
        }


        /* ==================================================
           BLOQUE INFERIOR
        ================================================== */

        .factura-inferior {

            display:
                table;

            width:
                100%;

            max-width:
                100%;

            table-layout:
                fixed;

            margin-top:
                2mm;
        }


        .factura-inferior > div,
        .factura-inferior > .factura-panel {

            display:
                table-cell;

            width:
                33.33%;

            vertical-align:
                top;

            padding-right:
                1mm;
        }


        .factura-panel {

            border:
                1px solid #d8e2ec;

            padding:
                2mm;

            min-height:
                18mm;

            font-size:
                6pt;
        }


        .factura-panel h4 {

            margin:
                0 0 1mm 0;

            font-size:
                7pt;
        }


        .factura-estado {

            font-size:
                6.5pt;

            font-weight:
                bold;
        }


        .factura-fecha-limite {

            font-size:
                9pt;

            font-weight:
                bold;
        }


        .factura-qr-placeholder {

            height:
                16mm;

            padding:
                1mm;

            font-size:
                5.5pt;

            text-align:
                center;
        }


        .factura-observaciones {

            margin-top:
                2mm;

            font-size:
                6pt;
        }


        .factura-nota {

            margin-top:
                2mm;

            padding:
                1.5mm;

            font-size:
                6pt;
        }


        /* ==================================================
           CONTROL DE CORTES
        ================================================== */

        .factura-cabecera,
        .factura-meta,
        .factura-cliente,
        .factura-resumen-mora,
        .factura-inferior,
        .factura-panel {

            page-break-inside:
                avoid;
        }


        .factura-tabla tr {

            page-break-inside:
                avoid;
        }

    ';


    // ======================================================
    // HTML PDF
    // ======================================================

    $htmlPdf = '
        <!DOCTYPE html>

        <html lang="es">

        <head>

            <meta charset="UTF-8">

            <style>
                ' .
                $cssPdf .
                '
            </style>

        </head>

        <body>

            ' .
            $htmlFactura .
            '

        </body>

        </html>
    ';


    // ======================================================
    // DOMPDF
    // ======================================================

    $options =
        new Options();


    $options->set(
        'isRemoteEnabled',
        true
    );


    $options->set(
        'isHtml5ParserEnabled',
        true
    );


    $options->set(
        'defaultFont',
        'DejaVu Sans'
    );


    $options->set(
        'chroot',
        ROOT_PATH
    );


    $dompdf =
        new Dompdf(
            $options
        );


    $dompdf->loadHtml(
        $htmlPdf,
        'UTF-8'
    );


    $dompdf->setPaper(
        'A4',
        'portrait'
    );


    $dompdf->render();


    return $dompdf->output();
}