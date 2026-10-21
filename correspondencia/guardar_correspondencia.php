<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Capturamos los datos enviados desde el modal
    $remitente = trim($_POST['remitente']);
    $descripcion = trim($_POST['descripcion']);
    $id_unidad = $_POST['id_unidad'];

    try {
        // La consulta SQL. No enviamos el 'estado' ni 'fecha_recepcion' porque la BD 
        // tiene configurado por defecto que sea 'Pendiente' y la fecha actual.
        $consulta = "INSERT INTO correspondencia (id_unidad, remitente, descripcion) VALUES (?, ?, ?)";
        
        $stmt = $conexion->prepare($consulta);
        $stmt->execute([$id_unidad, $remitente, $descripcion]);

        // Redirigimos de vuelta a la lista con la alerta verde
        header("Location: listar_correspondencia.php?mensaje=creado");
        exit;

    } catch (PDOException $e) {
        error_log("Error al guardar correspondencia: " . $e->getMessage());
        die("Error al registrar el paquete en la base de datos: " . $e->getMessage());
    }
} else {
    die("Acceso denegado.");
}
?>