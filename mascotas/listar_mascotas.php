<?php
// conexion ala base de datos

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/auth/requerir_sesion.php";
require_once ROOT_PATH . "/config/conexion.php";

// cargar las mascotas de la base de datos para la tabla
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
try {
    if ($buscar != '') {
        // consulta filtrada por nombre o especie usando parametros separados
        $query = "SELECT m.id_mascota, m.nombre AS nombre_mascota, m.especie, u.id_unidad, u.codigo AS apartamento, dtu.nombre_grupo AS torre
                    FROM mascotas m
                    INNER JOIN unidades u ON m.id_unidad = u.id_unidad
                    INNER JOIN detalle_tipos_unidad dtu ON u.id_tipo_config = dtu.id_tipo_config
                    WHERE m.nombre LIKE :buscar1 OR m.especie LIKE :buscar2
                    ORDER BY m.nombre ASC";
        $stmt = $conexion->prepare($query);
        $stmt->execute([
            ':buscar1' => '%' . $buscar . '%',
            ':buscar2' => '%' . $buscar . '%'
        ]);
    } else {
        // consulta general para traer todo
        $query = "SELECT m.id_mascota, m.nombre AS nombre_mascota, m.especie, u.id_unidad, u.codigo AS apartamento, dtu.nombre_grupo AS torre
                    FROM mascotas m
                    INNER JOIN unidades u ON m.id_unidad = u.id_unidad
                    INNER JOIN detalle_tipos_unidad dtu ON u.id_tipo_config = dtu.id_tipo_config
                    ORDER BY m.nombre ASC";
        $stmt = $conexion->query($query);
    }
} catch (PDOException $e) {
    // capturamos el error exacto si falla la consulta sql
    die("Error de SQL exacto: " . $e->getMessage());
}

