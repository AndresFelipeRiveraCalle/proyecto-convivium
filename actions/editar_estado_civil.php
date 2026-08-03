<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


try {


    $id = $_POST["id_estado_civil"];
    $nombre = trim($_POST["nombre"]);
    $estado = $_POST["estado"];



    $sql = "
        UPDATE estados_civiles
        SET
            nombre = ?,
            estado = ?
        WHERE id_estado_civil = ?
    ";


    $stmt = $conexion->prepare($sql);


    $stmt->execute([
        $nombre,
        $estado,
        $id
    ]);



    header(
        "Location: ../configuracion/estados_civiles.php?tipo=success&texto=Estado civil actualizado"
    );


}catch(Exception $e){


    header(
        "Location: ../configuracion/estados_civiles.php?tipo=error&texto=".$e->getMessage()
    );

}