<?php
function verificar_sesion() {
    if (!isset($_SESSION["usuario"])) { // verificamos si el usaurio existe si no vuelve al logi
        header("Location: ../login.php"); // header permitiendo redirigir usuario al login
        exit;
    }
}


function solo_admin() {
    verificar_sesion();
    if ($_SESSION["rol"] !== "admin") { // verificamos si el usaurio si admin
        header("Location: ../dashboard/index.php");// permitiendo redirigir dashboard
        exit;
    }
}

/**
 * Devuelve true si el usuario actual es admin.
 */
function es_admin() {
    return isset($_SESSION["rol"]) && $_SESSION["rol"] === "admin";
}

/**
Devuelve true si el usuario actual es rol usuario.
 */
function es_usuario() {
    return isset($_SESSION["rol"]) && $_SESSION["rol"] === "usuario";
}
?>