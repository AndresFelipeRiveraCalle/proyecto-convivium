<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// VALIDAR MÉTODO
// ==========================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/basico.php"
    );

    exit;
}


// ==========================================================
// REDIRECCIONAR
// ==========================================================

function redireccionarTipoUnidad(
    string $tipo,
    string $mensaje,
    int $idTipoConfig = 0
): void {

    $url =
        BASE_URL .
        "configuracion/basico.php";


    if ($idTipoConfig > 0) {

        $url .=
            "?id=" .
            $idTipoConfig .
            "&tipo=" .
            urlencode($tipo) .
            "&texto=" .
            urlencode($mensaje);

    } else {

        $url .=
            "?tipo=" .
            urlencode($tipo) .
            "&texto=" .
            urlencode($mensaje);
    }


    header("Location: " . $url);

    exit;
}


try {

    // ======================================================
    // DATOS DEL FORMULARIO
    // ======================================================

    $idTipoConfig =
        isset($_POST["id_tipo_config"])
            ? (int)$_POST["id_tipo_config"]
            : 0;


    $idTipoVivienda =
        isset($_POST["id_tipo_vivienda"])
            ? (int)$_POST["id_tipo_vivienda"]
            : 0;


    $nombreGrupo =
        trim(
            $_POST["nombre_grupo"] ?? ""
        );


    $cantidad =
        isset($_POST["cantidad_unidades"])
            ? (int)$_POST["cantidad_unidades"]
            : 0;


    $area =
        isset($_POST["area_total"]) &&
        $_POST["area_total"] !== ""
            ? (float)$_POST["area_total"]
            : null;


    $coeficiente =
        isset($_POST["coeficiente_total"]) &&
        $_POST["coeficiente_total"] !== ""
            ? (float)$_POST["coeficiente_total"]
            : null;


    $observaciones =
        trim(
            $_POST["observaciones"] ?? ""
        );


    $observaciones =
        $observaciones !== ""
            ? $observaciones
            : null;


    // ======================================================
    // VALIDAR ID DEL GRUPO
    // ======================================================

    if ($idTipoConfig <= 0) {

        redireccionarTipoUnidad(
            "warning",
            "No se encontró el grupo de unidades que desea modificar."
        );
    }


    // ======================================================
    // VALIDAR TIPO DE UNIDAD
    // ======================================================

    if ($idTipoVivienda <= 0) {

        redireccionarTipoUnidad(
            "warning",
            "Debe seleccionar un tipo de unidad.",
            $idTipoConfig
        );
    }


    // ======================================================
    // VALIDAR NOMBRE
    // ======================================================

    if ($nombreGrupo === "") {

        redireccionarTipoUnidad(
            "warning",
            "Debe ingresar el nombre del grupo.",
            $idTipoConfig
        );
    }


    if (mb_strlen($nombreGrupo) > 100) {

        redireccionarTipoUnidad(
            "warning",
            "El nombre del grupo no puede superar los 100 caracteres.",
            $idTipoConfig
        );
    }


    // ======================================================
    // VALIDAR CANTIDAD
    // ======================================================

    if ($cantidad <= 0) {

        redireccionarTipoUnidad(
            "warning",
            "La cantidad de unidades debe ser mayor que cero.",
            $idTipoConfig
        );
    }


    // ======================================================
    // VALIDAR ÁREA
    // ======================================================

    if (
        $area !== null &&
        $area < 0
    ) {

        redireccionarTipoUnidad(
            "warning",
            "El área total no puede ser negativa.",
            $idTipoConfig
        );
    }


    // ======================================================
    // VALIDAR COEFICIENTE
    // ======================================================

    if (
        $coeficiente !== null &&
        $coeficiente < 0
    ) {

        redireccionarTipoUnidad(
            "warning",
            "El coeficiente total no puede ser negativo.",
            $idTipoConfig
        );
    }


    // ======================================================
    // VERIFICAR QUE EL GRUPO EXISTA
    // ======================================================

    $sqlGrupo = "
        SELECT
            id_tipo_config,
            id_tipo_vivienda,
            nombre_grupo,
            cantidad_unidades

        FROM detalle_tipos_unidad

        WHERE
            id_tipo_config = :id_tipo_config
            AND activo = 1

        LIMIT 1
    ";


    $stmtGrupo =
        $conexion->prepare(
            $sqlGrupo
        );


    $stmtGrupo->execute([

        ':id_tipo_config'
            => $idTipoConfig

    ]);


    $grupoActual =
        $stmtGrupo->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$grupoActual) {

        redireccionarTipoUnidad(
            "error",
            "El grupo de unidades que desea modificar no existe."
        );
    }


    // ======================================================
    // VERIFICAR QUE EL TIPO DE UNIDAD EXISTA
    // ======================================================

    $sqlTipoVivienda = "
        SELECT
            id_tipo_vivienda,
            nombre

        FROM tipos_vivienda

        WHERE
            id_tipo_vivienda = :id_tipo_vivienda
            AND activo = 1

        LIMIT 1
    ";


    $stmtTipoVivienda =
        $conexion->prepare(
            $sqlTipoVivienda
        );


    $stmtTipoVivienda->execute([

        ':id_tipo_vivienda'
            => $idTipoVivienda

    ]);


    $tipoVivienda =
        $stmtTipoVivienda->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$tipoVivienda) {

        redireccionarTipoUnidad(
            "warning",
            "El tipo de unidad seleccionado no existe o se encuentra inactivo.",
            $idTipoConfig
        );
    }


    // ======================================================
    // VALIDAR NOMBRE DUPLICADO
    // ======================================================
    //
    // No permitimos otro grupo activo con el mismo nombre.
    // Excluimos el registro que estamos editando.
    // ======================================================

    $sqlDuplicado = "
        SELECT
            id_tipo_config

        FROM detalle_tipos_unidad

        WHERE
            nombre_grupo = :nombre_grupo
            AND activo = 1
            AND id_tipo_config <> :id_tipo_config

        LIMIT 1
    ";


    $stmtDuplicado =
        $conexion->prepare(
            $sqlDuplicado
        );


    $stmtDuplicado->execute([

        ':nombre_grupo'
            => $nombreGrupo,

        ':id_tipo_config'
            => $idTipoConfig

    ]);


    if ($stmtDuplicado->fetchColumn()) {

        redireccionarTipoUnidad(
            "warning",
            "Ya existe otro grupo activo con el nombre \"" .
            $nombreGrupo .
            "\".",
            $idTipoConfig
        );
    }


    // ======================================================
    // OBTENER RESUMEN DE UNIDADES ACTIVAS CREADAS
    // ======================================================

    $sqlResumen = "
        SELECT
            COUNT(*) AS cantidad_real,

            COALESCE(
                SUM(area),
                0
            ) AS area_real,

            COALESCE(
                SUM(coeficiente),
                0
            ) AS coeficiente_real

        FROM unidades

        WHERE
            id_tipo_config = :id_tipo_config
            AND activo = 1
    ";


    $stmtResumen =
        $conexion->prepare(
            $sqlResumen
        );


    $stmtResumen->execute([

        ':id_tipo_config'
            => $idTipoConfig

    ]);


    $resumen =
        $stmtResumen->fetch(
            PDO::FETCH_ASSOC
        );


    $cantidadReal =
        (int)($resumen['cantidad_real'] ?? 0);


    $areaReal =
        (float)($resumen['area_real'] ?? 0);


    $coeficienteReal =
        (float)($resumen['coeficiente_real'] ?? 0);


    // ======================================================
    // VALIDAR CANTIDAD CONTRA UNIDADES EXISTENTES
    // ======================================================

    if ($cantidad < $cantidadReal) {

        redireccionarTipoUnidad(
            "warning",
            "No puede configurar " .
            $cantidad .
            " unidades porque actualmente existen " .
            $cantidadReal .
            " unidades activas creadas en este grupo.",
            $idTipoConfig
        );
    }


    // ======================================================
    // VALIDAR ÁREA CONTRA UNIDADES EXISTENTES
    // ======================================================

    if (
        $area !== null &&
        $area < $areaReal
    ) {

        redireccionarTipoUnidad(
            "warning",
            "El área total configurada no puede ser menor que la suma de las áreas de las unidades activas existentes (" .
            number_format(
                $areaReal,
                2,
                ",",
                "."
            ) .
            " m²).",
            $idTipoConfig
        );
    }


    // ======================================================
    // VALIDAR COEFICIENTE CONTRA UNIDADES EXISTENTES
    // ======================================================

    if (
        $coeficiente !== null &&
        $coeficiente < $coeficienteReal
    ) {

        redireccionarTipoUnidad(
            "warning",
            "El coeficiente total configurado no puede ser menor que la suma de los coeficientes de las unidades activas existentes (" .
            number_format(
                $coeficienteReal,
                8,
                ".",
                ""
            ) .
            ").",
            $idTipoConfig
        );
    }


    // ======================================================
    // ACTUALIZAR GRUPO
    // ======================================================

    $sqlActualizar = "
        UPDATE detalle_tipos_unidad

        SET
            id_tipo_vivienda = :id_tipo_vivienda,
            nombre_grupo = :nombre_grupo,
            cantidad_unidades = :cantidad_unidades,
            area_total = :area_total,
            coeficiente_total = :coeficiente_total,
            observaciones = :observaciones

        WHERE
            id_tipo_config = :id_tipo_config
            AND activo = 1
    ";


    $stmtActualizar =
        $conexion->prepare(
            $sqlActualizar
        );


    $stmtActualizar->execute([

        ':id_tipo_vivienda'
            => $idTipoVivienda,

        ':nombre_grupo'
            => $nombreGrupo,

        ':cantidad_unidades'
            => $cantidad,

        ':area_total'
            => $area,

        ':coeficiente_total'
            => $coeficiente,

        ':observaciones'
            => $observaciones,

        ':id_tipo_config'
            => $idTipoConfig

    ]);


    // ======================================================
    // MENSAJE
    // ======================================================

    redireccionarTipoUnidad(
        "success",
        "El grupo \"" .
        $nombreGrupo .
        "\" fue actualizado correctamente.",
        $idTipoConfig
    );


} catch (PDOException $e) {

    error_log(
        "Error actualizando grupo de unidades: " .
        $e->getMessage()
    );


    redireccionarTipoUnidad(
        "error",
        "No fue posible actualizar el grupo de unidades.",
        $idTipoConfig ?? 0
    );


} catch (Throwable $e) {

    error_log(
        "Error general actualizando grupo de unidades: " .
        $e->getMessage()
    );


    redireccionarTipoUnidad(
        "error",
        "Ocurrió un error al actualizar el grupo de unidades.",
        $idTipoConfig ?? 0
    );
}