// 2. cargar las unidades activas para los selects de los modales
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
</head>
<body>

    <?php include ROOT_PATH . "/includes/header.php"; ?>
    <?php require_once ROOT_PATH . "/includes/mensajes.php"; ?>

    <div class="contenedor">
        <?php include ROOT_PATH . "/includes/sidebar.php"; ?>
        
        <main class="contenido">
            <div class="container">
                <h2 class="titulo-pagina" style="margin-bottom: 5px;">Mascotas</h2>
                <p style="color: #64748b; margin-bottom: 20px; font-size: 14px;">Mascotas registradas</p>

                <!-- alertas de exito cuando creas, editas o borras -->
                <?php if (isset($_GET['mensaje'])): ?>
                    <div class="alerta-warning" style="background-color: #dcfce7; border-color: #2ecc71; color: #166534;">
                        <strong>
                            <?php
                            if ($_GET['mensaje'] == 'creado') echo "Mascota registrada exitosamente.";
                            if ($_GET['mensaje'] == 'eliminado') echo "Mascota eliminada del sistema.";
                            if ($_GET['mensaje'] == 'actualizado') echo "Datos de la mascota actualizados correctamente.";
                            ?>
                        </strong>
                    </div>
                    <br>
                <?php endif; ?>

                <button id="btn-abrir-crear" class="btn btn-success" style="margin-bottom: 15px;">Nueva mascota</button>

                <div class="bloque" style="display: block; padding: 20px;">
                    
                    <!-- formulario del buscador -->
                    <form action="listar_mascotas.php" method="GET" class="filtros">
                        <input type="text" id="buscar" name="buscar" placeholder="Buscar por nombre o especie..." value="<?php echo htmlspecialchars($buscar); ?>">
                        <button type="submit" class="btn-filtrar">Buscar</button>
                        <a href="listar_mascotas.php" class="btn-limpiar">Mostrar todos</a>
                    </form>
                    <br>

                    <div class="card-tabla">
                        <table class="tabla-general">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Especie</th>
                                    <th>Ubicación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($stmt->rowCount() > 0): ?>
                                    <?php while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td><?php echo $fila['id_mascota']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($fila['nombre_mascota']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($fila['especie']); ?></td>
                                            <td><?php echo htmlspecialchars($fila['torre'] . " - Apto " . $fila['apartamento']); ?></td>
                                            <td>
                                                <!-- boton editar con datos escondidos para javascript -->
                                                <button class="btn btn-success btn-abrir-editar" 
                                                        style="padding: 5px 10px; font-size: 12px; border:none; cursor:pointer;"
                                                        data-id="<?php echo $fila['id_mascota']; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($fila['nombre_mascota'], ENT_QUOTES); ?>"
                                                        data-especie="<?php echo htmlspecialchars($fila['especie'], ENT_QUOTES); ?>"
                                                        data-unidad="<?php echo $fila['id_unidad']; ?>"
                                                        data-torre="<?php echo htmlspecialchars($fila['torre'], ENT_QUOTES); ?>">
                                                    <i class="fa-solid fa-pen"></i> Editar
                                                </button>
                                                
                                                <!-- boton para eliminar con confirmacion -->
                                                <a href='eliminar_mascota.php?id=<?php echo $fila['id_mascota']; ?>' class='btn btn-danger' style='padding: 5px 10px; font-size: 12px;' onclick='return confirm("¿Eliminar esta mascota permanentemente?");'><i class="fa-solid fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan='5' class='sin-datos'>No hay mascotas registradas.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- modal para registrar una mascota nueva                 -->

    <div id="modal-crear" class="modal">
        <div class="modal-contenido">
            <div class="modal-header">
                <h3>Nueva mascota</h3>
                <button id="btn-cerrar-crear" class="cerrar">&times;</button>
            </div>
            <form action="guardar_mascota.php" method="POST">
                <h4 style="margin-bottom: 10px; color: #333;">Datos del animal</h4>
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre_mascota" required>
                </div>
                <div class="form-group">
                    <label>Especie *</label>
                    <input type="text" name="especie_mascota" placeholder="Ej: Perro, Gato" required>
                </div>
                
                <h4 style="margin-top: 20px; margin-bottom: 10px; color: #333;">Ubicación</h4>
                <div class="form-group">
                    <label>Apartamento *</label>
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
                    <label>Agrupación (Se llena solo)</label>
                    <input type="text" id="crear_torre" readonly style="background-color: #f1f1f1; cursor: not-allowed; color: #666;">
                </div>

                <div class="acciones-modal" style="justify-content: flex-end; margin-top: 20px;">
                    <button type="button" id="btn-cancelar-crear" class="btn" style="background: #ccc; margin-right: 10px;">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>


    <!--  modal para editar una mascota existente                -->

    <div id="modal-editar" class="modal">
        <div class="modal-contenido">
            <div class="modal-header">
                <h3>Editar mascota</h3>
                <button id="btn-cerrar-editar" class="cerrar">&times;</button>
            </div>
            <form action="editar_mascota.php" method="POST">
                
                <!-- id oculto para que el update sepa que fila cambiar -->
                <input type="hidden" id="edit_id_mascota" name="id_mascota">

                <h4 style="margin-bottom: 10px; color: #333;">Datos del animal</h4>
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" id="edit_nombre" name="nombre_mascota" required>
                </div>
                <div class="form-group">
                    <label>Especie *</label>
                    <input type="text" id="edit_especie" name="especie_mascota" required>
                </div>
                
                <h4 style="margin-top: 20px; margin-bottom: 10px; color: #333;">Ubicación</h4>
                <div class="form-group">
                    <label>Apartamento *</label>
                    <select id="edit_id_unidad" name="id_unidad" required>
                        <option value="" data-torre="">Seleccione...</option>
                        <?php foreach ($lista_unidades as $unidad): ?>
                            <option value="<?php echo $unidad['id_unidad']; ?>" data-torre="<?php echo htmlspecialchars($unidad['nombre_torre']); ?>">
                                <?php echo htmlspecialchars('Apto ' . $unidad['codigo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Agrupación (Se llena solo)</label>
                    <input type="text" id="edit_torre" readonly style="background-color: #f1f1f1; cursor: not-allowed; color: #666;">
                </div>

                <div class="acciones-modal" style="justify-content: flex-end; margin-top: 20px;">
                    <button type="button" id="btn-cancelar-editar" class="btn" style="background: #ccc; margin-right: 10px;">Cancelar</button>
                    <button type="submit" class="btn btn-success">Actualizar Datos</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/mascotas.js"></script>
</body>
</html>