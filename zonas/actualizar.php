<?php

/**
 * =========================================================================
 * MÓDULO: Zonas Comunes - Convivium
 * ARCHIVO: actualizar.php
 * DESCRIPCIÓN: Permite guardar una zona común.
 * AUTOR: Andrés Felipe Rivera Calle
 * FECHA: 2026-08-07
 * =========================================================================
 */

// Importar la conexión a la base de datos
require_once "../config/conexion.php";

// Verificar que los parámetros existen en el arreglo y que no contengan cadenas vacías
if (isset($_POST['id']) && !empty($_POST['id']) && isset($_POST['nombre']) && !empty($_POST['nombre']) && isset($_POST['descripcion']) && !empty($_POST['descripcion']) && isset($_POST['capacidad']) && !empty($_POST['capacidad']) && isset($_POST['horario']) && !empty($_POST['horario'])) {

    // Capturar los valores del formulario
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $capacidad = $_POST['capacidad'];
    $horario = $_POST['horario'];

    // Preparar la consulta SQL
    $sql_consultar_nombre = 'SELECT id FROM zona_comun WHERE nombre = ? AND id != ?';

    try {

        $stmt = $conexion->prepare($sql_consultar_nombre);
        $stmt->execute([$nombre, $id]);

        // Verificar que no exista otra zona con el mismo nombre
        if ($stmt->fetch()) {

            // Redireccionar a index.php
            header("Location: index.php?tipo=error&mensaje=" . urlencode('Ya existe otra zona con ese nombre'));
            exit;
        }

        // Preparar la consulta SQL
        $sql_actualizar = 'UPDATE zona_comun SET nombre = ?, descripcion = ?, capacidad = ?, horario_disponible = ? WHERE id = ?';
        $stmt = $conexion->prepare($sql_actualizar);
        $stmt->execute([$nombre, $descripcion, $capacidad, $horario, $id]);

        // Redireccionar a index.php
        header("Location: index.php?tipo=exito&mensaje=" . urlencode('Zona actualizada correctamente'));
        exit;
    } catch (PDOException $e) {

        header("Location: index.php?tipo=error&mensaje=" . urlencode("No se pudo actualizar la zona"));
        exit;
    }
}