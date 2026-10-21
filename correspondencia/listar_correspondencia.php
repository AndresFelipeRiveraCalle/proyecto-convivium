<?php


require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

// 1. FILTROS Y BUSCADOR
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$filtro_estado = isset($_GET['estado']) && $_GET['estado'] == 'Entregado' ? 'Entregado' : 'Pendiente';

try {
    if ($buscar != '') {
        $query = "SELECT 
                    c.id_correspondencia, c.remitente, c.descripcion, c.estado, c.fecha_recepcion,
                    u.id_unidad, u.codigo AS apartamento, dtu.nombre_grupo AS torre,
                    MAX(usr.nombres) AS nombre_residente, MAX(usr.celular) AS celular
                    FROM correspondencia c
                    INNER JOIN unidades u ON c.id_unidad = u.id_unidad
                    INNER JOIN detalle_tipos_unidad dtu ON u.id_tipo_config = dtu.id_tipo_config
                    LEFT JOIN residente r ON u.id_unidad = r.unidad_id AND r.activo = 1
                    LEFT JOIN usuario usr ON r.usuario_id = usr.id
                    WHERE c.estado = :estado 
                    AND (c.remitente LIKE :buscar1 OR u.codigo LIKE :buscar2)
                    GROUP BY c.id_correspondencia
                    ORDER BY c.fecha_recepcion DESC";
                
        $stmt = $conexion->prepare($query);
        $stmt->execute([
            ':estado'  => $filtro_estado,
            ':buscar1' => '%' . $buscar . '%',
            ':buscar2' => '%' . $buscar . '%'
        ]);
    } else {
        $query = "SELECT 
                    c.id_correspondencia, c.remitente, c.descripcion, c.estado, c.fecha_recepcion,
                    u.id_unidad, u.codigo AS apartamento, dtu.nombre_grupo AS torre,
                    MAX(usr.nombres) AS nombre_residente, MAX(usr.celular) AS celular
                    FROM correspondencia c
                    INNER JOIN unidades u ON c.id_unidad = u.id_unidad
                    INNER JOIN detalle_tipos_unidad dtu ON u.id_tipo_config = dtu.id_tipo_config
                    LEFT JOIN residente r ON u.id_unidad = r.unidad_id AND r.activo = 1
                    LEFT JOIN usuario usr ON r.usuario_id = usr.id
                    WHERE c.estado = :estado
                    GROUP BY c.id_correspondencia
                    ORDER BY c.fecha_recepcion DESC";
                
        $stmt = $conexion->prepare($query);
        $stmt->execute([':estado' => $filtro_estado]);
    }
} catch (PDOException $e) {
    die("Error de SQL exacto: " . $e->getMessage());
}

