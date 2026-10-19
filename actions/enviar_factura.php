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
        "configuracion/facturas_generadas.php"
    );

    exit;
}


// ==========================================================
// FACTURA
// ==========================================================

$idFactura =
    isset($_POST['id_factura'])
        ? (int)$_POST['id_factura']
        : 0;


if ($idFactura <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/facturas_generadas.php?" .
        http_build_query([
            'tipo'  => 'warning',
            'texto' => 'Debe seleccionar una factura válida.'
        ])
    );

    exit;
}


// ==========================================================
// DATOS DE LA FACTURA
// ==========================================================

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


if (!$factura) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/facturas_generadas.php?" .
        http_build_query([
            'tipo'  => 'error',
            'texto' => 'La factura seleccionada no existe.'
        ])
    );

    exit;
}


// ==========================================================
// DESTINATARIOS SELECCIONADOS
// Valida que pertenezcan realmente a la unidad.
// ==========================================================

$idsDestinatarios =
    $_POST[
        'destinatarios'
    ]
    ?? [];


if (
    !is_array(
        $idsDestinatarios
    )
) {

    $idsDestinatarios =
        [];
}


// ======================================================
// NORMALIZAR IDS
// ======================================================

$idsDestinatarios =
    array_values(
        array_unique(
            array_filter(
                array_map(
                    'intval',
                    $idsDestinatarios
                ),
                static function (
                    $id
                ) {

                    return $id > 0;
                }
            )
        )
    );


if (
    empty(
        $idsDestinatarios
    )
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/factura_detalle.php?" .
        http_build_query([
            'id'
                => $idFactura,

            'tipo'
                => 'warning',

            'texto'
                => 'Debe seleccionar al menos un destinatario.'
        ])
    );

    exit;
}


// ======================================================
// PLACEHOLDERS
// ======================================================

$placeholders =
    implode(
        ',',
        array_fill(
            0,
            count(
                $idsDestinatarios
            ),
            '?'
        )
    );


// ======================================================
// CONSULTAR DESTINATARIOS
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

        r.unidad_id = ?

        AND r.activo = 1

        AND r.fecha_hasta IS NULL

        AND u.id IN (
            $placeholders
        )

        AND u.correo IS NOT NULL

        AND TRIM(
            u.correo
        ) <> ''

";


// ======================================================
// PARÁMETROS
// ======================================================

$parametrosDestinatarios = [

    (int)$factura[
        'id_unidad'
    ]

];


foreach (
    $idsDestinatarios
    as $idUsuario
) {

    $parametrosDestinatarios[] =
        $idUsuario;
}


// ======================================================
// EJECUTAR
// ======================================================

$stmtDestinatarios =
    $conexion->prepare(
        $sqlDestinatarios
    );


$stmtDestinatarios->execute(
    $parametrosDestinatarios
);


$destinatarios =
    $stmtDestinatarios->fetchAll(
        PDO::FETCH_ASSOC
    );


if (
    empty(
        $destinatarios
    )
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/factura_detalle.php?" .
        http_build_query([
            'id'
                => $idFactura,

            'tipo'
                => 'warning',

            'texto'
                => 'No se encontraron destinatarios válidos para esta factura.'
        ])
    );

    exit;
}


// ======================================================
// VALIDAR CORREOS
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

    header(
        "Location: " .
        BASE_URL .
        "configuracion/factura_detalle.php?" .
        http_build_query([
            'id'
                => $idFactura,

            'tipo'
                => 'warning',

            'texto'
                => 'Los destinatarios seleccionados no tienen correos válidos.'
        ])
    );

    exit;
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


if (!$configuracionCorreo) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/factura_detalle.php?" .
        http_build_query([
            'id'    => $idFactura,
            'tipo'  => 'error',
            'texto' => 'No existe una configuración de correo activa.'
        ])
    );

    exit;
}


// ==========================================================
// GENERAR PDF
// ==========================================================

try {

    $pdf =
        generarPdfFactura(
            $idFactura
        );

} catch (Throwable $e) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/factura_detalle.php?" .
        http_build_query([
            'id'    => $idFactura,
            'tipo'  => 'error',
            'texto' => 'No fue posible generar el PDF: ' .
                       $e->getMessage()
        ])
    );

    exit;
}


// ==========================================================
// NOMBRE DEL PDF
// ==========================================================

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


// ==========================================================
// PERÍODO
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


$periodoTexto =
    (
        $meses[
            (int)$factura['mes']
        ]
        ?? ''
    )
    .
    ' '
    .
    $factura['periodo'];


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
        "configuracion/factura_detalle.php?" .
        http_build_query([
            'id'    => $idFactura,
            'tipo'  => 'error',
            'texto' => 'No fue posible recuperar la credencial SMTP.'
        ])
    );

    exit;
}


// ==========================================================
// CONTADORES
// ==========================================================

$enviados = 0;

$errores = 0;


// ==========================================================
// RESULTADO DE DESTINATARIOS
// Guarda los correos enviados y los que fallaron.
// ==========================================================

$correosEnviados = [];

$correosError = [];


// ==========================================================
// ENVIAR A CADA DESTINATARIO
// ==========================================================

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


    // ======================================================
    // REGISTRAR INTENTO
    // ======================================================

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
            'INDIVIDUAL',
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
        // ADJUNTO
        // ==================================================

        $mail->addStringAttachment(
            $pdf,
            $nombrePdf,
            'base64',
            'application/pdf'
        );


        // ==================================================
        // CORREO
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
                    Adjuntamos la cuenta de cobro correspondiente
                    al período
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
                    El documento se encuentra adjunto en formato PDF.
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
        // REGISTRAR ÉXITO
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


        $enviados++;
        $correosEnviados[] =  $correoDestinatario;

    } catch (Throwable $e) {


        $errores++;
        $correosError[] = $correoDestinatario;

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


// ==========================================================
// RESULTADO
// Muestra destinatarios enviados y errores.
// ==========================================================

if (
    $enviados > 0
    &&
    $errores === 0
) {

    $tipo =
        'success';


    $texto =
        'Factura enviada correctamente a: ' .
        implode(
            ', ',
            $correosEnviados
        );


} elseif (
    $enviados > 0
) {

    $tipo =
        'warning';


    $texto =
        'Enviados: ' .
        implode(
            ', ',
            $correosEnviados
        );


    if (
        !empty(
            $correosError
        )
    ) {

        $texto .=
            ' | Con error: ' .
            implode(
                ', ',
                $correosError
            );
    }


} else {

    $tipo =
        'error';


    $texto =
        'No fue posible enviar la factura.';


    if (
        !empty(
            $correosError
        )
    ) {

        $texto .=
            ' Destinatarios con error: ' .
            implode(
                ', ',
                $correosError
            );
    }
}


header(
    "Location: " .
    BASE_URL .
    "configuracion/factura_detalle.php?" .
    http_build_query([
        'id'
            => $idFactura,

        'tipo'
            => $tipo,

        'texto'
            => $texto
    ])
);

exit;