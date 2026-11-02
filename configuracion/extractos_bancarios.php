<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/auth/requerir_sesion.php";
require_once ROOT_PATH . "/config/conexion.php";

$tipoMensaje = $_GET['tipo'] ?? null;
$mensaje = $_GET['mensaje'] ?? null;

// CUENTAS BANCARIAS ACTIVAS
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
$cuentasBancarias = $conexion->query($sqlCuentas)->fetchAll(PDO::FETCH_ASSOC);

// DOCUMENTOS. Un documento queda protegido si tiene al menos un movimiento
// conciliado o asociado a un pago.
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
        cb.numero_cuenta,
        EXISTS (
            SELECT 1
            FROM extractos_bancarios e
            LEFT JOIN pagos p ON p.id_extracto = e.id_extracto
            WHERE e.id_documento = db.id_documento
              AND (
                    e.estado_conciliacion = 'CONCILIADO'
                    OR p.id_pago IS NOT NULL
              )
        ) AS protegido_por_pago,
        (
            SELECT COUNT(*)
            FROM extractos_bancarios e
            WHERE e.id_documento = db.id_documento
        ) AS cantidad_movimientos
    FROM documentos_bancarios db
    LEFT JOIN cuentas_bancarias cb
        ON cb.id_cuenta_bancaria = db.id_cuenta_bancaria
    ORDER BY db.fecha_creacion DESC, db.id_documento DESC
";
$documentosBancarios = $conexion->query($sqlDocumentos)->fetchAll(PDO::FETCH_ASSOC);

// MOVIMIENTOS DEL EXTRACTO
$sqlMovimientos = "
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
        db.metodo_extraccion,
        db.estado_procesamiento,
        db.observaciones AS observaciones_documento
    FROM extractos_bancarios eb
    LEFT JOIN cuentas_bancarias cb
        ON cb.id_cuenta_bancaria = eb.id_cuenta_bancaria
    LEFT JOIN documentos_bancarios db
        ON db.id_documento = eb.id_documento
    ORDER BY eb.fecha_movimiento DESC, eb.id_extracto DESC
";
$extractos = $conexion->query($sqlMovimientos)->fetchAll(PDO::FETCH_ASSOC);

// Etiquetas de meses iguales a las pestañas. Cada documento puede incluir
// movimientos de más de un mes, por eso se conserva cada mes distinto.
$nombreMeses = [
    1 => 'Enero',
    2 => 'Febrero',
    3 => 'Marzo',
    4 => 'Abril',
    5 => 'Mayo',
    6 => 'Junio',
    7 => 'Julio',
    8 => 'Agosto',
    9 => 'Septiembre',
    10 => 'Octubre',
    11 => 'Noviembre',
    12 => 'Diciembre'
];

$mesesDisponibles = [];
$mesesPorDocumento = [];

foreach ($extractos as $extracto) {
    $marcaTiempo = strtotime((string)($extracto['fecha_movimiento'] ?? ''));
    if ($marcaTiempo === false) {
        continue;
    }

    $claveMes = date('Y-m', $marcaTiempo);
    $etiquetaMes = $nombreMeses[(int)date('n', $marcaTiempo)] . ' ' . date('Y', $marcaTiempo);
    $mesesDisponibles[$claveMes] = $etiquetaMes;

    $idDocumento = (int)($extracto['id_documento'] ?? 0);
    if ($idDocumento > 0) {
        $mesesPorDocumento[$idDocumento][$claveMes] = $etiquetaMes;
    }
}

ksort($mesesDisponibles, SORT_STRING);
foreach ($mesesPorDocumento as &$mesesDocumento) {
    ksort($mesesDocumento, SORT_STRING);
}
unset($mesesDocumento);

