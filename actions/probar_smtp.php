<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";
require_once ROOT_PATH . "/config/seguridad_correo.php";
require_once ROOT_PATH . "/vendor/autoload.php";


use PHPMailer\PHPMailer\PHPMailer;


// ==========================================================
// VALIDAR MÉTODO
// La prueba solo se ejecuta mediante POST.
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
// ID CONFIGURACIÓN
// Obtiene la configuración que se desea probar.
// ==========================================================

$idConfiguracion =
    isset($_POST['id_configuracion_correo'])
        ? (int)$_POST['id_configuracion_correo']
        : 0;


if ($idConfiguracion <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/configuracion_correo.php?" .
        http_build_query([
            'tipo'  => 'warning',
            'texto' => 'Primero debe guardar la configuración de correo.'
        ])
    );

    exit;
}


// ==========================================================
// OBTENER CONFIGURACIÓN
// Carga los datos SMTP almacenados.
// ==========================================================

$sqlConfiguracion = "
    SELECT
        id_configuracion_correo,

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

        activo

    FROM configuracion_correo

    WHERE
        id_configuracion_correo =
            :id_configuracion_correo

    LIMIT 1
";


$stmtConfiguracion =
    $conexion->prepare(
        $sqlConfiguracion
    );


$stmtConfiguracion->execute([

    ':id_configuracion_correo'
        => $idConfiguracion

]);


$configuracion =
    $stmtConfiguracion->fetch(
        PDO::FETCH_ASSOC
    );


if (!$configuracion) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/configuracion_correo.php?" .
        http_build_query([
            'tipo'  => 'error',
            'texto' => 'La configuración de correo no existe.'
        ])
    );

    exit;
}


// ==========================================================
// VALIDAR CONFIGURACIÓN
// Comprueba que existan los datos mínimos para SMTP.
// ==========================================================

if (
    empty($configuracion['smtp_host'])
    ||
    empty($configuracion['smtp_puerto'])
    ||
    empty($configuracion['correo_remitente'])
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/configuracion_correo.php?" .
        http_build_query([
            'tipo'  => 'warning',
            'texto' => 'La configuración SMTP está incompleta.'
        ])
    );

    exit;
}


// ==========================================================
// DESCIFRAR CONTRASEÑA
// Recupera la contraseña únicamente durante la conexión.
// ==========================================================

$smtpPassword = null;


