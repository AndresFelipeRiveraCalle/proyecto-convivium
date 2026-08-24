<?php
/**
 * Procesador - Eliminar Artículo (Borrado Lógico con PDO)
 * Propósito: Cambiar el estado 'activo' a 0 para ocultarlo del catálogo sin borrar el historial.
 */

require_once '../config/conexion.php';

// 1. Validar que llegue un ID por la URL y que sea un número válido
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    
    // Capturamos el ID de forma segura
    $id_articulo = (int)$_GET['id'];

    try {
        // 2. Preparar el UPDATE para el borrado lógico
        $query = "UPDATE articulos SET activo = 0 WHERE id_articulo = :id_articulo";
        
        // 3. Preparar y ejecutar la consulta usando marcadores de PDO
        $stmt = $conexion->prepare($query);
        $stmt->execute([
            ':id_articulo' => $id_articulo
        ]);
        
        // 4. Redireccionar de vuelta a la lista principal con el mensaje de éxito
        header("Location: listar.php?mensaje=eliminado");
        exit();

    } catch (PDOException $e) {
        // En caso de error en la base de datos, lo mostramos
        echo "Error al eliminar el artículo: " . $e->getMessage();
    }
} else {
    // Si alguien intenta acceder directamente sin enviar un ID, lo devolvemos
    header("Location: listar.php");
    exit();
}
?>