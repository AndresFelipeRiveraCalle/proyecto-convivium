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
        "configuracion/generar_obligacion.php"
    );

    exit;
}


// ==========================================================
// ID CALENDARIO
// ==========================================================

$idCalendario = (int)($_POST['id_calendario'] ?? 0);

if ($idCalendario <= 0) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/generar_obligacion.php?tipo=error&mensaje=" .
        urlencode("Debe seleccionar un período financiero.")
    );

    exit;
}


// ==========================================================
// BUSCAR CALENDARIO
// ==========================================================

$sqlCalendario = "
    SELECT
        id_calendario,
        periodo,
        fecha_facturacion,
        fecha_vencimiento,
        estado
    FROM calendario_financiero
    WHERE id_calendario = :id_calendario
    LIMIT 1
";

$stmtCalendario = $conexion->prepare($sqlCalendario);

$stmtCalendario->execute([
    ':id_calendario' => $idCalendario
]);

$calendario = $stmtCalendario->fetch(PDO::FETCH_ASSOC);


if (!$calendario) {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/generar_obligacion.php?tipo=error&mensaje=" .
        urlencode("El calendario financiero seleccionado no existe.")
    );

    exit;
}


// ==========================================================
// VALIDAR ESTADO DEL CALENDARIO
// ==========================================================

if ($calendario['estado'] === 'CERRADO') {

    header(
        "Location: " .
        BASE_URL .
        "configuracion/generar_obligacion.php?tipo=error&mensaje=" .
        urlencode(
            "El período seleccionado está cerrado y no permite generar obligaciones."
        )
    );

    exit;
}


// ==========================================================
// DATOS DEL PERÍODO
// ==========================================================

$periodo = $calendario['periodo'];

$fechaGeneracion = $calendario['fecha_facturacion'];

$fechaVencimiento = $calendario['fecha_vencimiento'];


// ==========================================================
// BUSCAR UNIDADES ACTIVAS
// ==========================================================

$sqlUnidades = "
    SELECT
        id_unidad,
        id_tipo_config,
        codigo,
        nombre,
        area,
        coeficiente
    FROM unidades
    WHERE activo = 1
    ORDER BY id_unidad ASC
";

$stmtUnidades = $conexion->prepare($sqlUnidades);

$stmtUnidades->execute();

$unidades = $stmtUnidades->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// CONTADORES
// ==========================================================

$generadas = 0;

$omitidas = 0;

$sinTarifa = 0;


// ==========================================================
// INICIAR TRANSACCIÓN
// ==========================================================

