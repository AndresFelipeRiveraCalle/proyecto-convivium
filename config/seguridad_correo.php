<?php

// ========
// CLAVE DE CIFRADO
// Usa una clave privada distinta para cada instalación.
// ========

if (!defined('CORREO_ENCRYPTION_KEY')) {
    // Definimos una clave segura por defecto para evitar el error fatal
    define('CORREO_ENCRYPTION_KEY', 'ClaveSecretaConvivium2026_Segura!');
}

// ========
// OBTENER CLAVE BINARIA
// Convierte la clave configurada a 256 bits.
// ========

function obtenerClaveCorreo(): string
{
    return hash(
        'sha256',
        CORREO_ENCRYPTION_KEY,
        true
    );
}

// ========
// CIFRAR
// Protege contraseñas SMTP e IMAP antes de guardarlas.
// ========

function cifrarCredencialCorreo(?string $valor): ?string 
{
    if ($valor === null || $valor === '') {
        return null;
    }

    $metodo = 'aes-256-gcm';
    $clave = obtenerClaveCorreo();
    $ivLength = openssl_cipher_iv_length($metodo);
    $iv = random_bytes($ivLength);
    $tag = '';

    $cifrado = openssl_encrypt(
        $valor,
        $metodo,
        $clave,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if ($cifrado === false) {
        throw new RuntimeException('No fue posible cifrar la credencial de correo.');
    }

    $paquete = [
        'v'    => 1,
        'iv'   => base64_encode($iv),
        'tag'  => base64_encode($tag),
        'data' => base64_encode($cifrado)
    ];

    return base64_encode(json_encode($paquete, JSON_UNESCAPED_SLASHES));
}

// ========
// DESCIFRAR
// Recupera la contraseña únicamente cuando será utilizada.
// ========

function descifrarCredencialCorreo(?string $valorCifrado): ?string 
{
    if ($valorCifrado === null || $valorCifrado === '') {
        return null;
    }

    $json = base64_decode($valorCifrado, true);

    if ($json === false) {
        throw new RuntimeException('La credencial almacenada no tiene un formato válido.');
    }

    $paquete = json_decode($json, true);

    if (!is_array($paquete) || !isset($paquete['iv'], $paquete['tag'], $paquete['data'])) {
        throw new RuntimeException('La credencial almacenada no tiene un formato válido.');
    }

    $iv = base64_decode($paquete['iv'], true);
    $tag = base64_decode($paquete['tag'], true);
    $cifrado = base64_decode($paquete['data'], true);

    if ($iv === false || $tag === false || $cifrado === false) {
        throw new RuntimeException('La credencial almacenada está dañada.');
    }

    $resultado = openssl_decrypt(
        $cifrado,
        'aes-256-gcm',
        obtenerClaveCorreo(),
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if ($resultado === false) {
        throw new RuntimeException('No fue posible descifrar la credencial de correo.');
    }

    return $resultado;
}



// FUNCIÓN PARA ENVIAR CORREOS CON PHPMAILER Y RECUPERAR CONTRASEÑA


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cargamos el autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

function enviarCorreoRecuperacion($correoDestino, $token) {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP (Ejemplo usando Gmail)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        // --- CREDENCIALES INTEGRADAS ---
        $mail->Username   = 'ccastrillon33@gmail.com'; 
        $mail->Password   = 'rzyghjhnwnmpsrlr'; 
        // -------------------------------
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Remitente y Destinatario
        $mail->setFrom('ccastrillon33@gmail.com', 'Sistema Convivium');
        $mail->addAddress($correoDestino);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = mb_encode_mimeheader('Recuperación de Contraseña - Convivium', 'UTF-8');
        
        $enlace = "http://localhost/proyecto-convivium/auth/restablecer_contrasena.php?token=" . $token;

        $mail->Body = "
            <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                <h2>Solicitud de Recuperación de Contraseña</h2>
                <p>Has solicitado restablecer tu contraseña en el sistema <b>Convivium</b>.</p>
                <p>Haz clic en el siguiente botón para cambiarla (el enlace expira en 1 hora):</p>
                <p style='margin: 20px 0;'>
                    <a href='{$enlace}' style='background-color: #f59e0b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Restablecer Contraseña</a>
                </p>
                <p>Si tú no solicitaste esto, puedes ignorar este mensaje.</p>
            </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo: {$mail->ErrorInfo}");
        return false;
    }
}
?>