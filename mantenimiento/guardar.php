<?php
require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $zona_id            = $_POST["zona_id"];
    $usuario_reporta_id = $_POST["usuario_reporta_id"];
    $descripcion        = trim($_POST["descripcion"]);
    $prioridad          = $_POST["prioridad"];
    $estado             = $_POST["estado"];
    $fecha_solucion     = $_POST["fecha_solucion"] !== "" ? $_POST["fecha_solucion"] : null;

    // Validar obligatorios
    if ($descripcion == "" || $zona_id == "" || $usuario_reporta_id == "") {
        echo "Error: descripción, zona y solicitante son obligatorios.";
        exit;
    }

    // subir archivo o foto de evidencia
    $evidencia = null;
    if (isset($_FILES["evidencia"]) && $_FILES["evidencia"]["error"] == 0) {
        $carpeta = "uploads/";
        if (!file_exists($carpeta)) {
            mkdir($carpeta, 0777, true);
        }
        $nombreArchivo = time() . "_" . basename($_FILES["evidencia"]["name"]);
        $rutaCompleta  = $carpeta . $nombreArchivo;
        move_uploaded_file($_FILES["evidencia"]["tmp_name"], $rutaCompleta);
        $evidencia = $rutaCompleta;
    }

    $sql = "INSERT INTO mantenimiento 
                (zona_id, usuario_reporta_id, descripcion, prioridad, estado, fecha_solucion, evidencia)
            VALUES 
                (:zona_id, :usuario_reporta_id, :descripcion, :prioridad, :estado, :fecha_solucion, :evidencia)";

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

} else {
    echo "Acceso no permitido.";
}
?>