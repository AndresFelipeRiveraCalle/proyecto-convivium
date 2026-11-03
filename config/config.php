<?php

$rootPath = realpath(__DIR__ . "/..");

if ($rootPath === false) {
    throw new RuntimeException(
        'No se pudo determinar la ruta raíz del proyecto.'
    );
}

define("ROOT_PATH", $rootPath);

$baseUrl = getenv('APP_BASE_URL');

if ($baseUrl === false || trim($baseUrl) === '') {
    $baseUrl = '/proyecto-convivium/';
}

$baseUrl = trim($baseUrl);

if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $baseUrl)) {
    throw new RuntimeException(
        'APP_BASE_URL debe ser una ruta, por ejemplo / o /proyecto-convivium/.'
    );
}

$baseUrl = '/' . trim($baseUrl, '/');
$baseUrl = rtrim($baseUrl, '/') . '/';

define("BASE_URL", $baseUrl);

$archivoSecretosCorreo = getenv('CONVIVIUM_SECRETS_FILE');

if (
    ($archivoSecretosCorreo === false || $archivoSecretosCorreo === '')
    && isset($_SERVER['CONVIVIUM_SECRETS_FILE'])
) {
    $archivoSecretosCorreo = $_SERVER['CONVIVIUM_SECRETS_FILE'];
}

if (!is_string($archivoSecretosCorreo)) {
    throw new RuntimeException(
        'No se configuró la ruta de los secretos de correo.'
    );
}

$archivoSecretosCorreo = trim($archivoSecretosCorreo);

if (
    $archivoSecretosCorreo === ''
    || !is_file($archivoSecretosCorreo)
    || !is_readable($archivoSecretosCorreo)
) {
    throw new RuntimeException(
        'El archivo privado de secretos no existe o no se puede leer.'
    );
}

require_once $archivoSecretosCorreo;

unset($archivoSecretosCorreo);