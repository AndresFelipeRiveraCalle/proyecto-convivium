<?php
/**
 * Procesador - Actualizar Artículo
 */
require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Capturamos todos los datos que envió el formulario, incluyendo el ID oculto
    $id_articulo = (int)$_POST['id_articulo'];
    $nombre = trim($_POST['nombre']);
    $categoria = trim($_POST['categoria']);
    $unidad_medida = trim($_POST['unidad_medida']);
    
    try {
        // Preparamos el UPDATE. Nota: No actualizamos el stock aquí, el stock solo cambia con los movimientos.
        $query = "UPDATE articulos 
                    SET nombre = :nombre, categoria = :categoria, unidad_medida = :unidad_medida 
                    WHERE id_articulo = :id_articulo";
        
        $stmt = $conexion->prepare($query);
        $stmt->execute([
            ':nombre' => $nombre,
            ':categoria' => $categoria,
            ':unidad_medida' => $unidad_medida,
            ':id_articulo' => $id_articulo
        ]);
        
        // Redirigimos a listar con el mensaje de éxito
        header("Location: listar.php?mensaje=actualizado");
        exit();

    } catch (PDOException $e) {
        echo "Error al actualizar el artículo: " . $e->getMessage();
    }
} else {
    header("Location: listar.php");
    exit();
}
?>