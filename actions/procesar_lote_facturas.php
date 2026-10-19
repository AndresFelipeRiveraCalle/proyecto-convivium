<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";
require_once ROOT_PATH . "/config/seguridad_correo.php";
require_once ROOT_PATH . "/config/generador_factura_pdf.php";
require_once ROOT_PATH . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;


// ==========================================================
// RESPUESTA JSON
// ==========================================================

header(
    'Content-Type: application/json; charset=UTF-8'
);


// ==========================================================
// FUNCIÓN RESPUESTA
// ==========================================================

function responderJson(
    bool $success,
    array $datos = []
): void {

    echo json_encode(
        array_merge(
            [
                'success' => $success
            ],
            $datos
        ),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


// ==========================================================
// VALIDAR MÉTODO
// ==========================================================

if (
    $_SERVER['REQUEST_METHOD']
    !== 'POST'
) {

    responderJson(
        false,
        [
            'mensaje'
                => 'Método no permitido.'
        ]
    );
}


// ==========================================================
// FACTURAS
// ==========================================================

$idsFacturas =
    $_POST[
        'facturas'
    ]
    ?? [];


if (
    !is_array(
        $idsFacturas
    )
) {

    $idsFacturas =
        [];
}


// ==========================================================
// NORMALIZAR IDS
// ==========================================================

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

    responderJson(
        false,
        [
            'mensaje'
                => 'No se recibieron facturas para procesar.'
        ]
    );
}


// ==========================================================
// SEGURIDAD DEL LOTE
// Máximo 10 facturas por petición.
// ==========================================================

if (
    count(
        $idsFacturas
    ) > 10
) {

    responderJson(
        false,
        [
            'mensaje'
                => 'El lote supera el máximo permitido de 10 facturas.'
        ]
    );
}


// ==========================================================
// CONFIGURACIÓN DE CORREO
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

    responderJson(
        false,
        [
            'mensaje'
                => 'No existe una configuración de correo activa.'
        ]
    );
}


// ==========================================================
// CREDENCIAL SMTP
// ==========================================================

try {

    $smtpPassword =
        descifrarCredencialCorreo(
            $configuracionCorreo[
                'smtp_password'
            ]
        );

} catch (Throwable $e) {

    responderJson(
        false,
        [
            'mensaje'
                => 'No fue posible recuperar la credencial SMTP.'
        ]
    );
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
// RESULTADO DEL LOTE
// ==========================================================

$resultado = [

    'facturas_recibidas'
        => count(
            $idsFacturas
        ),

    'facturas_procesadas'
        => 0,

    'facturas_enviadas'
        => 0,

    'facturas_sin_destinatario'
        => 0,

    'facturas_error'
        => 0,

    'correos_enviados'
        => 0,

    'correos_error'
        => 0,

    'detalle'
        => []

];


// ==========================================================
// PROCESAR FACTURAS
// ==========================================================

foreach (
    $idsFacturas
    as $idFactura
) {


    $detalleFactura = [

        'id_factura'
            => $idFactura,

        'numero_factura'
            => '',

        'estado'
            => 'PENDIENTE',

        'correos_enviados'
            => 0,

        'correos_error'
            => 0,

        'mensaje'
            => ''

    ];


    // ======================================================
    // CONSULTAR FACTURA
    // ======================================================

    $sqlFactura = "

        SELECT

            f.id_factura,
            f.id_unidad,
            f.numero_factura,
            f.periodo,
            f.mes,
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

        $resultado[
            'facturas_error'
        ]++;


        $detalleFactura[
            'estado'
        ] = 'ERROR';


        $detalleFactura[
            'mensaje'
        ] = 'La factura no existe o está anulada.';


        $resultado[
            'detalle'
        ][] = $detalleFactura;


        continue;
    }


    $resultado[
        'facturas_procesadas'
    ]++;


    $detalleFactura[
        'numero_factura'
    ] =
        $factura[
            'numero_factura'
        ];


    // ======================================================
    // DESTINATARIOS
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
    // VALIDAR DESTINATARIOS
    // ======================================================

    $destinatariosValidos =
        [];


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

        $resultado[
            'facturas_sin_destinatario'
        ]++;


        $detalleFactura[
            'estado'
        ] = 'SIN_DESTINATARIO';


        $detalleFactura[
            'mensaje'
        ] = 'No tiene destinatarios válidos.';


        $resultado[
            'detalle'
        ][] = $detalleFactura;


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

        $resultado[
            'facturas_error'
        ]++;


        $detalleFactura[
            'estado'
        ] = 'ERROR';


        $detalleFactura[
            'mensaje'
        ] = 'No fue posible generar el PDF.';


        $resultado[
            'detalle'
        ][] = $detalleFactura;


        continue;
    }


    // ======================================================
    // NOMBRE PDF
    // ======================================================

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


    // ======================================================
    // PERÍODO
    // ======================================================

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
    // ENVIAR DESTINATARIOS
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
            // SEGURIDAD
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
            // ACTUALIZAR ENVÍO
            // ==================================================

            $sqlActualizar = "

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


            $stmtActualizar =
                $conexion->prepare(
                    $sqlActualizar
                );


            $stmtActualizar->execute([

                ':message_id'
                    => $messageId !== ''
                        ? $messageId
                        : null,

                ':id_envio'
                    => $idEnvio

            ]);


            $resultado[
                'correos_enviados'
            ]++;


            $detalleFactura[
                'correos_enviados'
            ]++;


            $facturaTuvoEnvio =
                true;


        } catch (Throwable $e) {


            $resultado[
                'correos_error'
            ]++;


            $detalleFactura[
                'correos_error'
            ]++;


            $mensajeError =
                mb_substr(
                    $e->getMessage(),
                    0,
                    65000,
                    'UTF-8'
                );


            // ==================================================
            // REGISTRAR ERROR
            // ==================================================

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


    // ======================================================
    // RESULTADO DE FACTURA
    // ======================================================

    if (
        $facturaTuvoEnvio
    ) {

        $resultado[
            'facturas_enviadas'
        ]++;


        $detalleFactura[
            'estado'
        ] = 'ENVIADA';


        $detalleFactura[
            'mensaje'
        ] = 'Factura procesada correctamente.';

    } else {

        $resultado[
            'facturas_error'
        ]++;


        $detalleFactura[
            'estado'
        ] = 'ERROR';


        $detalleFactura[
            'mensaje'
        ] = 'No fue posible enviar la factura.';
    }


    $resultado[
        'detalle'
    ][] =
        $detalleFactura;

}


// ==========================================================
// RESPUESTA
// ==========================================================

responderJson(
    true,
    $resultado
);