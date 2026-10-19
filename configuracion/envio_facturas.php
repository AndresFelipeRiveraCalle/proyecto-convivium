<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// FUNCIONES
// ==========================================================

function e($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}


function dinero($valor)
{
    return '$' . number_format(
        (float)$valor,
        2,
        ',',
        '.'
    );
}


// ==========================================================
// FILTROS
// ==========================================================

$anio =
    isset($_GET['anio'])
        ? (int)$_GET['anio']
        : (int)date('Y');


$mes =
    isset($_GET['mes'])
        ? (int)$_GET['mes']
        : (int)date('n');


if (
    $mes < 1
    ||
    $mes > 12
) {

    $mes =
        (int)date('n');
}


// ==========================================================
// MESES
// ==========================================================

$meses = [

    1  => 'Enero',
    2  => 'Febrero',
    3  => 'Marzo',
    4  => 'Abril',
    5  => 'Mayo',
    6  => 'Junio',
    7  => 'Julio',
    8  => 'Agosto',
    9  => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre'

];


// ==========================================================
// FACTURAS DEL PERÍODO
// Obtiene facturas y cantidad de destinatarios configurados.
// ==========================================================

$sqlFacturas = "

    SELECT

        f.id_factura,
        f.numero_factura,
        f.periodo,
        f.mes,
        f.total,
        f.estado,

        u.id_unidad,
        u.codigo AS unidad_codigo,

        COUNT(
            DISTINCT CASE

                WHEN
                    r.activo = 1

                    AND r.fecha_hasta IS NULL

                    AND r.recibe_factura = 1

                    AND usu.correo IS NOT NULL

                    AND TRIM(
                        usu.correo
                    ) <> ''

                THEN usu.id

                ELSE NULL

            END
        ) AS cantidad_destinatarios,

        MAX(
            CASE

                WHEN
                    ef.estado = 'ENVIADO'

                THEN 1

                ELSE 0

            END
        ) AS tiene_envio_exitoso,

        MAX(
            ef.fecha_envio
        ) AS ultimo_envio

    FROM facturas f

    INNER JOIN unidades u
        ON u.id_unidad =
           f.id_unidad

    LEFT JOIN residente r
        ON r.unidad_id =
           u.id_unidad

    LEFT JOIN usuario usu
        ON usu.id =
           r.usuario_id

    LEFT JOIN envios_facturas ef
        ON ef.id_factura =
           f.id_factura

    WHERE

        f.periodo =
            :anio

        AND f.mes =
            :mes

        AND f.estado <>
            'ANULADA'

    GROUP BY

        f.id_factura,
        f.numero_factura,
        f.periodo,
        f.mes,
        f.total,
        f.estado,

        u.id_unidad,
        u.codigo

    ORDER BY

        u.codigo,
        f.id_factura
";


$stmtFacturas =
    $conexion->prepare(
        $sqlFacturas
    );


$stmtFacturas->execute([

    ':anio'
        => $anio,

    ':mes'
        => $mes

]);


$facturas =
    $stmtFacturas->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// RESUMEN
// ==========================================================

$totalFacturas =
    count(
        $facturas
    );


$facturasListas = 0;

$facturasSinCorreo = 0;

$facturasEnviadas = 0;


