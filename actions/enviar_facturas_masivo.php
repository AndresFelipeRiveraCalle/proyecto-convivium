<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";
require_once ROOT_PATH . "/config/seguridad_correo.php";
require_once ROOT_PATH . "/config/generador_factura_pdf.php";
require_once ROOT_PATH . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;


// ==========================================================
// VALIDAR MÉTODO
// ==========================================================

if (
    $_SERVER['REQUEST_METHOD']
    !== 'POST'
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/envio_facturas.php"
    );

    exit;
}


// ==========================================================
// PERÍODO
// ==========================================================

$anio =
    isset($_POST['anio'])
        ? (int)$_POST['anio']
        : (int)date('Y');


$mes =
    isset($_POST['mes'])
        ? (int)$_POST['mes']
        : (int)date('n');


// ==========================================================
// FACTURAS SELECCIONADAS
// ==========================================================

$idsFacturas =
    $_POST['facturas']
    ?? [];


if (
    !is_array(
        $idsFacturas
    )
) {

    $idsFacturas = [];
}


$idsFacturas =
    array_values(
        array_unique(
            array_filter(
                array_map(
                    'intval',
                    $idsFacturas
                ),
                static function ($id) {

                    return $id > 0;
                }
            )
        )
    );


if (
    empty(
        $idsFacturas
    )
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/envio_facturas.php?" .
        http_build_query([
            'anio'  => $anio,
            'mes'   => $mes,
            'tipo'  => 'warning',
            'texto' => 'Debe seleccionar al menos una factura.'
        ])
    );

    exit;
}


// ==========================================================
// LÍMITE POR EJECUCIÓN
// ==========================================================

$limitePorEjecucion = 20;


if (
    count(
        $idsFacturas
    ) > $limitePorEjecucion
) {

    $idsFacturas =
        array_slice(
            $idsFacturas,
            0,
            $limitePorEjecucion
        );
}


// ==========================================================
// CONFIGURACIÓN SMTP
// ==========================================================

$sqlCorreo = "

    SELECT
        *

    FROM configuracion_correo

    WHERE
        activo = 1

    ORDER BY
        id_configuracion_correo DESC

    LIMIT 1
";


$stmtCorreo =
    $conexion->query(
        $sqlCorreo
    );


$configuracionCorreo =
    $stmtCorreo->fetch(
        PDO::FETCH_ASSOC
    );


if (
    !$configuracionCorreo
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/envio_facturas.php?" .
        http_build_query([
            'anio'  => $anio,
            'mes'   => $mes,
            'tipo'  => 'error',
            'texto' => 'No existe una configuración de correo activa.'
        ])
    );

    exit;
}


// ==========================================================
// CONTRASEÑA SMTP
// ==========================================================

try {

    $smtpPassword =
        descifrarCredencialCorreo(
            $configuracionCorreo[
                'smtp_password'
            ]
        );

} catch (Throwable $e) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/envio_facturas.php?" .
        http_build_query([
            'anio'  => $anio,
            'mes'   => $mes,
            'tipo'  => 'error',
            'texto' => 'No fue posible recuperar la credencial SMTP.'
        ])
    );

    exit;
}


// ==========================================================
// MESES
// ==========================================================

$meses = [

    1  => 'ENERO',
    2  => 'FEBRERO',
    3  => 'MARZO',
    4  => 'ABRIL',
    5  => 'MAYO',
    6  => 'JUNIO',
    7  => 'JULIO',
    8  => 'AGOSTO',
    9  => 'SEPTIEMBRE',
    10 => 'OCTUBRE',
    11 => 'NOVIEMBRE',
    12 => 'DICIEMBRE'

];


// ==========================================================
// CONTADORES
// ==========================================================

$facturasProcesadas = 0;

$facturasConEnvio = 0;

$facturasSinDestinatario = 0;

$facturasConError = 0;

$correosEnviados = 0;

$correosError = 0;


// ==========================================================
// PROCESAR FACTURAS
// ==========================================================

