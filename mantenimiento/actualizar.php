<?php
// Conectar a la base de datos
require_once "conexion.php";

// Verificar que los datos vienen del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ========== 1. RECIBIR TODOS LOS DATOS ==========
    $id = $_POST["id"];
    $descripcion = $_POST["descripcion"];
    $estado = $_POST["estado"];
    $prioridad = $_POST["prioridad"];
    $responsable = $_POST["responsable"];
    $costo = $_POST["costo"];
    $usuario_id = $_POST["usuario_id"];
    $conjunto_id = $_POST["conjunto_id"];
    $fecha_finalizacion = $_POST["fecha_finalizacion"];
    $comentarios = $_POST["comentarios"];
    
    // ========== 2. MANEJAR LA EVIDENCIA (FOTO/FACTURA) ==========
    
    // Primero, obtener la evidencia actual para no perderla
    $sqlVer = "SELECT evidencia FROM mantenimiento WHERE id = $id";
    $resVer = $conn->query($sqlVer);
    $filaVer = $resVer->fetch_assoc();
    $evidenciaActual = $filaVer['evidencia'];
    
    // Verificar si el usuario subió un archivo nuevo
    if(isset($_FILES["evidencia"]) && $_FILES["evidencia"]["error"] == 0) {
        
        // Crear carpeta si no existe
        $carpeta = "uploads/";
        if(!file_exists($carpeta)) {
            mkdir($carpeta, 0777, true);
        }
        
        // Generar nombre único
        $nombreArchivo = time() . "_" . $_FILES["evidencia"]["name"];
        $rutaCompleta = $carpeta . $nombreArchivo;
        
        // Mover el archivo
        move_uploaded_file($_FILES["evidencia"]["tmp_name"], $rutaCompleta);
        
        // Guardar la nueva ruta
        $evidencia = $rutaCompleta;
    } else {
        // Si no subió nada, mantener la evidencia actual
        $evidencia = $evidenciaActual;
    }
    
    // ========== 3. PREPARAR VALORES NULOS ==========
    if($responsable == "") { $responsable = "NULL"; }
    else { $responsable = "'$responsable'"; }
    
    if($costo == "") { $costo = "NULL"; }
    
    if($fecha_finalizacion == "") { $fecha_finalizacion = "NULL"; }
    else { $fecha_finalizacion = "'$fecha_finalizacion'"; }
    
    if($comentarios == "") { $comentarios = "NULL"; }
    else { $comentarios = "'" . addslashes($comentarios) . "'"; }
    
    if($evidencia == "") { $evidencia = "NULL"; }
    else { $evidencia = "'$evidencia'"; }
    
    // ========== 4. ACTUALIZAR EN LA BASE DE DATOS ==========
    $sql = "UPDATE mantenimiento SET 
                descripcion = '$descripcion',
                estado = '$estado',
                prioridad = '$prioridad',
                responsable = $responsable,
                costo = $costo,
                usuario_id = $usuario_id,
                conjunto_id = $conjunto_id,
                fecha_finalizacion = $fecha_finalizacion,
                comentarios = $comentarios,
                evidencia = $evidencia
            WHERE id = $id";
    
    // Ejecutar
    if($conn->query($sql)) {
        header("Location: listar.php");
    } else {
        echo "Error al actualizar: " . $conn->error;
    }
    
} else {
    echo "Acceso no permitido";
}

$conn->close();
?>