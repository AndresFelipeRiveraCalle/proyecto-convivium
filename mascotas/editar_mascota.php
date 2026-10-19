<?php
// Archivo: mascotas/editar_mascota.php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

// Verificamos que los datos nos lleguen por POST desde el Modal de Edición
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Recibimos los datos modificados
    $id_mascota_a_editar = $_POST['id_mascota'];
    $nuevo_nombre = $_POST['nombre_mascota'];
    $nueva_especie = $_POST['especie_mascota'];
    $nuevo_id_unidad = $_POST['id_unidad'];

    try {
        // Escribimos la consulta UPDATE (segura con PDO)
        $consulta_actualizar = "UPDATE mascotas SET nombre = ?, especie = ?, id_unidad = ? WHERE id_mascota = ?";
        
        $stmt_actualizar = $conexion->prepare($consulta_actualizar);
        
        // Ejecutamos pasando los datos en el orden exacto de los '?'
        $stmt_actualizar->execute([
            $nuevo_nombre, 
            $nueva_especie, 
            $nuevo_id_unidad, 
            $id_mascota_a_editar
        ]);

        // Redirigimos a la vista principal con mensaje de éxito
        header("Location: listar_mascotas.php?mensaje=actualizado");
        exit;

    } catch (PDOException $e) {
        error_log("Error al actualizar mascota: " . $e->getMessage());
        die("Ocurrió un error grave al guardar los cambios en la base de datos.");
    }
} else {
    // Si entran directamente sin usar el formulario
    die("Acceso denegado.");
}
?>