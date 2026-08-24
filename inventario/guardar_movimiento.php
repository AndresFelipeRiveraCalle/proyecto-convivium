<?php

require_once '../config/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_articulo = (int)$_POST['id_articulo'];
    $tipo_movimiento = trim($_POST['tipo_movimiento']);
    $cantidad = (int)$_POST['cantidad'];
    $nota = trim($_POST['nota']);
    
    // Tu ID real en la base de datos para evitar el error 1452
    $id_usuario = 7; 
    
    if ($cantidad <= 0) {
        die("La cantidad debe ser mayor a cero.");
    }

    try {
        $query = "INSERT INTO movimientos_inventario (id_articulo, id_usuario, tipo_movimiento, cantidad, nota) 
                    VALUES (:id_articulo, :id_usuario, :tipo, :cantidad, :nota)";
        $stmt = $conexion->prepare($query);
        $stmt->execute([
            ':id_articulo' => $id_articulo,
            ':id_usuario' => $id_usuario,
            ':tipo' => $tipo_movimiento,
            ':cantidad' => $cantidad,
            ':nota' => $nota
        ]);
        
        header("Location: listar.php?mensaje=movimiento_registrado");
        exit();

    } catch (PDOException $e) {
        echo "Error al registrar el movimiento: " . $e->getMessage();
    }
} else {
    header("Location: listar.php");
    exit();
}
?>