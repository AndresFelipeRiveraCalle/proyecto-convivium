<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";
require_once ROOT_PATH . "/config/seguridad_correo.php";


// ==========================================================
// VALIDAR MÉTODO
// Solo permite guardar mediante POST.
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/configuracion_correo.php"
    );

    exit;
}


// ==========================================================
// DATOS
// Obtiene los valores enviados por el formulario.
// ==========================================================

$idConfiguracion =
    isset($_POST['id_configuracion_correo'])
        ? (int)$_POST['id_configuracion_correo']
        : 0;


$nombreConfiguracion =
    trim(
        $_POST['nombre_configuracion'] ?? ''
    );


$correoRemitente =
    trim(
        $_POST['correo_remitente'] ?? ''
    );


$nombreRemitente =
    trim(
        $_POST['nombre_remitente'] ?? ''
    );


$correoRespuesta =
    trim(
        $_POST['correo_respuesta'] ?? ''
    );


// ==========================================================
// SMTP
// ==========================================================

$smtpHost =
    trim(
        $_POST['smtp_host'] ?? ''
    );


$smtpPuerto =
    isset($_POST['smtp_puerto'])
        ? (int)$_POST['smtp_puerto']
        : 587;


$smtpSeguridad =
    trim(
        $_POST['smtp_seguridad'] ?? 'TLS'
    );


$smtpAutenticacion =
    isset($_POST['smtp_autenticacion'])
        ? (int)$_POST['smtp_autenticacion']
        : 1;


$smtpUsuario =
    trim(
        $_POST['smtp_usuario'] ?? ''
    );


$smtpPassword =
    (string)(
        $_POST['smtp_password']
        ?? ''
    );


// ==========================================================
// IMAP
// ==========================================================

$imapHost =
    trim(
        $_POST['imap_host'] ?? ''
    );


$imapPuerto =
    isset($_POST['imap_puerto'])
        ? (int)$_POST['imap_puerto']
        : 993;


$imapSeguridad =
    trim(
        $_POST['imap_seguridad'] ?? 'SSL'
    );


$imapUsuario =
    trim(
        $_POST['imap_usuario'] ?? ''
    );


$imapPassword =
    (string)(
        $_POST['imap_password']
        ?? ''
    );


$imapCarpeta =
    trim(
        $_POST['imap_carpeta']
        ?? 'INBOX'
    );


$activo =
    isset($_POST['activo'])
        ? (int)$_POST['activo']
        : 1;


// ==========================================================
// VALIDACIONES
// Verifica los campos obligatorios.
// ==========================================================

if (
    $nombreConfiguracion === ''
    ||
    $correoRemitente === ''
    ||
    $nombreRemitente === ''
    ||
    $smtpHost === ''
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/configuracion_correo.php?" .
        http_build_query([
            'tipo'  => 'warning',
            'texto' => 'Complete los campos obligatorios.'
        ])
    );

    exit;
}


// ==========================================================
// VALIDAR CORREO REMITENTE
// ==========================================================

if (
    !filter_var(
        $correoRemitente,
        FILTER_VALIDATE_EMAIL
    )
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/configuracion_correo.php?" .
        http_build_query([
            'tipo'  => 'warning',
            'texto' => 'El correo remitente no es válido.'
        ])
    );

    exit;
}


// ==========================================================
// VALIDAR CORREO DE RESPUESTA
// ==========================================================

if (
    $correoRespuesta !== ''
    &&
    !filter_var(
        $correoRespuesta,
        FILTER_VALIDATE_EMAIL
    )
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/configuracion_correo.php?" .
        http_build_query([
            'tipo'  => 'warning',
            'texto' => 'El correo de respuesta no es válido.'
        ])
    );

    exit;
}


// ==========================================================
// VALIDAR SEGURIDAD SMTP
// ==========================================================

if (
    !in_array(
        $smtpSeguridad,
        [
            'TLS',
            'SSL',
            'NINGUNA'
        ],
        true
    )
) {

    $smtpSeguridad = 'TLS';
}


// ==========================================================
// VALIDAR SEGURIDAD IMAP
// ==========================================================

if (
    !in_array(
        $imapSeguridad,
        [
            'TLS',
            'SSL',
            'NINGUNA'
        ],
        true
    )
) {

    $imapSeguridad = 'SSL';
}


// ==========================================================
// VALIDAR PUERTO SMTP
// ==========================================================

