<?php
require_once __DIR__ . "/config/config.php";
require_once ROOT_PATH . "/auth/requerir_sesion.php";
require_once ROOT_PATH . "/config/conexion.php";

$tituloPagina = "Panel principal";

$nombreUsuario = trim((string) (
    $_SESSION['nombre_usuario_autenticado']
    ?? $_SESSION['nombre_usuario']
    ?? 'Administrador'
));

$primerNombre = explode(' ', $nombreUsuario)[0] ?: 'Administrador';

$horaActual = (int) date('G');
$saludo = $horaActual < 12
    ? 'Buenos días'
    : ($horaActual < 19 ? 'Buenas tardes' : 'Buenas noches');

$diasSemana = [
    1 => 'lunes', 2 => 'martes', 3 => 'miércoles',
    4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo'
];

$mesesNombre = [
    1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
    5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
    9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
];

$fechaHoy = $diasSemana[(int) date('N')]
    . ', ' . date('d')
    . ' de ' . $mesesNombre[(int) date('n')]
    . ' de ' . date('Y');

$dashboardScalar = static function (string $sql) use ($conexion) {
    try {
        $stmt = $conexion->query($sql);
        $valor = $stmt->fetchColumn();
        return $valor === false ? 0 : (int) $valor;
    } catch (Throwable $e) {
        error_log('Convivium dashboard: ' . $e->getMessage());
        return null;
    }
};

$dashboardRows = static function (string $sql) use ($conexion): array {
    try {
        $stmt = $conexion->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('Convivium dashboard: ' . $e->getMessage());
        return [];
    }
};

$escapar = static function ($valor): string {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
};

$dinero = static function ($valor): string {
    return '$ ' . number_format((float) $valor, 0, ',', '.');
};

/* Indicadores conectados a las tablas existentes del proyecto. */
$unidadesActivas = $dashboardScalar(
    "SELECT COUNT(*) FROM unidades WHERE activo = 1"
);

$residentesActivos = $dashboardScalar(
    "SELECT COUNT(*) FROM residente WHERE activo = 1 AND fecha_hasta IS NULL"
);

$obligacionesPendientes = $dashboardScalar(
    "SELECT COUNT(*) FROM cartera WHERE estado = 'PENDIENTE' AND saldo > 0.009"
);

$solicitudesAbiertas = $dashboardScalar(
    "SELECT COUNT(*) FROM mantenimiento WHERE estado IN ('pendiente', 'en_proceso')"
);

