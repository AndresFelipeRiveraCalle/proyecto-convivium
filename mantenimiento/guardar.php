<?php

require_once "../config/conexion.php";
require_once "../config/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $zona_id            = $_POST["zona_id"] ?? null;
    $usuario_reporta_id = $_POST["usuario_reporta_id"] ?? null;
    $descripcion        = isset($_POST["descripcion"]) ? trim($_POST["descripcion"]) : "";
    $prioridad          = $_POST["prioridad"] ?? 'media';
    $estado             = $_POST["estado"] ?? 'pendiente';
    $fecha_solucion     = !empty($_POST["fecha_solucion"]) ? $_POST["fecha_solucion"] : null;

    if ($descripcion == "" || empty($zona_id) || empty($usuario_reporta_id)) {
        echo "Error: descripción, zona y solicitante son obligatorios.";
        exit;
    }

    $evidencia = null;
    if (isset($_FILES["evidencia"]) && $_FILES["evidencia"]["error"] == 0) {
        $carpetaRelativa = "uploads/";
        $carpetaFisica   = "../uploads/";

        if (!file_exists($carpetaFisica)) {
            mkdir($carpetaFisica, 0777, true);
        }

        $nombreArchivo        = time() . "_" . basename($_FILES["evidencia"]["name"]);
        $rutaCompletaFisica   = $carpetaFisica . $nombreArchivo;
        $rutaCompletaRelativa = $carpetaRelativa . $nombreArchivo;

        if (move_uploaded_file($_FILES["evidencia"]["tmp_name"], $rutaCompletaFisica)) {
            $evidencia = $rutaCompletaRelativa;
        }
    }

    $sql = "INSERT INTO mantenimiento 
                (zona_id, usuario_reporta_id, descripcion, prioridad, estado, fecha_solucion, evidencia)
            VALUES 
                (:zona_id, :usuario_reporta_id, :descripcion, :prioridad, :estado, :fecha_solucion, :evidencia)";

    try {
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':zona_id'            => $zona_id,
            ':usuario_reporta_id' => $usuario_reporta_id,
            ':descripcion'        => $descripcion,
            ':prioridad'          => $prioridad,
            ':estado'             => $estado,
            ':fecha_solucion'     => $fecha_solucion,
            ':evidencia'          => $evidencia,
        ]);

        header("Location: listar.php");
        exit;
        
    } catch (PDOException $e) {
        echo "Error en la base de datos: " . $e->getMessage();
    }

} else {
    echo "Acceso no permitido.";
}
?>