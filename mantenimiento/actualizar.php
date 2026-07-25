<?php
require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "Acceso no permitido.";
    exit;
}

$id                 = (int)$_POST["id"];
$zona_id            = $_POST["zona_id"];
$usuario_reporta_id = $_POST["usuario_reporta_id"];
$descripcion        = trim($_POST["descripcion"]);
$prioridad          = $_POST["prioridad"];
$estado             = $_POST["estado"];
$fecha_solucion     = $_POST["fecha_solucion"] !== "" ? $_POST["fecha_solucion"] : null;

// Obtener evidencia actual
$stmtVer = $conexion->prepare("SELECT evidencia FROM mantenimiento WHERE id = :id");
$stmtVer->execute([':id' => $id]);
$actual = $stmtVer->fetch();
$evidencia = $actual['evidencia'];

// Subir nueva evidencia si viene
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

$sql = "UPDATE mantenimiento SET
            zona_id            = :zona_id,
            usuario_reporta_id = :usuario_reporta_id,
            descripcion        = :descripcion,
            prioridad          = :prioridad,
            estado             = :estado,
            fecha_solucion     = :fecha_solucion,
            evidencia          = :evidencia
        WHERE id = :id";

$stmt = $conexion->prepare($sql);
$stmt->execute([
    ':zona_id'            => $zona_id,
    ':usuario_reporta_id' => $usuario_reporta_id,
    ':descripcion'        => $descripcion,
    ':prioridad'          => $prioridad,
    ':estado'             => $estado,
    ':fecha_solucion'     => $fecha_solucion,
    ':evidencia'          => $evidencia,
    ':id'                 => $id,
]);

header("Location: listar.php");
exit;
?>