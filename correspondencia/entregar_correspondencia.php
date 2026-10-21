<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

// Verificamos que nos hayan enviado un ID por la URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    
    $id_correspondencia = $_GET['id'];

    try {
        // Hacemos el UPDATE. 
        // Cambiamos el estado y guardamos la fecha/hora actual de MySQL (CURRENT_TIMESTAMP)
        $consulta = "UPDATE correspondencia 
                        SET estado = 'Entregado', fecha_entrega = CURRENT_TIMESTAMP 
                        WHERE id_correspondencia = ?";
        
        $stmt = $conexion->prepare($consulta);
        $stmt->execute([$id_correspondencia]);

        // Redirigimos con el mensaje de éxito de entrega
        header("Location: listar_correspondencia.php?mensaje=entregado");
        exit;

    } catch (PDOException $e) {
        error_log("Error al entregar paquete: " . $e->getMessage());
        die("Error al procesar la entrega: " . $e->getMessage());
    }
} else {
    die("Acceso denegado: ID no válido.");
}
?>