foreach (
    $facturas
    as $factura
) {

    if (
        (int)$factura[
            'cantidad_destinatarios'
        ] > 0
    ) {

        $facturasListas++;

    } else {

        $facturasSinCorreo++;
    }


    if (
        (int)$factura[
            'tiene_envio_exitoso'
        ] === 1
    ) {

        $facturasEnviadas++;
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

        <div class="form-card">

            <div class="form-header">

                <div>

                    <h2>
                        Envío masivo de facturas
                    </h2>

                    <p>
                        Seleccione el período y las facturas
                        que desea enviar por correo electrónico.
                    </p>

                </div>

            </div>


            <!-- ==================================================
                 FILTROS
            =================================================== -->

            <form
                method="GET"
                class="form-grid envio-masivo-filtros"
            >

                <div class="form-group">

                    <label for="anio">
                        Año
                    </label>

                    <select
                        name="anio"
                        id="anio"
                    >

                        <?php for (
                            $a = date('Y') - 3;
                            $a <= date('Y') + 1;
                            $a++
                        ): ?>

                            <option
                                value="<?= (int)$a ?>"
                                <?= $a === $anio
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= (int)$a ?>
                            </option>

                        <?php endfor; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label for="mes">
                        Mes
                    </label>

                    <select
                        name="mes"
                        id="mes"
                    >

                        <?php foreach (
                            $meses
                            as $numeroMes => $nombreMes
                        ): ?>

                            <option
                                value="<?= (int)$numeroMes ?>"
                                <?= $numeroMes === $mes
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= e($nombreMes) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-actions">

                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        Buscar
                    </button>

                </div>

            </form>

        </div>


        <!-- ======================================================
             RESUMEN
        ======================================================= -->

        <div class="envio-masivo-resumen">

            <div class="envio-resumen-item">

                <span>
                    Facturas del período
                </span>

                <strong>
                    <?= (int)$totalFacturas ?>
                </strong>

            </div>


            <div class="envio-resumen-item">

                <span>
                    Listas para enviar
                </span>

                <strong>
                    <?= (int)$facturasListas ?>
                </strong>

            </div>


            <div class="envio-resumen-item">

                <span>
                    Sin destinatario
                </span>

                <strong>
                    <?= (int)$facturasSinCorreo ?>
                </strong>

            </div>


            <div class="envio-resumen-item">

                <span>
                    Ya enviadas
                </span>

                <strong>
                    <?= (int)$facturasEnviadas ?>
                </strong>

            </div>

        </div>


        <!-- ======================================================
             LISTADO
        ======================================================= -->

        <div class="form-card">

            <form
                method="POST"
                action="<?= BASE_URL ?>actions/enviar_facturas_masivo.php"
                id="formEnvioMasivo"
            >

                <input
                    type="hidden"
                    name="anio"
                    value="<?= (int)$anio ?>"
                >

                <input
                    type="hidden"
                    name="mes"
                    value="<?= (int)$mes ?>"
                >


                <div class="envio-masivo-toolbar">

                    <div>

                        <strong>
                            <?= e(
                                strtoupper(
                                    $meses[$mes]
                                )
                            ) ?>
                            <?= (int)$anio ?>
                        </strong>

                    </div>


                    <div class="envio-masivo-acciones">

                        <button
                            type="button"
                            class="btn-limpiar"
                            id="btnSeleccionarTodas"
                        >
                            Seleccionar todas
                        </button>


                        <button
                            type="button"
                            class="btn-limpiar"
                            id="btnDeseleccionarTodas"
                        >
                            Quitar selección
                        </button>


                        <button
                            type="submit"
                            class="btn-primary"
                            id="btnEnviarSeleccionadas"
                        >
                            Enviar seleccionadas
                        </button>

                    </div>

                </div>


                <?php if (
                    empty(
                        $facturas
                    )
                ): ?>

                    <div class="envio-masivo-vacio">

                        No se encontraron facturas
                        para este período.

                    </div>

                <?php else: ?>


                    <div class="tabla-responsive">

                        <table class="tabla-general">

                            <thead>

                                <tr>

                                    <th style="width:45px;">
                                        Seleccionar
                                    </th>

                                    <th>
                                        Factura
                                    </th>

                                    <th>
                                        Unidad
                                    </th>

                                    <th>
                                        Período
                                    </th>

                                    <th>
                                        Total
                                    </th>

                                    <th>
                                        Destinatarios
                                    </th>

                                    <th>
                                        Estado envío
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php foreach (
                                $facturas
                                as $factura
                            ): ?>


                                <?php

                                $cantidadDestinatarios =
                                    (int)$factura[
                                        'cantidad_destinatarios'
                                    ];


                                $puedeEnviar =
                                    $cantidadDestinatarios > 0;


                                $yaEnviada =
                                    (int)$factura[
                                        'tiene_envio_exitoso'
                                    ] === 1;

                                ?>


                                <tr>


                                    <td
                                        style="
                                            text-align:center;
                                        "
                                    >

                                        <input
                                            type="checkbox"
                                            class="check-factura"
                                            name="facturas[]"
                                            value="<?= (int)$factura['id_factura'] ?>"
                                            <?= !$puedeEnviar
                                                ? 'disabled'
                                                : ''
                                            ?>
                                        >

                                    </td>


                                    <td>

                                        <strong>
                                            <?= e(
                                                $factura[
                                                    'numero_factura'
                                                ]
                                            ) ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?= e(
                                            $factura[
                                                'unidad_codigo'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $meses[
                                                (int)$factura[
                                                    'mes'
                                                ]
                                            ]
                                        ) ?>

                                        <?= (int)$factura[
                                            'periodo'
                                        ] ?>

                                    </td>


                                    <td>

                                        <?= dinero(
                                            $factura[
                                                'total'
                                            ]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            $puedeEnviar
                                        ): ?>

                                            <span
                                                class="envio-estado-listo"
                                            >
                                                <?= $cantidadDestinatarios ?>

                                                <?= $cantidadDestinatarios === 1
                                                    ? 'destinatario'
                                                    : 'destinatarios'
                                                ?>
                                            </span>

                                        <?php else: ?>

                                            <span
                                                class="envio-estado-sin-correo"
                                            >
                                                Sin destinatario
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?php if (
                                            $yaEnviada
                                        ): ?>

                                            <span
                                                class="envio-estado-enviado"
                                            >
                                                ENVIADA
                                            </span>

                                            <?php if (
                                                !empty(
                                                    $factura[
                                                        'ultimo_envio'
                                                    ]
                                                )
                                            ): ?>

                                                <div
                                                    class="envio-fecha"
                                                >

                                                    <?= date(
                                                        'd/m/Y H:i',
                                                        strtotime(
                                                            $factura[
                                                                'ultimo_envio'
                                                            ]
                                                        )
                                                    ) ?>

                                                </div>

                                            <?php endif; ?>

                                        <?php else: ?>

                                            <span
                                                class="envio-estado-pendiente"
                                            >
                                                PENDIENTE
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                            </tbody>

                        </table>

                    </div>


                <?php endif; ?>


                <div
                    id="mensajeSeleccionMasiva"
                    class="envio-masivo-validacion"
                    style="display:none;"
                >
                    Debe seleccionar al menos una factura.
                </div>

                <!-- ======================================================
                    PROGRESO DEL ENVÍO
                ======================================================= -->

                <div
                    id="panelProgresoEnvio"
                    class="envio-progreso-panel"
                    style="display:none;"
                >

                    <div class="envio-progreso-encabezado">

                        <div>

                            <strong>
                                Enviando facturas
                            </strong>

                            <div
                                id="textoProgresoEnvio"
                                class="envio-progreso-texto"
                            >
                                Preparando envío...
                            </div>

                        </div>


                        <div
                            id="porcentajeProgresoEnvio"
                            class="envio-progreso-porcentaje"
                        >
                            0%
                        </div>

                    </div>


                    <div class="envio-progreso-barra">

                        <div
                            id="barraProgresoEnvio"
                            class="envio-progreso-avance"
                            style="width:0%;"
                        ></div>

                    </div>


                    <div class="envio-progreso-resumen">

                        <div>
                            <span>Procesadas</span>

                            <strong id="resumenProcesadas">
                                0
                            </strong>
                        </div>


                        <div>
                            <span>Facturas enviadas</span>

                            <strong id="resumenFacturasEnviadas">
                                0
                            </strong>
                        </div>


                        <div>
                            <span>Correos enviados</span>

                            <strong id="resumenCorreosEnviados">
                                0
                            </strong>
                        </div>


                        <div>
                            <span>Errores</span>

                            <strong id="resumenErrores">
                                0
                            </strong>
                        </div>

                    </div>


                    <div
                        id="resultadoEnvioMasivo"
                        class="envio-progreso-resultado"
                    ></div>

                </div>
            </form>

        </div>


    </main>

</div>


<script>

// ==========================================================
// ENVÍO MASIVO POR LOTES
// Procesa hasta 10 facturas por petición.
// ==========================================================

document.addEventListener(
    'DOMContentLoaded',
    function () {


        const formulario =
            document.getElementById(
                'formEnvioMasivo'
            );


        const btnSeleccionarTodas =
            document.getElementById(
                'btnSeleccionarTodas'
            );


        const btnDeseleccionarTodas =
            document.getElementById(
                'btnDeseleccionarTodas'
            );


        const btnEnviar =
            document.getElementById(
                'btnEnviarSeleccionadas'
            );


        const mensajeSeleccion =
            document.getElementById(
                'mensajeSeleccionMasiva'
            );


        const panelProgreso =
            document.getElementById(
                'panelProgresoEnvio'
            );


        const barraProgreso =
            document.getElementById(
                'barraProgresoEnvio'
            );


        const porcentajeProgreso =
            document.getElementById(
                'porcentajeProgresoEnvio'
            );


        const textoProgreso =
            document.getElementById(
                'textoProgresoEnvio'
            );


        const resumenProcesadas =
            document.getElementById(
                'resumenProcesadas'
            );


        const resumenFacturasEnviadas =
            document.getElementById(
                'resumenFacturasEnviadas'
            );


        const resumenCorreosEnviados =
            document.getElementById(
                'resumenCorreosEnviados'
            );


        const resumenErrores =
            document.getElementById(
                'resumenErrores'
            );


        const resultadoFinal =
            document.getElementById(
                'resultadoEnvioMasivo'
            );


        const urlProcesarLote =
            <?= json_encode(
                BASE_URL .
                'actions/procesar_lote_facturas.php'
            ) ?>;


        let envioEnProceso =
            false;


        // ==================================================
        // SELECCIONAR TODAS
        // ==================================================

        if (
            btnSeleccionarTodas
        ) {

            btnSeleccionarTodas.addEventListener(
                'click',
                function () {

                    if (
                        envioEnProceso
                    ) {
                        return;
                    }


                    const checks =
                        document.querySelectorAll(
                            '.check-factura:not(:disabled)'
                        );


                    checks.forEach(
                        function (check) {

                            check.checked =
                                true;
                        }
                    );


                    if (
                        mensajeSeleccion
                    ) {

                        mensajeSeleccion.style.display =
                            'none';
                    }
                }
            );
        }


        // ==================================================
        // QUITAR SELECCIÓN
        // ==================================================

        if (
            btnDeseleccionarTodas
        ) {

            btnDeseleccionarTodas.addEventListener(
                'click',
                function () {

                    if (
                        envioEnProceso
                    ) {
                        return;
                    }


                    const checks =
                        document.querySelectorAll(
                            '.check-factura'
                        );


                    checks.forEach(
                        function (check) {

                            check.checked =
                                false;
                        }
                    );
                }
            );
        }


        // ==================================================
        // DIVIDIR EN LOTES
        // ==================================================

        function dividirEnLotes(
            elementos,
            tamano
        ) {

            const lotes = [];


            for (
                let i = 0;
                i < elementos.length;
                i += tamano
            ) {

                lotes.push(
                    elementos.slice(
                        i,
                        i + tamano
                    )
                );
            }


            return lotes;
        }


        // ==================================================
        // ACTUALIZAR PROGRESO
        // ==================================================

        function actualizarProgreso(
            procesadas,
            total
        ) {

            let porcentaje =
                total > 0
                    ? Math.round(
                        (
                            procesadas
                            /
                            total
                        )
                        *
                        100
                    )
                    : 0;


            if (
                porcentaje > 100
            ) {

                porcentaje =
                    100;
            }


            barraProgreso.style.width =
                porcentaje + '%';


            porcentajeProgreso.textContent =
                porcentaje + '%';
        }


        // ==================================================
        // BLOQUEAR CONTROLES
        // ==================================================

        function bloquearPantalla(
            bloquear
        ) {

            envioEnProceso =
                bloquear;


            if (
                btnEnviar
            ) {

                btnEnviar.disabled =
                    bloquear;
            }


            if (
                btnSeleccionarTodas
            ) {

                btnSeleccionarTodas.disabled =
                    bloquear;
            }


            if (
                btnDeseleccionarTodas
            ) {

                btnDeseleccionarTodas.disabled =
                    bloquear;
            }


            const checks =
                document.querySelectorAll(
                    '.check-factura'
                );


            checks.forEach(
                function (check) {

                    if (
                        bloquear
                    ) {

                        if (
                            check.dataset.disabledOriginal ===
                            undefined
                        ) {

                            check.dataset.disabledOriginal =
                                check.disabled
                                    ? '1'
                                    : '0';
                        }


                        check.disabled =
                            true;

                    } else {


                        check.disabled =
                            check.dataset.disabledOriginal ===
                            '1';


                        delete check.dataset.disabledOriginal;

                    }

                }
            );
        }


        // ==================================================
        // ENVIAR LOTE
        // ==================================================

        async function procesarLote(
            lote
        ) {

            const datos =
                new FormData();


            lote.forEach(
                function (idFactura) {

                    datos.append(
                        'facturas[]',
                        idFactura
                    );
                }
            );


            const respuesta =
                await fetch(
                    urlProcesarLote,
                    {
                        method:
                            'POST',

                        body:
                            datos,

                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest'
                        }
                    }
                );


            if (
                !respuesta.ok
            ) {

                throw new Error(
                    'Error HTTP ' +
                    respuesta.status
                );
            }


            const texto =
                await respuesta.text();


            let datosRespuesta;


            try {

                datosRespuesta =
                    JSON.parse(
                        texto
                    );

            } catch (error) {

                console.error(
                    texto
                );


                throw new Error(
                    'El servidor devolvió una respuesta inválida.'
                );
            }


            if (
                !datosRespuesta.success
            ) {

                throw new Error(
                    datosRespuesta.mensaje
                    ||
                    'No fue posible procesar el lote.'
                );
            }


            return datosRespuesta;
        }


        // ==================================================
        // ENVÍO MASIVO
        // ==================================================

        if (
            formulario
        ) {

            formulario.addEventListener(
                'submit',
                async function (event) {

                    event.preventDefault();


                    if (
                        envioEnProceso
                    ) {

                        return;
                    }


                    const seleccionadas =
                        Array.from(
                            formulario.querySelectorAll(
                                '.check-factura:checked'
                            )
                        );


                    if (
                        seleccionadas.length === 0
                    ) {

                        if (
                            mensajeSeleccion
                        ) {

                            mensajeSeleccion.style.display =
                                'block';
                        }


                        return;
                    }


                    if (
                        mensajeSeleccion
                    ) {

                        mensajeSeleccion.style.display =
                            'none';
                    }


                    const cantidad =
                        seleccionadas.length;


                    const confirmar =
                        confirm(
                            '¿Desea enviar '
                            +
                            cantidad
                            +
                            (
                                cantidad === 1
                                    ? ' factura?'
                                    : ' facturas?'
                            )
                        );


                    if (
                        !confirmar
                    ) {

                        return;
                    }


                    // ==========================================
                    // IDS
                    // ==========================================

                    const idsFacturas =
                        seleccionadas.map(
                            function (check) {

                                return parseInt(
                                    check.value,
                                    10
                                );
                            }
                        );


                    const lotes =
                        dividirEnLotes(
                            idsFacturas,
                            10
                        );


                    // ==========================================
                    // RESULTADO ACUMULADO
                    // ==========================================

                    const totalSeleccionadas =
                        idsFacturas.length;


                    let procesadasCliente =
                        0;


                    const acumulado = {

                        facturas_procesadas:
                            0,

                        facturas_enviadas:
                            0,

                        facturas_sin_destinatario:
                            0,

                        facturas_error:
                            0,

                        correos_enviados:
                            0,

                        correos_error:
                            0

                    };


                    // ==========================================
                    // PREPARAR PANTALLA
                    // ==========================================

                    panelProgreso.style.display =
                        'block';


                    resultadoFinal.className =
                        'envio-progreso-resultado';


                    resultadoFinal.textContent =
                        '';


                    barraProgreso.style.width =
                        '0%';


                    porcentajeProgreso.textContent =
                        '0%';


                    resumenProcesadas.textContent =
                        '0 / ' +
                        totalSeleccionadas;


                    resumenFacturasEnviadas.textContent =
                        '0';


                    resumenCorreosEnviados.textContent =
                        '0';


                    resumenErrores.textContent =
                        '0';


                    bloquearPantalla(
                        true
                    );


                    try {


                        // ======================================
                        // PROCESAR LOTES
                        // ======================================

                        for (
                            let i = 0;
                            i < lotes.length;
                            i++
                        ) {


                            textoProgreso.textContent =
                                'Procesando lote '
                                +
                                (i + 1)
                                +
                                ' de '
                                +
                                lotes.length
                                +
                                '...';


                            const resultado =
                                await procesarLote(
                                    lotes[i]
                                );


                            // ==================================
                            // ACUMULAR
                            // ==================================

                            acumulado.facturas_procesadas +=
                                Number(
                                    resultado.facturas_procesadas
                                    || 0
                                );


                            acumulado.facturas_enviadas +=
                                Number(
                                    resultado.facturas_enviadas
                                    || 0
                                );


                            acumulado.facturas_sin_destinatario +=
                                Number(
                                    resultado.facturas_sin_destinatario
                                    || 0
                                );


                            acumulado.facturas_error +=
                                Number(
                                    resultado.facturas_error
                                    || 0
                                );


                            acumulado.correos_enviados +=
                                Number(
                                    resultado.correos_enviados
                                    || 0
                                );


                            acumulado.correos_error +=
                                Number(
                                    resultado.correos_error
                                    || 0
                                );


                            procesadasCliente +=
                                lotes[i].length;


                            // ==================================
                            // ACTUALIZAR PANTALLA
                            // ==================================

                            actualizarProgreso(
                                procesadasCliente,
                                totalSeleccionadas
                            );


                            resumenProcesadas.textContent =
                                procesadasCliente
                                +
                                ' / '
                                +
                                totalSeleccionadas;


                            resumenFacturasEnviadas.textContent =
                                acumulado.facturas_enviadas;


                            resumenCorreosEnviados.textContent =
                                acumulado.correos_enviados;


                            resumenErrores.textContent =
                                acumulado.facturas_error
                                +
                                acumulado.correos_error;

                        }


                        // ======================================
                        // FINALIZADO
                        // ======================================

                        textoProgreso.textContent =
                            'Proceso terminado.';


                        actualizarProgreso(
                            totalSeleccionadas,
                            totalSeleccionadas
                        );


                        if (
                            acumulado.facturas_error === 0
                            &&
                            acumulado.correos_error === 0
                            &&
                            acumulado.facturas_sin_destinatario === 0
                        ) {

                            resultadoFinal.classList.add(
                                'exito'
                            );


                            resultadoFinal.textContent =
                                'Envío completado correctamente. '
                                +
                                acumulado.facturas_enviadas
                                +
                                ' factura(s) enviadas y '
                                +
                                acumulado.correos_enviados
                                +
                                ' correo(s) enviados.';

                        } else {

                            resultadoFinal.classList.add(
                                'advertencia'
                            );


                            resultadoFinal.textContent =
                                'Proceso terminado con novedades. '
                                +
                                'Facturas enviadas: '
                                +
                                acumulado.facturas_enviadas
                                +
                                '. Correos enviados: '
                                +
                                acumulado.correos_enviados
                                +
                                '. Sin destinatario: '
                                +
                                acumulado.facturas_sin_destinatario
                                +
                                '. Errores de factura: '
                                +
                                acumulado.facturas_error
                                +
                                '. Errores de correo: '
                                +
                                acumulado.correos_error
                                +
                                '.';
                        }


                    } catch (error) {


                        textoProgreso.textContent =
                            'El proceso fue interrumpido.';


                        resultadoFinal.classList.add(
                            'error'
                        );


                        resultadoFinal.textContent =
                            'Error: '
                            +
                            error.message;


                        console.error(
                            error
                        );


                    } finally {


                        bloquearPantalla(
                            false
                        );

                    }

                }
            );
        }

    }
);

</script>


</body>

</html>