<?php
// iniciamos sesion php antes de cualquier html o impresion para recordar la autenticacion del usuario
session_start();

// Traemos tu archivo de conexión a la base de datos
require_once "../config/conexion.php";

// verificamos que los datos lleguen por post por seguridad y nunca por get en la url
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // capturamos los datos con variables muy descriptivas y usamos trim en el correo para borrar espacios accidentales
    $correo_ingresado_por_usuario = trim($_POST['correo_electronico']);
    $contrasena_ingresada_por_usuario = $_POST['contrasena_secreta'];

    try {
        // ACTUALIZACIÓN: Buscamos al usuario incluyendo su foto y hacemos un LEFT JOIN para traer el nombre real de su rol
        $consulta_sql_buscar_usuario = "
            SELECT u.id, u.nombres, u.apellidos, u.correo, u.contrasena, u.rol_id, u.estado, u.foto, r.nombre AS rol_nombre 
            FROM usuario u 
            LEFT JOIN rol r ON u.rol_id = r.id 
            WHERE u.correo = :correo_buscado 
            LIMIT 1
        ";
        
        $sentencia_preparada = $conexion->prepare($consulta_sql_buscar_usuario);
        
        // vinculamos el parametro para evitar inyeccion sql como regla de oro de seguridad
        $sentencia_preparada->bindParam(':correo_buscado', $correo_ingresado_por_usuario);
        $sentencia_preparada->execute();

        // extraemos los datos del usuario si existe en la base de datos
        $datos_del_usuario_encontrado = $sentencia_preparada->fetch(PDO::FETCH_ASSOC);

        // verificamos que el usuario exista y este activo
        if ($datos_del_usuario_encontrado && $datos_del_usuario_encontrado['estado'] == 1) {
            
            // password verify compara la clave del usuario con el hash de la base de datos protegiendo contra ataques de tiempo
            if (password_verify($contrasena_ingresada_por_usuario, $datos_del_usuario_encontrado['contrasena'])) {
                
                // 1. MANTENEMOS TUS VARIABLES ORIGINALES INTACTAS PARA NO DAÑAR TU SOFTWARE ACTUAL
                $_SESSION['id_usuario_autenticado'] = $datos_del_usuario_encontrado['id'];
                $_SESSION['nombre_usuario_autenticado'] = $datos_del_usuario_encontrado['nombres'] . ' ' . $datos_del_usuario_encontrado['apellidos'];
                $_SESSION['rol_usuario_autenticado'] = $datos_del_usuario_encontrado['rol_id'];

                // 2. AGREGAMOS LAS VARIABLES NECESARIAS ÚNICAMENTE PARA LA ESQUINA DEL HEADER
                $_SESSION['nombres'] = $datos_del_usuario_encontrado['nombres'];
                $_SESSION['apellidos'] = $datos_del_usuario_encontrado['apellidos'];
                $_SESSION['foto'] = $datos_del_usuario_encontrado['foto']; // Ruta de la foto en BD
                $_SESSION['rol_nombre'] = $datos_del_usuario_encontrado['rol_nombre'] ?? 'Administrador General';

                // redirigimos al usuario al panel principal y usamos exit para detener el script
                header("Location: ../inicio.php"); 
                exit();

            } else {
                // si falla la contrasena redirigimos al login con un error generico para no dar pistas a un atacante
                header("Location: ../login.php?error=credenciales");
                exit();
            }

        } else {
            // si el correo no existe o esta inactivo mostramos el mismo mensaje generico por seguridad
            header("Location: ../login.php?error=credenciales");
            exit();
        }

    } catch (PDOException $error_de_base_de_datos) {
        // manejamos errores catastroficos del sistema de autenticacion
        die("Error en el sistema de autenticación: " . $error_de_base_de_datos->getMessage());
    }
} else {
    // si alguien entra directo al archivo por url lo devolvemos al login
    header("Location: ../login.php");
    exit();
}
?>