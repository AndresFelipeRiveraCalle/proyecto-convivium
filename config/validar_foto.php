<?php

function guardarFotoPersona(?array $archivo): ?string
{
    if (
        $archivo === null
        || !isset($archivo['error'])
        || $archivo['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(
            'No fue posible recibir la fotografía.'
        );
    }

    $rutaTemporal = $archivo['tmp_name'] ?? '';

    if (
        $rutaTemporal === ''
        || !is_uploaded_file($rutaTemporal)
    ) {
        throw new RuntimeException(
            'El archivo recibido no es una carga válida.'
        );
    }

    $tamano = filesize($rutaTemporal);
    $tamanoMaximo = 5 * 1024 * 1024; // 5 MB

    if ($tamano === false || $tamano <= 0 || $tamano > $tamanoMaximo) {
        throw new RuntimeException(
            'La fotografía debe pesar máximo 5 MB.'
        );
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($rutaTemporal);

    $formatosPermitidos = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($formatosPermitidos[$mime])) {
        throw new RuntimeException(
            'La fotografía debe ser JPG, PNG o WEBP.'
        );
    }

    $datosImagen = @getimagesize($rutaTemporal);

    if (
        $datosImagen === false
        || ($datosImagen['mime'] ?? '') !== $mime
        || $datosImagen[0] <= 0
        || $datosImagen[1] <= 0
        || ($datosImagen[0] * $datosImagen[1]) > 25000000
    ) {
        throw new RuntimeException(
            'El contenido de la fotografía no es válido.'
        );
    }

    $directorio = ROOT_PATH
        . DIRECTORY_SEPARATOR . 'uploads'
        . DIRECTORY_SEPARATOR . 'personas';

    if (
        !is_dir($directorio)
        && !mkdir($directorio, 0750, true)
        && !is_dir($directorio)
    ) {
        throw new RuntimeException(
            'No fue posible preparar la carpeta de fotografías.'
        );
    }

    $nombre = bin2hex(random_bytes(16))
        . '.'
        . $formatosPermitidos[$mime];

    $destino = $directorio
        . DIRECTORY_SEPARATOR
        . $nombre;

    if (!move_uploaded_file($rutaTemporal, $destino)) {
        throw new RuntimeException(
            'No fue posible guardar la fotografía.'
        );
    }

    return 'uploads/personas/' . $nombre;
}
function eliminarFotoPersonaAnterior(?string $rutaRelativa): void
{
    if ($rutaRelativa === null || $rutaRelativa === '') {
        return;
    }

    $directorioRelativo = 'uploads/personas/';

    if (strncmp(
        $rutaRelativa,
        $directorioRelativo,
        strlen($directorioRelativo)
    ) !== 0) {
        return;
    }

    $nombre = substr(
        $rutaRelativa,
        strlen($directorioRelativo)
    );

    if ($nombre === '' || basename($nombre) !== $nombre) {
        return;
    }

    $extension = strtolower(
        pathinfo($nombre, PATHINFO_EXTENSION)
    );

    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return;
    }

    $directorio = ROOT_PATH
        . DIRECTORY_SEPARATOR . 'uploads'
        . DIRECTORY_SEPARATOR . 'personas';

    $directorioReal = realpath($directorio);
    $archivoReal = realpath(
        $directorio . DIRECTORY_SEPARATOR . $nombre
    );

    if (
        $directorioReal === false
        || $archivoReal === false
        || !is_file($archivoReal)
    ) {
        return;
    }

    $prefijoReal = rtrim(
        $directorioReal,
        DIRECTORY_SEPARATOR
    ) . DIRECTORY_SEPARATOR;

    if (stripos($archivoReal, $prefijoReal) !== 0) {
        return;
    }

    if (!unlink($archivoReal)) {
        error_log(
            'No fue posible eliminar una fotografía anterior: '
            . basename($archivoReal)
        );
    }
}