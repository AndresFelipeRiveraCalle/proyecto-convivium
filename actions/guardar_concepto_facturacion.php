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
        "configuracion/conceptos_facturacion.php"
    );

    exit;
}


// ==========================================================
// FUNCIÓN DE REDIRECCIÓN
// ==========================================================

function redireccionarConceptos(
    $tipo,
    $mensaje
) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/conceptos_facturacion.php" .
        "?tipo=" .
        urlencode($tipo) .
        "&texto=" .
        urlencode($mensaje)
    );

    exit;
}


try {

    // ==========================================================
    // DATOS DEL FORMULARIO
    // ==========================================================

    $nombre = isset($_POST["nombre"])
        ? trim($_POST["nombre"])
        : "";

    $descripcion = !empty($_POST["descripcion"])
        ? trim($_POST["descripcion"])
        : null;

    $tipo_calculo = isset($_POST["tipo_calculo"])
        ? trim($_POST["tipo_calculo"])
        : "";

    $id_tipo_obligacion = !empty(
        $_POST["id_tipo_obligacion"]
    )
        ? (int) $_POST["id_tipo_obligacion"]
        : 0;

    $id_cuenta_contable = !empty(
        $_POST["id_cuenta_contable"]
    )
        ? (int) $_POST["id_cuenta_contable"]
        : null;

    $obligatorio = isset($_POST["obligatorio"])
        ? 1
        : 0;

    $estado = isset($_POST["estado"])
        ? (int) $_POST["estado"]
        : 1;


    // ==========================================================
    // VALIDAR NOMBRE
    // ==========================================================

    if ($nombre === "") {

        redireccionarConceptos(
            "warning",
            "Debe ingresar el nombre del concepto."
        );
    }


    if (mb_strlen($nombre) > 100) {

        redireccionarConceptos(
            "warning",
            "El nombre del concepto no puede superar los 100 caracteres."
        );
    }


    // ==========================================================
    // VALIDAR DESCRIPCIÓN
    // ==========================================================

    if (
        $descripcion !== null &&
        mb_strlen($descripcion) > 255
    ) {

        redireccionarConceptos(
            "warning",
            "La descripción no puede superar los 255 caracteres."
        );
    }


    // ==========================================================
    // VALIDAR TIPO DE CÁLCULO
    // ==========================================================

    $tiposPermitidos = [
        "FIJO",
        "METRO_CUADRADO",
        "COEFICIENTE",
        "PORCENTAJE"
    ];


    if (
        !in_array(
            $tipo_calculo,
            $tiposPermitidos,
            true
        )
    ) {

        redireccionarConceptos(
            "warning",
            "El tipo de cálculo seleccionado no es válido."
        );
    }


    // ==========================================================
    // VALIDAR TIPO DE OBLIGACIÓN
    // ==========================================================

    if ($id_tipo_obligacion <= 0) {

        redireccionarConceptos(
            "warning",
            "Debe seleccionar un tipo de obligación."
        );
    }


    $sqlTipoObligacion = "
        SELECT
            id_tipo_obligacion

        FROM tipos_obligacion

        WHERE id_tipo_obligacion =
            :id_tipo_obligacion

        LIMIT 1
    ";


    $stmtTipoObligacion = $conexion->prepare(
        $sqlTipoObligacion
    );


    $stmtTipoObligacion->execute([

        ":id_tipo_obligacion"
            => $id_tipo_obligacion

    ]);


    if (
        !$stmtTipoObligacion->fetch(
            PDO::FETCH_ASSOC
        )
    ) {

        redireccionarConceptos(
            "warning",
            "El tipo de obligación seleccionado no existe."
        );
    }


    // ==========================================================
    // VALIDAR CUENTA CONTABLE
    // ==========================================================

    if ($id_cuenta_contable !== null) {

        $sqlCuenta = "
            SELECT
                id_cuenta_contable

            FROM cuentas_contables

            WHERE id_cuenta_contable =
                :id_cuenta_contable

              AND estado = 1

            LIMIT 1
        ";


        $stmtCuenta = $conexion->prepare(
            $sqlCuenta
        );


        $stmtCuenta->execute([

            ":id_cuenta_contable"
                => $id_cuenta_contable

        ]);


        if (
            !$stmtCuenta->fetch(
                PDO::FETCH_ASSOC
            )
        ) {

            redireccionarConceptos(
                "warning",
                "La cuenta contable seleccionada no existe o está inactiva."
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
    // VERIFICAR CONCEPTO DUPLICADO
    // ==========================================================

    $sqlDuplicado = "
        SELECT
            id_concepto

        FROM conceptos_facturacion

        WHERE LOWER(TRIM(nombre)) =
              LOWER(TRIM(:nombre))

        LIMIT 1
    ";


    $stmtDuplicado = $conexion->prepare(
        $sqlDuplicado
    );


    $stmtDuplicado->execute([

        ":nombre"
            => $nombre

    ]);


    if (
        $stmtDuplicado->fetch(
            PDO::FETCH_ASSOC
        )
    ) {

        redireccionarConceptos(
            "warning",
            "Ya existe un concepto de facturación con ese nombre."
        );
    }


    // ==========================================================
    // INSERTAR CONCEPTO
    // ==========================================================

    $sql = "
        INSERT INTO conceptos_facturacion
        (
            nombre,
            descripcion,
            tipo_calculo,
            id_tipo_obligacion,
            id_cuenta_contable,
            obligatorio,
            estado
        )
        VALUES
        (
            :nombre,
            :descripcion,
            :tipo_calculo,
            :id_tipo_obligacion,
            :id_cuenta_contable,
            :obligatorio,
            :estado
        )
    ";


    $stmt = $conexion->prepare(
        $sql
    );


    $stmt->execute([

        ":nombre"
            => $nombre,

        ":descripcion"
            => $descripcion,

        ":tipo_calculo"
            => $tipo_calculo,

        ":id_tipo_obligacion"
            => $id_tipo_obligacion,

        ":id_cuenta_contable"
            => $id_cuenta_contable,

        ":obligatorio"
            => $obligatorio,

        ":estado"
            => $estado

    ]);


    // ==========================================================
    // MENSAJE DE ÉXITO
    // ==========================================================

    redireccionarConceptos(
        "success",
        "Concepto de facturación creado correctamente."
    );


} catch (PDOException $e) {

    // ==========================================================
    // REGISTRAR ERROR REAL
    // ==========================================================

    error_log(
        "Error guardando concepto de facturación: " .
        $e->getMessage()
    );


    // ==========================================================
    // MENSAJE AL USUARIO
    // ==========================================================

    redireccionarConceptos(
        "error",
        "No fue posible guardar el concepto de facturación."
    );
}