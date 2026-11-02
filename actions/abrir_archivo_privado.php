<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/auth/requerir_sesion.php";
require_once ROOT_PATH . "/config/conexion.php";
require_once ROOT_PATH . "/config/almacenamiento.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tipo = trim($_GET['tipo'] ?? '');

if ($id <= 0) {
    http_response_code(404);
    exit('Archivo no disponible.');
}

try {
    switch ($tipo) {
        case 'extracto':
            $sql = "
                SELECT
                    ruta_archivo AS ruta,
                    nombre_original AS nombre
                FROM documentos_bancarios
                WHERE id_documento = :id
                LIMIT 1
            ";
            $directorioRelativo = 'uploads/documentos_bancarios';
            $tiposPermitidos = [
                'application/pdf',
                'image/jpeg',
                'image/png'
            ];
            break;

        case 'foto':
            $sql = "
                SELECT
                    foto AS ruta,
                    NULL AS nombre
                FROM usuario
                WHERE id = :id
                LIMIT 1
            ";
            $directorioRelativo = 'uploads/personas';
            $tiposPermitidos = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];
            break;

        case 'mantenimiento':
            $sql = "
                SELECT
                    evidencia AS ruta,
                    NULL AS nombre
                FROM mantenimiento
                WHERE id = :id
                LIMIT 1
            ";
            $directorioRelativo = 'mantenimiento/uploads';
            $tiposPermitidos = [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/webp'
            ];
            break;

        case 'logo':
            $sql = "
                SELECT
                    logo AS ruta,
                    NULL AS nombre
                FROM datos_unidad
                WHERE id = :id
                LIMIT 1
            ";
            $directorioRelativo = 'assets/logos';
            $tiposPermitidos = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];
            break;

        case 'reglamento':
            $sql = "
                SELECT
                    reglamento AS ruta,
                    NULL AS nombre
                FROM datos_unidad
                WHERE id = :id
                LIMIT 1
            ";
            $directorioRelativo = 'assets/documentos';
            $tiposPermitidos = [
                'application/pdf'
            ];
            break;

        case 'manual':
            $sql = "
                SELECT
                    manual AS ruta,
                    NULL AS nombre
                FROM datos_unidad
                WHERE id = :id
                LIMIT 1
            ";
            $directorioRelativo = 'assets/documentos';
            $tiposPermitidos = [
                'application/pdf'
            ];
            break;

        default:
            http_response_code(404);
            exit('Archivo no disponible.');
    }

    $stmt = $conexion->prepare($sql);
    $stmt->execute([':id' => $id]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registro || empty($registro['ruta'])) {
        http_response_code(404);
        exit('Archivo no disponible.');
    }

    $rutaRegistrada = str_replace(
        '\\',
        '/',
        (string)$registro['ruta']
    );

    $nombreArchivo = basename($rutaRegistrada);

    if (
        $nombreArchivo === ''
        || $nombreArchivo === '.'
        || $nombreArchivo === '..'
    ) {
        http_response_code(404);
        exit('Archivo no disponible.');
    }

    $directorio = realpath(
        convivium_storage_path($directorioRelativo)
    );

    if ($directorio === false) {
        http_response_code(404);
        exit('Archivo no disponible.');
    }

    $archivoReal = realpath(
        $directorio . DIRECTORY_SEPARATOR . $nombreArchivo
    );

    if ($archivoReal === false || !is_file($archivoReal)) {
        http_response_code(404);
        exit('Archivo no disponible.');
    }

    $directorioNormalizado = rtrim(
        str_replace('\\', '/', $directorio),
        '/'
    ) . '/';

    $archivoNormalizado = str_replace('\\', '/', $archivoReal);

    if (
        strncmp(
            $archivoNormalizado,
            $directorioNormalizado,
            strlen($directorioNormalizado)
        ) !== 0
    ) {
        http_response_code(404);
        exit('Archivo no disponible.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($archivoReal);

    if (!in_array($mime, $tiposPermitidos, true)) {
        http_response_code(404);
        exit('Archivo no disponible.');
    }

    $nombreSalida = basename(
        str_replace(
            '\\',
            '/',
            (string)($registro['nombre'] ?: $nombreArchivo)
        )
    );

    $nombreAscii = preg_replace(
        '/[^A-Za-z0-9._-]/',
        '_',
        $nombreSalida
    );

    if ($nombreAscii === '' || $nombreAscii === null) {
        $nombreAscii = 'archivo';
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: ' . $mime);
    header(
        'Content-Disposition: inline; filename="' .
        $nombreAscii .
        '"; filename*=UTF-8\'\'' .
        rawurlencode($nombreSalida)
    );

    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store, max-age=0');

    readfile($archivoReal);
    exit;
} catch (Throwable $e) {
    error_log(
        'No fue posible servir un archivo privado: '
        . $e->getMessage()
    );

    http_response_code(404);
    exit('Archivo no disponible.');
}