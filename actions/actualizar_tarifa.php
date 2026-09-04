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
// VALIDAR FECHA
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

    $id_tarifa = isset(
        $_POST['id_tarifa']
    )
        ? (int)$_POST['id_tarifa']
        : 0;


    $id_concepto = isset(
        $_POST['id_concepto']
    )
        ? (int)$_POST['id_concepto']
        : 0;


    $id_tipo_config = isset(
        $_POST['id_tipo_config']
    )
        ? (int)$_POST['id_tipo_config']
        : 0;


    $nombre = !empty(
        $_POST['nombre']
    )
        ? trim($_POST['nombre'])
        : null;


    $valorTexto = isset(
        $_POST['valor']
    )
        ? trim($_POST['valor'])
        : "";


    $fecha_inicio = !empty(
        $_POST['fecha_inicio']
    )
        ? trim($_POST['fecha_inicio'])
        : null;


    $fecha_fin = !empty(
        $_POST['fecha_fin']
    )
        ? trim($_POST['fecha_fin'])
        : null;


    $estado = isset(
        $_POST['estado']
    )
        ? (int)$_POST['estado']
        : 1;


    $observaciones = !empty(
        $_POST['observaciones']
    )
        ? trim($_POST['observaciones'])
        : null;


    // ==========================================================
    // VALIDAR ID TARIFA
    // ==========================================================

    if ($id_tarifa <= 0) {

        redireccionarTarifas(
            "warning",
            "La tarifa seleccionada no es válida."
        );
    }


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
    // VALIDAR FECHA INICIO
    // ==========================================================

    if (
        $fecha_inicio === null ||
        !fechaValida($fecha_inicio)
    ) {

        redireccionarTarifas(
            "warning",
            "La fecha de inicio no es válida."
        );
    }


    // ==========================================================
    // VALIDAR FECHA FIN
    // ==========================================================

    if ($fecha_fin !== null) {

        if (!fechaValida($fecha_fin)) {

            redireccionarTarifas(
                "warning",
                "La fecha de finalización no es válida."
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
    // VALIDAR QUE LA TARIFA EXISTA
    // ==========================================================

    $sqlTarifa = "
        SELECT
            id_tarifa

        FROM tarifas_facturacion

        WHERE id_tarifa = :id_tarifa

        LIMIT 1
    ";


    $stmtTarifa = $conexion->prepare(
        $sqlTarifa
    );


    $stmtTarifa->execute([

        ':id_tarifa'
            => $id_tarifa

    ]);


    if (!$stmtTarifa->fetch(PDO::FETCH_ASSOC)) {

        redireccionarTarifas(
            "warning",
            "La tarifa que intenta editar no existe."
        );
    }


    // ==========================================================
    // VALIDAR CONCEPTO ACTIVO
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


    if (!$stmtConcepto->fetch(PDO::FETCH_ASSOC)) {

        redireccionarTarifas(
            "warning",
            "El concepto seleccionado no existe o está inactivo."
        );
    }


    // ==========================================================
    // VALIDAR TIPO DE UNIDAD ACTIVO
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


    if (!$stmtTipoUnidad->fetch(PDO::FETCH_ASSOC)) {

        redireccionarTarifas(
            "warning",
            "El tipo de unidad seleccionado no existe o está inactivo."
        );
    }


    // ==========================================================
    // VALIDAR VIGENCIAS SUPERPUESTAS
    // ==========================================================
    //
    // Importante:
    //
    // Excluimos la tarifa que actualmente estamos editando.
    //
    // Solo validamos contra otras tarifas ACTIVAS.
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

        AND id_tarifa <> :id_tarifa

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

        ':id_tarifa'
            => $id_tarifa,

        ':fecha_fin'
            => $fecha_fin,

        ':fecha_inicio'
            => $fecha_inicio

    ]);


    $tarifaExistente = $stmtSolapamiento->fetch(
        PDO::FETCH_ASSOC
    );


    // ==========================================================
    // SOLO BLOQUEAR SOLAPAMIENTO SI LA TARIFA QUEDA ACTIVA
    // ==========================================================

    if (
        $estado === 1 &&
        $tarifaExistente
    ) {

        redireccionarTarifas(
            "warning",
            "Ya existe otra tarifa activa para este concepto y tipo de unidad cuya vigencia se cruza con las fechas seleccionadas."
        );
    }


    // ==========================================================
    // ACTUALIZAR TARIFA
    // ==========================================================

    $sqlActualizar = "
        UPDATE tarifas_facturacion

        SET
            id_concepto = :id_concepto,
            id_tipo_config = :id_tipo_config,
            nombre = :nombre,
            valor = :valor,
            fecha_inicio = :fecha_inicio,
            fecha_fin = :fecha_fin,
            estado = :estado,
            observaciones = :observaciones

        WHERE id_tarifa = :id_tarifa
    ";


    $stmtActualizar = $conexion->prepare(
        $sqlActualizar
    );


    $stmtActualizar->execute([

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
            => $observaciones,

        ':id_tarifa'
            => $id_tarifa

    ]);


    // ==========================================================
    // CONFIRMACIÓN
    // ==========================================================

    redireccionarTarifas(
        "success",
        "La tarifa de facturación fue actualizada correctamente."
    );


} catch (PDOException $e) {

    // ==========================================================
    // REGISTRAR ERROR
    // ==========================================================

    error_log(
        "Error actualizando tarifa de facturación: " .
        $e->getMessage()
    );


    // ==========================================================
    // MENSAJE AL USUARIO
    // ==========================================================

    redireccionarTarifas(
        "error",
        "No fue posible actualizar la tarifa de facturación."
    );
}