try {

    if (
        !empty(
            $configuracion[
                'smtp_password'
            ]
        )
    ) {

        $smtpPassword =
            descifrarCredencialCorreo(
                $configuracion[
                    'smtp_password'
                ]
            );
    }


// ==========================================================
// CREAR PHPMailer
// Prepara la conexión con el servidor SMTP.
// ==========================================================

    $mail =
        new PHPMailer(true);


    $mail->isSMTP();


    // ======================================================
    // SERVIDOR
    // ======================================================

    $mail->Host =
        trim(
            $configuracion[
                'smtp_host'
            ]
        );


    $mail->Port =
        (int)$configuracion[
            'smtp_puerto'
        ];


    // ======================================================
    // AUTENTICACIÓN
    // ======================================================

    $mail->SMTPAuth =
        (int)$configuracion[
            'smtp_autenticacion'
        ] === 1;


    if ($mail->SMTPAuth) {

        if (
            empty(
                $configuracion[
                    'smtp_usuario'
                ]
            )
        ) {

            throw new RuntimeException(
                'La autenticación SMTP está activa pero no existe un usuario configurado.'
            );
        }


        if (
            $smtpPassword === null
            ||
            $smtpPassword === ''
        ) {

            throw new RuntimeException(
                'La autenticación SMTP está activa pero no existe una contraseña configurada.'
            );
        }


        $mail->Username =
            $configuracion[
                'smtp_usuario'
            ];


        $mail->Password =
            $smtpPassword;
    }


    // ======================================================
    // SEGURIDAD SMTP
    // Configura TLS, SSL o conexión sin cifrado.
    // ======================================================

    $seguridad =
        strtoupper(
            trim(
                $configuracion[
                    'smtp_seguridad'
                ]
                ?? 'TLS'
            )
        );


    switch ($seguridad) {


        case 'SSL':

            $mail->SMTPSecure =
                PHPMailer::ENCRYPTION_SMTPS;

            $mail->SMTPAutoTLS =
                false;

            break;


        case 'NINGUNA':

            $mail->SMTPSecure =
                '';

            $mail->SMTPAutoTLS =
                false;

            break;


        case 'TLS':

        default:

            $mail->SMTPSecure =
                PHPMailer::ENCRYPTION_STARTTLS;

            $mail->SMTPAutoTLS =
                true;

            break;
    }


    // ======================================================
    // CODIFICACIÓN
    // ======================================================

    $mail->CharSet =
        'UTF-8';


    $mail->Encoding =
        'base64';


    // ======================================================
    // REMITENTE
    // ======================================================

    $mail->setFrom(
        $configuracion[
            'correo_remitente'
        ],
        $configuracion[
            'nombre_remitente'
        ]
        ?: 'Convivium'
    );


    // ======================================================
    // RESPUESTAS
    // ======================================================

    if (
        !empty(
            $configuracion[
                'correo_respuesta'
            ]
        )
        &&
        filter_var(
            $configuracion[
                'correo_respuesta'
            ],
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $mail->addReplyTo(
            $configuracion[
                'correo_respuesta'
            ]
        );
    }


    // ======================================================
    // DESTINATARIO DE PRUEBA
    // Se envía al mismo correo configurado.
    // ======================================================

    $mail->addAddress(
        $configuracion[
            'correo_remitente'
        ]
    );


    // ======================================================
    // CONTENIDO
    // ======================================================

    $mail->isHTML(true);


    $mail->Subject =
        'Prueba de correo - Convivium';


    $mail->Body = '

        <div
            style="
                font-family:
                    Arial,
                    Helvetica,
                    sans-serif;

                font-size:
                    14px;

                color:
                    #333333;

                line-height:
                    1.6;
            "
        >

            <h2
                style="
                    margin-bottom:
                        10px;
                "
            >
                Prueba de configuración de correo
            </h2>

            <p>
                Este mensaje fue enviado automáticamente
                desde <strong>Convivium</strong>.
            </p>

            <p>
                La conexión con el servidor SMTP
                se realizó correctamente.
            </p>

            <p>
                <strong>
                    Configuración:
                </strong>

                ' .
                htmlspecialchars(
                    $configuracion[
                        'nombre_configuracion'
                    ],
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '
            </p>

            <p>
                <strong>
                    Servidor SMTP:
                </strong>

                ' .
                htmlspecialchars(
                    $configuracion[
                        'smtp_host'
                    ],
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '
            </p>

            <p>
                <strong>
                    Puerto:
                </strong>

                ' .
                (int)$configuracion[
                    'smtp_puerto'
                ]
                . '
            </p>

            <p>
                <strong>
                    Seguridad:
                </strong>

                ' .
                htmlspecialchars(
                    $seguridad,
                    ENT_QUOTES,
                    'UTF-8'
                )
                . '
            </p>

            <hr>

            <p
                style="
                    font-size:
                        12px;

                    color:
                        #777777;
                "
            >
                Este es un mensaje de prueba.
                No requiere respuesta.
            </p>

        </div>
    ';


    $mail->AltBody =
        "Prueba de configuración de correo de Convivium.\n\n" .
        "La conexión con el servidor SMTP se realizó correctamente.\n\n" .
        "Servidor: " .
        $configuracion['smtp_host'] .
        "\nPuerto: " .
        $configuracion['smtp_puerto'] .
        "\nSeguridad: " .
        $seguridad;


    // ======================================================
    // ENVIAR
    // ======================================================

    $mail->send();


    // ======================================================
    // GUARDAR RESULTADO
    // ======================================================

    $resultado =
        'Conexión SMTP correcta. Correo de prueba enviado.';


    $sqlActualizar = "
        UPDATE configuracion_correo

        SET
            ultima_prueba_smtp =
                NOW(),

            resultado_prueba_smtp =
                :resultado

        WHERE
            id_configuracion_correo =
                :id_configuracion_correo
    ";


    $stmtActualizar =
        $conexion->prepare(
            $sqlActualizar
        );


    $stmtActualizar->execute([

        ':resultado'
            => $resultado,

        ':id_configuracion_correo'
            => $idConfiguracion

    ]);


    // ======================================================
    // REDIRECCIONAR
    // ======================================================

    header(
        "Location: " .
        BASE_URL .
        "configuracion/configuracion_correo.php?" .
        http_build_query([
            'tipo'  => 'success',
            'texto' => 'Prueba SMTP correcta. Se envió un correo de prueba a ' .
                       $configuracion['correo_remitente'] . '.'
        ])
    );

    exit;


// ==========================================================
// ERROR SMTP
// Guarda el error para poder diagnosticarlo.
// ==========================================================

} catch (Throwable $e) {


    $mensajeError =
        trim(
            $e->getMessage()
        );


    if ($mensajeError === '') {

        $mensajeError =
            'Error desconocido durante la prueba SMTP.';
    }


    // La columna es VARCHAR(255), evitamos excederla.
    $resultadoBD =
        mb_substr(
            'ERROR: ' .
            $mensajeError,
            0,
            255,
            'UTF-8'
        );


    try {

        $sqlActualizarError = "
            UPDATE configuracion_correo

            SET
                ultima_prueba_smtp =
                    NOW(),

                resultado_prueba_smtp =
                    :resultado

            WHERE
                id_configuracion_correo =
                    :id_configuracion_correo
        ";


        $stmtActualizarError =
            $conexion->prepare(
                $sqlActualizarError
            );


        $stmtActualizarError->execute([

            ':resultado'
                => $resultadoBD,

            ':id_configuracion_correo'
                => $idConfiguracion

        ]);

    } catch (Throwable $errorBD) {

        // No interrumpimos el mensaje principal
        // si falla el registro del diagnóstico.
    }


    header(
        "Location: " .
        BASE_URL .
        "configuracion/configuracion_correo.php?" .
        http_build_query([
            'tipo'  => 'error',
            'texto' => 'No fue posible enviar el correo de prueba: ' .
                       $mensajeError
        ])
    );

    exit;
}