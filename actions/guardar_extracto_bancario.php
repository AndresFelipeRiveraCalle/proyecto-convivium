<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// CONFIGURACIÓN
// ==========================================================

$carpetaRelativa = "uploads/documentos_bancarios/";
$carpetaFisica = ROOT_PATH . "/" . $carpetaRelativa;


// Crear carpeta si no existe
if (!is_dir($carpetaFisica)) {

    if (!mkdir($carpetaFisica, 0755, true)) {

        header(
            "Location: " . BASE_URL .
            "configuracion/extractos_bancarios.php" .
            "?tipo=error&mensaje=" .
            urlencode("No fue posible crear la carpeta de documentos.")
        );

        exit;
    }
}


// ==========================================================
// DATOS DEL FORMULARIO
// ==========================================================

$id_cuenta_bancaria = filter_input(
    INPUT_POST,
    "id_cuenta_bancaria",
    FILTER_VALIDATE_INT
);

$observaciones = trim(
    $_POST["observaciones"] ?? ""
);


// ==========================================================
// VALIDAR CUENTA
// ==========================================================

if (!$id_cuenta_bancaria) {

    header(
        "Location: " . BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=error&mensaje=" .
        urlencode("Debe seleccionar una cuenta bancaria.")
    );

    exit;
}


// ==========================================================
// VALIDAR ARCHIVO
// ==========================================================

if (
    !isset($_FILES["archivo"]) ||
    $_FILES["archivo"]["error"] !== UPLOAD_ERR_OK
) {

    header(
        "Location: " . BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=error&mensaje=" .
        urlencode("No se recibió correctamente el archivo.")
    );

    exit;
}


$archivo = $_FILES["archivo"];


// ==========================================================
// TAMAÑO MÁXIMO
// ==========================================================

$maximo = 10 * 1024 * 1024;

if ($archivo["size"] > $maximo) {

    header(
        "Location: " . BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=error&mensaje=" .
        urlencode("El archivo no puede superar los 10 MB.")
    );

    exit;
}


// ==========================================================
// DATOS DEL ARCHIVO
// ==========================================================

$nombreOriginal = basename(
    $archivo["name"]
);

$extension = strtolower(
    pathinfo(
        $nombreOriginal,
        PATHINFO_EXTENSION
    )
);


// ==========================================================
// EXTENSIONES PERMITIDAS
// ==========================================================

$extensionesPermitidas = [
    "pdf",
    "jpg",
    "jpeg",
    "png"
];


if (
    !in_array(
        $extension,
        $extensionesPermitidas,
        true
    )
) {

    header(
        "Location: " . BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=error&mensaje=" .
        urlencode("Tipo de archivo no permitido.")
    );

    exit;
}


// ==========================================================
// VALIDAR MIME REAL
// ==========================================================

$finfo = new finfo(FILEINFO_MIME_TYPE);

$tipoArchivo = $finfo->file(
    $archivo["tmp_name"]
);


$tiposPermitidos = [

    "pdf" => [
        "application/pdf"
    ],

    "jpg" => [
        "image/jpeg"
    ],

    "jpeg" => [
        "image/jpeg"
    ],

    "png" => [
        "image/png"
    ]

];


if (
    !isset($tiposPermitidos[$extension]) ||
    !in_array(
        $tipoArchivo,
        $tiposPermitidos[$extension],
        true
    )
) {

    header(
        "Location: " . BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=error&mensaje=" .
        urlencode("El tipo real del archivo no coincide con su extensión.")
    );

    exit;
}


// ==========================================================
// HASH DEL ARCHIVO
// ==========================================================

$hashArchivo = hash_file(
    "sha256",
    $archivo["tmp_name"]
);


// ==========================================================
// EVITAR ARCHIVO DUPLICADO
// ==========================================================

$sqlDuplicado = "
    SELECT id_documento
    FROM documentos_bancarios
    WHERE hash_archivo = :hash
    LIMIT 1
";

