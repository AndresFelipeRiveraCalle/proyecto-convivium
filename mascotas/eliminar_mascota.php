<?php
// archivo: mascotas/eliminar_mascota.php

// traemos la configuracion y la conexion a bd
require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

// validamos que llegue un id por get y que sea numero
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    
    // guardamos el id para usarlo mas facil
    $id_de_la_mascota = $_GET['id'];

    try {
        // consulta para borrar
        // ojo: siempre usar where o se borra toda la tabla
        $consulta_para_eliminar = "DELETE FROM mascotas WHERE id_mascota = ?";
        
        // preparamos la consulta pdo
        $consulta_preparada = $conexion->prepare($consulta_para_eliminar);
        
        // ejecutamos y le pasamos el id
        $consulta_preparada->execute([$id_de_la_mascota]);

        // si todo sale bien devolvemos al usuario a la lista
        // mandamos parametro por url para mostrar la alerta de exito
        header("Location: listar_mascotas.php?mensaje=eliminado");
        
        // frenamos el script por seguridad
        exit;

    } catch (PDOException $error_capturado) {
        // si algo se rompe guardamos el error en el log oculto
        error_log("Error al eliminar la mascota: " . $error_capturado->getMessage());
        
        // y mostramos un mensaje normal en pantalla
        die("Ocurrió un error al intentar eliminar la mascota. Por favor, contacta al administrador.");
    }

} else {
    // si intentan entrar directo sin id bloqueamos el paso
    die("Acceso denegado: No se proporcionó un ID de mascota válido.");
}
?>