<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// CARGAR TIPOS DE UNIDAD
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

$stmtTipos = $conexion->query($sqlTipos);

$tiposUnidad = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// CARGAR CONCEPTOS DE FACTURACIÓN
// ==========================================================

$sqlConceptos = "
    SELECT
        cf.id_concepto,
        cf.nombre,
        cf.descripcion,
        cf.tipo_calculo,
        cf.obligatorio,
        cf.estado,

        cc.codigo AS codigo_cuenta,
        cc.nombre AS nombre_cuenta

    FROM conceptos_facturacion cf

    LEFT JOIN cuentas_contables cc
        ON cc.id_cuenta_contable = cf.id_cuenta_contable

    WHERE cf.estado = 1

    ORDER BY cf.nombre
";

$stmtConceptos = $conexion->query($sqlConceptos);

$conceptos = $stmtConceptos->fetchAll(PDO::FETCH_ASSOC);

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

        <h2 align="center">
            Generación de facturación
        </h2>

        <br>

        <p>
            Genera los cobros correspondientes a un período
            para las unidades de la copropiedad.
        </p>

        <br>


        <!-- ======================================================
             CONFIGURACIÓN DE FACTURACIÓN
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Configuración de facturación
                </h3>

                <br>


                <form
                    id="formGenerarFacturacion"
                    method="POST"
                    action="<?= BASE_URL ?>actions/generar_facturacion.php"
                >


                    <!-- PERÍODO -->

                    <div class="form-group">

                        <label for="periodo">

                            Período a facturar *

                        </label>

                        <input
                            type="month"
                            name="periodo"
                            id="periodo"
                            required
                        >

                    </div>


                    <!-- FECHA VENCIMIENTO -->

                    <div class="form-group">

                        <label for="fecha_vencimiento">

                            Fecha de vencimiento *

                        </label>

                        <input
                            type="date"
                            name="fecha_vencimiento"
                            id="fecha_vencimiento"
                            required
                        >

                    </div>


                    <!-- TIPO DE UNIDAD -->

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
                                    value="<?= $tipo['id_tipo_config'] ?>"
                                >

                                    <?= htmlspecialchars(
                                        $tipo['nombre_grupo']
                                    ) ?>

                                    (<?= $tipo['cantidad_unidades'] ?>)

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- CONCEPTOS -->

                    <div class="form-group">

                        <label>

                            Conceptos a facturar *

                        </label>

                        <br>


                        <?php if (empty($conceptos)): ?>

                            <p class="inactivo">

                                No hay conceptos de facturación
                                activos.

                            </p>

                        <?php else: ?>


                            <?php foreach ($conceptos as $concepto): ?>

                                <label
                                    style="display:block; margin-bottom:8px;"
                                >

                                    <input
                                        type="checkbox"
                                        name="conceptos[]"
                                        value="<?= $concepto['id_concepto'] ?>"
                                        <?= $concepto['obligatorio'] == 1
                                            ? 'checked disabled'
                                            : ''
                                        ?>
                                    >

                                    <strong>

                                        <?= htmlspecialchars(
                                            $concepto['nombre']
                                        ) ?>

                                    </strong>


                                    <?php if (!empty($concepto['descripcion'])): ?>

                                        <small>

                                            -
                                            <?= htmlspecialchars(
                                                $concepto['descripcion']
                                            ) ?>

                                        </small>

                                    <?php endif; ?>


                                    <?php if ($concepto['obligatorio'] == 1): ?>

                                        <span class="activo">

                                            Obligatorio

                                        </span>

                                    <?php endif; ?>

                                </label>

                            <?php endforeach; ?>


                        <?php endif; ?>

                    </div>


                    <!-- OBSERVACIONES -->

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


                    <!-- BOTONES -->

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

                            Generar facturación

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
                    Conceptos que pueden facturarse
                </h3>

                <br>


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


                                    <?php if (!empty($concepto['descripcion'])): ?>

                                        <br>

                                        <small>

                                            <?= htmlspecialchars(
                                                $concepto['descripcion']
                                            ) ?>

                                        </small>

                                    <?php endif; ?>

                                </td>


                                <!-- TIPO -->

                                <td>

                                    <?php

                                    switch (
                                        $concepto['tipo_calculo']
                                    ) {

                                        case 'FIJO':
                                            echo 'Valor fijo';
                                            break;

                                        case 'METRO_CUADRADO':
                                            echo 'Por metro cuadrado';
                                            break;

                                        case 'COEFICIENTE':
                                            echo 'Por coeficiente';
                                            break;

                                        case 'PORCENTAJE':
                                            echo 'Porcentaje';
                                            break;

                                        default:
                                            echo htmlspecialchars(
                                                $concepto['tipo_calculo']
                                            );
                                    }

                                    ?>

                                </td>


                                <!-- CUENTA -->

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
                                        $concepto['obligatorio'] == 1
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

    </main>

</div>


</body>

</html>