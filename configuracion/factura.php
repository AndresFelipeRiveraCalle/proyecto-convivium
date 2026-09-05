<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// CARGAR CALENDARIOS FINANCIEROS
// ==========================================================

$sqlCalendarios = "
    SELECT
        id_calendario,
        periodo,
        fecha_facturacion,
        fecha_vencimiento,
        estado

    FROM calendario_financiero

    ORDER BY periodo DESC
";


$stmtCalendarios = $conexion->query(
    $sqlCalendarios
);

$calendarios = $stmtCalendarios->fetchAll(
    PDO::FETCH_ASSOC
);


// ==========================================================
// CARGAR TIPOS DE UNIDAD ACTIVOS
// ==========================================================

$sqlTipos = "
    SELECT
        id_tipo_config,
        nombre_grupo,
        cantidad_unidades

    FROM detalle_tipos_unidad

    WHERE activo = 1

    ORDER BY nombre_grupo
";


$stmtTipos = $conexion->query(
    $sqlTipos
);

$tiposUnidad = $stmtTipos->fetchAll(
    PDO::FETCH_ASSOC
);


// ==========================================================
// CARGAR CONCEPTOS DE FACTURACIÓN ACTIVOS
// ==========================================================

$sqlConceptos = "
    SELECT
        cf.id_concepto,
        cf.nombre,
        cf.descripcion,
        cf.tipo_calculo,
        cf.obligatorio,
        cf.estado,
        cf.id_tipo_obligacion,

        cc.codigo AS codigo_cuenta,
        cc.nombre AS nombre_cuenta

    FROM conceptos_facturacion cf

    LEFT JOIN cuentas_contables cc
        ON cc.id_cuenta_contable =
           cf.id_cuenta_contable

    WHERE cf.estado = 1

    ORDER BY
        cf.obligatorio DESC,
        cf.nombre
";


$stmtConceptos = $conexion->query(
    $sqlConceptos
);

$conceptos = $stmtConceptos->fetchAll(
    PDO::FETCH_ASSOC
);


// ==========================================================
// FUNCIÓN NOMBRE TIPO DE CÁLCULO
// ==========================================================

