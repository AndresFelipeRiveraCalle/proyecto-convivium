<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/auth/requerir_sesion.php";
require_once ROOT_PATH . "/config/conexion.php";

// Filtros de la bandeja.
$buscar = isset($_GET['buscar']) ? trim((string) $_GET['buscar']) : '';
$filtro_estado = (($_GET['estado'] ?? '') === 'Entregado')
    ? 'Entregado'
    : 'Pendiente';

$correspondencias = [];
$errorCorrespondencia = false;

try {
    $query = "
        SELECT
            c.id_correspondencia,
            c.remitente,
            c.descripcion,
            c.estado,
            c.fecha_recepcion,
            u.id_unidad,
            u.codigo AS apartamento,
            dtu.nombre_grupo AS torre,
            MAX(usr.nombres) AS nombre_residente,
            MAX(usr.celular) AS celular
        FROM correspondencia c
        INNER JOIN unidades u
            ON c.id_unidad = u.id_unidad
        INNER JOIN detalle_tipos_unidad dtu
            ON u.id_tipo_config = dtu.id_tipo_config
        LEFT JOIN residente r
            ON u.id_unidad = r.unidad_id
            AND r.activo = 1
            AND r.fecha_hasta IS NULL
        LEFT JOIN usuario usr
            ON r.usuario_id = usr.id
        WHERE c.estado = :estado
    ";

    $parametros = [
        ':estado' => $filtro_estado
    ];

    if ($buscar !== '') {
        $query .= "
            AND (
                c.remitente LIKE :buscar_remitente
                OR u.codigo LIKE :buscar_unidad
            )
        ";

        $terminoBusqueda = '%' . $buscar . '%';
        $parametros[':buscar_remitente'] = $terminoBusqueda;
        $parametros[':buscar_unidad'] = $terminoBusqueda;
    }

    // Se incluyen todas las columnas no agregadas para que la consulta
    // también funcione con ONLY_FULL_GROUP_BY habilitado.
    $query .= "
        GROUP BY
            c.id_correspondencia,
            c.remitente,
            c.descripcion,
            c.estado,
            c.fecha_recepcion,
            u.id_unidad,
            u.codigo,
            dtu.nombre_grupo
        ORDER BY c.fecha_recepcion DESC
    ";

    $stmt = $conexion->prepare($query);
    $stmt->execute($parametros);
    $correspondencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error al cargar correspondencia: " . $e->getMessage());
    $errorCorrespondencia = true;
}

// Unidades disponibles para el formulario de registro.
$lista_unidades = [];