// CARGAR UNIDADES (Para el select del modal de crear)
try {
    $consulta_unidades = "SELECT u.id_unidad, u.codigo, dtu.nombre_grupo AS nombre_torre
                            FROM unidades u
                            INNER JOIN detalle_tipos_unidad dtu ON u.id_tipo_config = dtu.id_tipo_config
                            WHERE u.activo = 1 ORDER BY dtu.nombre_grupo ASC, u.codigo ASC";
    $lista_unidades = $conexion->query($consulta_unidades)->fetchAll();
} catch (PDOException $e) {
    $lista_unidades = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <?php include ROOT_PATH . "/includes/head.php"; ?>
    <!-- Cargar CSS específico del módulo -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/correspondencia.css">
    
    <!-- Estilos rápidos para las pestañas de navegación -->
    <style>
        .nav-pestañas {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .pestana {
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            color: #333;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
        }
        .pestana.activa {
            background-color: #1e293b;
            color: white;
            border-color: #1e293b;
        }
    </style>
</head>
<body>

    <?php include ROOT_PATH . "/includes/header.php"; ?>
    <?php require_once ROOT_PATH . "/includes/mensajes.php"; ?>

    <div class="contenedor">
        <?php include ROOT_PATH . "/includes/sidebar.php"; ?>
        
        <main class="contenido">
            <div class="container">
                <h2 class="titulo-pagina" style="margin-bottom: 5px;">Correspondencia</h2>
                <p class="subtitulo-pagina">Recepción y control de paquetes en portería</p>

                <!-- Alertas -->
                <?php if (isset($_GET['mensaje'])): ?>
                    <div class="alerta-exito">
                        <strong>
                            <?php
                            if ($_GET['mensaje'] == 'creado') echo "Paquete registrado en portería.";
                            if ($_GET['mensaje'] == 'entregado') echo "Paquete marcado como ENTREGADO al residente.";
                            ?>
                        </strong>
                    </div>
                <?php endif; ?>

                <button id="btn-abrir-crear" class="btn btn-success" style="margin-bottom: 15px;">Registrar Paquete</button>

                <div class="bloque" style="display: block; padding: 20px;">
                    
                    <!-- PESTAÑAS DE NAVEGACIÓN -->
                    <div class="nav-pestañas">
                        <a href="listar_correspondencia.php?estado=Pendiente" class="pestana <?php echo $filtro_estado == 'Pendiente' ? 'activa' : ''; ?>">
                            Paquetes Pendientes (En portería)
                        </a>
                        <a href="listar_correspondencia.php?estado=Entregado" class="pestana <?php echo $filtro_estado == 'Entregado' ? 'activa' : ''; ?>">
                            Historial Entregados
                        </a>
                    </div>

                    <form action="listar_correspondencia.php" method="GET" class="filtros" style="margin-bottom: 20px;">
                        <!-- Mantener el estado actual en el buscador -->
                        <input type="hidden" name="estado" value="<?php echo htmlspecialchars($filtro_estado); ?>">
                        <input type="text" id="buscar" name="buscar" placeholder="Buscar remitente o apto..." value="<?php echo htmlspecialchars($buscar); ?>">
                        <button type="submit" class="btn-filtrar">Buscar</button>
                        <a href="listar_correspondencia.php?estado=<?php echo htmlspecialchars($filtro_estado); ?>" class="btn-limpiar">Mostrar todos</a>
                    </form>

                    <div class="card-tabla">
                        <table class="tabla-general">
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
                                <?php if ($stmt->rowCount() > 0): ?>
                                    <?php while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><?php echo $fila['id_correspondencia']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($fila['remitente']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($fila['descripcion']); ?></td>
                                            <td><?php echo htmlspecialchars($fila['torre'] . " - Apto " . $fila['apartamento']); ?></td>
                                            
                                            <!-- Columna de Estado con clases CSS -->
                                            <td>
                                                <?php if($fila['estado'] == 'Pendiente'): ?>
                                                    <span class="estado-etiqueta estado-pendiente">Pendiente</span>
                                                <?php else: ?>
                                                    <span class="estado-etiqueta estado-entregado">Entregado</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?php if($fila['estado'] == 'Pendiente'): ?>
                                                    
                                                    <!-- BOTÓN DE WHATSAPP -->
                                                    <?php if(!empty($fila['celular'])): ?>
                                                        <?php 
                                                            $mensajeWa = urlencode("Hola " . $fila['nombre_residente'] . ", te informamos desde la portería de Convivium que acaba de llegar un paquete de " . $fila['remitente'] . " a tu nombre. ¡Puedes pasar a recogerlo!");
                                                        ?>
                                                        <a href="https://wa.me/57<?php echo preg_replace('/[^0-9]/', '', $fila['celular']); ?>?text=<?php echo $mensajeWa; ?>" target="_blank" class="btn-accion-tabla btn-whatsapp">
                                                            <i class="fa-brands fa-whatsapp"></i> Avisar
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="sin-accion">Sin celular</span>
                                                    <?php endif; ?>

                                                    <!-- BOTÓN DE ENTREGAR -->
                                                    <a href="entregar_correspondencia.php?id=<?php echo $fila['id_correspondencia']; ?>" class="btn-accion-tabla btn-entregar" onclick="return confirm('¿Confirmas que el residente ya recogió este paquete?');">
                                                        <i class="fa-solid fa-check"></i> Entregar
                                                    </a>

                                                <?php else: ?>
                                                    <span class="sin-accion">Sin acciones</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
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

    <!-- ========================================================= -->
    <!-- MODAL: REGISTRAR PAQUETE                                  -->
    <!-- ========================================================= -->
    <div id="modal-crear" class="modal">
        <div class="modal-contenido">
            <div class="modal-header">
                <h3>Registrar Paquete</h3>
                <button type="button" id="btn-cerrar-crear" class="cerrar">&times;</button>
            </div>
            <form action="guardar_correspondencia.php" method="POST">
                <h4 style="margin-bottom: 10px; color: #333;">Información del envío</h4>
                <div class="form-group">
                    <label>Remitente / Empresa de mensajería *</label>
                    <input type="text" name="remitente" placeholder="Ej: Servientrega, MercadoLibre, Familiar" required>
                </div>
                <div class="form-group">
                    <label>Descripción del paquete *</label>
                    <input type="text" name="descripcion" placeholder="Ej: Caja grande, Sobre manila, Domicilio" required>
                </div>
                
                <h4 class="modal-separador">Destinatario</h4>
                <div class="form-group">
                    <label>Apartamento destino *</label>
                    <select id="crear_id_unidad" name="id_unidad" required>
                        <option value="" data-torre="">Seleccione...</option>
                        <?php foreach ($lista_unidades as $unidad): ?>
                            <option value="<?php echo $unidad['id_unidad']; ?>" data-torre="<?php echo htmlspecialchars($unidad['nombre_torre']); ?>">
                                <?php echo htmlspecialchars('Apto ' . $unidad['codigo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Agrupación</label>
                    <input type="text" id="crear_torre" class="campo-solo-lectura" readonly>
                </div>

                <div class="acciones-modal" style="justify-content: flex-end; margin-top: 20px;">
                    <button type="button" id="btn-cancelar-crear" class="btn" style="background: #ccc; margin-right: 10px;">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar Paquete</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cargar JS específico del módulo -->
    <script src="<?php echo BASE_URL; ?>/assets/js/correspondencia.js"></script>

</body>
</html>