function nombreTipoCalculo($tipo)
{
    switch ($tipo) {

        case 'FIJO':
            return 'Valor fijo';

        case 'METRO_CUADRADO':
            return 'Por metro cuadrado';

        case 'COEFICIENTE':
            return 'Por coeficiente';

        case 'PORCENTAJE':
            return 'Porcentaje';

        default:
            return $tipo;
    }
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <?php include ROOT_PATH . "/includes/head.php"; ?>

</head>


<body>


<?php include ROOT_PATH . "/includes/header.php"; ?>

<?php require_once ROOT_PATH . "/includes/mensajes.php"; ?>


<div class="contenedor">


    <?php include ROOT_PATH . "/includes/sidebar.php"; ?>


    <main class="contenido">


        <!-- ======================================================
             ENCABEZADO
        ======================================================= -->

        <h2 align="center">
            Generación de facturación
        </h2>


        <br>


        <p>

            Seleccione el período financiero y los conceptos
            que desea incluir para preparar la facturación.

        </p>


        <br>


        <!-- ======================================================
             INFORMACIÓN
        ======================================================= -->

        <div class="info-box">

            <strong>
                Antes de generar
            </strong>

            <p>

                El sistema validará las unidades activas,
                las tarifas vigentes y los datos necesarios
                para cada tipo de cálculo.

            </p>

            <small>

                En esta etapa no se generan facturas.
                Primero se mostrará una vista previa.

            </small>

        </div>


        <br>


        <!-- ======================================================
             FORMULARIO DE PREVISUALIZACIÓN
        ======================================================= -->

        <div class="bloque filtros">


            <div class="form-card">


                <h3>
                    Configuración de facturación
                </h3>


                <br>


                <form
                    id="formPrevisualizarFacturacion"
                    method="POST"
                    action="<?= BASE_URL ?>actions/previsualizar_facturacion.php"
                >


                    <!-- ==========================================
                         PERÍODO FINANCIERO
                    =========================================== -->

                    <div class="form-group">


                        <label for="id_calendario">
                            Período financiero *
                        </label>


                        <select
                            name="id_calendario"
                            id="id_calendario"
                            required
                        >


                            <option value="">
                                Seleccione un período...
                            </option>


                            <?php foreach ($calendarios as $calendario): ?>


                                <?php

                                $periodoTexto = date(
                                    'm/Y',
                                    strtotime(
                                        $calendario['periodo']
                                    )
                                );

                                ?>


                                <option
                                    value="<?= (int)$calendario['id_calendario'] ?>"
                                    data-fecha-facturacion="<?= htmlspecialchars(
                                        $calendario['fecha_facturacion']
                                    ) ?>"
                                    data-fecha-vencimiento="<?= htmlspecialchars(
                                        $calendario['fecha_vencimiento']
                                    ) ?>"
                                    data-estado="<?= htmlspecialchars(
                                        $calendario['estado']
                                    ) ?>"
                                >

                                    <?= htmlspecialchars(
                                        $periodoTexto
                                    ) ?>

                                    -

                                    <?= htmlspecialchars(
                                        $calendario['estado']
                                    ) ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                    </div>


                    <!-- ==========================================
                         DATOS DEL CALENDARIO
                    =========================================== -->

                    <div class="form-group">


                        <label for="fecha_facturacion_mostrar">
                            Fecha de facturación
                        </label>


                        <input
                            type="date"
                            id="fecha_facturacion_mostrar"
                            readonly
                        >


                    </div>


                    <div class="form-group">


                        <label for="fecha_vencimiento_mostrar">
                            Fecha de vencimiento
                        </label>


                        <input
                            type="date"
                            id="fecha_vencimiento_mostrar"
                            readonly
                        >


                    </div>


                    <div class="form-group">


                        <label for="estado_periodo_mostrar">
                            Estado del período
                        </label>


                        <input
                            type="text"
                            id="estado_periodo_mostrar"
                            readonly
                            placeholder="Seleccione un período"
                        >


                    </div>


                    <!-- ==========================================
                         TIPO DE UNIDAD
                    =========================================== -->

                    <div class="form-group">


                        <label for="id_tipo_config">
                            Tipo de unidad
                        </label>


                        <select
                            name="id_tipo_config"
                            id="id_tipo_config"
                        >


                            <option value="">
                                Todas las unidades
                            </option>


                            <?php foreach ($tiposUnidad as $tipo): ?>


                                <option
                                    value="<?= (int)$tipo['id_tipo_config'] ?>"
                                >

                                    <?= htmlspecialchars(
                                        $tipo['nombre_grupo']
                                    ) ?>

                                    <?php if (
                                        isset(
                                            $tipo['cantidad_unidades']
                                        )
                                    ): ?>

                                        (<?= (int)$tipo['cantidad_unidades'] ?>)

                                    <?php endif; ?>

                                </option>


                            <?php endforeach; ?>


                        </select>


                    </div>


                    <!-- ==========================================
                         CONCEPTOS
                    =========================================== -->

                    <div class="form-group">


                        <label>
                            Conceptos a facturar
                        </label>


                        <br>


                        <?php if (empty($conceptos)): ?>


                            <p class="inactivo">

                                No hay conceptos de facturación
                                activos.

                            </p>


                        <?php else: ?>


                            <?php foreach ($conceptos as $concepto): ?>


                                <?php

                                $esObligatorio =
                                    (int)$concepto['obligatorio'] === 1;

                                ?>


                                <label
                                    style="
                                        display:block;
                                        margin-bottom:10px;
                                    "
                                >


                                    <?php if ($esObligatorio): ?>


                                        <input
                                            type="checkbox"
                                            checked
                                            disabled
                                        >


                                    <?php else: ?>


                                        <input
                                            type="checkbox"
                                            name="conceptos[]"
                                            value="<?= (int)$concepto['id_concepto'] ?>"
                                        >


                                    <?php endif; ?>


                                    <strong>

                                        <?= htmlspecialchars(
                                            $concepto['nombre']
                                        ) ?>

                                    </strong>


                                    <?php if (
                                        !empty(
                                            $concepto['descripcion']
                                        )
                                    ): ?>


                                        <small>

                                            -

                                            <?= htmlspecialchars(
                                                $concepto['descripcion']
                                            ) ?>

                                        </small>


                                    <?php endif; ?>


                                    <?php if ($esObligatorio): ?>


                                        <span class="activo">

                                            Obligatorio

                                        </span>


                                    <?php endif; ?>


                                </label>


                            <?php endforeach; ?>


                        <?php endif; ?>


                        <small>

                            Los conceptos obligatorios serán
                            incluidos automáticamente por el sistema.

                        </small>


                    </div>


                    <!-- ==========================================
                         OBSERVACIONES
                    =========================================== -->

                    <div class="form-group">


                        <label for="observaciones">
                            Observaciones
                        </label>


                        <textarea
                            name="observaciones"
                            id="observaciones"
                            rows="3"
                            maxlength="255"
                            placeholder="Observaciones de la facturación"
                        ></textarea>


                    </div>


                    <!-- ==========================================
                         BOTONES
                    =========================================== -->

                    <div class="form-actions">


                        <button
                            type="reset"
                            class="btn-limpiar"
                        >

                            Limpiar

                        </button>


                        <button
                            type="submit"
                            class="btn-filtrar"
                        >

                            Vista previa

                        </button>


                    </div>


                </form>


            </div>


        </div>


        <br>


        <!-- ======================================================
             CONCEPTOS ACTIVOS
        ======================================================= -->

        <div class="bloque filtros">


            <div class="form-card">


                <h3>
                    Conceptos disponibles para facturación
                </h3>


                <br>


                <div class="tabla-responsive">


                    <table class="tabla">


                        <thead>


                            <tr>


                                <th>
                                    Concepto
                                </th>


                                <th>
                                    Tipo de cálculo
                                </th>


                                <th>
                                    Cuenta contable
                                </th>


                                <th>
                                    Obligatorio
                                </th>


                            </tr>


                        </thead>


                        <tbody>


                        <?php if (empty($conceptos)): ?>


                            <tr>


                                <td
                                    colspan="4"
                                    align="center"
                                >

                                    No existen conceptos activos.

                                </td>


                            </tr>


                        <?php else: ?>


                            <?php foreach ($conceptos as $concepto): ?>


                                <tr>


                                    <!-- CONCEPTO -->

                                    <td>


                                        <strong>

                                            <?= htmlspecialchars(
                                                $concepto['nombre']
                                            ) ?>

                                        </strong>


                                        <?php if (
                                            !empty(
                                                $concepto['descripcion']
                                            )
                                        ): ?>


                                            <br>


                                            <small>

                                                <?= htmlspecialchars(
                                                    $concepto['descripcion']
                                                ) ?>

                                            </small>


                                        <?php endif; ?>


                                    </td>


                                    <!-- TIPO DE CÁLCULO -->

                                    <td>


                                        <?= htmlspecialchars(
                                            nombreTipoCalculo(
                                                $concepto['tipo_calculo']
                                            )
                                        ) ?>


                                    </td>


                                    <!-- CUENTA CONTABLE -->

                                    <td>


                                        <?php if (
                                            !empty(
                                                $concepto['codigo_cuenta']
                                            )
                                        ): ?>


                                            <?= htmlspecialchars(
                                                $concepto['codigo_cuenta']
                                            ) ?>

                                            -

                                            <?= htmlspecialchars(
                                                $concepto['nombre_cuenta']
                                            ) ?>


                                        <?php else: ?>


                                            <span class="inactivo">

                                                Sin configurar

                                            </span>


                                        <?php endif; ?>


                                    </td>


                                    <!-- OBLIGATORIO -->

                                    <td>


                                        <?php if (
                                            (int)$concepto['obligatorio'] === 1
                                        ): ?>


                                            <span class="activo">
                                                Sí
                                            </span>


                                        <?php else: ?>


                                            No


                                        <?php endif; ?>


                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            </div>


        </div>


    </main>


</div>


<!-- ==========================================================
     JAVASCRIPT
========================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const selectCalendario =
            document.getElementById(
                "id_calendario"
            );

        const fechaFacturacion =
            document.getElementById(
                "fecha_facturacion_mostrar"
            );

        const fechaVencimiento =
            document.getElementById(
                "fecha_vencimiento_mostrar"
            );

        const estadoPeriodo =
            document.getElementById(
                "estado_periodo_mostrar"
            );


        function actualizarCalendario() {

            const opcion =
                selectCalendario.options[
                    selectCalendario.selectedIndex
                ];


            if (
                !opcion ||
                !opcion.value
            ) {

                fechaFacturacion.value = "";
                fechaVencimiento.value = "";
                estadoPeriodo.value = "";

                return;
            }


            fechaFacturacion.value =
                opcion.dataset.fechaFacturacion ?? "";


            fechaVencimiento.value =
                opcion.dataset.fechaVencimiento ?? "";


            estadoPeriodo.value =
                opcion.dataset.estado ?? "";

        }


        if (selectCalendario) {

            selectCalendario.addEventListener(
                "change",
                actualizarCalendario
            );

        }

    }
);

</script>


</body>

</html>