function escaparExtracto($valor): string
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function urlArchivoExtracto(int $idDocumento): string
{
    return BASE_URL .
        'actions/abrir_archivo_privado.php?tipo=extracto&id=' .
        $idDocumento;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include ROOT_PATH . "/includes/head.php"; ?>
    <style>
        .tabla-documentos th,
        .tabla-documentos td {
            vertical-align: middle;
        }
        .documento-periodo {
            display: block;
            margin-top: 3px;
        }
        .documento-observaciones {
            min-width: 220px;
            max-width: 360px;
            text-align: left;
            white-space: normal;
        }
        .documento-acciones {
            min-width: 130px;
            white-space: nowrap;
        }
        .documento-acciones form {
            display: inline-block;
            margin: 4px 2px 0;
        }
        .documento-acciones .btn-filtrar,
        .documento-acciones .btn-secondary {
            display: inline-block;
            margin: 2px;
        }
        .extractos-pestanas {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        .tabla-movimientos tr[hidden] {
            display: none;
        }
    </style>
</head>
<body>
<?php include ROOT_PATH . "/includes/header.php"; ?>
<?php require_once ROOT_PATH . "/includes/mensajes.php"; ?>

<div class="contenedor">
    <?php include ROOT_PATH . "/includes/sidebar.php"; ?>

    <main class="contenido">
        <h2 align="center">Extractos bancarios</h2>
        <p>Administra los extractos bancarios utilizados para identificar y conciliar los pagos recibidos por la copropiedad.</p>
        <br>

        <button type="button" id="btnNuevoExtracto" class="btn-filtrar">
            Cargar extracto bancario
        </button>

        <div class="bloque filtros">
            <div class="form-card">
                <h3>Documentos bancarios cargados</h3>
                <br>

                <div class="tabla-responsive">
                    <table class="tabla tabla-documentos">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Cuenta bancaria</th>
                                <th>Método</th>
                                <th>Estado</th>
                                <th>Fecha de carga</th>
                                <th>Observaciones</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($documentosBancarios)): ?>
                            <tr>
                                <td colspan="7" align="center">No hay documentos bancarios cargados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($documentosBancarios as $documento): ?>
                                <?php
                                $idDocumentoFila = (int)$documento['id_documento'];
                                $protegido = (int)$documento['protegido_por_pago'] === 1;
                                $cantidadMovimientos = (int)$documento['cantidad_movimientos'];
                                $estadoDocumento = (string)$documento['estado_procesamiento'];
                                $periodosDocumento = $mesesPorDocumento[$idDocumentoFila] ?? [];
                                $urlArchivo = urlArchivoExtracto($idDocumentoFila);
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= escaparExtracto($documento['nombre_original'] ?: $documento['nombre_archivo']) ?></strong>
                                        <small class="documento-periodo">
                                            Período:
                                            <?= !empty($periodosDocumento)
                                                ? escaparExtracto(implode(', ', array_values($periodosDocumento)))
                                                : 'Sin movimientos' ?>
                                        </small>
                                    </td>

                                    <td>
                                        <?php if (!empty($documento['banco'])): ?>
                                            <strong><?= escaparExtracto($documento['banco']) ?></strong><br>
                                            <small>
                                                <?= escaparExtracto($documento['tipo_cuenta']) ?> - **** -
                                                <?= escaparExtracto(substr((string)$documento['numero_cuenta'], -4)) ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="inactivo">Cuenta no disponible</span>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= escaparExtracto($documento['metodo_extraccion'] ?? '') ?></td>

                                    <td>
                                        <?php
                                        $etiquetasEstado = [
                                            'PENDIENTE' => ['Pendiente', 'advertencia'],
                                            'PROCESANDO' => ['Procesando', 'advertencia'],
                                            'PROCESADO' => ['Procesado', 'activo'],
                                            'ERROR' => ['Error', 'inactivo'],
                                            'REVISADO' => ['Revisado', 'activo']
                                        ];
                                        $estadoUI = $etiquetasEstado[$estadoDocumento] ?? [$estadoDocumento, 'inactivo'];
                                        ?>
                                        <span class="<?= escaparExtracto($estadoUI[1]) ?>">
                                            <?= escaparExtracto($estadoUI[0]) ?>
                                        </span>
                                    </td>

                                    <td><?= escaparExtracto($documento['fecha_creacion'] ?? '') ?></td>

                                    <td class="documento-observaciones">
                                        <?php if (!empty($documento['observaciones'])): ?>
                                            <?= nl2br(escaparExtracto($documento['observaciones'])) ?>
                                        <?php endif; ?>
                                        <?php if ($protegido): ?>
                                            <?php if (!empty($documento['observaciones'])): ?><br><?php endif; ?>
                                            <span class="advertencia">
                                                Tiene movimientos conciliados o vinculados a pagos; no se puede eliminar ni reprocesar.
                                            </span>
                                        <?php elseif ($estadoDocumento === 'PROCESANDO'): ?>
                                            <?php if (!empty($documento['observaciones'])): ?><br><?php endif; ?>
                                            <small>El documento está en proceso.</small>
                                        <?php endif; ?>
                                    </td>

                                    <td class="documento-acciones">
                                        <?php if ($estadoDocumento === 'PENDIENTE'): ?>
                                            <a
                                                href="<?= escaparExtracto(BASE_URL) ?>actions/procesar_extracto_bancario.php?id_documento=<?= $idDocumentoFila ?>"
                                                class="btn-filtrar"
                                                onclick="return confirm('¿Desea procesar este documento bancario?');">
                                                Procesar
                                            </a>

                                        <?php elseif ($estadoDocumento === 'PROCESANDO'): ?>
                                            <form method="POST" action="<?= escaparExtracto(BASE_URL) ?>actions/restablecer_procesamiento_extracto.php">
                                                <input type="hidden" name="id_documento" value="<?= $idDocumentoFila ?>">
                                                <input type="hidden" name="reprocesar" value="<?= $cantidadMovimientos > 0 ? '1' : '0' ?>">
                                                <button
                                                    type="submit"
                                                    class="btn-filtrar"
                                                    onclick="return confirm('Usa esta opción solo si el proceso terminó o la ventana se cerró. ¿Restablecer el documento?');">
                                                    Restablecer
                                                </button>
                                            </form>

                                        <?php elseif ($estadoDocumento === 'PROCESADO'): ?>
                                            <?php if ($urlArchivo !== ''): ?>
                                                <a href="<?= escaparExtracto($urlArchivo) ?>" target="_blank" rel="noopener" class="btn-filtrar">Ver</a>
                                            <?php endif; ?>

                                            <?php if (!$protegido): ?>
                                                <a
                                                    href="<?= escaparExtracto(BASE_URL) ?>actions/procesar_extracto_bancario.php?id_documento=<?= $idDocumentoFila ?>&amp;reprocesar=1"
                                                    class="btn-filtrar"
                                                    onclick="return confirm('El nuevo análisis reemplazará los movimientos actuales. ¿Deseas continuar?');">
                                                    Reprocesar
                                                </a>
                                            <?php endif; ?>

                                        <?php elseif ($estadoDocumento === 'ERROR'): ?>
                                            <?php if (!$protegido): ?>
                                                <?php $modoReproceso = $cantidadMovimientos > 0; ?>
                                                <a
                                                    href="<?= escaparExtracto(BASE_URL) ?>actions/procesar_extracto_bancario.php?id_documento=<?= $idDocumentoFila ?><?= $modoReproceso ? '&amp;reprocesar=1' : '' ?>"
                                                    class="btn-filtrar"
                                                    onclick="return confirm('¿Deseas volver a procesar este documento?');">
                                                    <?= $modoReproceso ? 'Reprocesar' : 'Reintentar' ?>
                                                </a>
                                            <?php endif; ?>

                                        <?php elseif ($estadoDocumento === 'REVISADO'): ?>
                                            <?php if ($urlArchivo !== ''): ?>
                                                <a href="<?= escaparExtracto($urlArchivo) ?>" target="_blank" rel="noopener" class="btn-filtrar">Ver</a>
                                            <?php endif; ?>

                                        <?php else: ?>
                                            <span class="inactivo">Sin acción</span>
                                        <?php endif; ?>

                                        <?php if ($estadoDocumento !== 'PROCESANDO' && !$protegido): ?>
                                            <form
                                                method="POST"
                                                action="<?= escaparExtracto(BASE_URL) ?>actions/eliminar_documento_bancario.php"
                                                onsubmit="return confirm('¿Eliminar este documento y sus <?= $cantidadMovimientos ?> movimientos? Esta acción no se puede deshacer.');">
                                                <input type="hidden" name="id_documento" value="<?= $idDocumentoFila ?>">
                                                <button type="submit" class="btn-secondary">Eliminar</button>
                                            </form>
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

        <div class="tabs-container extractos-pestanas" role="group" aria-label="Filtrar movimientos por mes">
            <button type="button" class="tab-button active" data-filtro-mes="todos" aria-pressed="true">Todos</button>
            <?php foreach ($mesesDisponibles as $claveMes => $etiquetaMes): ?>
                <button
                    type="button"
                    class="tab-button"
                    data-filtro-mes="<?= escaparExtracto($claveMes) ?>"
                    aria-pressed="false">
                    <?= escaparExtracto($etiquetaMes) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="bloque filtros">
            <div class="form-card">
                <h3>Movimientos bancarios</h3>
                <br>
                <div class="tabla-responsive">
                    <table class="tabla tabla-movimientos">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Cuenta bancaria</th>
                                <th>Descripción</th>
                                <th>Referencia</th>
                                <th>Valor</th>
                                <th>Tipo</th>
                                <th>Conciliación</th>
                                <th>OCR</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($extractos)): ?>
                            <tr><td colspan="8" align="center">No hay movimientos bancarios registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($extractos as $extracto): ?>
                                <?php
                                $marcaTiempoMovimiento = strtotime((string)$extracto['fecha_movimiento']);
                                $mesMovimiento = $marcaTiempoMovimiento !== false ? date('Y-m', $marcaTiempoMovimiento) : '';
                                $estadoOCR = (string)($extracto['estado_procesamiento'] ?? 'PENDIENTE');
                                ?>
                                <tr data-mes-movimiento="<?= escaparExtracto($mesMovimiento) ?>">
                                    <td><?= escaparExtracto($extracto['fecha_movimiento']) ?></td>
                                    <td>
                                        <strong><?= escaparExtracto($extracto['banco'] ?? '') ?></strong><br>
                                        <small><?= escaparExtracto($extracto['tipo_cuenta'] ?? '') ?> - <?= escaparExtracto($extracto['numero_cuenta'] ?? '') ?></small>
                                    </td>
                                    <td><?= escaparExtracto($extracto['descripcion'] ?? '') ?></td>
                                    <td>
                                        <?= escaparExtracto($extracto['referencia'] ?? '') ?>
                                        <?php if (!empty($extracto['numero_documento'])): ?>
                                            <br><small>Doc: <?= escaparExtracto($extracto['numero_documento']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong>$ <?= number_format((float)$extracto['valor'], 2, ',', '.') ?></strong></td>
                                    <td>
                                        <?php if ($extracto['tipo_movimiento'] === 'INGRESO'): ?>
                                            <span class="activo">Ingreso</span>
                                        <?php else: ?>
                                            <span class="inactivo">Egreso</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($extracto['estado_conciliacion'] === 'CONCILIADO'): ?>
                                            <span class="activo">Conciliado</span>
                                        <?php elseif ($extracto['estado_conciliacion'] === 'RECHAZADO'): ?>
                                            <span class="inactivo">Rechazado</span>
                                        <?php elseif ($extracto['estado_conciliacion'] === 'CON_DIFERENCIA'): ?>
                                            <span class="advertencia">Con diferencia</span>
                                        <?php else: ?>
                                            <span class="advertencia">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($estadoOCR === 'PROCESADO'): ?>
                                            <span class="activo">Procesado</span>
                                        <?php elseif ($estadoOCR === 'PROCESANDO'): ?>
                                            <span class="advertencia">Procesando</span>
                                        <?php elseif ($estadoOCR === 'ERROR'): ?>
                                            <span class="inactivo" title="<?= escaparExtracto($extracto['observaciones_documento'] ?? '') ?>">Error</span>
                                        <?php else: ?>
                                            <span class="advertencia">Pendiente</span>
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

<!-- MODAL PARA CARGAR EXTRACTO -->
<div id="modalNuevoExtracto" class="modal">
    <div class="modal-contenido">
        <div class="modal-header">
            <h3>Cargar extracto bancario</h3>
            <button type="button" class="modal-cerrar" id="cerrarNuevoExtracto">&times;</button>
        </div>

        <form
            action="<?= escaparExtracto(BASE_URL) ?>actions/guardar_extracto_bancario.php"
            method="POST"
            enctype="multipart/form-data">
            <div class="form-group">
                <label for="id_cuenta_bancaria">Cuenta bancaria *</label>
                <select name="id_cuenta_bancaria" id="id_cuenta_bancaria" required>
                    <option value="">Seleccione una cuenta...</option>
                    <?php foreach ($cuentasBancarias as $cuenta): ?>
                        <option value="<?= (int)$cuenta['id_cuenta_bancaria'] ?>">
                            <?= escaparExtracto($cuenta['banco']) ?> -
                            <?= escaparExtracto($cuenta['tipo_cuenta']) ?> - ****
                            <?= escaparExtracto(substr((string)$cuenta['numero_cuenta'], -4)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="archivo">Extracto bancario *</label>
                <input type="file" name="archivo" id="archivo" accept=".pdf,.jpg,.jpeg,.png" required>
                <small>Formatos permitidos: PDF, JPG, JPEG y PNG.</small>
            </div>

            <div class="form-group">
                <label for="observaciones">Observaciones</label>
                <textarea name="observaciones" id="observaciones" maxlength="255" rows="3" placeholder="Observaciones del extracto"></textarea>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-limpiar" id="cancelarNuevoExtracto">Cancelar</button>
                <button type="submit" class="btn-filtrar">Guardar extracto</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const botonesMes = document.querySelectorAll('[data-filtro-mes]');
    const filasMovimiento = document.querySelectorAll('[data-mes-movimiento]');

    botonesMes.forEach(function (boton) {
        boton.addEventListener('click', function () {
            const mes = boton.dataset.filtroMes;

            botonesMes.forEach(function (otroBoton) {
                const activo = otroBoton === boton;
                otroBoton.setAttribute('aria-pressed', activo ? 'true' : 'false');
                otroBoton.classList.toggle('active', activo);
            });

            filasMovimiento.forEach(function (fila) {
                fila.hidden = mes !== 'todos' && fila.dataset.mesMovimiento !== mes;
            });
        });
    });
});
</script>
<script src="<?= escaparExtracto(BASE_URL) ?>assets/js/extractos_bancarios.js"></script>
</body>
</html>
