<?php
$archivoHeader = basename($_SERVER['PHP_SELF'] ?? '');

$titulosHeader = [
    'inicio.php' => 'Panel principal',
    'usuarios.php' => 'Usuarios',
    'unidades.php' => 'Unidades',
    'cartera.php' => 'Cartera',
    'pagos.php' => 'Pagos',
    'facturas_generadas.php' => 'Facturas generadas',
    'listar_correspondencia.php' => 'Correspondencia',
    'reservas.php' => 'Reservas',
    'listar.php' => 'Gestión de solicitudes',
    'crear.php' => 'Nueva solicitud'
];

$tituloHeader = $tituloPagina
    ?? ($titulosHeader[$archivoHeader] ?? 'Gestión de la copropiedad');

$nombreUsuarioHeader = trim((string) (
    $_SESSION['nombre_usuario_autenticado']
    ?? $_SESSION['nombre_usuario']
    ?? 'Administrador'
));

$rolUsuarioHeader = trim((string) (
    $_SESSION['rol_usuario_autenticado']
    ?? 'Administrador'
));
?>

<header class="header">
    <div class="header-page-title">
        <h1><?= htmlspecialchars($tituloHeader, ENT_QUOTES, 'UTF-8') ?></h1>
    </div>

    <div class="header-actions">
        <a class="header-quick-link" href="<?= BASE_URL ?>configuracion/cartera.php">
            <i class="fa-solid fa-wallet" aria-hidden="true"></i>
            <span>Consultar cartera</span>
        </a>

        <div class="usuario">
            <img src="<?= BASE_URL ?>assets/img/user.png" alt="Perfil de usuario">
            <div class="usuario-info">
                <span class="nombre">
                    <?= htmlspecialchars($nombreUsuarioHeader, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <small>
                    <?= htmlspecialchars($rolUsuarioHeader, ENT_QUOTES, 'UTF-8') ?>
                </small>
            </div>
        </div>
    </div>
</header>