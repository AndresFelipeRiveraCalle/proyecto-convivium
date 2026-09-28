<?php

// ==========================================================
// CLAVE DE CIFRADO
// Usa una clave privada distinta para cada instalación.
// ==========================================================

if (!defined('CORREO_ENCRYPTION_KEY')) {

    throw new RuntimeException(
        'No se ha definido CORREO_ENCRYPTION_KEY.'
    );
}


// ==========================================================
// OBTENER CLAVE BINARIA
// Convierte la clave configurada a 256 bits.
// ==========================================================

function obtenerClaveCorreo(): string
{
    return hash(
        'sha256',
        CORREO_ENCRYPTION_KEY,
        true
    );
}


// ==========================================================
// CIFRAR
// Protege contraseñas SMTP e IMAP antes de guardarlas.
// ==========================================================

function cifrarCredencialCorreo(
    ?string $valor
): ?string {

    if (
        $valor === null
        ||
        $valor === ''
    ) {
        return null;
    }


    $metodo = 'aes-256-gcm';

    $clave =
        obtenerClaveCorreo();

    $ivLength =
        openssl_cipher_iv_length(
            $metodo
        );


    $iv =
        random_bytes(
            $ivLength
        );


    $tag = '';


    $cifrado =
        openssl_encrypt(
            $valor,
            $metodo,
            $clave,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );


    if ($cifrado === false) {

        throw new RuntimeException(
            'No fue posible cifrar la credencial de correo.'
        );
    }


    $paquete = [
        'v'    => 1,
        'iv'   => base64_encode($iv),
        'tag'  => base64_encode($tag),
        'data' => base64_encode($cifrado)
    ];


    return base64_encode(
        json_encode(
            $paquete,
            JSON_UNESCAPED_SLASHES
        )
    );
}


// ==========================================================
// DESCIFRAR
// Recupera la contraseña únicamente cuando será utilizada.
// ==========================================================

function descifrarCredencialCorreo(
    ?string $valorCifrado
): ?string {

    if (
        $valorCifrado === null
        ||
        $valorCifrado === ''
    ) {
        return null;
    }


    $json =
        base64_decode(
            $valorCifrado,
            true
        );


    if ($json === false) {

        throw new RuntimeException(
            'La credencial almacenada no tiene un formato válido.'
        );
    }


    $paquete =
        json_decode(
            $json,
            true
        );


    if (
        !is_array($paquete)
        ||
        !isset(
            $paquete['iv'],
            $paquete['tag'],
            $paquete['data']
        )
    ) {

        throw new RuntimeException(
            'La credencial almacenada no tiene un formato válido.'
        );
    }


    $iv =
        base64_decode(
            $paquete['iv'],
            true
        );


    $tag =
        base64_decode(
            $paquete['tag'],
            true
        );


    $cifrado =
        base64_decode(
            $paquete['data'],
            true
        );


    if (
        $iv === false
        ||
        $tag === false
        ||
        $cifrado === false
    ) {

        throw new RuntimeException(
            'La credencial almacenada está dañada.'
        );
    }


    $resultado =
        openssl_decrypt(
            $cifrado,
            'aes-256-gcm',
            obtenerClaveCorreo(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );


    if ($resultado === false) {

        throw new RuntimeException(
            'No fue posible descifrar la credencial de correo.'
        );
    }


    return $resultado;
}