/* Resumen de obligaciones: pagadas, pendientes, vencidas y por vencer. */
$filasResumenCartera = $dashboardRows("
    SELECT
        COUNT(*) AS total_obligaciones,
        COALESCE(SUM(CASE WHEN estado = 'PAGADA' THEN 1 ELSE 0 END), 0) AS pagadas,
        COALESCE(SUM(CASE WHEN estado = 'PENDIENTE' AND saldo > 0.009 THEN 1 ELSE 0 END), 0) AS pendientes,
        COALESCE(SUM(CASE WHEN estado = 'PENDIENTE' AND saldo > 0.009 AND fecha_vencimiento < CURDATE() THEN 1 ELSE 0 END), 0) AS vencidas,
        COALESCE(SUM(CASE WHEN estado = 'PENDIENTE' AND saldo > 0.009 AND fecha_vencimiento >= CURDATE() THEN 1 ELSE 0 END), 0) AS por_vencer
    FROM cartera
    WHERE estado <> 'ANULADA'
");

$resumenCartera = $filasResumenCartera[0] ?? [
    'total_obligaciones' => 0,
    'pagadas' => 0,
    'pendientes' => 0,
    'vencidas' => 0,
    'por_vencer' => 0
];

$totalObligaciones = (int) ($resumenCartera['total_obligaciones'] ?? 0);
$totalPagadas = (int) ($resumenCartera['pagadas'] ?? 0);
$totalPendientes = (int) ($resumenCartera['pendientes'] ?? 0);
$totalVencidas = (int) ($resumenCartera['vencidas'] ?? 0);
$totalPorVencer = (int) ($resumenCartera['por_vencer'] ?? 0);

$porcentajePagado = $totalObligaciones > 0
    ? (int) round(($totalPagadas / $totalObligaciones) * 100)
    : 0;

/* Evolución mensual de cartera por fecha de vencimiento. */
$filasGrafico = $dashboardRows("
    SELECT
        DATE_FORMAT(fecha_vencimiento, '%Y-%m') AS periodo,
        COALESCE(SUM(valor_pagado), 0) AS pagado,
        COALESCE(SUM(saldo), 0) AS pendiente
    FROM cartera
    WHERE
        estado <> 'ANULADA'
        AND fecha_vencimiento >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 11 MONTH)
        AND fecha_vencimiento < DATE_ADD(LAST_DAY(CURDATE()), INTERVAL 1 DAY)
    GROUP BY DATE_FORMAT(fecha_vencimiento, '%Y-%m')
    ORDER BY periodo
");

$graficoPorMes = [];
foreach ($filasGrafico as $filaGrafico) {
    $graficoPorMes[$filaGrafico['periodo']] = [
        'pagado' => (float) $filaGrafico['pagado'],
        'pendiente' => (float) $filaGrafico['pendiente']
    ];
}

$nombresMesCorto = [
    1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
    5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
    9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
];

$mesesGrafico = [];
$inicioMes = new DateTimeImmutable('first day of this month');
$maximoGrafico = 1;

for ($i = 11; $i >= 0; $i--) {
    $fechaMes = $inicioMes->modify('-' . $i . ' months');
    $claveMes = $fechaMes->format('Y-m');
    $valoresMes = $graficoPorMes[$claveMes] ?? [
        'pagado' => 0,
        'pendiente' => 0
    ];

    $maximoGrafico = max(
        $maximoGrafico,
        $valoresMes['pagado'],
        $valoresMes['pendiente']
    );

    $mesesGrafico[] = [
        'clave' => $claveMes,
        'nombre' => $nombresMesCorto[(int) $fechaMes->format('n')],
        'pagado' => $valoresMes['pagado'],
        'pendiente' => $valoresMes['pendiente']
    ];
}

/* Solicitudes recientes de mantenimiento. */
$mantenimientosRecientes = $dashboardRows("
    SELECT
        m.id,
        m.descripcion,
        m.prioridad,
        m.estado,
        m.fecha_reporte,
        z.nombre AS nombre_zona,
        u.nombres AS nombre_reporta,
        u.apellidos AS apellido_reporta
    FROM mantenimiento m
    LEFT JOIN zona_comun z ON z.id = m.zona_id
    LEFT JOIN usuario u ON u.id = m.usuario_reporta_id
    ORDER BY m.fecha_reporte DESC, m.id DESC
    LIMIT 5
");

/* Pagos recientes registrados. */
$pagosRecientes = $dashboardRows("
    SELECT
        p.id_pago,
        p.fecha_pago,
        p.valor,
        p.estado,
        u.codigo AS unidad_codigo
    FROM pagos p
    INNER JOIN unidades u ON u.id_unidad = p.id_unidad
    WHERE p.estado = 'REGISTRADO'
    ORDER BY p.fecha_pago DESC, p.id_pago DESC
    LIMIT 5
");

/* Se combinan los movimientos reales para construir la actividad reciente. */
$actividadReciente = [];

foreach ($mantenimientosRecientes as $mantenimiento) {
    $estadoMantenimiento = (string) ($mantenimiento['estado'] ?? '');
    $estadoTexto = [
        'pendiente' => 'Pendiente',
        'en_proceso' => 'En proceso',
        'solucionado' => 'Solucionado'
    ][$estadoMantenimiento] ?? ucfirst(str_replace('_', ' ', $estadoMantenimiento));

    $claseEstado = $estadoMantenimiento === 'solucionado'
        ? 'dashboard-status--done'
        : ($estadoMantenimiento === 'pendiente'
            ? 'dashboard-status--pending'
            : 'dashboard-status--progress');

    $actividadReciente[] = [
        'orden' => strtotime((string) ($mantenimiento['fecha_reporte'] ?? '')) ?: 0,
        'tipo' => 'mantenimiento',
        'titulo' => 'Solicitud de mantenimiento',
        'detalle' => trim(
            (string) ($mantenimiento['nombre_zona'] ?? 'Zona común')
            . ' · ' . (string) ($mantenimiento['descripcion'] ?? '')
        ),
        'fecha' => $mantenimiento['fecha_reporte'] ?? '',
        'estado' => $estadoTexto,
        'clase' => $claseEstado
    ];
}

foreach ($pagosRecientes as $pago) {
    $actividadReciente[] = [
        'orden' => strtotime((string) ($pago['fecha_pago'] ?? '')) ?: 0,
        'tipo' => 'pago',
        'titulo' => 'Pago recibido',
        'detalle' => 'Unidad ' . (string) ($pago['unidad_codigo'] ?? '—')
            . ' · ' . $dinero($pago['valor'] ?? 0),
        'fecha' => $pago['fecha_pago'] ?? '',
        'estado' => 'Registrado',
        'clase' => 'dashboard-status--done'
    ];
}

usort($actividadReciente, static function (array $a, array $b): int {
    return $b['orden'] <=> $a['orden'];
});

$actividadReciente = array_slice($actividadReciente, 0, 6);

$estadoMantenimientoTexto = [
    'pendiente' => 'Pendiente',
    'en_proceso' => 'En proceso',
    'solucionado' => 'Solucionado'
];

$estadoMantenimientoClase = [
    'pendiente' => 'dashboard-status--pending',
    'en_proceso' => 'dashboard-status--progress',
    'solucionado' => 'dashboard-status--done'
];

$prioridadClase = [
    'baja' => 'dashboard-priority--baja',
    'media' => 'dashboard-priority--media',
    'alta' => 'dashboard-priority--alta',
    'critica' => 'dashboard-priority--critica'
];
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
            <div class="dashboard">
                <section class="dashboard-welcome">
                    <div>
                        <h2><?= $escapar($saludo) ?>, <?= $escapar($primerNombre) ?></h2>
                        <p>Aquí tienes un resumen de la operación de tu comunidad.</p>
                    </div>
                    <div class="dashboard-date">
                        <?= $escapar($fechaHoy) ?>
                    </div>
                </section>

                <section class="dashboard-stats" aria-label="Indicadores principales">
                    <a class="dashboard-stat-card" href="<?= BASE_URL ?>configuracion/unidades.php">
                        <span class="dashboard-stat-icon dashboard-stat-icon--units">
                            <i class="fa-solid fa-building" aria-hidden="true"></i>
                        </span>
                        <span class="dashboard-stat-copy">
                            <span class="dashboard-stat-label">Unidades activas</span>
                            <strong class="dashboard-stat-value">
                                <?= $unidadesActivas === null ? '—' : number_format($unidadesActivas, 0, ',', '.') ?>
                            </strong>
                            <span class="dashboard-stat-note">Unidades registradas</span>
                        </span>
                        <i class="fa-solid fa-chevron-right dashboard-stat-arrow" aria-hidden="true"></i>
                    </a>

                    <a class="dashboard-stat-card" href="<?= BASE_URL ?>configuracion/usuarios.php">
                        <span class="dashboard-stat-icon dashboard-stat-icon--residents">
                            <i class="fa-solid fa-users" aria-hidden="true"></i>
                        </span>
                        <span class="dashboard-stat-copy">
                            <span class="dashboard-stat-label">Residentes activos</span>
                            <strong class="dashboard-stat-value">
                                <?= $residentesActivos === null ? '—' : number_format($residentesActivos, 0, ',', '.') ?>
                            </strong>
                            <span class="dashboard-stat-note">Relaciones vigentes</span>
                        </span>
                        <i class="fa-solid fa-chevron-right dashboard-stat-arrow" aria-hidden="true"></i>
                    </a>

                    <a class="dashboard-stat-card" href="<?= BASE_URL ?>configuracion/cartera.php">
                        <span class="dashboard-stat-icon dashboard-stat-icon--payments">
                            <i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>
                        </span>
                        <span class="dashboard-stat-copy">
                            <span class="dashboard-stat-label">Obligaciones pendientes</span>
                            <strong class="dashboard-stat-value">
                                <?= $obligacionesPendientes === null ? '—' : number_format($obligacionesPendientes, 0, ',', '.') ?>
                            </strong>
                            <span class="dashboard-stat-note">Con saldo por pagar</span>
                        </span>
                        <i class="fa-solid fa-chevron-right dashboard-stat-arrow" aria-hidden="true"></i>
                    </a>

                    <a class="dashboard-stat-card" href="<?= BASE_URL ?>mantenimiento/listar.php">
                        <span class="dashboard-stat-icon dashboard-stat-icon--maintenance">
                            <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
                        </span>
                        <span class="dashboard-stat-copy">
                            <span class="dashboard-stat-label">Solicitudes abiertas</span>
                            <strong class="dashboard-stat-value">
                                <?= $solicitudesAbiertas === null ? '—' : number_format($solicitudesAbiertas, 0, ',', '.') ?>
                            </strong>
                            <span class="dashboard-stat-note">Pendientes y en proceso</span>
                        </span>
                        <i class="fa-solid fa-chevron-right dashboard-stat-arrow" aria-hidden="true"></i>
                    </a>
                </section>

                <section class="dashboard-grid">
                    <article class="dashboard-panel">
                        <div class="dashboard-panel-header">
                            <div>
                                <div class="dashboard-panel-title">
                                    <i class="fa-solid fa-chart-column" aria-hidden="true"></i>
                                    <h3>Comportamiento de cartera</h3>
                                </div>
                                <p class="dashboard-panel-subtitle">
                                    Valores pagados y saldos pendientes por mes de vencimiento
                                </p>
                            </div>
                            <a class="dashboard-panel-link" href="<?= BASE_URL ?>configuracion/cartera.php">
                                Ver cartera <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </a>
                        </div>

                        <div class="dashboard-chart-legend">
                            <span class="dashboard-legend-item">
                                <span class="dashboard-legend-dot dashboard-legend-dot--paid"></span>
                                Pagado
                            </span>
                            <span class="dashboard-legend-item">
                                <span class="dashboard-legend-dot dashboard-legend-dot--pending"></span>
                                Saldo pendiente
                            </span>
                        </div>

                        <div class="dashboard-chart">
                            <div class="dashboard-chart-columns">
                                <?php foreach ($mesesGrafico as $mesGrafico): ?>
                                    <?php
                                    $alturaPagado = $maximoGrafico > 0
                                        ? (int) round(($mesGrafico['pagado'] / $maximoGrafico) * 100)
                                        : 0;
                                    $alturaPendiente = $maximoGrafico > 0
                                        ? (int) round(($mesGrafico['pendiente'] / $maximoGrafico) * 100)
                                        : 0;
                                    ?>
                                    <div class="dashboard-chart-column">
                                        <div class="dashboard-chart-bars"
                                             title="<?= $escapar($mesGrafico['nombre'] . ': pagado ' . $dinero($mesGrafico['pagado']) . ', pendiente ' . $dinero($mesGrafico['pendiente'])) ?>">
                                            <span class="dashboard-bar dashboard-bar--paid"
                                                  style="height: <?= $alturaPagado ?>%;"></span>
                                            <span class="dashboard-bar dashboard-bar--pending"
                                                  style="height: <?= $alturaPendiente ?>%;"></span>
                                        </div>
                                        <span class="dashboard-chart-label"><?= $escapar($mesGrafico['nombre']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (empty($filasGrafico)): ?>
                                <p class="dashboard-chart-empty">
                                    Aún no hay movimientos de cartera para el período consultado.
                                </p>
                            <?php endif; ?>
                        </div>
                    </article>

                    <article class="dashboard-panel">
                        <div class="dashboard-panel-header">
                            <div class="dashboard-panel-title">
                                <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                <h3>Actividad reciente</h3>
                            </div>
                            <a class="dashboard-panel-link" href="<?= BASE_URL ?>mantenimiento/listar.php">
                                Ver solicitudes <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </a>
                        </div>

                        <div class="dashboard-activity-list">
                            <?php if (empty($actividadReciente)): ?>
                                <div class="sin-datos">
                                    No hay movimientos recientes para mostrar.
                                </div>
                            <?php else: ?>
                                <?php foreach ($actividadReciente as $actividad): ?>
                                    <div class="dashboard-activity-item">
                                        <span class="dashboard-activity-icon <?= $actividad['tipo'] === 'pago' ? 'dashboard-activity-icon--payment' : '' ?>">
                                            <i class="fa-solid <?= $actividad['tipo'] === 'pago' ? 'fa-check' : 'fa-screwdriver-wrench' ?>"
                                               aria-hidden="true"></i>
                                        </span>
                                        <div class="dashboard-activity-copy">
                                            <strong><?= $escapar($actividad['titulo']) ?></strong>
                                            <span><?= $escapar($actividad['detalle']) ?></span>
                                        </div>
                                        <div class="dashboard-activity-meta">
                                            <span class="dashboard-activity-date">
                                                <?= !empty($actividad['fecha']) ? $escapar(date('d/m/Y H:i', strtotime($actividad['fecha']))) : '—' ?>
                                            </span>
                                            <span class="dashboard-status <?= $escapar($actividad['clase']) ?>">
                                                <?= $escapar($actividad['estado']) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </article>
                </section>

                <section class="dashboard-lower-grid">
                    <article class="dashboard-panel">
                        <div class="dashboard-panel-header">
                            <div class="dashboard-panel-title">
                                <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
                                <h3>Solicitudes de mantenimiento</h3>
                            </div>
                            <a class="dashboard-panel-link" href="<?= BASE_URL ?>mantenimiento/listar.php">
                                Ver todas <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </a>
                        </div>

                        <div class="dashboard-table-wrap">
                            <table class="dashboard-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Descripción</th>
                                        <th>Zona</th>
                                        <th>Prioridad</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($mantenimientosRecientes)): ?>
                                        <tr>
                                            <td colspan="6" class="sin-datos">
                                                No hay solicitudes de mantenimiento registradas.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($mantenimientosRecientes as $fila): ?>
                                            <?php
                                            $prioridad = strtolower((string) ($fila['prioridad'] ?? ''));
                                            $estado = strtolower((string) ($fila['estado'] ?? ''));
                                            ?>
                                            <tr>
                                                <td>MNT-<?= (int) ($fila['id'] ?? 0) ?></td>
                                                <td><?= $escapar($fila['descripcion'] ?? '') ?></td>
                                                <td><?= $escapar($fila['nombre_zona'] ?? '—') ?></td>
                                                <td>
                                                    <span class="dashboard-priority <?= $escapar($prioridadClase[$prioridad] ?? '') ?>">
                                                        <?= $escapar(ucfirst($prioridad ?: '—')) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="dashboard-status <?= $escapar($estadoMantenimientoClase[$estado] ?? '') ?>">
                                                        <?= $escapar($estadoMantenimientoTexto[$estado] ?? ucfirst(str_replace('_', ' ', $estado))) ?>
                                                    </span>
                                                </td>
                                                <td><?= !empty($fila['fecha_reporte']) ? $escapar(date('d/m/Y', strtotime($fila['fecha_reporte']))) : '—' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </article>

                    <article class="dashboard-panel">
                        <div class="dashboard-panel-header">
                            <div class="dashboard-panel-title">
                                <i class="fa-regular fa-file-lines" aria-hidden="true"></i>
                                <h3>Estado de obligaciones</h3>
                            </div>
                            <a class="dashboard-panel-link" href="<?= BASE_URL ?>configuracion/cartera.php">
                                Ver detalles <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </a>
                        </div>

                        <div class="dashboard-payment-layout">
                            <div class="dashboard-donut" style="--dashboard-paid: <?= $porcentajePagado ?>%;">
                                <div class="dashboard-donut-hole">
                                    <strong><?= $porcentajePagado ?>%</strong>
                                    <span>Pagadas</span>
                                </div>
                            </div>

                            <div class="dashboard-payment-legend">
                                <div class="dashboard-payment-row">
                                    <span class="dashboard-payment-dot dashboard-payment-dot--paid"></span>
                                    <span>Pagadas</span>
                                    <strong><?= number_format($totalPagadas, 0, ',', '.') ?></strong>
                                    <small><?= $totalObligaciones > 0 ? (int) round(($totalPagadas / $totalObligaciones) * 100) : 0 ?>%</small>
                                </div>
                                <div class="dashboard-payment-row">
                                    <span class="dashboard-payment-dot dashboard-payment-dot--pending"></span>
                                    <span>Pendientes</span>
                                    <strong><?= number_format($totalPendientes, 0, ',', '.') ?></strong>
                                    <small><?= $totalObligaciones > 0 ? (int) round(($totalPendientes / $totalObligaciones) * 100) : 0 ?>%</small>
                                </div>
                                <div class="dashboard-payment-row">
                                    <span class="dashboard-payment-dot dashboard-payment-dot--overdue"></span>
                                    <span>Vencidas</span>
                                    <strong><?= number_format($totalVencidas, 0, ',', '.') ?></strong>
                                    <small><?= $totalObligaciones > 0 ? (int) round(($totalVencidas / $totalObligaciones) * 100) : 0 ?>%</small>
                                </div>
                                <div class="dashboard-payment-row">
                                    <span class="dashboard-payment-dot dashboard-payment-dot--upcoming"></span>
                                    <span>Por vencer</span>
                                    <strong><?= number_format($totalPorVencer, 0, ',', '.') ?></strong>
                                    <small><?= $totalObligaciones > 0 ? (int) round(($totalPorVencer / $totalObligaciones) * 100) : 0 ?>%</small>
                                </div>
                            </div>
                        </div>
                    </article>
                </section>
            </div>
        </main>
    </div>
</body>
</html>