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
// FUNCIÓN REDIRECCIONAR
// ==========================================================

function redireccionarGrupo(
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
    // VALIDAR TIPO DE UNIDAD
    // ======================================================

    if ($idTipoVivienda <= 0) {

        redireccionarGrupo(
            "warning",
            "Debe seleccionar un tipo de unidad."
        );
    }


    // ======================================================
    // VALIDAR NOMBRE
    // ======================================================

    if ($nombreGrupo === "") {

        redireccionarGrupo(
            "warning",
            "Debe ingresar el nombre del grupo."
        );
    }


    if (mb_strlen($nombreGrupo) > 100) {

        redireccionarGrupo(
            "warning",
            "El nombre del grupo no puede superar los 100 caracteres."
        );
    }


    // ======================================================
    // VALIDAR CANTIDAD
    // ======================================================

    if ($cantidad <= 0) {

        redireccionarGrupo(
            "warning",
            "La cantidad de unidades debe ser mayor que cero."
        );
    }


    // ======================================================
    // VALIDAR ÁREA
    // ======================================================

    if (
        $area !== null &&
        $area < 0
    ) {

        redireccionarGrupo(
            "warning",
            "El área total no puede ser negativa."
        );
    }


    // ======================================================
    // VALIDAR COEFICIENTE
    // ======================================================

    if (
        $coeficiente !== null &&
        $coeficiente < 0
    ) {

        redireccionarGrupo(
            "warning",
            "El coeficiente total no puede ser negativo."
        );
    }


    // ======================================================
    // VALIDAR OBSERVACIONES
    // ======================================================

    if (
        $observaciones !== null &&
        mb_strlen($observaciones) > 255
    ) {

        redireccionarGrupo(
            "warning",
            "Las observaciones no pueden superar los 255 caracteres."
        );
    }


    // ======================================================
    // VERIFICAR QUE EL TIPO DE UNIDAD EXISTA
    // ======================================================

    $sqlTipo = "
        SELECT
            id_tipo_vivienda,
            nombre

        FROM tipos_vivienda

        WHERE
            id_tipo_vivienda = :id_tipo_vivienda
            AND activo = 1

        LIMIT 1
    ";


    $stmtTipo =
        $conexion->prepare(
            $sqlTipo
        );


    $stmtTipo->execute([

        ':id_tipo_vivienda'
            => $idTipoVivienda

    ]);


    $tipoUnidad =
        $stmtTipo->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$tipoUnidad) {

        redireccionarGrupo(
            "warning",
            "El tipo de unidad seleccionado no existe o se encuentra inactivo."
        );
    }


    // ======================================================
    // VALIDAR NOMBRE DUPLICADO
    // ======================================================
    //
    // No permitimos dos grupos activos con el mismo nombre.
    //
    // Ejemplo:
    //
    // Torre A
    // Torre A   <- no permitido
    //
    // ======================================================

    $sqlDuplicado = "
        SELECT
            id_tipo_config

        FROM detalle_tipos_unidad

        WHERE
            nombre_grupo = :nombre_grupo
            AND activo = 1

        LIMIT 1
    ";


    $stmtDuplicado =
        $conexion->prepare(
            $sqlDuplicado
        );


    $stmtDuplicado->execute([

        ':nombre_grupo'
            => $nombreGrupo

    ]);


    if ($stmtDuplicado->fetchColumn()) {

        redireccionarGrupo(
            "warning",
            "Ya existe un grupo activo con el nombre \"" .
            $nombreGrupo .
            "\"."
        );
    }


    // ======================================================
    // CREAR GRUPO
    // ======================================================

    $sqlInsertar = "
        INSERT INTO detalle_tipos_unidad
        (
            id_tipo_vivienda,
            nombre_grupo,
            cantidad_unidades,
            area_total,
            coeficiente_total,
            observaciones,
            activo
        )
        VALUES
        (
            :id_tipo_vivienda,
            :nombre_grupo,
            :cantidad_unidades,
            :area_total,
            :coeficiente_total,
            :observaciones,
            1
        )
    ";


    $stmtInsertar =
        $conexion->prepare(
            $sqlInsertar
        );


    $stmtInsertar->execute([

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
            => $observaciones

    ]);


    // ======================================================
    // ID DEL NUEVO GRUPO
    // ======================================================

    $idTipoConfig =
        (int)$conexion->lastInsertId();


    // ======================================================
    // MENSAJE
    // ======================================================

    redireccionarGrupo(
        "success",
        "El grupo \"" .
        $nombreGrupo .
        "\" fue creado correctamente.",
        $idTipoConfig
    );


} catch (PDOException $e) {

    error_log(
        "Error guardando grupo de unidades: " .
        $e->getMessage()
    );


    redireccionarGrupo(
        "error",
        "No fue posible crear el grupo de unidades."
    );


} catch (Throwable $e) {

    error_log(
        "Error general guardando grupo de unidades: " .
        $e->getMessage()
    );


    redireccionarGrupo(
        "error",
        "Ocurrió un error al crear el grupo de unidades."
    );
}