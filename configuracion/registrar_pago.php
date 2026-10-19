<?php
// ==========================================================
// REGISTRAR PAGO
// Muestra el formulario para registrar un nuevo pago.
// ==========================================================

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

// Escapa texto antes de mostrarlo en pantalla.
function e($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

// ==========================================================
// UNIDAD PRESELECCIONADA
// Recibe la unidad cuando se abre desde cartera.
// ==========================================================
$idUnidadSeleccionada = isset($_GET['id_unidad']) ? (int)$_GET['id_unidad'] : 0;

// ==========================================================
// UNIDADES ACTIVAS
// Carga las unidades disponibles para registrar el pago.
// ==========================================================
$sqlUnidades = "
    SELECT
        u.id_unidad,
        u.codigo,
        u.nombre,
        dtu.nombre_grupo
    FROM unidades u
    LEFT JOIN detalle_tipos_unidad dtu
        ON dtu.id_tipo_config = u.id_tipo_config
    WHERE u.activo = 1
    ORDER BY dtu.nombre_grupo, u.codigo
";

$stmtUnidades = $conexion->query($sqlUnidades);
$unidades = $stmtUnidades->fetchAll(PDO::FETCH_ASSOC);

// ==========================================================
// MENSAJE DE RETORNO
// Recibe el resultado enviado por guardar_pago.php.
// ==========================================================
$tipo = trim($_GET['tipo'] ?? '');
$texto = trim($_GET['texto'] ?? $_GET['mensaje'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php include ROOT_PATH . "/includes/head.php"; ?>
</head>
<body>

<?php include ROOT_PATH . "/includes/header.php"; ?>

<div class="contenedor">

    <?php include ROOT_PATH . "/includes/sidebar.php"; ?>

    <main class="contenido">

        <div style="display:flex; justify-content:space-between; align-items:center; gap:15px; flex-wrap:wrap;">
            <div>
                <h2>Registrar pago</h2>
                <p>Registra un nuevo ingreso para una unidad. El pago quedará disponible para aplicarlo posteriormente a una o varias obligaciones.</p>
            </div>

            <a href="<?= BASE_URL ?>configuracion/pagos.php" class="btn-secondary">
                ← Volver a pagos
            </a>
        </div>

        <br>

        <div class="bloque filtros">
            <div class="form-card">

                <h3>Datos del pago</h3>
                <br>

                <!-- REGISTRO DEL PAGO: guarda el ingreso sin aplicarlo todavía a cartera. -->
                <form method="POST" action="<?= BASE_URL ?>actions/guardar_pago.php">

                    <div class="form-grid">

                        <div>
                            <label>Unidad *</label>
                            <select name="id_unidad" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($unidades as $unidad): ?>
                                    <option
                                        value="<?= (int)$unidad['id_unidad'] ?>"
                                        <?= $idUnidadSeleccionada === (int)$unidad['id_unidad'] ? 'selected' : '' ?>
                                    >
                                        <?= e($unidad['codigo']) ?>
                                        <?= !empty($unidad['nombre_grupo']) ? ' - ' . e($unidad['nombre_grupo']) : '' ?>
                                        <?= !empty($unidad['nombre']) ? ' - ' . e($unidad['nombre']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label>Fecha del pago *</label>
                            <input type="date" name="fecha_pago" value="<?= e(date('Y-m-d')) ?>" required>
                        </div>

                        <div>
                            <label>Valor *</label>
                            <input type="number" name="valor" min="0.01" step="0.01" required placeholder="0.00">
                        </div>

                        <div>
                            <label>Medio de pago *</label>
                            <select name="medio_pago" required>
                                <option value="">Seleccione...</option>
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                                <option value="CONSIGNACION">Consignación</option>
                                <option value="PSE">PSE</option>
                                <option value="TARJETA">Tarjeta</option>
                                <option value="OTRO">Otro</option>
                            </select>
                        </div>

                        <div>
                            <label>Origen *</label>
                            <select name="origen_pago" required>
                                <option value="MANUAL" selected>Manual</option>
                                <option value="BANCO">Banco</option>
                                <option value="PASARELA">Pasarela</option>
                            </select>
                        </div>

                        <div>
                            <label>Referencia</label>
                            <input type="text" name="referencia" maxlength="255" placeholder="Recibo, transferencia, comprobante...">
                        </div>

                        <div>
                            <label>Referencia externa</label>
                            <input type="text" name="referencia_externa" maxlength="255">
                        </div>

                        <div>
                            <label>ID externo</label>
                            <input type="text" name="id_externo" maxlength="255">
                        </div>

                    </div>

                    <br>

                    <div>
                        <label>Observaciones</label>
                        <textarea name="observaciones" rows="4" style="width:100%;" placeholder="Observaciones opcionales"></textarea>
                    </div>

                    <br>

                    <div class="form-actions">
                        <a href="<?= BASE_URL ?>configuracion/pagos.php" class="btn-limpiar">Cancelar</a>
                        <button type="submit" class="btn-filtrar">Guardar pago</button>
                    </div>

                </form>

            </div>
        </div>

    </main>
</div>


<?php if ($texto !== ''): ?>

    <!-- ======================================================
         MENSAJE DE RESULTADO
         Muestra el resultado del registro en una ventana emergente.
    ======================================================= -->
    <div
        id="modalMensajePago"
        style="
            display:flex;
            position:fixed;
            inset:0;
            z-index:9999;
            background:rgba(0,0,0,.50);
            align-items:center;
            justify-content:center;
            padding:20px;
        "
    >
        <div
            style="
                width:min(430px, 94vw);
                background:#fff;
                border-radius:12px;
                box-shadow:0 20px 60px rgba(0,0,0,.25);
                overflow:hidden;
            "
        >
            <div
                style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    gap:15px;
                    padding:18px 20px;
                    border-bottom:1px solid #e5e7eb;
                "
            >
                <h3 style="margin:0;">
                    <?php if ($tipo === 'success'): ?>
                        ✓ Operación exitosa
                    <?php elseif ($tipo === 'error'): ?>
                        ⚠ Ocurrió un error
                    <?php elseif ($tipo === 'warning'): ?>
                        ⚠ Atención
                    <?php else: ?>
                        Información
                    <?php endif; ?>
                </h3>

                <button
                    type="button"
                    id="cerrarModalMensajePago"
                    aria-label="Cerrar"
                    style="
                        border:0;
                        background:transparent;
                        font-size:28px;
                        cursor:pointer;
                        line-height:1;
                    "
                >
                    &times;
                </button>
            </div>

            <div style="padding:20px;">
                <p style="margin:0; font-size:15px; line-height:1.5;">
                    <?= e($texto) ?>
                </p>

                <div
                    style="
                        display:flex;
                        justify-content:flex-end;
                        margin-top:20px;
                    "
                >
                    <button
                        type="button"
                        id="aceptarModalMensajePago"
                        class="btn-filtrar"
                    >
                        Aceptar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('modalMensajePago');
            const btnCerrar = document.getElementById('cerrarModalMensajePago');
            const btnAceptar = document.getElementById('aceptarModalMensajePago');

            // ======================================================
            // CERRAR MENSAJE
            // Oculta el popup y limpia el mensaje de la URL.
            // ======================================================
            function cerrarMensaje() {
                if (modal) {
                    modal.style.display = 'none';
                }

                const url = new URL(window.location.href);
                url.searchParams.delete('tipo');
                url.searchParams.delete('texto');
                url.searchParams.delete('mensaje');

                window.history.replaceState({}, '', url.toString());
            }

            if (btnCerrar) {
                btnCerrar.addEventListener('click', cerrarMensaje);
            }

            if (btnAceptar) {
                btnAceptar.addEventListener('click', cerrarMensaje);
            }

            if (modal) {
                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        cerrarMensaje();
                    }
                });
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    cerrarMensaje();
                }
            });
        });
    </script>

<?php endif; ?>

</body>
</html>