foreach (
    $idsFacturas
    as $idFactura
) {


    // ======================================================
    // FACTURA
    // ======================================================

    $sqlFactura = "

        SELECT

            f.id_factura,
            f.id_unidad,
            f.numero_factura,
            f.periodo,
            f.mes,
            f.fecha_vencimiento,
            f.estado,

            u.codigo AS unidad_codigo

        FROM facturas f

        INNER JOIN unidades u
            ON u.id_unidad =
               f.id_unidad

        WHERE
            f.id_factura =
                :id_factura

            AND f.estado <>
                'ANULADA'

        LIMIT 1
    ";


    $stmtFactura =
        $conexion->prepare(
            $sqlFactura
        );


    $stmtFactura->execute([

        ':id_factura'
            => $idFactura

    ]);


    $factura =
        $stmtFactura->fetch(
            PDO::FETCH_ASSOC
        );


    if (
        !$factura
    ) {

        $facturasConError++;

        continue;
    }


    $facturasProcesadas++;


    // ======================================================
    // DESTINATARIOS
    // Solo personas configuradas para recibir factura.
    // ======================================================

    $sqlDestinatarios = "

        SELECT DISTINCT

            u.id,
            u.nombres,
            u.apellidos,
            u.correo

        FROM residente r

        INNER JOIN usuario u
            ON u.id =
               r.usuario_id

        WHERE

            r.unidad_id =
                :id_unidad

            AND r.activo = 1

            AND r.fecha_hasta IS NULL

            AND r.recibe_factura = 1

            AND u.correo IS NOT NULL

            AND TRIM(
                u.correo
            ) <> ''

        ORDER BY
            u.apellidos,
            u.nombres
    ";


    $stmtDestinatarios =
        $conexion->prepare(
            $sqlDestinatarios
        );


    $stmtDestinatarios->execute([

        ':id_unidad'
            => (int)$factura[
                'id_unidad'
            ]

    ]);


    $destinatarios =
        $stmtDestinatarios->fetchAll(
            PDO::FETCH_ASSOC
        );


    // ======================================================
    // VALIDAR CORREOS
    // ======================================================

    $destinatariosValidos = [];


    foreach (
        $destinatarios
        as $destinatario
    ) {

        if (
            filter_var(
                $destinatario[
                    'correo'
                ],
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $destinatariosValidos[] =
                $destinatario;
        }
    }


    if (
        empty(
            $destinatariosValidos
        )
    ) {

        $facturasSinDestinatario++;

        continue;
    }


    // ======================================================
    // GENERAR PDF
    // ======================================================

    try {

        $pdf =
            generarPdfFactura(
                $idFactura
            );

    } catch (Throwable $e) {

        $facturasConError++;

        continue;
    }


    $nombrePdf =
        'Factura-' .
        preg_replace(
            '/[^A-Za-z0-9\-_]/',
            '-',
            $factura[
                'numero_factura'
            ]
        ) .
        '.pdf';


    $periodoTexto =
        (
            $meses[
                (int)$factura[
                    'mes'
                ]
            ]
            ?? ''
        )
        .
        ' '
        .
        $factura[
            'periodo'
        ];


    $facturaTuvoEnvio =
        false;


    // ======================================================
    // ENVIAR A DESTINATARIOS
    // ======================================================

    foreach (
        $destinatariosValidos
        as $destinatario
    ) {


        $nombreDestinatario =
            trim(
                $destinatario[
                    'nombres'
                ]
                .
                ' '
                .
                $destinatario[
                    'apellidos'
                ]
            );


        $correoDestinatario =
            trim(
                $destinatario[
                    'correo'
                ]
            );


        $asunto =
            'Factura ' .
            $factura[
                'numero_factura'
            ] .
            ' - ' .
            $periodoTexto;


        // ==================================================
        // REGISTRAR INTENTO
        // ==================================================

        $sqlEnvio = "

            INSERT INTO envios_facturas
            (
                id_factura,
                tipo_envio,
                destinatario,
                nombre_destinatario,
                asunto,
                estado,
                intentos,
                fecha_creacion
            )
            VALUES
            (
                :id_factura,
                'MASIVO',
                :destinatario,
                :nombre_destinatario,
                :asunto,
                'ENVIANDO',
                1,
                NOW()
            )
        ";


        $stmtEnvio =
            $conexion->prepare(
                $sqlEnvio
            );


        $stmtEnvio->execute([

            ':id_factura'
                => $idFactura,

            ':destinatario'
                => $correoDestinatario,

            ':nombre_destinatario'
                => $nombreDestinatario,

            ':asunto'
                => $asunto

        ]);


        $idEnvio =
            (int)$conexion->lastInsertId();


        try {


            // ==================================================
            // PHPMailer
            // ==================================================

            $mail =
                new PHPMailer(
                    true
                );


            $mail->isSMTP();


            $mail->Host =
                $configuracionCorreo[
                    'smtp_host'
                ];


            $mail->Port =
                (int)$configuracionCorreo[
                    'smtp_puerto'
                ];


            $mail->SMTPAuth =
                (int)$configuracionCorreo[
                    'smtp_autenticacion'
                ] === 1;


            if (
                $mail->SMTPAuth
            ) {

                $mail->Username =
                    $configuracionCorreo[
                        'smtp_usuario'
                    ];


                $mail->Password =
                    $smtpPassword;
            }


            // ==================================================
            // SEGURIDAD SMTP
            // ==================================================

            $seguridad =
                strtoupper(
                    trim(
                        $configuracionCorreo[
                            'smtp_seguridad'
                        ]
                        ?? 'TLS'
                    )
                );


            if (
                $seguridad === 'SSL'
            ) {

                $mail->SMTPSecure =
                    PHPMailer::ENCRYPTION_SMTPS;


                $mail->SMTPAutoTLS =
                    false;

            } elseif (
                $seguridad === 'NINGUNA'
            ) {

                $mail->SMTPSecure =
                    '';


                $mail->SMTPAutoTLS =
                    false;

            } else {

                $mail->SMTPSecure =
                    PHPMailer::ENCRYPTION_STARTTLS;


                $mail->SMTPAutoTLS =
                    true;
            }


            $mail->CharSet =
                'UTF-8';


            // ==================================================
            // REMITENTE
            // ==================================================

            $mail->setFrom(

                $configuracionCorreo[
                    'correo_remitente'
                ],

                $configuracionCorreo[
                    'nombre_remitente'
                ]

            );


            if (
                !empty(
                    $configuracionCorreo[
                        'correo_respuesta'
                    ]
                )
            ) {

                $mail->addReplyTo(
                    $configuracionCorreo[
                        'correo_respuesta'
                    ]
                );
            }


            // ==================================================
            // DESTINATARIO
            // ==================================================

            $mail->addAddress(
                $correoDestinatario,
                $nombreDestinatario
            );


            // ==================================================
            // PDF
            // ==================================================

            $mail->addStringAttachment(

                $pdf,

                $nombrePdf,

                'base64',

                'application/pdf'

            );


            // ==================================================
            // MENSAJE
            // ==================================================

            $mail->isHTML(
                true
            );


            $mail->Subject =
                $asunto;


            $mail->Body = '

                <div
                    style="
                        font-family:
                            Arial,
                            Helvetica,
                            sans-serif;

                        color:
                            #243447;

                        line-height:
                            1.6;

                        font-size:
                            14px;
                    "
                >

                    <p>
                        Buen día
                        <strong>' .
                        htmlspecialchars(
                            $nombreDestinatario,
                            ENT_QUOTES,
                            'UTF-8'
                        ) .
                        '</strong>.
                    </p>

                    <p>
                        Adjuntamos la cuenta de cobro
                        correspondiente al período
                        <strong>' .
                        htmlspecialchars(
                            $periodoTexto,
                            ENT_QUOTES,
                            'UTF-8'
                        ) .
                        '</strong>
                        de la unidad
                        <strong>' .
                        htmlspecialchars(
                            $factura[
                                'unidad_codigo'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        ) .
                        '</strong>.
                    </p>

                    <p>
                        Número de factura:
                        <strong>' .
                        htmlspecialchars(
                            $factura[
                                'numero_factura'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        ) .
                        '</strong>.
                    </p>

                    <p>
                        El documento se encuentra adjunto
                        en formato PDF.
                    </p>

                    <p>
                        Cordialmente,<br>

                        <strong>' .
                        htmlspecialchars(
                            $configuracionCorreo[
                                'nombre_remitente'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        ) .
                        '</strong>
                    </p>

                </div>
            ';


            $mail->AltBody =
                "Buen día " .
                $nombreDestinatario .
                ".\n\n" .
                "Adjuntamos la factura " .
                $factura[
                    'numero_factura'
                ] .
                " correspondiente al período " .
                $periodoTexto .
                ".\n\n" .
                "El documento se encuentra adjunto en formato PDF.";


            // ==================================================
            // ENVIAR
            // ==================================================

            $mail->send();


            $messageId =
                $mail->getLastMessageID();


            // ==================================================
            // ACTUALIZAR ÉXITO
            // ==================================================

            $sqlActualizarEnvio = "

                UPDATE envios_facturas

                SET

                    estado =
                        'ENVIADO',

                    fecha_envio =
                        NOW(),

                    message_id =
                        :message_id,

                    mensaje_error =
                        NULL

                WHERE
                    id_envio =
                        :id_envio
            ";


            $stmtActualizarEnvio =
                $conexion->prepare(
                    $sqlActualizarEnvio
                );


            $stmtActualizarEnvio->execute([

                ':message_id'
                    => $messageId !== ''
                        ? $messageId
                        : null,

                ':id_envio'
                    => $idEnvio

            ]);


            $correosEnviados++;

            $facturaTuvoEnvio =
                true;


        } catch (Throwable $e) {


            $correosError++;


            $mensajeError =
                mb_substr(
                    $e->getMessage(),
                    0,
                    65000,
                    'UTF-8'
                );


            $sqlError = "

                UPDATE envios_facturas

                SET

                    estado =
                        'ERROR',

                    mensaje_error =
                        :mensaje_error

                WHERE
                    id_envio =
                        :id_envio
            ";


            $stmtError =
                $conexion->prepare(
                    $sqlError
                );


            $stmtError->execute([

                ':mensaje_error'
                    => $mensajeError,

                ':id_envio'
                    => $idEnvio

            ]);
        }
    }


    if (
        $facturaTuvoEnvio
    ) {

        $facturasConEnvio++;

    } else {

        $facturasConError++;
    }
}


// ==========================================================
// RESULTADO
// ==========================================================

if (
    $facturasConEnvio > 0
    &&
    $facturasConError === 0
    &&
    $correosError === 0
) {

    $tipo =
        'success';


    $texto =
        'Envío masivo completado. ' .
        $facturasConEnvio .
        ' factura(s) procesada(s) y ' .
        $correosEnviados .
        ' correo(s) enviado(s).';


} elseif (
    $facturasConEnvio > 0
) {

    $tipo =
        'warning';


    $texto =
        'El envío terminó con novedades. ' .
        'Facturas enviadas: ' .
        $facturasConEnvio .
        '. Correos enviados: ' .
        $correosEnviados .
        '. Correos con error: ' .
        $correosError .
        '. Sin destinatario: ' .
        $facturasSinDestinatario .
        '.';


} else {

    $tipo =
        'error';


    $texto =
        'No fue posible enviar las facturas seleccionadas.';
}


// ==========================================================
// RETORNO
// ==========================================================

header(
    "Location: " .
    BASE_URL .
    "configuracion/envio_facturas.php?" .
    http_build_query([
        'anio'
            => $anio,

        'mes'
            => $mes,

        'tipo'
            => $tipo,

        'texto'
            => $texto
    ])
);

exit;