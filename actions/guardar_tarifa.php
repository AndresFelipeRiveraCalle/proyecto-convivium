<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// VALIDAR MÉTODO
// ==========================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/tarifas.php"
    );

    exit;
}


// ==========================================================
// FUNCIÓN DE REDIRECCIÓN
// ==========================================================

function redireccionarTarifas(
    $tipo,
    $mensaje
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/tarifas.php" .
        "?tipo=" .
        urlencode($tipo) .
        "&texto=" .
        urlencode($mensaje)
    );

    exit;
}


// ==========================================================
// FUNCIÓN PARA VALIDAR FECHAS
// ==========================================================

function fechaValida($fecha)
{
    if ($fecha === null || $fecha === '') {
        return false;
    }

    $fechaObj = DateTime::createFromFormat(
        'Y-m-d',
        $fecha
    );

    $errores = DateTime::getLastErrors();

    if (!$fechaObj) {
        return false;
    }

    if (
        $errores !== false &&
        (
            $errores['warning_count'] > 0 ||
            $errores['error_count'] > 0
        )
    ) {
        return false;
    }

    return $fechaObj->format('Y-m-d') === $fecha;
}


try {

    // ==========================================================
    // DATOS DEL FORMULARIO
    // ==========================================================

    $id_concepto = !empty(
        $_POST["id_concepto"]
    )
        ? (int) $_POST["id_concepto"]
        : 0;


    $id_tipo_config = !empty(
        $_POST["id_tipo_config"]
    )
        ? (int) $_POST["id_tipo_config"]
        : 0;


    $nombre = !empty(
        $_POST["nombre"]
    )
        ? trim($_POST["nombre"])
        : null;


    $valorTexto = isset(
        $_POST["valor"]
    )
        ? trim($_POST["valor"])
        : "";


    $fecha_inicio = !empty(
        $_POST["fecha_inicio"]
    )
        ? trim($_POST["fecha_inicio"])
        : null;


    $fecha_fin = !empty(
        $_POST["fecha_fin"]
    )
        ? trim($_POST["fecha_fin"])
        : null;


    $estado = isset(
        $_POST["estado"]
    )
        ? (int) $_POST["estado"]
        : 1;


    $observaciones = !empty(
        $_POST["observaciones"]
    )
        ? trim($_POST["observaciones"])
        : null;


    // ==========================================================
    // VALIDAR CONCEPTO
    // ==========================================================

    if ($id_concepto <= 0) {

        redireccionarTarifas(
            "warning",
            "Debe seleccionar un concepto de facturación."
        );
    }


    // ==========================================================
    // VALIDAR TIPO DE UNIDAD
    // ==========================================================

    if ($id_tipo_config <= 0) {

        redireccionarTarifas(
            "warning",
            "Debe seleccionar un tipo de unidad."
        );
    }


    // ==========================================================
    // VALIDAR NOMBRE
    // ==========================================================

    if (
        $nombre !== null &&
        mb_strlen($nombre) > 150
    ) {

        redireccionarTarifas(
            "warning",
            "El nombre de la tarifa no puede superar los 150 caracteres."
        );
    }


    // ==========================================================
    // VALIDAR VALOR
    // ==========================================================

    if (
        $valorTexto === '' ||
        !is_numeric($valorTexto)
    ) {

        redireccionarTarifas(
            "warning",
            "Debe ingresar un valor válido para la tarifa."
        );
    }


    $valor = (float)$valorTexto;


    if ($valor < 0) {

        redireccionarTarifas(
            "warning",
            "El valor de la tarifa no puede ser negativo."
        );
    }


    // ==========================================================
    // VALIDAR FECHA DE INICIO
    // ==========================================================

    if (
        $fecha_inicio === null ||
        !fechaValida($fecha_inicio)
    ) {

        redireccionarTarifas(
            "warning",
            "La fecha de inicio de la tarifa no es válida."
        );
    }


    // ==========================================================
    // VALIDAR FECHA DE FINALIZACIÓN
    // ==========================================================

    if ($fecha_fin !== null) {

        if (!fechaValida($fecha_fin)) {

            redireccionarTarifas(
                "warning",
                "La fecha de finalización de la tarifa no es válida."
            );
        }


        if ($fecha_fin < $fecha_inicio) {

            redireccionarTarifas(
                "warning",
                "La fecha de finalización no puede ser anterior a la fecha de inicio."
            );
        }
    }


    // ==========================================================
    // VALIDAR ESTADO
    // ==========================================================

    if (
        !in_array(
            $estado,
            [0, 1],
            true
        )
    ) {

        $estado = 1;
    }


    // ==========================================================
    // VALIDAR OBSERVACIONES
    // ==========================================================

    if (
        $observaciones !== null &&
        mb_strlen($observaciones) > 255
    ) {

        redireccionarTarifas(
            "warning",
            "Las observaciones no pueden superar los 255 caracteres."
        );
    }


    // ==========================================================
    // VALIDAR QUE EL CONCEPTO EXISTA Y ESTÉ ACTIVO
    // ==========================================================

    $sqlConcepto = "
        SELECT
            id_concepto,
            nombre,
            tipo_calculo

        FROM conceptos_facturacion

        WHERE id_concepto = :id_concepto
        AND estado = 1

        LIMIT 1
    ";


    $stmtConcepto = $conexion->prepare(
        $sqlConcepto
    );


    $stmtConcepto->execute([

        ':id_concepto'
            => $id_concepto

    ]);


    $concepto = $stmtConcepto->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$concepto) {

        redireccionarTarifas(
            "warning",
            "El concepto seleccionado no existe o está inactivo."
        );
    }


    // ==========================================================
    // VALIDAR TIPO DE UNIDAD
    // ==========================================================

    $sqlTipoUnidad = "
        SELECT
            id_tipo_config,
            nombre_grupo

        FROM detalle_tipos_unidad

        WHERE id_tipo_config = :id_tipo_config
        AND activo = 1

        LIMIT 1
    ";


    $stmtTipoUnidad = $conexion->prepare(
        $sqlTipoUnidad
    );


    $stmtTipoUnidad->execute([

        ':id_tipo_config'
            => $id_tipo_config

    ]);


    $tipoUnidad = $stmtTipoUnidad->fetch(
        PDO::FETCH_ASSOC
    );


    if (!$tipoUnidad) {

        redireccionarTarifas(
            "warning",
            "El tipo de unidad seleccionado no existe o está inactivo."
        );
    }


    // ==========================================================
    // VALIDAR VIGENCIAS SUPERPUESTAS
    // ==========================================================
    //
    // Se considera superposición cuando:
    //
    // tarifa existente.fecha_inicio <= nueva.fecha_fin
    //
    // Y
    //
    // tarifa existente.fecha_fin >= nueva.fecha_inicio
    //
    // Los NULL se interpretan como vigencia abierta.
    //
    // ==========================================================

    $sqlSolapamiento = "
        SELECT
            id_tarifa,
            fecha_inicio,
            fecha_fin

        FROM tarifas_facturacion

        WHERE id_concepto = :id_concepto
        AND id_tipo_config = :id_tipo_config
        AND estado = 1

        AND fecha_inicio <=
            COALESCE(
                :fecha_fin,
                '9999-12-31'
            )

        AND (
            fecha_fin IS NULL
            OR fecha_fin >= :fecha_inicio
        )

        LIMIT 1
    ";


    $stmtSolapamiento = $conexion->prepare(
        $sqlSolapamiento
    );


    $stmtSolapamiento->execute([

        ':id_concepto'
            => $id_concepto,

        ':id_tipo_config'
            => $id_tipo_config,

        ':fecha_fin'
            => $fecha_fin,

        ':fecha_inicio'
            => $fecha_inicio

    ]);


    $tarifaExistente = $stmtSolapamiento->fetch(
        PDO::FETCH_ASSOC
    );


    if ($tarifaExistente) {

        redireccionarTarifas(
            "warning",
            "Ya existe una tarifa activa para este concepto y tipo de unidad cuya vigencia se cruza con las fechas seleccionadas."
        );
    }


    // ==========================================================
    // INSERTAR TARIFA
    // ==========================================================

    $sqlInsertar = "
        INSERT INTO tarifas_facturacion
        (
            id_concepto,
            id_tipo_config,
            nombre,
            valor,
            fecha_inicio,
            fecha_fin,
            estado,
            observaciones
        )
        VALUES
        (
            :id_concepto,
            :id_tipo_config,
            :nombre,
            :valor,
            :fecha_inicio,
            :fecha_fin,
            :estado,
            :observaciones
        )
    ";


    $stmtInsertar = $conexion->prepare(
        $sqlInsertar
    );


    $stmtInsertar->execute([

        ':id_concepto'
            => $id_concepto,

        ':id_tipo_config'
            => $id_tipo_config,

        ':nombre'
            => $nombre,

        ':valor'
            => $valor,

        ':fecha_inicio'
            => $fecha_inicio,

        ':fecha_fin'
            => $fecha_fin,

        ':estado'
            => $estado,

        ':observaciones'
            => $observaciones

    ]);


    // ==========================================================
    // CONFIRMACIÓN
    // ==========================================================

    redireccionarTarifas(
        "success",
        "La tarifa de facturación fue creada correctamente."
    );


} catch (PDOException $e) {

    // ==========================================================
    // REGISTRAR ERROR REAL
    // ==========================================================

    error_log(
        "Error guardando tarifa de facturación: " .
        $e->getMessage()
    );


    // ==========================================================
    // MENSAJE AL USUARIO
    // ==========================================================

    redireccionarTarifas(
        "error",
        "No fue posible guardar la tarifa de facturación."
    );
}