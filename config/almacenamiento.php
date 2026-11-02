<?php

/**
 * Devuelve la raíz para los archivos persistentes de Convivium.
 *
 * En XAMPP se usa la raíz del proyecto.
 * En Render se configura CONVIVIUM_STORAGE_ROOT=/var/data/convivium.
 */
function convivium_storage_root(): string
{
    $configurada = getenv('CONVIVIUM_STORAGE_ROOT');

    if ($configurada === false || trim($configurada) === '') {
        $configurada = ROOT_PATH;
    }

    $raiz = trim($configurada);
    $raiz = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $raiz);

    // Conserva correctamente la raíz del sistema, por ejemplo "/" o "\".
    if (strlen($raiz) > 1) {
        $raiz = rtrim($raiz, '/\\');
    }

    if ($raiz === '') {
        throw new RuntimeException(
            'La ruta raíz del almacenamiento no es válida.'
        );
    }

    return $raiz;
}

/**
 * Resuelve una ruta relativa dentro del almacenamiento.
 * Rechaza rutas absolutas y segmentos que intenten salir de la raíz.
 */
function convivium_storage_path(string $rutaRelativa): string
{
    $ruta = str_replace('\\', '/', trim($rutaRelativa));

    if (
        $ruta === ''
        || $ruta[0] === '/'
        || preg_match('/^[A-Za-z]:/', $ruta)
        || preg_match('#(^|/)\.\.?(/|$)#', $ruta)
    ) {
        throw new InvalidArgumentException(
            'La ruta relativa de almacenamiento no es válida.'
        );
    }

    $segmentos = explode('/', $ruta);

    if (in_array('', $segmentos, true)) {
        throw new InvalidArgumentException(
            'La ruta relativa contiene segmentos vacíos.'
        );
    }

    return convivium_storage_root()
        . DIRECTORY_SEPARATOR
        . implode(DIRECTORY_SEPARATOR, $segmentos);
}

/**
 * Crea una carpeta dentro del almacenamiento y devuelve su ruta física.
 */
function convivium_storage_directory(
    string $rutaRelativa,
    int $permisos = 0750
): string {
    $directorio = convivium_storage_path($rutaRelativa);

    if (
        !is_dir($directorio)
        && !mkdir($directorio, $permisos, true)
        && !is_dir($directorio)
    ) {
        throw new RuntimeException(
            'No fue posible crear la carpeta de almacenamiento.'
        );
    }

    return $directorio;
}