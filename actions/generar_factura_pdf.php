<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";
require_once ROOT_PATH . "/config/generador_factura_pdf.php";


// ==========================================================
// FACTURA
// ==========================================================

$idFactura =
    isset($_GET['id'])
        ? (int)$_GET['id']
        : 0;


if ($idFactura <= 0) {

    exit(
        'Debe seleccionar una factura válida.'
    );
}


// ==========================================================
// NÚMERO DE FACTURA
// ==========================================================

$sql = "
    SELECT
        numero_factura

    FROM facturas

    WHERE
        id_factura =
            :id_factura

    LIMIT 1
";


$stmt =
    $conexion->prepare(
        $sql
    );


$stmt->execute([
    ':id_factura'
        => $idFactura
]);


$numeroFactura =
    $stmt->fetchColumn();


if (!$numeroFactura) {

    exit(
        'La factura seleccionada no existe.'
    );
}


// ==========================================================
// GENERAR PDF
// ==========================================================

$pdf =
    generarPdfFactura(
        $idFactura
    );


$nombreArchivo =
    'Factura-' .
    preg_replace(
        '/[^A-Za-z0-9\-_]/',
        '-',
        $numeroFactura
    ) .
    '.pdf';


// ==========================================================
// MOSTRAR
// ==========================================================

header(
    'Content-Type: application/pdf'
);

header(
    'Content-Disposition: inline; filename="' .
    $nombreArchivo .
    '"'
);

header(
    'Content-Length: ' .
    strlen($pdf)
);


echo $pdf;

exit;