try {
    $consulta_unidades = "
        SELECT
            u.id_unidad,
            u.codigo,
            dtu.nombre_grupo AS nombre_torre
        FROM unidades u
        INNER JOIN detalle_tipos_unidad dtu
            ON u.id_tipo_config = dtu.id_tipo_config
        WHERE u.activo = 1
        ORDER BY dtu.nombre_grupo ASC, u.codigo ASC
    ";

    $stmtUnidades = $conexion->query($consulta_unidades);
    $lista_unidades = $stmtUnidades->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error al cargar unidades para correspondencia: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <?php include ROOT_PATH . "/includes/head.php"; ?>
    <!-- Cargar CSS específico del módulo -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/correspondencia.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include ROOT_PATH . "/includes/header.php"; ?>
    <?php require_once ROOT_PATH . "/includes/mensajes.php"; ?>

    <div class="contenedor">
        <?php include ROOT_PATH . "/includes/sidebar.php"; ?>
        
        <main class="contenido correspondencia-page">
            <div class="container correspondencia-container">
                <div class="correspondencia-encabezado">
                    <div>
                        <h2 class="titulo-pagina correspondencia-titulo">Correspondencia</h2>
                        <p class="subtitulo-pagina">Recepción y control de paquetes en portería</p>
                    </div>
                    <button id="btn-abrir-crear" class="btn btn-success correspondencia-btn-primary" type="button">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Registrar paquete
                    </button>
                </div>

                <!-- Alertas -->
                <?php if (isset($_GET['mensaje'])): ?>
                    <div class="alerta-exito correspondencia-alerta" role="status">
                        <strong>
                            <?php
                            if ($_GET['mensaje'] == 'creado') echo "Paquete registrado en portería.";
                            if ($_GET['mensaje'] == 'entregado') echo "Paquete marcado como ENTREGADO al residente.";
                            ?>
                        </strong>
                    </div>
                <?php endif; ?>

                <div class="bloque correspondencia-panel">
                    
                    <!-- PESTAÑAS DE NAVEGACIÓN -->
                    <nav class="nav-pestañas correspondencia-tabs" aria-label="Filtrar paquetes por estado">
                        <a href="listar_correspondencia.php?estado=Pendiente" class="pestana correspondencia-tab <?php echo $filtro_estado == 'Pendiente' ? 'activa' : ''; ?>" <?php echo $filtro_estado == 'Pendiente' ? 'aria-current="page"' : ''; ?>>
                            Paquetes Pendientes (En portería)
                        </a>
                        <a href="listar_correspondencia.php?estado=Entregado" class="pestana correspondencia-tab <?php echo $filtro_estado == 'Entregado' ? 'activa' : ''; ?>" <?php echo $filtro_estado == 'Entregado' ? 'aria-current="page"' : ''; ?>>
                            Historial Entregados
                        </a>
                    </nav>

                    <form action="listar_correspondencia.php" method="GET" class="filtros correspondencia-filtros">
                        <!-- Mantener el estado actual en el buscador -->
                        <input type="hidden" name="estado" value="<?php echo htmlspecialchars($filtro_estado); ?>">
                        <label class="sr-only" for="buscar">Buscar remitente o apartamento</label>
                        <input type="search" id="buscar" name="buscar" placeholder="Buscar remitente o apartamento..." value="<?php echo htmlspecialchars($buscar); ?>">
                        <button type="submit" class="btn-filtrar correspondencia-btn-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Buscar</button>
                        <a href="listar_correspondencia.php?estado=<?php echo htmlspecialchars($filtro_estado); ?>" class="btn-limpiar correspondencia-btn-reset">Mostrar todos</a>
                    </form>

                    <div class="card-tabla correspondencia-table-wrap">
                        <table class="tabla-general correspondencia-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Remitente</th>
                                    <th>Descripción</th>
                                    <th>Destino</th>
                                    <th>Estado</th>
                                    <th>Notificar / Entregar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($errorCorrespondencia): ?>
                                    <tr>
                                        <td colspan="6" class="sin-datos">
                                            No fue posible cargar los paquetes. Revisa el registro de errores de PHP para identificar la causa.
                                        </td>
                                    </tr>
                                <?php elseif (!empty($correspondencias)): ?>
                                    <?php foreach ($correspondencias as $fila): ?>
                                        <tr>
                                            <td><?php echo $fila['id_correspondencia']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($fila['remitente']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($fila['descripcion']); ?></td>
                                            <td><?php echo htmlspecialchars($fila['torre'] . " - Apto " . $fila['apartamento']); ?></td>
                                            
                                            <!-- Columna de Estado con clases CSS -->
                                            <td>
                                                <?php if($fila['estado'] == 'Pendiente'): ?>
                                                    <span class="estado-etiqueta estado-pendiente correspondencia-status">Pendiente</span>
                                                <?php else: ?>
                                                    <span class="estado-etiqueta estado-entregado correspondencia-status">Entregado</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?php if($fila['estado'] == 'Pendiente'): ?>
                                                    
                                                    <!-- BOTÓN DE WHATSAPP -->
                                                    <?php if(!empty($fila['celular'])): ?>
                                                        <?php 
                                                            $mensajeWa = urlencode("Hola " . $fila['nombre_residente'] . ", te informamos desde la portería de Convivium que acaba de llegar un paquete de " . $fila['remitente'] . " a tu nombre. ¡Puedes pasar a recogerlo!");
                                                        ?>
                                                        <a href="https://wa.me/57<?php echo preg_replace('/[^0-9]/', '', $fila['celular']); ?>?text=<?php echo $mensajeWa; ?>" target="_blank" class="btn-accion-tabla btn-whatsapp correspondencia-action">
                                                            <i class="fa-brands fa-whatsapp"></i> Avisar
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="sin-accion">Sin celular</span>
                                                    <?php endif; ?>

                                                    <!-- BOTÓN DE ENTREGAR -->
                                                    <a href="entregar_correspondencia.php?id=<?php echo $fila['id_correspondencia']; ?>" class="btn-accion-tabla btn-entregar correspondencia-action" onclick="return confirm('¿Confirmas que el residente ya recogió este paquete?');">
                                                        <i class="fa-solid fa-check"></i> Entregar
                                                    </a>

                                                <?php else: ?>
                                                    <span class="sin-accion">Sin acciones</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan='6' class='sin-datos'>No hay paquetes <?php echo strtolower($filtro_estado); ?>s.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- ==== -->
    <!-- MODAL: REGISTRAR PAQUETE                                  -->
    <!-- ==== -->
    <div id="modal-crear" class="modal correspondencia-modal" role="presentation" aria-hidden="true">
        <div class="modal-contenido correspondencia-modal-content" role="dialog" aria-modal="true" aria-labelledby="correspondencia-modal-title">
            <div class="modal-header">
                <h3 id="correspondencia-modal-title">Registrar paquete</h3>
                <button type="button" id="btn-cerrar-crear" class="cerrar" aria-label="Cerrar ventana">&times;</button>
            </div>
            <form action="guardar_correspondencia.php" method="POST" class="correspondencia-form">
                <h4 class="correspondencia-form-section">Información del envío</h4>
                <div class="form-group">
                    <label for="correspondencia-remitente">Remitente / empresa de mensajería *</label>
                    <input type="text" id="correspondencia-remitente" name="remitente" placeholder="Ej: Servientrega, MercadoLibre, Familiar" autocomplete="organization" required>
                </div>
                <div class="form-group">
                    <label for="correspondencia-descripcion">Descripción del paquete *</label>
                    <input type="text" id="correspondencia-descripcion" name="descripcion" placeholder="Ej: Caja grande, sobre manila, domicilio" required>
                </div>
                
                <h4 class="modal-separador correspondencia-form-section">Destinatario</h4>
                <div class="form-group">
                    <label for="crear_id_unidad">Apartamento destino *</label>
                    <select id="crear_id_unidad" name="id_unidad" required>
                        <option value="" data-torre="">Seleccione...</option>
                        <?php if (empty($lista_unidades)): ?>
                            <option value="" disabled>No hay unidades activas disponibles</option>
                        <?php else: ?>
                            <?php foreach ($lista_unidades as $unidad): ?>
                                <option value="<?php echo (int) $unidad['id_unidad']; ?>" data-torre="<?php echo htmlspecialchars($unidad['nombre_torre'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars('Apto ' . $unidad['codigo'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="crear_torre">Agrupación</label>
                    <input type="text" id="crear_torre" class="campo-solo-lectura" readonly>
                </div>

                <div class="acciones-modal correspondencia-modal-actions">
                    <button type="button" id="btn-cancelar-crear" class="btn correspondencia-btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-success correspondencia-btn-primary">Guardar paquete</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cargar JS específico del módulo -->
    <script src="<?php echo BASE_URL; ?>assets/js/correspondencia.js?v=<?php echo time(); ?>"></script>

</body>
</html>