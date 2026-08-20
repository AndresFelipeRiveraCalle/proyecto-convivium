<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


$tipoMensaje = $_GET["tipo"] ?? null;
$mensaje = $_GET["mensaje"] ?? null;

// ==========================================================
// CARGAR CUENTAS BANCARIAS
// ==========================================================

$sqlCuentas = "
    SELECT
        id_cuenta_bancaria,
        banco,
        tipo_cuenta,
        numero_cuenta,
        titular
    FROM cuentas_bancarias
    WHERE estado = 1
    ORDER BY banco, numero_cuenta
";

$stmtCuentas = $conexion->query($sqlCuentas);

$cuentasBancarias = $stmtCuentas->fetchAll(PDO::FETCH_ASSOC);

// ==========================================================
// CARGAR DOCUMENTOS BANCARIOS
// ==========================================================

$sqlDocumentos = "

    SELECT

        db.id_documento,
        db.nombre_archivo,
        db.nombre_original,
        db.ruta_archivo,
        db.tipo_archivo,
        db.metodo_extraccion,
        db.estado_procesamiento,
        db.observaciones,
        db.fecha_procesamiento,
        db.fecha_creacion,

        cb.banco,
        cb.tipo_cuenta,
        cb.numero_cuenta

    FROM documentos_bancarios db

    LEFT JOIN cuentas_bancarias cb
        ON cb.id_cuenta_bancaria =
           db.id_cuenta_bancaria

    ORDER BY
        db.fecha_creacion DESC

";

$stmtDocumentos =
    $conexion->query($sqlDocumentos);

$documentosBancarios =
    $stmtDocumentos->fetchAll(
        PDO::FETCH_ASSOC
    );

// ==========================================================
// CARGAR EXTRACTOS BANCARIOS
// ==========================================================

$sql = "
    SELECT
        eb.id_extracto,
        eb.id_documento,
        eb.id_cuenta_bancaria,
        eb.fecha_movimiento,
        eb.fecha_valor,
        eb.descripcion,
        eb.referencia,
        eb.numero_documento,
        eb.valor,
        eb.tipo_movimiento,
        eb.estado_conciliacion,
        eb.archivo_origen,

        cb.banco,
        cb.tipo_cuenta,
        cb.numero_cuenta,

        db.nombre_original,
        db.nombre_archivo,
        db.ruta_archivo,
        db.tipo_archivo,
        db.metodo_extraccion,
        db.estado_procesamiento

    FROM extractos_bancarios eb

    LEFT JOIN cuentas_bancarias cb
        ON cb.id_cuenta_bancaria = eb.id_cuenta_bancaria

    LEFT JOIN documentos_bancarios db
        ON db.id_documento = eb.id_documento

    ORDER BY
        eb.fecha_movimiento DESC,
        eb.id_extracto DESC
";

$stmt = $conexion->query($sql);

