<?php
// Aseguramos que la sesión esté iniciada para poder leer los datos
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtenemos el nombre y el rol desde las variables de sesión que creamos en procesar_login.php
// Usamos el operador ?? para poner un valor por defecto por si acaso
$nombre_completo = $_SESSION['nombre_usuario_autenticado'] ?? 'Administrador';
$rol_usuario     = $_SESSION['rol_nombre'] ?? 'Administrador General';

// Validamos la foto del usuario
// Comprueba si existe la ruta en la sesión y si el archivo físico realmente está en el servidor
if (!empty($_SESSION['foto']) && file_exists(ROOT_PATH . '/' . $_SESSION['foto'])) {
    // Si la foto existe, armamos la ruta completa
    $foto_usuario = BASE_URL . ltrim($_SESSION['foto'], '/');
} else {
    // Si no tiene foto, usamos la imagen genérica por defecto de tu diseño
    $foto_usuario = BASE_URL . 'assets/img/usuario.png';
}
?>

<header class="header">
    <div class="logo">
        <a href="<?= BASE_URL ?>index.php">
            <img src="<?= BASE_URL ?>assets/img/Logo_2.png" alt="Convivium">
        </a>
    </div>

    <div class="usuario">
        <!-- Mostramos la foto dinámica ajustada para que no se deforme -->
        <img src="<?= htmlspecialchars($foto_usuario) ?>" alt="Usuario" style="object-fit: cover; border-radius: 50%; width: 45px; height: 45px;">
    
        <div class="usuario-info">
            <span class="nombre">
                <?= htmlspecialchars($nombre_completo) ?>
            </span>
            -
            <small><?= htmlspecialchars($rol_usuario) ?></small>
        </div>
    </div>
</header>