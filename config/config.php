<?php
    define("ROOT_PATH", realpath(__DIR__ . "/.."));
    define("BASE_URL", "/proyecto-convivium/");

    // 
    // CIFRADO DE CREDENCIALES DE CORREO
    // Clave privada utilizada para SMTP e IMAP.
    // 

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
?>