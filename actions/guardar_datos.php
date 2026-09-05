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
        "configuracion/datos.php"
    );

    exit;
}


// ==========================================================
// FUNCIÓN REDIRECCIONAR
// ==========================================================

function redireccionarDatos(
    string $tipo,
    string $mensaje
): void {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/datos.php" .
        "?tipo=" .
        urlencode($tipo) .
        "&texto=" .
        urlencode($mensaje)
    );

    exit;
}


// ==========================================================
// FUNCIÓN NORMALIZAR TEXTO OPCIONAL
// ==========================================================

function textoOpcional($valor): ?string
{
    $valor = trim((string)$valor);

    return $valor !== ''
        ? $valor
        : null;
}


try {

    // ======================================================
    // ID DEL REGISTRO ACTUAL
    // ======================================================

    $id = isset($_POST["id"]) &&
          $_POST["id"] !== ""
        ? (int)$_POST["id"]
        : null;


    // ======================================================
    // BUSCAR VERSIÓN ACTUAL SI ES EDICIÓN
    // ======================================================

    $versionActual = null;


    if ($id !== null) {

        $sqlActual = "
            SELECT *
            FROM datos_unidad
            WHERE id = :id
            LIMIT 1
        ";


        $stmtActual = $conexion->prepare(
            $sqlActual
        );


        $stmtActual->execute([
            ':id' => $id
        ]);


        $versionActual =
            $stmtActual->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$versionActual) {

            redireccionarDatos(
                'error',
                'No se encontró el registro que desea editar.'
            );
        }
    }


    // ======================================================
    // DATOS GENERALES
    // ======================================================

    $nombre = trim(
        $_POST["nombre_unidad"] ?? ""
    );


    $nit = trim(
        $_POST["nit_unidad"] ?? ""
    );


    $representante = trim(
        $_POST["representante_legal"] ?? ""
    );


    $correo = textoOpcional(
        $_POST["correo_propiedad"] ?? ""
    );


    $telefono = textoOpcional(
        $_POST["telefono_propiedad"] ?? ""
    );


    $direccion = trim(
        $_POST["direccion"] ?? ""
    );


    $sector = textoOpcional(
        $_POST["sector"] ?? ""
    );


    // ======================================================
    // TIPO DE COPROPIEDAD
    // ======================================================
    //
    // Si el select continúa disabled durante una edición,
    // no llegará por POST. En ese caso conservamos el valor
    // de la versión anterior.
    // ======================================================

    if (!empty($_POST["tipo_copropiedad"])) {

        $idTipoCopropiedad =
            (int)$_POST["tipo_copropiedad"];

    } elseif ($versionActual) {

        $idTipoCopropiedad =
            !empty(
                $versionActual["id_tipo_copropiedad"]
            )
                ? (int)$versionActual["id_tipo_copropiedad"]
                : null;

    } else {

        $idTipoCopropiedad = null;
    }


    // ======================================================
    // UBICACIÓN
    // ======================================================

    if (!empty($_POST["id_pais"])) {

        $idPais =
            (int)$_POST["id_pais"];

    } elseif ($versionActual) {

        $idPais =
            !empty($versionActual["id_pais"])
                ? (int)$versionActual["id_pais"]
                : null;

    } else {

        $idPais = null;
    }


    if (!empty($_POST["id_departamento"])) {

        $idDepartamento =
            (int)$_POST["id_departamento"];

    } elseif ($versionActual) {

        $idDepartamento =
            !empty(
                $versionActual["id_departamento"]
            )
                ? (int)$versionActual["id_departamento"]
                : null;

    } else {

        $idDepartamento = null;
    }


    if (!empty($_POST["id_ciudad"])) {

        $idCiudad =
            (int)$_POST["id_ciudad"];

    } elseif ($versionActual) {

        $idCiudad =
            !empty($versionActual["id_ciudad"])
                ? (int)$versionActual["id_ciudad"]
                : null;

    } else {

        $idCiudad = null;
    }


    // ======================================================
    // VALIDACIONES GENERALES
    // ======================================================

    if ($nombre === "") {

        redireccionarDatos(
            'warning',
            'Debe ingresar el nombre de la copropiedad.'
        );
    }


    if ($nit === "") {

        redireccionarDatos(
            'warning',
            'Debe ingresar el NIT de la copropiedad.'
        );
    }


    if ($representante === "") {

        redireccionarDatos(
            'warning',
            'Debe ingresar el representante legal.'
        );
    }


    if (!$idTipoCopropiedad) {

        redireccionarDatos(
            'warning',
            'Debe seleccionar un tipo de copropiedad.'
        );
    }


    if (!$idPais) {

        redireccionarDatos(
            'warning',
            'Debe seleccionar un país.'
        );
    }


    if (!$idDepartamento) {

        redireccionarDatos(
            'warning',
            'Debe seleccionar un departamento.'
        );
    }


    if (!$idCiudad) {

        redireccionarDatos(
            'warning',
            'Debe seleccionar una ciudad.'
        );
    }


    if ($direccion === "") {

        redireccionarDatos(
            'warning',
            'Debe ingresar la dirección de la copropiedad.'
        );
    }


    if (
        $correo !== null &&
        !filter_var(
            $correo,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        redireccionarDatos(
            'warning',
            'El correo electrónico no es válido.'
        );
    }


    // ======================================================
    // VALIDAR TIPO DE COPROPIEDAD
    // ======================================================

    $sqlTipoCopropiedad = "
        SELECT id
        FROM tipos_copropiedad
        WHERE id = :id
        LIMIT 1
    ";


    $stmtTipoCopropiedad =
        $conexion->prepare(
            $sqlTipoCopropiedad
        );


    $stmtTipoCopropiedad->execute([
        ':id' => $idTipoCopropiedad
    ]);


    if (
        !$stmtTipoCopropiedad->fetchColumn()
    ) {

        redireccionarDatos(
            'warning',
            'El tipo de copropiedad seleccionado no existe.'
        );
    }


    // ======================================================
    // VALIDAR RELACIÓN PAÍS / DEPARTAMENTO
    // ======================================================

    $sqlDepartamento = "
        SELECT id_departamento
        FROM departamentos
        WHERE id_departamento = :id_departamento
          AND id_pais = :id_pais
        LIMIT 1
    ";


    $stmtDepartamento =
        $conexion->prepare(
            $sqlDepartamento
        );


    $stmtDepartamento->execute([

        ':id_departamento'
            => $idDepartamento,

        ':id_pais'
            => $idPais

    ]);


    if (!$stmtDepartamento->fetchColumn()) {

        redireccionarDatos(
            'warning',
            'El departamento seleccionado no pertenece al país indicado.'
        );
    }


    // ======================================================
    // VALIDAR RELACIÓN DEPARTAMENTO / CIUDAD
    // ======================================================

    $sqlCiudad = "
        SELECT id_ciudad
        FROM ciudades
        WHERE id_ciudad = :id_ciudad
          AND id_departamento = :id_departamento
        LIMIT 1
    ";


    $stmtCiudad =
        $conexion->prepare(
            $sqlCiudad
        );


    $stmtCiudad->execute([

        ':id_ciudad'
            => $idCiudad,

        ':id_departamento'
            => $idDepartamento

    ]);


    if (!$stmtCiudad->fetchColumn()) {

        redireccionarDatos(
            'warning',
            'La ciudad seleccionada no pertenece al departamento indicado.'
        );
    }


    // ======================================================
    // CALCULAR CANTIDAD TOTAL DE UNIDADES
    // ======================================================
    //
    // Ya NO se recibe desde datos.php.
    //
    // detalle_tipos_unidad pasa a ser la fuente de verdad.
    // ======================================================

    $sqlCantidad = "
        SELECT
            COALESCE(
                SUM(cantidad_unidades),
                0
            )

        FROM detalle_tipos_unidad

        WHERE activo = 1
    ";


    $cantidadUnidades =
        (int)$conexion
            ->query($sqlCantidad)
            ->fetchColumn();


    // ======================================================
    // CARPETAS
    // ======================================================

    $carpetaLogos =
        ROOT_PATH .
        "/assets/logos/";


    $carpetaDocs =
        ROOT_PATH .
        "/assets/documentos/";


    if (!is_dir($carpetaLogos)) {

        if (
            !mkdir(
                $carpetaLogos,
                0777,
                true
            ) &&
            !is_dir($carpetaLogos)
        ) {

            throw new RuntimeException(
                'No fue posible crear la carpeta de logos.'
            );
        }
    }


    if (!is_dir($carpetaDocs)) {

        if (
            !mkdir(
                $carpetaDocs,
                0777,
                true
            ) &&
            !is_dir($carpetaDocs)
        ) {

            throw new RuntimeException(
                'No fue posible crear la carpeta de documentos.'
            );
        }
    }


    // ======================================================
    // ARCHIVOS ACTUALES
    // ======================================================

    $logo = $versionActual
        ? ($versionActual["logo"] ?? null)
        : null;


    $reglamento = $versionActual
        ? ($versionActual["reglamento"] ?? null)
        : null;


    $manual = $versionActual
        ? ($versionActual["manual"] ?? null)
        : null;


    // ======================================================
    // LOGO NUEVO
    // ======================================================

    if (
        isset($_FILES["logo"]) &&
        $_FILES["logo"]["error"] ===
            UPLOAD_ERR_OK
    ) {

        $extension = strtolower(
            pathinfo(
                $_FILES["logo"]["name"],
                PATHINFO_EXTENSION
            )
        );


        $extensionesPermitidas = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];


        if (
            !in_array(
                $extension,
                $extensionesPermitidas,
                true
            )
        ) {

            throw new RuntimeException(
                'El logo debe ser JPG, JPEG, PNG o WEBP.'
            );
        }


        $logo =
            "logo_" .
            time() .
            "_" .
            uniqid() .
            "." .
            $extension;


        if (
            !move_uploaded_file(
                $_FILES["logo"]["tmp_name"],
                $carpetaLogos . $logo
            )
        ) {

            throw new RuntimeException(
                "No fue posible guardar el logo."
            );
        }
    }


    // ======================================================
    // REGLAMENTO NUEVO
    // ======================================================

    if (
        isset($_FILES["reglamento"]) &&
        $_FILES["reglamento"]["error"] ===
            UPLOAD_ERR_OK
    ) {

        $extension = strtolower(
            pathinfo(
                $_FILES["reglamento"]["name"],
                PATHINFO_EXTENSION
            )
        );


        if ($extension !== 'pdf') {

            throw new RuntimeException(
                'El reglamento debe estar en formato PDF.'
            );
        }


        $reglamento =
            "reglamento_" .
            time() .
            "_" .
            uniqid() .
            ".pdf";


        if (
            !move_uploaded_file(
                $_FILES["reglamento"]["tmp_name"],
                $carpetaDocs . $reglamento
            )
        ) {

            throw new RuntimeException(
                "No fue posible guardar el reglamento."
            );
        }
    }


    // ======================================================
    // MANUAL NUEVO
    // ======================================================

    if (
        isset($_FILES["manual"]) &&
        $_FILES["manual"]["error"] ===
            UPLOAD_ERR_OK
    ) {

        $extension = strtolower(
            pathinfo(
                $_FILES["manual"]["name"],
                PATHINFO_EXTENSION
            )
        );


        if ($extension !== 'pdf') {

            throw new RuntimeException(
                'El manual debe estar en formato PDF.'
            );
        }


        $manual =
            "manual_" .
            time() .
            "_" .
            uniqid() .
            ".pdf";


        if (
            !move_uploaded_file(
                $_FILES["manual"]["tmp_name"],
                $carpetaDocs . $manual
            )
        ) {

            throw new RuntimeException(
                "No fue posible guardar el manual."
            );
        }
    }


    // ======================================================
    // DETERMINAR VERSIÓN
    // ======================================================

    if ($versionActual) {

        $version =
            ((int)$versionActual["version"]) + 1;

    } else {

        $version = 1;
    }


    // ======================================================
    // INICIAR TRANSACCIÓN
    // ======================================================

    $conexion->beginTransaction();


    // ======================================================
    // DESACTIVAR VERSIÓN ANTERIOR COMO ACTUAL
    // ======================================================

    if ($versionActual) {

        $sqlAnterior = "
            UPDATE datos_unidad
            SET es_actual = 0
            WHERE id = :id
        ";


        $stmtAnterior =
            $conexion->prepare(
                $sqlAnterior
            );


        $stmtAnterior->execute([
            ':id' => $id
        ]);
    }


    // ======================================================
    // CREAR NUEVA VERSIÓN
    // ======================================================
    //
    // IMPORTANTE:
    // Conservamos "cantidad_unidades" porque ese es el nombre
    // que utiliza actualmente tu tabla/código.
    //
    // Ahora su valor es CALCULADO.
    // ======================================================

    $sqlInsert = "
        INSERT INTO datos_unidad
        (
            version,
            es_actual,
            nombre,
            nit,
            representante_legal,
            id_pais,
            id_departamento,
            id_ciudad,
            direccion,
            sector,
            id_tipo_copropiedad,
            cantidad_unidades,
            correo,
            telefono,
            logo,
            reglamento,
            manual,
            activo
        )
        VALUES
        (
            :version,
            1,
            :nombre,
            :nit,
            :representante_legal,
            :id_pais,
            :id_departamento,
            :id_ciudad,
            :direccion,
            :sector,
            :id_tipo_copropiedad,
            :cantidad_unidades,
            :correo,
            :telefono,
            :logo,
            :reglamento,
            :manual,
            1
        )
    ";


    $stmtInsert =
        $conexion->prepare(
            $sqlInsert
        );


    $stmtInsert->execute([

        ':version'
            => $version,

        ':nombre'
            => $nombre,

        ':nit'
            => $nit,

        ':representante_legal'
            => $representante,

        ':id_pais'
            => $idPais,

        ':id_departamento'
            => $idDepartamento,

        ':id_ciudad'
            => $idCiudad,

        ':direccion'
            => $direccion,

        ':sector'
            => $sector,

        ':id_tipo_copropiedad'
            => $idTipoCopropiedad,

        ':cantidad_unidades'
            => $cantidadUnidades,

        ':correo'
            => $correo,

        ':telefono'
            => $telefono,

        ':logo'
            => $logo,

        ':reglamento'
            => $reglamento,

        ':manual'
            => $manual

    ]);


    // ======================================================
    // CONFIRMAR
    // ======================================================

    $conexion->commit();


    // ======================================================
    // MENSAJE
    // ======================================================

    if ($versionActual) {

        redireccionarDatos(
            'success',
            'La información fue actualizada correctamente. ' .
            'Se creó la versión ' .
            $version .
            '.'
        );
    }


    redireccionarDatos(
        'success',
        'La información de la copropiedad fue guardada correctamente.'
    );


} catch (Throwable $e) {

    // ======================================================
    // ROLLBACK
    // ======================================================

    if ($conexion->inTransaction()) {

        $conexion->rollBack();
    }


    // ======================================================
    // REGISTRAR ERROR REAL
    // ======================================================

    error_log(
        "Error guardando datos de copropiedad: " .
        $e->getMessage()
    );


    // ======================================================
    // MENSAJE
    // ======================================================

    if ($e instanceof RuntimeException) {

        redireccionarDatos(
            'warning',
            $e->getMessage()
        );
    }


    redireccionarDatos(
        'error',
        'No fue posible guardar la información de la copropiedad.'
    );
}