$extractos = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            Extractos bancarios
        </h2>

        <br>

        <p>
            Administra los extractos bancarios utilizados para
            identificar y conciliar los pagos recibidos por la
            copropiedad.
        </p>

        <br>

        <!-- ======================================================
             LISTADO
        ======================================================= -->

        <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Movimientos bancarios
                </h3>

                <br>


                <table class="tabla">

                    <thead>

                        <tr>

                            <th>
                                Fecha
                            </th>

                            <th>
                                Cuenta bancaria
                            </th>

                            <th>
                                Descripción
                            </th>

                            <th>
                                Referencia
                            </th>

                            <th>
                                Valor
                            </th>

                            <th>
                                Tipo
                            </th>

                            <th>
                                Conciliación
                            </th>

                            <th>
                                OCR
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (empty($extractos)): ?>

                        <tr>

                            <td
                                colspan="8"
                                align="center">

                                No hay movimientos bancarios
                                registrados.

                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($extractos as $extracto): ?>

                            <tr>


                                <!-- FECHA -->

                                <td>

                                    <?= htmlspecialchars(
                                        $extracto['fecha_movimiento']
                                    ) ?>

                                </td>


                                <!-- CUENTA -->

                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $extracto['banco']
                                        ) ?>

                                    </strong>

                                    <br>

                                    <small>

                                        <?= htmlspecialchars(
                                            $extracto['tipo_cuenta']
                                        ) ?>

                                        -

                                        <?= htmlspecialchars(
                                            $extracto['numero_cuenta']
                                        ) ?>

                                    </small>

                                </td>


                                <!-- DESCRIPCIÓN -->

                                <td>

                                    <?= htmlspecialchars(
                                        $extracto['descripcion'] ?? ''
                                    ) ?>

                                </td>


                                <!-- REFERENCIA -->

                                <td>

                                    <?= htmlspecialchars(
                                        $extracto['referencia'] ?? ''
                                    ) ?>

                                    <?php if (
                                        !empty(
                                            $extracto['numero_documento']
                                        )
                                    ): ?>

                                        <br>

                                        <small>

                                            Doc:

                                            <?= htmlspecialchars(
                                                $extracto[
                                                    'numero_documento'
                                                ]
                                            ) ?>

                                        </small>

                                    <?php endif; ?>

                                </td>


                                <!-- VALOR -->

                                <td>

                                    <strong>

                                        $
                                        <?= number_format(
                                            $extracto['valor'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>

                                    </strong>

                                </td>


                                <!-- TIPO -->

                                <td>

                                    <?php if (
                                        $extracto['tipo_movimiento']
                                        === 'INGRESO'
                                    ): ?>

                                        <span class="activo">

                                            Ingreso

                                        </span>

                                    <?php else: ?>

                                        <span class="inactivo">

                                            Egreso

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- CONCILIACIÓN -->

                                <td>

                                    <?php

                                    switch (
                                        $extracto[
                                            'estado_conciliacion'
                                        ]
                                    ) {

                                        case 'CONCILIADO':

                                            echo '<span class="activo">
                                                    Conciliado
                                                  </span>';

                                            break;


                                        case 'RECHAZADO':

                                            echo '<span class="inactivo">
                                                    Rechazado
                                                  </span>';

                                            break;


                                        case 'CONCILIACION_PARCIAL':

                                            echo '<span class="advertencia">
                                                    Parcial
                                                  </span>';

                                            break;


                                        default:

                                            echo '<span class="inactivo">
                                                    Pendiente
                                                  </span>';

                                            break;
                                    }

                                    ?>

                                </td>


                                <!-- OCR -->

                                <td>

                                    <?php

                                    switch (
                                        $extracto[
                                            'estado_procesamiento'
                                        ]
                                    ) {

                                        case 'PROCESADO':

                                            echo '<span class="activo">
                                                    Procesado
                                                  </span>';

                                            break;


                                        case 'PROCESANDO':

                                            echo '<span class="advertencia">
                                                    Procesando
                                                  </span>';

                                            break;
                          
                                        case 'ERROR':

                                            echo '
                                                <span class="inactivo">
                                                    Error
                                                </span>
                                            ';

                                            if (!empty($documento['observaciones'])) {

                                                echo '
                                                    <br>
                                                    <small title="' .
                                                    htmlspecialchars(
                                                        $documento['observaciones']
                                                    ) .
                                                    '">
                                                        ' .
                                                        htmlspecialchars(
                                                            $documento['observaciones']
                                                        ) .
                                                        '
                                                    </small>
                                                ';
                                            }

                                            break;


                                        default:

                                            echo '<span class="inactivo">
                                                    Pendiente
                                                  </span>';

                                            break;
                                    }

                                    ?>

                                </td>


                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>


                <br>


                <!-- ==================================================
                     BOTÓN NUEVO
                =================================================== -->

                <button
                    type="button"
                    id="btnNuevoExtracto"
                    class="btn-filtrar">

                    + Cargar extracto bancario

                </button>


            </div>

        </div>

                <div class="bloque filtros">

            <div class="form-card">

                <h3>
                    Documentos bancarios cargados
                </h3>

                <br>

                <table class="tabla">

                    <thead>

                        <tr>

                            <th>
                                Documento
                            </th>

                            <th>
                                Cuenta bancaria
                            </th>

                            <th>
                                Método
                            </th>

                            <th>
                                Estado
                            </th>

                            <th>
                                Fecha
                            </th>

                            <th>
                                Acción
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($documentosBancarios)): ?>

                            <tr>

                                <td
                                    colspan="6"
                                    align="center">

                                    No hay documentos bancarios
                                    cargados.

                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach (
                                $documentosBancarios
                                as $documento
                            ): ?>

                                <tr>

                                    <!-- DOCUMENTO -->

                                    <td>

                                        <strong>

                                            <?= htmlspecialchars(
                                                $documento[
                                                    'nombre_original'
                                                ]
                                            ) ?>

                                        </strong>

                                        <br>

                                        <small>

                                            <?= htmlspecialchars(
                                                strtoupper(
                                                    $documento[
                                                        'tipo_archivo'
                                                    ]
                                                )
                                            ) ?>

                                        </small>

                                    </td>


                                    <!-- CUENTA -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $documento['banco']
                                            )
                                        ): ?>

                                            <strong>

                                                <?= htmlspecialchars(
                                                    $documento['banco']
                                                ) ?>

                                            </strong>

                                            <br>

                                            <small>

                                                <?= htmlspecialchars(
                                                    $documento[
                                                        'tipo_cuenta'
                                                    ]
                                                ) ?>

                                                -

                                                ****

                                                <?= htmlspecialchars(
                                                    substr(
                                                        $documento[
                                                            'numero_cuenta'
                                                        ],
                                                        -4
                                                    )
                                                ) ?>

                                            </small>

                                        <?php else: ?>

                                            <span class="inactivo">
                                                Cuenta no disponible
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- MÉTODO -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $documento[
                                                'metodo_extraccion'
                                            ]
                                        ) ?>

                                    </td>


                                    <!-- ESTADO -->

                                    <td>

                                        <?php

                                        switch (
                                            $documento[
                                                'estado_procesamiento'
                                            ]
                                        ) {

                                            case 'PROCESADO':

                                                echo '
                                                    <span class="activo">
                                                        Procesado
                                                    </span>
                                                ';

                                                break;


                                            case 'PROCESANDO':

                                                echo '
                                                    <span class="advertencia">
                                                        Procesando
                                                    </span>
                                                ';

                                                break;



                                            case 'ERROR':

                                                echo '
                                                    <span class="inactivo">
                                                        Error
                                                    </span>
                                                ';

                                                if (!empty($documento['observaciones'])) {

                                                    echo '
                                                        <br>
                                                        <small
                                                            style="
                                                                display: block;
                                                                margin-top: 5px;
                                                                max-width: 250px;
                                                                white-space: normal;
                                                            "
                                                        >
                                                            ' .
                                                            htmlspecialchars(
                                                                $documento['observaciones']
                                                            ) .
                                                            '
                                                        </small>
                                                    ';
                                                }

                                                break;

                                        }

                                        ?>

                                    </td>


                                    <!-- FECHA -->

                                    <td>

                                        <?= htmlspecialchars(
                                            $documento[
                                                'fecha_creacion'
                                            ]
                                        ) ?>

                                    </td>


                                    <!-- ACCIÓN -->

                                    <td>
                                        <?php if ($documento['estado_procesamiento'] === 'PENDIENTE'): ?>
                                            <a
                                                href="<?= BASE_URL ?>actions/procesar_extracto_bancario.php?id_documento=<?= (int)$documento['id_documento'] ?>"
                                                class="btn-filtrar"
                                                onclick="return confirm('¿Desea procesar este documento bancario?');">
                                                Procesar
                                            </a>
                                        <?php elseif (
                                            $documento['estado_procesamiento'] === 'PROCESANDO'
                                        ): ?>
                                            <span class="advertencia">Procesando...</span>
                                        <?php elseif (
                                            $documento['estado_procesamiento'] === 'PROCESADO'
                                        ): ?>
                                            <button type="button" class="btn-secondary">
                                                Ver
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn-secondary">
                                                Reintentar
                                            </button>
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



