<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// CARGAR CONCEPTOS DE FACTURACIÓN
// ==========================================================

$sql = "
    SELECT
        cf.id_concepto,
        cf.nombre,
        cf.descripcion,
        cf.tipo_calculo,
        cf.id_tipo_obligacion,
        cf.id_cuenta_contable,
        cf.obligatorio,
        cf.estado,

        tob.nombre AS tipo_obligacion,

        cc.codigo AS codigo_cuenta,
        cc.nombre AS nombre_cuenta

    FROM conceptos_facturacion cf

    LEFT JOIN tipos_obligacion tob
        ON tob.id_tipo_obligacion = cf.id_tipo_obligacion

    LEFT JOIN cuentas_contables cc
        ON cc.id_cuenta_contable = cf.id_cuenta_contable

    ORDER BY cf.nombre
";

$stmt = $conexion->query($sql);

$conceptos = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// CARGAR CUENTAS CONTABLES
// ==========================================================

$sqlCuentas = "
    SELECT
        id_cuenta_contable,
        codigo,
        nombre,
        tipo
    FROM cuentas_contables
    WHERE estado = 1
    ORDER BY codigo
";

$stmtCuentas = $conexion->query($sqlCuentas);

$cuentasContables = $stmtCuentas->fetchAll(
    PDO::FETCH_ASSOC
);


// ==========================================================
// CARGAR TIPOS DE OBLIGACIÓN
// ==========================================================

$sqlTiposObligacion = "
    SELECT
        id_tipo_obligacion,
        nombre
    FROM tipos_obligacion
    ORDER BY nombre
";

$stmtTiposObligacion = $conexion->query(
    $sqlTiposObligacion
);

$tiposObligacion = $stmtTiposObligacion->fetchAll(
    PDO::FETCH_ASSOC
);

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
            Conceptos de facturación
        </h2>

        <br>

        <p>
            Configura los conceptos que podrán utilizarse
            para generar los cobros de la copropiedad.
        </p>

        <br>


        <!-- ======================================================
             LISTADO
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">


                <h3>
                    Conceptos configurados
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
                                    Tipo de obligación
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

                                <th>
                                    Estado
                                </th>

                                <th>
                                    Acciones
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (empty($conceptos)): ?>


                            <tr>

                                <td
                                    colspan="7"
                                    align="center"
                                >
                                    No hay conceptos de facturación
                                    configurados.
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
                                            !empty($concepto['descripcion'])
                                        ): ?>

                                            <br>

                                            <small>

                                                <?= htmlspecialchars(
                                                    $concepto['descripcion']
                                                ) ?>

                                            </small>

                                        <?php endif; ?>

                                    </td>


                                    <!-- TIPO DE OBLIGACIÓN -->

                                    <td>

                                        <?php if (
                                            !empty($concepto['tipo_obligacion'])
                                        ): ?>

                                            <?= htmlspecialchars(
                                                $concepto['tipo_obligacion']
                                            ) ?>

                                        <?php else: ?>

                                            <span class="inactivo">

                                                Sin configurar

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- TIPO DE CÁLCULO -->

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

                                                break;
                                        }

                                        ?>

                                    </td>


                                    <!-- CUENTA CONTABLE -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $concepto[
                                                    'id_cuenta_contable'
                                                ]
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


                                    <!-- ESTADO -->

                                    <td>

                                        <?php if (
                                            (int)$concepto['estado'] === 1
                                        ): ?>

                                            <span class="activo">

                                                Activo

                                            </span>

                                        <?php else: ?>

                                            <span class="inactivo">

                                                Inactivo

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- ACCIONES -->

                                    <td>

                                        <button
                                            type="button"
                                            class="btn-secondary btnEditarConcepto"
                                            data-id="<?= (int)$concepto['id_concepto'] ?>"
                                        >
                                            ✏ Editar
                                        </button>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>


                    </table>

                </div>


                <br>


                <button
                    type="button"
                    id="btnNuevoConcepto"
                    class="btn-filtrar"
                >
                    + Agregar concepto
                </button>


            </div>

        </div>


    </main>

</div>


<!-- ==========================================================
     MODAL NUEVO CONCEPTO
========================================================== -->

<div
    id="modalNuevoConcepto"
    class="modal"
