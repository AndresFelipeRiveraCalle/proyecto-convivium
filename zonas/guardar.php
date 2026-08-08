<?php

/**
 * =========================================================================
 * MÓDULO: Zonas Comunes - Convivium
 * ARCHIVO: guardar.php
 * DESCRIPCIÓN: Permite guardar una zona común.
 * AUTOR: Andrés Felipe Rivera Calle
 * FECHA: 2026-08-07
 * =========================================================================
 */

// Importar la conexión a la base de datos
require_once "../config/conexion.php";

// Verificar que los parámetros existen en el arreglo y que no contengan cadenas vacías
if (isset($_POST['nombre']) && !empty($_POST['nombre']) && isset($_POST['descripcion']) && !empty($_POST['descripcion']) && isset($_POST['capacidad']) && !empty($_POST['capacidad']) && isset($_POST['horario']) && !empty($_POST['horario'])) {

    // Capturar las variables del formulario
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $capacidad = $_POST['capacidad'];
    $horario = $_POST['horario'];

    // Validar que la zona no exista antes de insertarla en la base de datos
    $sql_validar = 'SELECT id FROM zona_comun WHERE nombre = ?';
    try {

        $stmt = $conexion->prepare($sql_validar);
        $stmt->execute([$nombre]);

        if ($stmt->fetch()) {
            header("Location: index.php?tipo=error&mensaje=" . urlencode("Ya existe una zona con ese nombre"));
            exit;
        }

        // Preparar la consulta SQL 
        $sql_insertar = 'INSERT INTO zona_comun (nombre, descripcion, capacidad, horario_disponible) VALUES (?, ?, ?, ?)';

        $stmt = $conexion->prepare($sql_insertar);
        $stmt->execute([$nombre, $descripcion, $capacidad, $horario]);

        // Redireccionar a index.php
        header("Location: index.php?tipo=exito&mensaje=" . urlencode("Zona creada correctamente"));
        exit;
    } catch (PDOException $e) {

        header("Location: index.php?tipo=error&mensaje=" . urlencode("No se pudo crear la zona"));
        exit;
    }
}