<!-- ==========================================================
     MODAL NUEVO EXTRACTO
========================================================== -->

<div
    id="modalNuevoExtracto"
    class="modal">


    <div class="modal-contenido">


        <div class="modal-header">

            <h3>
                Cargar extracto bancario
            </h3>


            <button
                type="button"
                class="modal-cerrar"
                id="cerrarNuevoExtracto">

                &times;

            </button>

        </div>



        <form
            action="<?= BASE_URL ?>actions/guardar_extracto_bancario.php"
            method="POST"
            enctype="multipart/form-data">


            <!-- ==================================================
                 CUENTA BANCARIA
            =================================================== -->

            <div class="form-group">

                <label for="id_cuenta_bancaria">

                    Cuenta bancaria *

                </label>


                <select
                    name="id_cuenta_bancaria"
                    id="id_cuenta_bancaria"
                    required>


                    <option value="">

                        Seleccione una cuenta...

                    </option>


                    <?php foreach (
                        $cuentasBancarias
                        as $cuenta
                    ): ?>


                        <option
                            value="<?= $cuenta[
                                'id_cuenta_bancaria'
                            ] ?>">


                            <?= htmlspecialchars(
                                $cuenta['banco']
                            ) ?>

                            -

                            <?= htmlspecialchars(
                                $cuenta['tipo_cuenta']
                            ) ?>

                            -

                            ****

                            <?= htmlspecialchars(
                                substr(
                                    $cuenta['numero_cuenta'],
                                    -4
                                )
                            ) ?>


                        </option>


                    <?php endforeach; ?>


                </select>

            </div>



            <!-- ==================================================
                 ARCHIVO
            =================================================== -->

            <div class="form-group">

                <label for="archivo">

                    Extracto bancario *

                </label>


                <input
                    type="file"
                    name="archivo"
                    id="archivo"
                    accept=".pdf,.jpg,.jpeg,.png"
                    required>


                <small>

                    Formatos permitidos:
                    PDF, JPG, JPEG y PNG.

                </small>

            </div>



            <!-- ==================================================
                 OBSERVACIONES
            =================================================== -->

            <div class="form-group">

                <label for="observaciones">

                    Observaciones

                </label>


                <textarea
                    name="observaciones"
                    id="observaciones"
                    maxlength="255"
                    rows="3"
                    placeholder="Observaciones del extracto"></textarea>

            </div>



            <!-- ==================================================
                 BOTONES
            =================================================== -->

            <div class="form-actions">


                <button
                    type="button"
                    class="btn-limpiar"
                    id="cancelarNuevoExtracto">

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="btn-filtrar">

                    Guardar extracto

                </button>


            </div>


        </form>


    </div>

</div>



<!-- ==========================================================
     MODAL MENSAJE
========================================================== -->


<script>const BASE_URL = "<?= BASE_URL ?>";</script>
<script src="<?= BASE_URL ?>assets/js/extractos_bancarios.js"> </script>

</body>

</html>