if (
    $smtpPuerto <= 0
    ||
    $smtpPuerto > 65535
) {

    $smtpPuerto = 587;
}


// ==========================================================
// VALIDAR PUERTO IMAP
// ==========================================================

if (
    $imapPuerto <= 0
    ||
    $imapPuerto > 65535
) {

    $imapPuerto = 993;
}


$smtpAutenticacion =
    $smtpAutenticacion === 1
        ? 1
        : 0;


$activo =
    $activo === 1
        ? 1
        : 0;


// ==========================================================
// VALIDAR CONTRASEÑA NUEVA
// Al crear una configuración autenticada requiere contraseña.
// ==========================================================

if (
    $idConfiguracion <= 0
    &&
    $smtpAutenticacion === 1
    &&
    $smtpPassword === ''
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/configuracion_correo.php?" .
        http_build_query([
            'tipo'  => 'warning',
            'texto' => 'Debe indicar la contraseña SMTP.'
        ])
    );

    exit;
}


// ==========================================================
// GUARDAR
// Crea o actualiza la configuración.
// ==========================================================

try {

    $conexion->beginTransaction();


    // ======================================================
    // ACTUALIZAR CONFIGURACIÓN
    // ======================================================

    if ($idConfiguracion > 0) {


        $sqlExiste = "
            SELECT
                id_configuracion_correo

            FROM configuracion_correo

            WHERE
                id_configuracion_correo =
                    :id_configuracion_correo

            LIMIT 1
        ";


        $stmtExiste =
            $conexion->prepare(
                $sqlExiste
            );


        $stmtExiste->execute([

            ':id_configuracion_correo'
                => $idConfiguracion

        ]);


        if (
            !$stmtExiste->fetchColumn()
        ) {

            throw new Exception(
                'La configuración de correo no existe.'
            );
        }


        // ==================================================
        // SQL BASE
        // Las contraseñas solo se cambian si se escriben.
        // ==================================================

        $sqlActualizar = "
            UPDATE configuracion_correo

            SET
                nombre_configuracion =
                    :nombre_configuracion,

                correo_remitente =
                    :correo_remitente,

                nombre_remitente =
                    :nombre_remitente,

                correo_respuesta =
                    :correo_respuesta,

                smtp_host =
                    :smtp_host,

                smtp_puerto =
                    :smtp_puerto,

                smtp_seguridad =
                    :smtp_seguridad,

                smtp_autenticacion =
                    :smtp_autenticacion,

                smtp_usuario =
                    :smtp_usuario,

                imap_host =
                    :imap_host,

                imap_puerto =
                    :imap_puerto,

                imap_seguridad =
                    :imap_seguridad,

                imap_usuario =
                    :imap_usuario,

                imap_carpeta =
                    :imap_carpeta,

                activo =
                    :activo
        ";


        // ==================================================
        // NUEVA CONTRASEÑA SMTP
        // ==================================================

        if ($smtpPassword !== '') {

            $sqlActualizar .= ",
                smtp_password =
                    :smtp_password
            ";
        }


        // ==================================================
        // NUEVA CONTRASEÑA IMAP
        // ==================================================

        if ($imapPassword !== '') {

            $sqlActualizar .= ",
                imap_password =
                    :imap_password
            ";
        }


        $sqlActualizar .= "
            WHERE
                id_configuracion_correo =
                    :id_configuracion_correo
        ";


        $stmtActualizar =
            $conexion->prepare(
                $sqlActualizar
            );


        $parametros = [

            ':nombre_configuracion'
                => $nombreConfiguracion,

            ':correo_remitente'
                => $correoRemitente,

            ':nombre_remitente'
                => $nombreRemitente,

            ':correo_respuesta'
                => $correoRespuesta !== ''
                    ? $correoRespuesta
                    : null,

            ':smtp_host'
                => $smtpHost,

            ':smtp_puerto'
                => $smtpPuerto,

            ':smtp_seguridad'
                => $smtpSeguridad,

            ':smtp_autenticacion'
                => $smtpAutenticacion,

            ':smtp_usuario'
                => $smtpUsuario !== ''
                    ? $smtpUsuario
                    : null,

            ':imap_host'
                => $imapHost !== ''
                    ? $imapHost
                    : null,

            ':imap_puerto'
                => $imapPuerto,

            ':imap_seguridad'
                => $imapSeguridad,

            ':imap_usuario'
                => $imapUsuario !== ''
                    ? $imapUsuario
                    : null,

            ':imap_carpeta'
                => $imapCarpeta !== ''
                    ? $imapCarpeta
                    : 'INBOX',

            ':activo'
                => $activo,

            ':id_configuracion_correo'
                => $idConfiguracion
        ];


        // ==================================================
        // CIFRAR NUEVA CONTRASEÑA SMTP
        // ==================================================

        if ($smtpPassword !== '') {

            $parametros[
                ':smtp_password'
            ] =
                cifrarCredencialCorreo(
                    $smtpPassword
                );
        }


        // ==================================================
        // CIFRAR NUEVA CONTRASEÑA IMAP
        // ==================================================

        if ($imapPassword !== '') {

            $parametros[
                ':imap_password'
            ] =
                cifrarCredencialCorreo(
                    $imapPassword
                );
        }


        $stmtActualizar->execute(
            $parametros
        );


    // ======================================================
    // CREAR CONFIGURACIÓN
    // ======================================================

    } else {


        $smtpPasswordCifrada =
            $smtpPassword !== ''
                ? cifrarCredencialCorreo(
                    $smtpPassword
                )
                : null;


        $imapPasswordCifrada =
            $imapPassword !== ''
                ? cifrarCredencialCorreo(
                    $imapPassword
                )
                : null;


        $sqlInsertar = "
            INSERT INTO configuracion_correo
            (
                nombre_configuracion,

                correo_remitente,
                nombre_remitente,
                correo_respuesta,

                smtp_host,
                smtp_puerto,
                smtp_seguridad,
                smtp_autenticacion,
                smtp_usuario,
                smtp_password,

                imap_host,
                imap_puerto,
                imap_seguridad,
                imap_usuario,
                imap_password,
                imap_carpeta,

                activo
            )
            VALUES
            (
                :nombre_configuracion,

                :correo_remitente,
                :nombre_remitente,
                :correo_respuesta,

                :smtp_host,
                :smtp_puerto,
                :smtp_seguridad,
                :smtp_autenticacion,
                :smtp_usuario,
                :smtp_password,

                :imap_host,
                :imap_puerto,
                :imap_seguridad,
                :imap_usuario,
                :imap_password,
                :imap_carpeta,

                :activo
            )
        ";


        $stmtInsertar =
            $conexion->prepare(
                $sqlInsertar
            );


        $stmtInsertar->execute([

            ':nombre_configuracion'
                => $nombreConfiguracion,

            ':correo_remitente'
                => $correoRemitente,

            ':nombre_remitente'
                => $nombreRemitente,

            ':correo_respuesta'
                => $correoRespuesta !== ''
                    ? $correoRespuesta
                    : null,

            ':smtp_host'
                => $smtpHost,

            ':smtp_puerto'
                => $smtpPuerto,

            ':smtp_seguridad'
                => $smtpSeguridad,

            ':smtp_autenticacion'
                => $smtpAutenticacion,

            ':smtp_usuario'
                => $smtpUsuario !== ''
                    ? $smtpUsuario
                    : null,

            ':smtp_password'
                => $smtpPasswordCifrada,

            ':imap_host'
                => $imapHost !== ''
                    ? $imapHost
                    : null,

            ':imap_puerto'
                => $imapPuerto,

            ':imap_seguridad'
                => $imapSeguridad,

            ':imap_usuario'
                => $imapUsuario !== ''
                    ? $imapUsuario
                    : null,

            ':imap_password'
                => $imapPasswordCifrada,

            ':imap_carpeta'
                => $imapCarpeta !== ''
                    ? $imapCarpeta
                    : 'INBOX',

            ':activo'
                => $activo

        ]);
    }


    // ======================================================
    // CONFIRMAR
    // ======================================================

    $conexion->commit();


    header(
        "Location: " .
        BASE_URL .
        "configuracion/configuracion_correo.php?" .
        http_build_query([
            'tipo'  => 'success',
            'texto' => 'Configuración de correo guardada correctamente.'
        ])
    );

    exit;


// ==========================================================
// ERROR
// ==========================================================

} catch (Throwable $e) {


    if (
        $conexion->inTransaction()
    ) {

        $conexion->rollBack();
    }


    header(
        "Location: " .
        BASE_URL .
        "configuracion/configuracion_correo.php?" .
        http_build_query([
            'tipo'  => 'error',
            'texto' => $e->getMessage()
        ])
    );

    exit;
}