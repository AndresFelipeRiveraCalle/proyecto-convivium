<?php
// 1. Requerimos las configuraciones y la conexión
require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

// 2. CONSULTA PARA EL SELECT DINÁMICO
// Vamos a buscar todos los apartamentos activos en la base de datos, uniendo con la tabla de agrupaciones (Torres)
try {
    $consulta_unidades = "
        SELECT 
            u.id_unidad, 
            u.codigo, 
            u.nombre AS nombre_residente, 
            dtu.nombre_grupo AS nombre_torre
        FROM unidades u
        INNER JOIN detalle_tipos_unidad dtu ON u.id_tipo_config = dtu.id_tipo_config
        WHERE u.activo = 1 
        ORDER BY dtu.nombre_grupo ASC, u.codigo ASC
    ";
    
    // Ejecutamos la consulta y guardamos los resultados
    $resultado_unidades = $conexion->query($consulta_unidades);
    $lista_unidades = $resultado_unidades->fetchAll();
    
} catch (PDOException $e) {
    // Si algo falla, lo registramos y dejamos el arreglo vacío
    error_log("Error al cargar las unidades: " . $e->getMessage());
    $lista_unidades = []; 
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <?php include ROOT_PATH . "/includes/head.php"; ?>
    <!-- Importamos NUESTRO archivo CSS para los estilos específicos de mascotas -->
    <link rel="stylesheet" href="../assets/css/mascotas.css">
</head>

<body>
    <?php include ROOT_PATH . "/includes/header.php"; ?>
    <?php require_once ROOT_PATH . "/includes/mensajes.php"; ?>

    <div class="contenedor">
        <?php include ROOT_PATH . "/includes/sidebar.php"; ?>
        
        <main class="contenido">
            <div class="container">
                <h2 class="titulo-pagina">Registrar Nueva Mascota</h2>

                <div class="form-card form-mascotas">
                    
                    <form id="formulario-mascota" class="w-100">
                        
                        <div class="form-group">
                            <label for="nombre_mascota">Nombre de la Mascota:</label>
                            <input type="text" id="nombre_mascota" name="nombre_mascota" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="especie_mascota">Especie (Ej: Perro, Gato):</label>
                            <input type="text" id="especie_mascota" name="especie_mascota" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="id_unidad">Apartamento asignado:</label>
                            <select id="id_unidad" name="id_unidad" required>
                                <option value="" data-torre="">Seleccione un apartamento...</option>
                                
                                <!-- Dibujamos las opciones dinámicamente y guardamos la torre en data-torre -->
                                <?php foreach ($lista_unidades as $unidad): ?>
                                    <option 
                                        value="<?php echo htmlspecialchars($unidad['id_unidad']); ?>"
                                        data-torre="<?php echo htmlspecialchars($unidad['nombre_torre']); ?>"
                                    >
                                        <?php echo htmlspecialchars('Apto ' . $unidad['codigo']); ?>
                                    </option>
                                <?php endforeach; ?>
                                
                            </select>
                        </div>

                        <!-- NUEVO CAMPO: Mostrará la torre automáticamente (Solo lectura) -->
                        <div class="form-group">
                            <label for="torre_mascota">Torre / Agrupación:</label>
                            <input type="text" id="torre_mascota" readonly placeholder="Se llenará automáticamente" style="background-color: #f1f1f1; cursor: not-allowed; color: #666;">
                        </div>
                        
                        <div class="form-actions centrar-boton">
                            <button type="submit" class="btn btn-success">Guardar Mascota</button>
                        </div>
                        
                    </form>

                    <div id="mensaje-respuesta" class="mensaje-alerta"></div>
                </div>
            </div>
        </main>
    </div>

    <!-- Cargamos nuestro script -->
    <script src="../assets/js/mascotas.js"></script>

</body>
</html>