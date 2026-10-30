<?php

use PHPMailer\PHPMailer\PHPMailer;

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";
require_once ROOT_PATH . "/config/seguridad_correo.php";
require_once ROOT_PATH . "/vendor/autoload.php";

function enviarCorreoRecuperacion(string $correoDestino, string $token): bool
{
    global $conexion;

    try {
        if (!filter_var($correoDestino, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("El correo de destino no es válido.");
        }

        $sql = "
            SELECT
                correo_remitente,
                nombre_remitente,
                correo_respuesta,
                smtp_host,
                smtp_puerto,
                smtp_seguridad,
                smtp_autenticacion,
                smtp_usuario,
                smtp_password
            FROM configuracion_correo
            WHERE activo = 1
            ORDER BY id_configuracion_correo DESC
            LIMIT 1
        ";

        $configuracion = $conexion->query($sql)->fetch(PDO::FETCH_ASSOC);

        if (!$configuracion) {
            throw new RuntimeException("No hay una configuración SMTP activa.");
        }

        if (
            empty($configuracion["smtp_host"])
            || empty($configuracion["smtp_puerto"])
            || empty($configuracion["correo_remitente"])
        ) {
            throw new RuntimeException("La configuración SMTP está incompleta.");
        }

        $smtpPassword = null;

        if (!empty($configuracion["smtp_password"])) {
            $smtpPassword = descifrarCredencialCorreo(
                $configuracion["smtp_password"]
            );
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = trim($configuracion["smtp_host"]);
        $mail->Port = (int) $configuracion["smtp_puerto"];
        $mail->SMTPAuth = (int) $configuracion["smtp_autenticacion"] === 1;
        $mail->Timeout = 20;
        $mail->CharSet = "UTF-8";
        $mail->Encoding = "base64";

        if ($mail->SMTPAuth) {
            if (empty($configuracion["smtp_usuario"]) || !$smtpPassword) {
                throw new RuntimeException("Faltan credenciales SMTP.");
            }

            $mail->Username = $configuracion["smtp_usuario"];
            $mail->Password = $smtpPassword;
        }

        $seguridad = strtoupper(trim($configuracion["smtp_seguridad"] ?? "TLS"));

        switch ($seguridad) {
            case "SSL":
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->SMTPAutoTLS = false;
                break;

            case "NINGUNA":
                $mail->SMTPSecure = "";
                $mail->SMTPAutoTLS = false;
                break;

            case "TLS":
            default:
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->SMTPAutoTLS = true;
                break;
        }

        $mail->setFrom(
            $configuracion["correo_remitente"],
            $configuracion["nombre_remitente"] ?: "Convivium"
        );

        if (
            !empty($configuracion["correo_respuesta"])
            && filter_var($configuracion["correo_respuesta"], FILTER_VALIDATE_EMAIL)
        ) {
            $mail->addReplyTo($configuracion["correo_respuesta"]);
        }

        $mail->addAddress($correoDestino);

        $https = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";
        $esquema = $https ? "https" : "http";
        $host = $_SERVER["HTTP_HOST"] ?? "localhost";
        $baseUrl = defined("BASE_URL") ? BASE_URL : "/proyecto-convivium/";
        $enlace = $esquema . "://" . $host . $baseUrl
            . "auth/restablecer_contrasena.php?token=" . rawurlencode($token);

        $enlaceSeguro = htmlspecialchars($enlace, ENT_QUOTES, "UTF-8");

        $mail->isHTML(true);
        $mail->Subject = "Recuperación de contraseña - Convivium";
        $mail->Body = "
            <p>Recibimos una solicitud para restablecer tu contraseña de Convivium.</p>
            <p><a href=\"{$enlaceSeguro}\">Restablecer contraseña</a></p>
            <p>El enlace vence en una hora. Si no solicitaste el cambio, ignora este mensaje.</p>
        ";
        $mail->AltBody = "Restablece tu contraseña con este enlace: " . $enlace;

        return $mail->send();
    } catch (Throwable $error) {
        error_log("Error al enviar recuperación de contraseña: " . $error->getMessage());
        return false;
    }
}
