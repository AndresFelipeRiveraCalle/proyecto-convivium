<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


try {
    $nombre = trim($_POST["nombre"]);

    if(empty($nombre)){
        throw new Exception(
            "El nombre es obligatorio"
        );

    }


    $sql = "
        INSERT INTO estados_civiles
        (
            nombre, estado
        )
        VALUES(?, 1)
    ";


    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        $nombre
    ]);



    header(
        "Location: ../configuracion/estados_civiles.php?tipo=success&texto=Estado civil creado correctamente"
    );


} catch(Exception $e){


    header(
        "Location: ../configuracion/estados_civiles.php?tipo=error&texto=".$e->getMessage()
    );

}