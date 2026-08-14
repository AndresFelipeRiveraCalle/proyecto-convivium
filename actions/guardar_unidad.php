<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: " . BASE_URL . "configuracion/unidades.php");
    exit;
}

try {

    //==========================================
    // DATOS DEL FORMULARIO
    //==========================================

    $idTipoConfig = (int) $_POST["id_tipo_config"];
    $codigo       = trim($_POST["codigo"]);
    $nombre       = trim($_POST["nombre"]);
    $piso         = trim($_POST["piso"]);
    $area         = $_POST["area"] !== "" ? $_POST["area"] : null;
    $coeficiente  = $_POST["coeficiente"] !== "" ? $_POST["coeficiente"] : null;
    $estado       = trim($_POST["estado"]);
    $observaciones = trim($_POST["observaciones"]);

    //==========================================
    // VALIDACIONES
    //==========================================

    if ($codigo == "") {

        throw new Exception("Debe ingresar el código de la unidad.");

    }

    //==========================================
    // VALIDAR QUE NO EXISTA EL CÓDIGO
    // DENTRO DEL MISMO GRUPO
    //==========================================

    $sql = "SELECT COUNT(*)
            FROM unidades
            WHERE id_tipo_config = :grupo
              AND codigo = :codigo";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":grupo"  => $idTipoConfig,
        ":codigo" => $codigo
    ]);

    if ($stmt->fetchColumn() > 0) {

        throw new Exception("Ya existe una unidad con ese código.");

    }

    //==========================================
    // INSERTAR
    //==========================================

    $sql = "INSERT INTO unidades
            (
                id_tipo_config,
                codigo,
                nombre,
                piso,
                area,
                coeficiente,
                estado,
                observaciones,
                fecha_creacion,
                fecha_actualizacion,
                activo
            )
            VALUES
            (
                :id_tipo_config,
                :codigo,
                :nombre,
                :piso,
                :area,
                :coeficiente,
                :estado,
                :observaciones,
                NOW(),
                NOW(),
                1
            )";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([

        ":id_tipo_config" => $idTipoConfig,
        ":codigo"         => $codigo,
        ":nombre"         => $nombre,
        ":piso"           => $piso,
        ":area"           => $area,
        ":coeficiente"    => $coeficiente,
        ":estado"         => $estado,
        ":observaciones"  => $observaciones

    ]);

    $mensaje = urlencode("La unidad fue registrada correctamente.");

    header(
        "Location: "
        . BASE_URL .
        "configuracion/unidades.php?id="
        . $idTipoConfig .
        "&tipo=success&texto="
        . $mensaje
    );

    exit;

} catch (Exception $e) {

    $mensaje = urlencode($e->getMessage());

    header(
        "Location: "
        . BASE_URL .
        "configuracion/unidades.php?id="
        . ($_POST["id_tipo_config"] ?? 0)
        . "&tipo=error&texto="
        . $mensaje
    );

    exit;

} catch (PDOException $e) {

    $mensaje = urlencode("Error en la base de datos: " . $e->getMessage());

    header(
        "Location: "
        . BASE_URL .
        "configuracion/unidades.php?id="
        . ($_POST["id_tipo_config"] ?? 0)
        . "&tipo=error&texto="
        . $mensaje
    );

    exit;

}