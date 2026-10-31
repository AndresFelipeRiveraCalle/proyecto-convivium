<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/auth/requerir_sesion.php";
require_once ROOT_PATH . "/config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id                 = $_POST["id"] ?? null;
    $zona_id            = $_POST["zona_id"] ?? null;
    $usuario_reporta_id = $_POST["usuario_reporta_id"] ?? null;
    $descripcion        = isset($_POST["descripcion"]) ? trim($_POST["descripcion"]) : "";
    $prioridad          = $_POST["prioridad"] ?? 'media';
    $estado             = $_POST["estado"] ?? 'pendiente';
    $fecha_solucion     = !empty($_POST["fecha_solucion"]) ? $_POST["fecha_solucion"] : null;

    if (empty($id) || $descripcion == "" || empty($zona_id) || empty($usuario_reporta_id)) {
        echo "Error: Todos los campos obligatorios deben estar llenos.";
        exit;
    }

    // 1. Obtener la evidencia anterior por si no se sube un archivo nuevo
    $stmtAntiguo = $conexion->prepare("SELECT evidencia FROM mantenimiento WHERE id = ?");
    $stmtAntiguo->execute([$id]);
    $registroAntiguo = $stmtAntiguo->fetch();
    $evidencia = $registroAntiguo['evidencia'] ?? null;

    // 2. Si se adjuntó un archivo nuevo en la edición, guardarlo limpiamente en la raíz
    if (isset($_FILES["evidencia"]) && $_FILES["evidencia"]["error"] == 0) {
        $carpetaRelativa = "uploads/";
        $carpetaFisica   = "../uploads/";

        if (!file_exists($carpetaFisica)) {
            mkdir($carpetaFisica, 0777, true);
        }

        $nombreArchivo        = time() . "_" . basename($_FILES["evidencia"]["name"]);
        $rutaCompletaFisica   = $carpetaFisica . $nombreArchivo;
        // Limpiamos los slashes para evitar duplicados como '//uploads/'
        $rutaCompletaRelativa = $carpetaRelativa . $nombreArchivo;

        if (move_uploaded_file($_FILES["evidencia"]["tmp_name"], $rutaCompletaFisica)) {
            $evidencia = $rutaCompletaRelativa; 
        }
    }

    // 3. Ejecutar la actualización en la base de datos
    $sql = "UPDATE mantenimiento SET 
                zona_id = :zona_id, 
                usuario_reporta_id = :usuario_reporta_id, 
                descripcion = :descripcion, 
                prioridad = :prioridad, 
                estado = :estado, 
                fecha_solucion = :fecha_solucion, 
                evidencia = :evidencia
            WHERE id = :id";

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
            ':id'                 => $id
        ]);

        header("Location: listar.php");
        exit;

    } catch (PDOException $e) {
        echo "Error al actualizar en la base de datos: " . $e->getMessage();
    }

} else {
    echo "Acceso no permitido.";
}
?>