try {

    $conexion->beginTransaction();


    // ======================================================
    // BUSCAR CONCEPTOS ACTIVOS
    // ======================================================

    $sqlConceptos = "
        SELECT
            id_concepto,
            nombre,
            descripcion,
            tipo_calculo,
            id_tipo_obligacion,
            obligatorio
        FROM conceptos_facturacion
        WHERE estado = 1
          AND id_tipo_obligacion IS NOT NULL
        ORDER BY id_concepto ASC
    ";

    $stmtConceptos = $conexion->prepare($sqlConceptos);

    $stmtConceptos->execute();

    $conceptos = $stmtConceptos->fetchAll(PDO::FETCH_ASSOC);


    // ======================================================
    // RECORRER UNIDADES
    // ======================================================

    foreach ($unidades as $unidad) {

        $idUnidad = (int)$unidad['id_unidad'];

        $idTipoConfig = (int)$unidad['id_tipo_config'];

        $area = (float)($unidad['area'] ?? 0);

        $coeficiente = (float)($unidad['coeficiente'] ?? 0);


        // ==================================================
        // RECORRER CONCEPTOS
        // ==================================================

        foreach ($conceptos as $concepto) {

            $idConcepto =
                (int)$concepto['id_concepto'];

            $tipoCalculo =
                $concepto['tipo_calculo'];

            $idTipoObligacion =
                (int)$concepto['id_tipo_obligacion'];


            // ==================================================
            // BUSCAR TARIFA VIGENTE
            // ==================================================

            $sqlTarifa = "
                SELECT
                    id_tarifa,
                    valor
                FROM tarifas_facturacion
                WHERE id_concepto = :id_concepto
                  AND id_tipo_config = :id_tipo_config
                  AND estado = 1
                  AND fecha_inicio <= :fecha_facturacion
                  AND (
                        fecha_fin IS NULL
                        OR fecha_fin >= :fecha_facturacion
                  )
                ORDER BY fecha_inicio DESC, id_tarifa DESC
                LIMIT 1
            ";

            $stmtTarifa =
                $conexion->prepare($sqlTarifa);

            $stmtTarifa->execute([
                ':id_concepto' =>
                    $idConcepto,

                ':id_tipo_config' =>
                    $idTipoConfig,

                ':fecha_facturacion' =>
                    $calendario['fecha_facturacion']
            ]);

            $tarifa =
                $stmtTarifa->fetch(PDO::FETCH_ASSOC);


            // ==================================================
            // SI NO EXISTE TARIFA
            // ==================================================

            if (!$tarifa) {

                $sinTarifa++;

                continue;
            }


            $idTarifa =
                (int)$tarifa['id_tarifa'];

            $valorTarifa =
                (float)$tarifa['valor'];


            // ==================================================
            // CALCULAR VALOR
            // ==================================================

            $valor = 0;


            switch ($tipoCalculo) {


                // ------------------------------------------
                // FIJO
                // ------------------------------------------

                case 'FIJO':

                    $valor = $valorTarifa;

                    break;


                // ------------------------------------------
                // METRO CUADRADO
                // ------------------------------------------

                case 'METRO_CUADRADO':

                    if ($area <= 0) {

                        continue 2;
                    }

                    $valor =
                        $area * $valorTarifa;

                    break;


                // ------------------------------------------
                // COEFICIENTE
                // ------------------------------------------

                case 'COEFICIENTE':

                    if ($coeficiente <= 0) {

                        continue 2;
                    }

                    $valor =
                        $coeficiente * $valorTarifa;

                    break;


                // ------------------------------------------
                // PORCENTAJE
                // ------------------------------------------

                case 'PORCENTAJE':

                    $valor =
                        $valorTarifa / 100;

                    break;


                // ------------------------------------------
                // TIPO NO RECONOCIDO
                // ------------------------------------------

                default:

                    continue 2;
            }


            // ==================================================
            // REDONDEAR
            // ==================================================

            $valor =
                round($valor, 2);


            if ($valor <= 0) {

                continue;
            }


            // ==================================================
            // EVITAR DUPLICADOS
            // ==================================================

            $sqlExiste = "
                SELECT
                    id_obligacion
                FROM obligaciones
                WHERE id_unidad = :id_unidad
                  AND id_tarifa = :id_tarifa
                  AND periodo = :periodo
                  AND estado <> 'Anulada'
                LIMIT 1
            ";

            $stmtExiste =
                $conexion->prepare($sqlExiste);

            $stmtExiste->execute([

                ':id_unidad' =>
                    $idUnidad,

                ':id_tarifa' =>
                    $idTarifa,

                ':periodo' =>
                    $periodo
            ]);


            if ($stmtExiste->fetch(PDO::FETCH_ASSOC)) {

                $omitidas++;

                continue;
            }


            // ==================================================
            // OBSERVACIONES
            // ==================================================

            $observaciones =
                $concepto['nombre'] .
                ' - obligación generada automáticamente para ' .
                date(
                    'm/Y',
                    strtotime($periodo)
                );


            // ==================================================
            // INSERTAR OBLIGACIÓN
            // ==================================================

            $sqlInsert = "
                INSERT INTO obligaciones
                (
                    id_unidad,
                    id_tarifa,
                    periodo,
                    fecha_generacion,
                    fecha_vencimiento,
                    valor,
                    valor_pagado,
                    saldo,
                    estado,
                    observaciones
                )
                VALUES
                (
                    :id_unidad,
                    :id_tarifa,
                    :periodo,
                    :fecha_generacion,
                    :fecha_vencimiento,
                    :valor,
                    0,
                    :saldo,
                    'Pendiente',
                    :observaciones
                )
            ";

            $stmtInsert =
                $conexion->prepare($sqlInsert);


            $stmtInsert->execute([

                ':id_unidad' =>
                    $idUnidad,

                ':id_tarifa' =>
                    $idTarifa,

                ':periodo' =>
                    $periodo,

                ':fecha_generacion' =>
                    $fechaGeneracion,

                ':fecha_vencimiento' =>
                    $fechaVencimiento,

                ':valor' =>
                    $valor,

                ':saldo' =>
                    $valor,

                ':observaciones' =>
                    $observaciones
            ]);


            $generadas++;
        }
    }


    // ======================================================
    // CONFIRMAR TRANSACCIÓN
    // ======================================================

    $conexion->commit();


    // ======================================================
    // MENSAJE DE RESULTADO
    // ======================================================

    $mensaje =
        "Proceso ejecutado correctamente. " .
        "Obligaciones generadas: " .
        $generadas .
        ". Obligaciones omitidas por existir previamente: " .
        $omitidas .
        ". Sin tarifa vigente: " .
        $sinTarifa .
        ".";


    header(
        "Location: " .
        BASE_URL .
        "configuracion/generar_obligacion.php" .
        "?tipo=success&mensaje=" .
        urlencode($mensaje)
    );

    exit;


} catch (PDOException $e) {


    // ======================================================
    // ROLLBACK
    // ======================================================

    if ($conexion->inTransaction()) {

        $conexion->rollBack();
    }


    // ======================================================
    // MOSTRAR ERROR
    // ======================================================

    echo "<div style='
        font-family: Arial;
        max-width: 900px;
        margin: 40px auto;
        padding: 25px;
        border: 1px solid #ddd;
        border-radius: 10px;
        background: #fff;
    '>";

    echo "<h2 style='color:#b91c1c;'>
        Error al generar las obligaciones
    </h2>";

    echo "<p>
        <strong>Mensaje de MySQL:</strong>
    </p>";

    echo "<pre style='
        background:#f5f5f5;
        padding:15px;
        overflow:auto;
    '>";

    echo htmlspecialchars(
        $e->getMessage()
    );

    echo "</pre>";

    echo "<p>
        <strong>SQLSTATE:</strong> " .
        htmlspecialchars(
            $e->getCode()
        ) .
    "</p>";

    echo "<hr>";

    echo "<a href='javascript:history.back()'>
        ← Regresar
    </a>";

    echo "</div>";
}