$stmtDuplicado = $conexion->prepare(
    $sqlDuplicado
);

$stmtDuplicado->execute([
    ":hash" => $hashArchivo
]);


$documentoExistente =
    $stmtDuplicado->fetch(
        PDO::FETCH_ASSOC
    );


if ($documentoExistente) {

    header(
        "Location: " . BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=error&mensaje=" .
        urlencode(
            "Este documento ya fue cargado anteriormente."
        )
    );

    exit;
}


// ==========================================================
// NOMBRE FÍSICO DEL ARCHIVO
// ==========================================================

$nombreArchivo =
    date("Ymd_His") .
    "_" .
    bin2hex(random_bytes(8)) .
    "." .
    $extension;


$rutaFisica =
    $carpetaFisica .
    $nombreArchivo;


$rutaBaseDatos =
    $carpetaRelativa .
    $nombreArchivo;


// ==========================================================
// MOVER ARCHIVO
// ==========================================================

if (
    !move_uploaded_file(
        $archivo["tmp_name"],
        $rutaFisica
    )
) {

    header(
        "Location: " . BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=error&mensaje=" .
        urlencode(
            "No fue posible guardar físicamente el archivo."
        )
    );

    exit;
}


// ==========================================================
// TRANSACCIÓN
// ==========================================================

try {

    $conexion->beginTransaction();


    // ======================================================
    // INSERTAR DOCUMENTO
    // ======================================================

    $sqlDocumento = "

        INSERT INTO documentos_bancarios (

            id_cuenta_bancaria,
            nombre_archivo,
            nombre_original,
            ruta_archivo,
            tipo_archivo,
            hash_archivo,
            metodo_extraccion,
            estado_procesamiento,
            observaciones

        )

        VALUES (

            :id_cuenta_bancaria,
            :nombre_archivo,
            :nombre_original,
            :ruta_archivo,
            :tipo_archivo,
            :hash_archivo,
            'MANUAL',
            'PENDIENTE',
            :observaciones

        )

    ";


    $stmtDocumento =
        $conexion->prepare(
            $sqlDocumento
        );


    $stmtDocumento->execute([

        ":id_cuenta_bancaria" =>
            $id_cuenta_bancaria,

        ":nombre_archivo" =>
            $nombreArchivo,

        ":nombre_original" =>
            $nombreOriginal,

        ":ruta_archivo" =>
            $rutaBaseDatos,

        ":tipo_archivo" =>
            $tipoArchivo,

        ":hash_archivo" =>
            $hashArchivo,

        ":observaciones" =>
            $observaciones !== ""
                ? $observaciones
                : null

    ]);

    $idDocumento =
        $conexion->lastInsertId();


    // ======================================================
    // IMPORTANTE
    //
    // Todavía no insertamos un movimiento en
    // extractos_bancarios porque el documento puede
    // contener muchos movimientos.
    //
    // El OCR/procesador será quien cree esos movimientos.
    // ======================================================


    $conexion->commit();


    // ======================================================
    // REDIRECCIÓN
    // ======================================================

    header(
        "Location: " . BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=success&mensaje=" .
        urlencode(
            "Extracto cargado correctamente. Documento #" .
            $idDocumento .
            " pendiente de procesamiento."
        )
    );

    exit;


} catch (Throwable $e) {


    // ======================================================
    // DESHACER TRANSACCIÓN
    // ======================================================

    if (
        $conexion->inTransaction()
    ) {

        $conexion->rollBack();

    }


    // ======================================================
    // ELIMINAR ARCHIVO SI FALLÓ LA BASE DE DATOS
    // ======================================================

    if (
        file_exists($rutaFisica)
    ) {

        unlink($rutaFisica);

    }


    // ======================================================
    // ERROR
    // ======================================================

    header(
        "Location: " . BASE_URL .
        "configuracion/extractos_bancarios.php" .
        "?tipo=error&mensaje=" .
        urlencode(
            "No fue posible guardar el extracto: " .
            $e->getMessage()
        )
    );

    exit;
}