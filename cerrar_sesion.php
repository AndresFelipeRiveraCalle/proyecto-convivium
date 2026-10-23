<?php
// reanudamos la sesion actual
session_start();

// vaciamos todas las variables de sesion limpiando los datos del usuario
session_unset();

// destruimos la sesion por completo en el servidor
session_destroy();

// redirigimos al usuario de vuelta al login
header("Location: login.php");
exit();
?>