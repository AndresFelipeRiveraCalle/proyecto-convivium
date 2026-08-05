<?php

/**
 * =========================================================================
 * MÓDULO: Zonas Comunes - Convivium
 * ARCHIVO: eliminar.php
 * DESCRIPCIÓN: Permite eliminar una zona común por el campo ID.
 * AUTOR: Andrés Felipe Rivera Calle
 * FECHA: 2026-08-04
 * =========================================================================
 */

// Importar la conexión a la base de datos 
require_once "../config/conexion.php";

// Verifiar que el parámetro ID existe y que sea diferente de cadena vacía
if(isset($_GET['id']) && !empty($_GET['id'])){

    $id = $_GET['id'];

    // Preparar la consulta SQL
    $sql = 'DELETE FROM zona_comun WHERE id = ?';

}
?>