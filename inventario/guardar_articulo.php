<?php
/**
 * Procesador - Guardar Artículo (PDO)
 */

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Con PDO ya no usamos real_escape_string. Capturamos los datos limpios.
    $nombre = trim($_POST['nombre']);
    $categoria = trim($_POST['categoria']);
    $unidad_medida = trim($_POST['unidad_medida']);
    
    try {
        // Usamos marcadores de posición (:nombre) por seguridad
        $query = "INSERT INTO articulos (nombre, categoria, unidad_medida) 
                    VALUES (:nombre, :categoria, :unidad_medida)";
        
        // Preparamos y ejecutamos pasando el arreglo de datos
        $stmt = $conexion->prepare($query);
        $stmt->execute([
            ':nombre' => $nombre,
            ':categoria' => $categoria,
            ':unidad_medida' => $unidad_medida
        ]);
        
        header("Location: listar.php?mensaje=creado");
        exit();

    } catch (PDOException $e) {
        echo "Error al guardar el artículo: " . $e->getMessage();
    }
} else {
    header("Location: listar.php");
    exit();
}
?>