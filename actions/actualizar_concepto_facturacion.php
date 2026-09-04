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


try {

    // ==========================================================
    // DATOS DEL FORMULARIO
    // ==========================================================

    $id_concepto = isset($_POST["id_concepto"])
        ? (int) $_POST["id_concepto"]
        : 0;

    $nombre = isset($_POST["nombre"])
        ? trim($_POST["nombre"])
        : "";

    $descripcion = !empty($_POST["descripcion"])
        ? trim($_POST["descripcion"])
        : null;

    $tipo_calculo = isset($_POST["tipo_calculo"])
        ? trim($_POST["tipo_calculo"])
        : "FIJO";

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
        ? (int) $_POST["obligatorio"]
        : 0;

    $estado = isset($_POST["estado"])
        ? (int) $_POST["estado"]
        : 1;


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


    // ==========================================================
    // VALIDAR ID
    // ==========================================================

    if ($id_concepto <= 0) {

        redireccionarConceptos(
            "warning",
            "El concepto de facturación no es válido."
        );
    }


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


    // ==========================================================
    // VALIDAR OBLIGATORIO
    // ==========================================================

    if (
        !in_array(
            $obligatorio,
            [0, 1],
            true
        )
    ) {

        redireccionarConceptos(
            "warning",
            "El valor de obligatorio no es válido."
        );
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

        redireccionarConceptos(
            "warning",
            "El estado seleccionado no es válido."
        );
    }


    // ==========================================================
    // VERIFICAR QUE EL CONCEPTO EXISTA
    // ==========================================================

    $sqlExisteConcepto = "
        SELECT
            id_concepto

        FROM conceptos_facturacion

        WHERE id_concepto = :id_concepto

        LIMIT 1
    ";


    $stmtExisteConcepto = $conexion->prepare(
        $sqlExisteConcepto
    );


    $stmtExisteConcepto->execute([

        ":id_concepto"
            => $id_concepto

    ]);


    if (
        !$stmtExisteConcepto->fetch(
            PDO::FETCH_ASSOC
        )
    ) {

        redireccionarConceptos(
            "warning",
            "El concepto de facturación no existe."
        );
    }


    // ==========================================================
    // VERIFICAR TIPO DE OBLIGACIÓN
    // ==========================================================

    $sqlTipoObligacion = "
        SELECT
            id_tipo_obligacion

        FROM tipos_obligacion

        WHERE id_tipo_obligacion = :id_tipo_obligacion

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
    // VERIFICAR CUENTA CONTABLE
    // ==========================================================
    //
    // La cuenta es opcional.
    //

    if ($id_cuenta_contable !== null) {

        $sqlCuenta = "
            SELECT
                id_cuenta_contable

            FROM cuentas_contables

            WHERE id_cuenta_contable =
                :id_cuenta_contable

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
                "La cuenta contable seleccionada no existe."
            );
        }
    }


    // ==========================================================
    // VALIDAR DUPLICADO
    // ==========================================================
    //
    // Excluye el concepto que se está editando.
    //

    $sqlDuplicado = "
        SELECT
            id_concepto

        FROM conceptos_facturacion

        WHERE nombre = :nombre
        AND id_concepto <> :id_concepto

        LIMIT 1
    ";


    $stmtDuplicado = $conexion->prepare(
        $sqlDuplicado
    );


    $stmtDuplicado->execute([

        ":nombre"
            => $nombre,

        ":id_concepto"
            => $id_concepto

    ]);


    if (
        $stmtDuplicado->fetch(
            PDO::FETCH_ASSOC
        )
    ) {

        redireccionarConceptos(
            "warning",
            "Ya existe otro concepto de facturación con ese nombre."
        );
    }


    // ==========================================================
    // ACTUALIZAR
    // ==========================================================

    $sql = "
        UPDATE conceptos_facturacion

        SET

            nombre =
                :nombre,

            descripcion =
                :descripcion,

            tipo_calculo =
                :tipo_calculo,

            id_tipo_obligacion =
                :id_tipo_obligacion,

            id_cuenta_contable =
                :id_cuenta_contable,

            obligatorio =
                :obligatorio,

            estado =
                :estado

        WHERE id_concepto =
            :id_concepto
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
            => $estado,

        ":id_concepto"
            => $id_concepto

    ]);


    // ==========================================================
    // MENSAJE DE ÉXITO
    // ==========================================================

    redireccionarConceptos(
        "success",
        "Concepto de facturación actualizado correctamente."
    );


} catch (PDOException $e) {

    // ==========================================================
    // REGISTRAR ERROR REAL
    // ==========================================================

    error_log(
        "Error actualizando concepto de facturación: " .
        $e->getMessage()
    );


    // ==========================================================
    // MENSAJE AL USUARIO
    // ==========================================================

    header(
        "Location: " .
        BASE_URL .
        "configuracion/conceptos_facturacion.php" .
        "?tipo=error&texto=" .
        urlencode(
            "No fue posible actualizar el concepto de facturación."
        )
    );

    exit;
}
