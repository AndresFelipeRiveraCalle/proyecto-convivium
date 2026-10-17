<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// VALIDAR MÉTODO
// ==========================================================

if (
    $_SERVER["REQUEST_METHOD"]
    !== "POST"
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/pagos.php"
    );

    exit;
}


// ==========================================================
// FUNCIÓN DE REDIRECCIÓN
// ==========================================================

function redirigirPago(
    string $tipo,
    string $mensaje,
    int $idUnidad = 0
): void {

    $url =
        BASE_URL .
        "configuracion/registrar_pago.php";


    $parametros = [

        'tipo'
            => $tipo,

        'mensaje'
            => $mensaje

    ];


    if (
        $idUnidad > 0
    ) {

        $parametros[
            'id_unidad'
        ] =
            $idUnidad;
    }


    header(
        "Location: " .
        $url .
        "?" .
        http_build_query(
            $parametros
        )
    );

    exit;
}


// ==========================================================
// PROCESAR
// ==========================================================

try {


    // ======================================================
    // ID PAGO
    // Se usa cuando el formulario también sirve para editar.
    // ======================================================

    $idPago =
        isset(
            $_POST["id_pago"]
        )
            ? (int)$_POST["id_pago"]
            : 0;


    // ======================================================
    // DATOS DEL PAGO
    // ======================================================

    $idUnidad =
        isset(
            $_POST["id_unidad"]
        )
            ? (int)$_POST["id_unidad"]
            : 0;


    $fechaPago =
        trim(
            $_POST["fecha_pago"]
            ?? ""
        );


    $valor =
        isset(
            $_POST["valor"]
        )
            ? (float)$_POST["valor"]
            : 0;


    $medioPago =
        strtoupper(
            trim(
                $_POST["medio_pago"]
                ?? ""
            )
        );


    $origenPago =
        strtoupper(
            trim(
                $_POST["origen_pago"]
                ?? ""
            )
        );


    $referencia =
        trim(
            $_POST["referencia"]
            ?? ""
        );


    $referenciaExterna =
        trim(
            $_POST["referencia_externa"]
            ?? ""
        );


    $idExterno =
        trim(
            $_POST["id_externo"]
            ?? ""
        );


    $observaciones =
        trim(
            $_POST["observaciones"]
            ?? ""
        );


    // ======================================================
    // VALIDACIONES GENERALES
    // ======================================================

    if (
        $idUnidad <= 0
    ) {

        throw new Exception(
            "Debe seleccionar una unidad."
        );
    }


    if (
        $fechaPago === ""
    ) {

        throw new Exception(
            "Debe indicar la fecha del pago."
        );
    }


    // ======================================================
    // VALIDAR FECHA
    // ======================================================

    $fechaObjeto =
        DateTime::createFromFormat(
            'Y-m-d',
            $fechaPago
        );


    if (
        !$fechaObjeto
        ||
        $fechaObjeto->format(
            'Y-m-d'
        ) !== $fechaPago
    ) {

        throw new Exception(
            "La fecha del pago no es válida."
        );
    }


    // ======================================================
    // VALIDAR VALOR
    // ======================================================

    if (
        $valor <= 0
    ) {

        throw new Exception(
            "El valor del pago debe ser mayor que cero."
        );
    }


    $valor =
        round(
            $valor,
            2
        );


    // ======================================================
    // MEDIOS PERMITIDOS
    // ======================================================

    $mediosPermitidos = [

        "EFECTIVO",
        "TRANSFERENCIA",
        "CONSIGNACION",
        "PSE",
        "TARJETA",
        "OTRO"

    ];


    if (
        !in_array(
            $medioPago,
            $mediosPermitidos,
            true
        )
    ) {

        throw new Exception(
            "El medio de pago no es válido."
        );
    }


    // ======================================================
    // ORÍGENES PERMITIDOS
    // ======================================================

    $origenesPermitidos = [

        "MANUAL",
        "BANCO",
        "PASARELA"

    ];


    if (
        !in_array(
            $origenPago,
            $origenesPermitidos,
            true
        )
    ) {

        throw new Exception(
            "El origen del pago no es válido."
        );
    }


    // ======================================================
    // VALIDAR LONGITUDES
    // ======================================================

    if (
        mb_strlen(
            $referencia,
            'UTF-8'
        ) > 100
    ) {

        throw new Exception(
            "La referencia no puede superar los 100 caracteres."
        );
    }


    if (
        mb_strlen(
            $referenciaExterna,
            'UTF-8'
        ) > 150
    ) {

        throw new Exception(
            "La referencia externa no puede superar los 150 caracteres."
        );
    }


    if (
        mb_strlen(
            $idExterno,
            'UTF-8'
        ) > 150
    ) {

        throw new Exception(
            "El ID externo no puede superar los 150 caracteres."
        );
    }


    if (
        mb_strlen(
            $observaciones,
            'UTF-8'
        ) > 255
    ) {

        throw new Exception(
            "Las observaciones no pueden superar los 255 caracteres."
        );
    }


    // ======================================================
    // VERIFICAR UNIDAD
    // ======================================================

    $sqlUnidad = "

        SELECT
            id_unidad

        FROM unidades

        WHERE
            id_unidad =
                :id_unidad

            AND activo = 1

        LIMIT 1
    ";


    $stmtUnidad =
        $conexion->prepare(
            $sqlUnidad
        );


    $stmtUnidad->execute([

        ":id_unidad"
            => $idUnidad

    ]);


    $unidad =
        $stmtUnidad->fetch(
            PDO::FETCH_ASSOC
        );


    if (
        !$unidad
    ) {

        throw new Exception(
            "La unidad seleccionada no existe o está inactiva."
        );
    }


    // ======================================================
    // VERIFICAR REFERENCIA DUPLICADA
    // La referencia debe ser única dentro de la misma unidad.
    // ======================================================

    if (
        $referencia !== ""
    ) {

        $sqlDuplicado = "

            SELECT
                id_pago

            FROM pagos

            WHERE
                id_unidad =
                    :id_unidad

                AND referencia =
                    :referencia

                AND estado =
                    'REGISTRADO'

                AND id_pago <>
                    :id_pago

            LIMIT 1
        ";


        $stmtDuplicado =
            $conexion->prepare(
                $sqlDuplicado
            );


        $stmtDuplicado->execute([

            ":id_unidad"
                => $idUnidad,

            ":referencia"
                => $referencia,

            ":id_pago"
                => $idPago

        ]);


        $pagoExistente =
            $stmtDuplicado->fetch(
                PDO::FETCH_ASSOC
            );


        if (
            $pagoExistente
        ) {

            throw new Exception(
                "Ya existe un pago registrado para esta unidad con la referencia indicada."
            );
        }
    }


    // ======================================================
    // EDITAR PAGO
    // ======================================================

    if (
        $idPago > 0
    ) {


        // ==================================================
        // CONSULTAR PAGO ACTUAL
        // ==================================================

        $sqlPagoActual = "

            SELECT

                p.id_pago,
                p.id_unidad,
                p.valor,
                p.estado,

                COALESCE(
                    (
                        SELECT
                            SUM(
                                ap.valor_aplicado
                            )

                        FROM aplicaciones_pagos ap

                        WHERE
                            ap.id_pago =
                                p.id_pago
                    ),
                    0
                ) AS valor_aplicado,

                COALESCE(
                    (
                        SELECT
                            SUM(
                                sf.valor_original
                            )

                        FROM saldo_favor sf

                        WHERE
                            sf.id_pago =
                                p.id_pago

                            AND sf.estado <>
                                'ANULADO'
                    ),
                    0
                ) AS saldo_favor_generado

            FROM pagos p

            WHERE
                p.id_pago =
                    :id_pago

            LIMIT 1
        ";


        $stmtPagoActual =
            $conexion->prepare(
                $sqlPagoActual
            );


        $stmtPagoActual->execute([

            ":id_pago"
                => $idPago

        ]);


        $pagoActual =
            $stmtPagoActual->fetch(
                PDO::FETCH_ASSOC
            );


        if (
            !$pagoActual
        ) {

            throw new Exception(
                "El pago que intenta editar no existe."
            );
        }


        if (
            $pagoActual[
                'estado'
            ] !== 'REGISTRADO'
        ) {

            throw new Exception(
                "El pago está anulado y no puede ser editado."
            );
        }


        $valorAplicado =
            (float)$pagoActual[
                'valor_aplicado'
            ];


        $saldoFavorGenerado =
            (float)$pagoActual[
                'saldo_favor_generado'
            ];


        $tieneMovimientos =
            $valorAplicado > 0.009
            ||
            $saldoFavorGenerado > 0.009;


        // ==================================================
        // PROTEGER PAGO CON MOVIMIENTOS
        // No permite cambiar unidad o valor si ya fue usado.
        // ==================================================

        if (
            $tieneMovimientos
        ) {


            if (
                $idUnidad !==
                (int)$pagoActual[
                    'id_unidad'
                ]
            ) {

                throw new Exception(
                    "No puede cambiar la unidad porque el pago ya tiene aplicaciones o saldo a favor."
                );
            }


            if (
                abs(
                    $valor -
                    (float)$pagoActual[
                        'valor'
                    ]
                ) > 0.009
            ) {

                throw new Exception(
                    "No puede cambiar el valor porque el pago ya tiene aplicaciones o saldo a favor."
                );
            }
        }


        // ==================================================
        // ACTUALIZAR
        // ==================================================

        $sqlActualizar = "

            UPDATE pagos

            SET

                id_unidad =
                    :id_unidad,

                fecha_pago =
                    :fecha_pago,

                valor =
                    :valor,

                medio_pago =
                    :medio_pago,

                origen_pago =
                    :origen_pago,

                referencia =
                    :referencia,

                referencia_externa =
                    :referencia_externa,

                id_externo =
                    :id_externo,

                observaciones =
                    :observaciones

            WHERE
                id_pago =
                    :id_pago
        ";


        $stmtActualizar =
            $conexion->prepare(
                $sqlActualizar
            );


        $stmtActualizar->execute([

            ":id_unidad"
                => $idUnidad,

            ":fecha_pago"
                => $fechaPago,

            ":valor"
                => $valor,

            ":medio_pago"
                => $medioPago,

            ":origen_pago"
                => $origenPago,

            ":referencia"
                => $referencia !== ""
                    ? $referencia
                    : null,

            ":referencia_externa"
                => $referenciaExterna !== ""
                    ? $referenciaExterna
                    : null,

            ":id_externo"
                => $idExterno !== ""
                    ? $idExterno
                    : null,

            ":observaciones"
                => $observaciones !== ""
                    ? $observaciones
                    : null,

            ":id_pago"
                => $idPago

        ]);


        redirigirPago(
            'success',
            'Pago actualizado correctamente.',
            $idUnidad
        );
    }


    // ======================================================
    // NUEVO PAGO
    // ======================================================

    $sqlInsertar = "

        INSERT INTO pagos
        (
            id_unidad,
            fecha_pago,
            valor,
            medio_pago,
            origen_pago,
            estado_conciliacion,
            referencia,
            referencia_externa,
            id_externo,
            observaciones,
            estado
        )
        VALUES
        (
            :id_unidad,
            :fecha_pago,
            :valor,
            :medio_pago,
            :origen_pago,
            'PENDIENTE',
            :referencia,
            :referencia_externa,
            :id_externo,
            :observaciones,
            'REGISTRADO'
        )
    ";


    $stmtInsertar =
        $conexion->prepare(
            $sqlInsertar
        );


    $stmtInsertar->execute([

        ":id_unidad"
            => $idUnidad,

        ":fecha_pago"
            => $fechaPago,

        ":valor"
            => $valor,

        ":medio_pago"
            => $medioPago,

        ":origen_pago"
            => $origenPago,

        ":referencia"
            => $referencia !== ""
                ? $referencia
                : null,

        ":referencia_externa"
            => $referenciaExterna !== ""
                ? $referenciaExterna
                : null,

        ":id_externo"
            => $idExterno !== ""
                ? $idExterno
                : null,

        ":observaciones"
            => $observaciones !== ""
                ? $observaciones
                : null

    ]);


    $nuevoIdPago =
        (int)$conexion->lastInsertId();


    // ======================================================
    // REDIRECCIÓN
    // ======================================================

    redirigirPago(
        'success',
        'Pago registrado correctamente. ID de pago: #' .
        $nuevoIdPago,
        $idUnidad
    );


} catch (Throwable $e) {


    // ======================================================
    // ERROR
    // ======================================================

    redirigirPago(
        'error',
        $e->getMessage(),
        isset($idUnidad)
            ? (int)$idUnidad
            : 0
    );
}