>

    <div class="modal-contenido">


        <div class="modal-header">

            <h3>
                Nuevo concepto de facturación
            </h3>

            <button
                type="button"
                class="modal-cerrar"
                id="cerrarNuevoConcepto"
            >
                &times;
            </button>

        </div>


        <form
            action="<?= BASE_URL ?>actions/guardar_concepto_facturacion.php"
            method="POST"
        >


            <!-- NOMBRE -->

            <div class="form-group">

                <label>
                    Nombre *
                </label>

                <input
                    type="text"
                    name="nombre"
                    maxlength="100"
                    required
                    placeholder="Ej. Cuota de administración"
                >

            </div>


            <!-- DESCRIPCIÓN -->

            <div class="form-group">

                <label>
                    Descripción
                </label>

                <textarea
                    name="descripcion"
                    maxlength="255"
                    rows="3"
                    placeholder="Descripción del concepto"
                ></textarea>

            </div>


            <!-- TIPO DE CÁLCULO -->

            <div class="form-group">

                <label>
                    Tipo de cálculo *
                </label>

                <select
                    name="tipo_calculo"
                    required
                >

                    <option value="">
                        Seleccione...
                    </option>

                    <option value="FIJO">
                        Valor fijo
                    </option>

                    <option value="METRO_CUADRADO">
                        Por metro cuadrado
                    </option>

                    <option value="COEFICIENTE">
                        Por coeficiente
                    </option>

                    <option value="PORCENTAJE">
                        Porcentaje
                    </option>

                </select>

            </div>


            <!-- TIPO DE OBLIGACIÓN -->

            <div class="form-group">

                <label>
                    Tipo de obligación *
                </label>

                <select
                    name="id_tipo_obligacion"
                    required
                >

                    <option value="">
                        Seleccione...
                    </option>

                    <?php foreach (
                        $tiposObligacion
                        as $tipo
                    ): ?>

                        <option
                            value="<?= (int)$tipo[
                                'id_tipo_obligacion'
                            ] ?>"
                        >

                            <?= htmlspecialchars(
                                $tipo['nombre']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- CUENTA CONTABLE -->

            <div class="form-group">

                <label>
                    Cuenta contable
                </label>

                <select
                    name="id_cuenta_contable"
                >

                    <option value="">
                        Seleccione una cuenta...
                    </option>

                    <?php foreach (
                        $cuentasContables
                        as $cuenta
                    ): ?>

                        <option
                            value="<?= (int)$cuenta[
                                'id_cuenta_contable'
                            ] ?>"
                        >

                            <?= htmlspecialchars(
                                $cuenta['codigo']
                            ) ?>

                            -

                            <?= htmlspecialchars(
                                $cuenta['nombre']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- OBLIGATORIO -->

            <div class="form-group">

                <label>

                    <input
                        type="checkbox"
                        name="obligatorio"
                        value="1"
                    >

                    Concepto obligatorio

                </label>

                <small>
                    Los conceptos obligatorios serán considerados
                    por el motor general de facturación cuando exista
                    una tarifa vigente para la unidad.
                </small>

            </div>


            <!-- ESTADO -->

            <div class="form-group">

                <label>
                    Estado
                </label>

                <select
                    name="estado"
                >

                    <option value="1">
                        Activo
                    </option>

                    <option value="0">
                        Inactivo
                    </option>

                </select>

            </div>


            <!-- BOTONES -->

            <div class="form-actions">

                <button
                    type="button"
                    class="btn-limpiar"
                    id="cancelarNuevoConcepto"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="btn-filtrar"
                >
                    Guardar
                </button>

            </div>


        </form>


    </div>

</div>


<!-- ==========================================================
     MODAL EDITAR CONCEPTO
========================================================== -->

<div
    id="modalEditarConcepto"
    class="modal"
>

    <div class="modal-contenido">


        <div class="modal-header">

            <h3>
                Editar concepto de facturación
            </h3>

            <button
                type="button"
                class="modal-cerrar"
                id="cerrarModalEditarConcepto"
            >
                &times;
            </button>

        </div>


        <form
            id="formEditarConcepto"
            action="<?= BASE_URL ?>actions/actualizar_concepto_facturacion.php"
            method="POST"
        >


            <!-- ID -->

            <input
                type="hidden"
                name="id_concepto"
                id="editar_id_concepto"
            >


            <!-- NOMBRE -->

            <div class="form-group">

                <label for="editar_nombre">
                    Nombre
                </label>

                <input
                    type="text"
                    name="nombre"
                    id="editar_nombre"
                    class="form-control"
                    maxlength="100"
                    required
                >

            </div>


            <!-- DESCRIPCIÓN -->

            <div class="form-group">

                <label for="editar_descripcion">
                    Descripción
                </label>

                <textarea
                    name="descripcion"
                    id="editar_descripcion"
                    class="form-control"
                    maxlength="255"
                    rows="3"
                ></textarea>

            </div>


            <!-- TIPO DE CÁLCULO -->

            <div class="form-group">

                <label for="editar_tipo_calculo">
                    Tipo de cálculo
                </label>

                <select
                    name="tipo_calculo"
                    id="editar_tipo_calculo"
                    class="form-control"
                    required
                >

                    <option value="FIJO">
                        Valor fijo
                    </option>

                    <option value="METRO_CUADRADO">
                        Por metro cuadrado
                    </option>

                    <option value="COEFICIENTE">
                        Por coeficiente
                    </option>

                    <option value="PORCENTAJE">
                        Porcentaje
                    </option>

                </select>

            </div>


            <!-- TIPO DE OBLIGACIÓN -->

            <div class="form-group">

                <label for="editar_id_tipo_obligacion">
                    Tipo de obligación
                </label>

                <select
                    name="id_tipo_obligacion"
                    id="editar_id_tipo_obligacion"
                    class="form-control"
                    required
                >

                    <option value="">
                        Seleccione...
                    </option>

                    <?php foreach (
                        $tiposObligacion
                        as $tipo
                    ): ?>

                        <option
                            value="<?= (int)$tipo[
                                'id_tipo_obligacion'
                            ] ?>"
                        >

                            <?= htmlspecialchars(
                                $tipo['nombre']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- CUENTA CONTABLE -->

            <div class="form-group">

                <label for="editar_id_cuenta_contable">
                    Cuenta contable
                </label>

                <select
                    name="id_cuenta_contable"
                    id="editar_id_cuenta_contable"
                    class="form-control"
                >

                    <option value="">
                        Sin configurar
                    </option>

                    <?php foreach (
                        $cuentasContables
                        as $cuenta
                    ): ?>

                        <option
                            value="<?= (int)$cuenta[
                                'id_cuenta_contable'
                            ] ?>"
                        >

                            <?= htmlspecialchars(
                                $cuenta['codigo'] .
                                ' - ' .
                                $cuenta['nombre']
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- OBLIGATORIO -->

            <div class="form-group">

                <label for="editar_obligatorio">
                    ¿Es obligatorio?
                </label>

                <select
                    name="obligatorio"
                    id="editar_obligatorio"
                    class="form-control"
                >

                    <option value="0">
                        No
                    </option>

                    <option value="1">
                        Sí
                    </option>

                </select>

            </div>


            <!-- ESTADO -->

            <div class="form-group">

                <label for="editar_estado">
                    Estado
                </label>

                <select
                    name="estado"
                    id="editar_estado"
                    class="form-control"
                >

                    <option value="1">
                        Activo
                    </option>

                    <option value="0">
                        Inactivo
                    </option>

                </select>

            </div>


            <!-- BOTONES -->

            <div class="form-actions">

                <button
                    type="button"
                    class="btn-limpiar"
                    id="cancelarModalEditarConcepto"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="btn-filtrar"
                >
                    Guardar cambios
                </button>

            </div>


        </form>


    </div>

</div>


<!-- ==========================================================
     MODAL MENSAJE
========================================================== -->

<div
    id="modalMensaje"
    class="modal"
>

    <div class="modal-contenido modal-mensaje">

        <h2 id="tituloMensaje"></h2>

        <br>

        <p id="textoMensaje"></p>

        <br><br>

        <div class="acciones-modal">

            <button
                type="button"
                id="btnCerrarMensaje"
                class="btn-filtrar"
            >
                Aceptar
            </button>

        </div>

    </div>

</div>


<!-- ==========================================================
     JAVASCRIPT
========================================================== -->

<script>
    const BASE_URL = "<?= BASE_URL ?>";
</script>

<script src="<?= BASE_URL ?>assets/js/conceptos_facturacion.js"></script>

<script src="<?= BASE_URL ?>assets/js/editar_concepto_facturacion.js"></script>


</body>

</html>