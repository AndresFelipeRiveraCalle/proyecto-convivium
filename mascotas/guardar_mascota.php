<?php
// archivo: mascotas/guardar_mascota.php

// traemos las configuraciones globales y la conexion pdo
require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

// verificamos que los datos lleguen a traves del formulario por post
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // atrapamos los datos enviados desde la ventana flotante del modal
    $nombre_de_la_mascota = $_POST['nombre_mascota'];
    $especie_de_la_mascota = $_POST['especie_mascota'];
    $id_del_apartamento = $_POST['id_unidad'];

    // armamos la consulta sql
    // usamos signos de interrogacion por seguridad para evitar inyecciones sql
    $consulta_sql_para_guardar = "INSERT INTO mascotas (nombre, especie, id_unidad) VALUES (?, ?, ?)";

    // usamos try catch para controlar los errores de manera limpia
    try {
        // preparamos la consulta usando la conexion
        $consulta_preparada = $conexion->prepare($consulta_sql_para_guardar);

        // ejecutamos y pasamos las variables en el mismo orden que los signos de interrogacion
        $consulta_preparada->execute([
            $nombre_de_la_mascota,
            $especie_de_la_mascota,
            $id_del_apartamento
        ]);

        // si todo guarda bien devolvemos al usuario a la lista de mascotas
        // mandamos un parametro por la url para que la vista muestre la alerta verde
        header("Location: listar_mascotas.php?mensaje=creado");
        
        // frenamos el script por seguridad siempre va despues de un header
        exit;

    } catch (PDOException $error_capturado) {
        // si algo se rompe guardamos el error real en el log del servidor
        error_log("Error al guardar mascota: " . $error_capturado->getMessage());
        
        // y matamos el proceso mostrando un mensaje normal sin detalles tecnicos
        die("Lo sentimos, ocurrió un problema al registrar la mascota. Por favor, contacta al administrador.");
    }
} else {
    // si intentan entrar directo por la url bloqueamos el acceso
    die("Acceso denegado. Método no permitido.");
}
?>