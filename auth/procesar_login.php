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
        // buscamos solo por correo porque la contrasena en la base de datos esta encriptada y php debe comparar el hash de forma segura
        $consulta_sql_buscar_usuario = "SELECT id, nombres, apellidos, correo, contrasena, rol_id, estado FROM usuario WHERE correo = :correo_buscado LIMIT 1";

        $sentencia_preparada = $conexion->prepare($consulta_sql_buscar_usuario);

        // vinculamos el parametro para evitar inyeccion sql como regla de oro de seguridad
        $sentencia_preparada->bindParam(':correo_buscado', $correo_ingresado_por_usuario);
        $sentencia_preparada->execute();

        // extraemos los datos del usuario si existe en la base de datos
        $datos_del_usuario_encontrado = $sentencia_preparada->fetch(PDO::FETCH_ASSOC);

        // verificamos que el usuario exista y este activo
        if (
            $datos_del_usuario_encontrado
            && (int) $datos_del_usuario_encontrado['estado'] === 1
            && (int) ($datos_del_usuario_encontrado['rol_id'] ?? 0) === 1
        ) {

            // password verify compara la clave del usuario con el hash de la base de datos protegiendo contra ataques de tiempo
            if (
    !empty($datos_del_usuario_encontrado['contrasena'])
    && password_verify(
        $contrasena_ingresada_por_usuario,
        $datos_del_usuario_encontrado['contrasena']
    )
) {
                session_regenerate_id(true);
                // al tener exito guardamos los datos importantes en la variable de sesion para que 		las demas paginas sepan quien navega
                $_SESSION['id_usuario_autenticado'] = $datos_del_usuario_encontrado['id'];
                $_SESSION['nombre_usuario_autenticado'] = $datos_del_usuario_encontrado['nombres'] . ' ' . $datos_del_usuario_encontrado['apellidos'];
                $_SESSION['rol_usuario_autenticado'] = $datos_del_usuario_encontrado['rol_id'];

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
        error_log("Error en autenticación: " . $error_de_base_de_datos->getMessage());
        header("Location: ../login.php?error=sistema");
        exit();
    }

} else {
    // si alguien entra directo al archivo por url lo devolvemos al login
    header("Location: ../login.php");
    exit();
}
?>