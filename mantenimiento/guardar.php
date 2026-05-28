<?php
// Conectar a la base de datos
require_once "conexion.php";

// Verificar que los datos vienen del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ========== 1. RECIBIR TODOS LOS DATOS DEL FORMULARIO ==========
    $descripcion = $_POST["descripcion"];           // Descripción del problema
    $estado = $_POST["estado"];                     // pendiente, en_proceso, finalizado
    $prioridad = $_POST["prioridad"];               // baja, media, alta, critica (NUEVO)
    $responsable = $_POST["responsable"];           // Técnico o empresa (NUEVO)
    $costo = $_POST["costo"];                       // Costo del trabajo (NUEVO)
    $usuario_id = $_POST["usuario_id"];             // Quién lo solicita
    $conjunto_id = $_POST["conjunto_id"];           // Torre o conjunto
    $fecha_finalizacion = $_POST["fecha_finalizacion"]; // Cuándo terminó
    $comentarios = $_POST["comentarios"];           // Notas adicionales (NUEVO)
    
    // ========== 2. VALIDAR QUE LOS CAMPOS OBLIGATORIOS NO ESTÉN VACÍOS ==========
    // Campos obligatorios: descripcion, usuario_id, conjunto_id
    if($descripcion == "" || $usuario_id == "" || $conjunto_id == "") {
        echo "Error: La descripción, el solicitante y la torre son obligatorios";
        exit;
    }
    
    // Si la fecha de finalización está vacía, poner NULL
    if($fecha_finalizacion == "") {
        $fecha_finalizacion = NULL;
    }
    
    // Si el responsable está vacío, poner NULL
    if($responsable == "") {
        $responsable = NULL;
    }
    
    // Si el costo está vacío, poner NULL
    if($costo == "") {
        $costo = NULL;
    }
    
    // Si los comentarios están vacíos, poner NULL
    if($comentarios == "") {
        $comentarios = NULL;
    }
    
    // ========== 3. SUBIR LA EVIDENCIA (FOTO O FACTURA) ==========
    $evidencia = NULL;  // Por defecto no hay evidencia
    
    // Verificar si el usuario subió un archivo
    if(isset($_FILES["evidencia"]) && $_FILES["evidencia"]["error"] == 0) {
        
        // Crear la carpeta "uploads" si no existe
        $carpeta = "uploads/";
        if(!file_exists($carpeta)) {
            mkdir($carpeta, 0777, true);  // Crear la carpeta con permisos
        }
        
        // Generar un nombre único para el archivo (para que no se repita)
        $nombreArchivo = time() . "_" . $_FILES["evidencia"]["name"];
        
        // Ruta completa donde se guardará el archivo
        $rutaCompleta = $carpeta . $nombreArchivo;
        
        // Mover el archivo de la carpeta temporal a nuestra carpeta "uploads"
        move_uploaded_file($_FILES["evidencia"]["tmp_name"], $rutaCompleta);
        
        // Guardar la ruta en la base de datos
        $evidencia = $rutaCompleta;
    }
    
    // ========== 4. GUARDAR EN LA BASE DE DATOS ==========
    $sql = "INSERT INTO mantenimiento 
            (descripcion, estado, prioridad, responsable, costo, 
                usuario_id, conjunto_id, fecha_finalizacion, comentarios, evidencia) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    // Preparar la consulta
    $stmt = $conn->prepare($sql);
    
    // Vincular los valores (s = texto, i = número entero, d = número decimal)
    $stmt->bind_param("sssssdiiss", 
        $descripcion,      // s
        $estado,           // s
        $prioridad,        // s
        $responsable,      // s
        $costo,            // s (se guarda como texto por ahora)
        $usuario_id,       // d (número)
        $conjunto_id,      // i (entero)
        $fecha_finalizacion, // s
        $comentarios,      // s
        $evidencia         // s
    );
    
    // Ejecutar y verificar si funcionó
    if($stmt->execute()) {
        // Si todo bien, ir al listado
        header("Location: listar.php");
    } else {
        echo "Error al guardar: " . $conn->error;
    }
    
} else {
    echo "Acceso no permitido";
}

$conn->close();
?>