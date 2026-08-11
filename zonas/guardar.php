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
if (
    isset($_POST['nombre']) && 
    !empty($_POST['nombre']) && 
    isset($_POST['descripcion']) && 
    !empty($_POST['descripcion']) && 
    isset($_POST['capacidad']) && 
    !empty($_POST['capacidad']) && 
    isset($_POST['horarios']) && 
    !empty($_POST['horarios'])) {

    // Capturar las variables del formulario
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $capacidad = $_POST['capacidad'];
    $horarios = $_POST['horarios'];

    // Validar que la zona no exista antes de insertarla en la base de datos
    $sql_validar = 'SELECT id FROM zona_comun WHERE nombre = ?';

    try {

        $stmt = $conexion->prepare($sql_validar);
        $stmt->execute([$nombre]);

        if ($stmt->fetch()) {
            header("Location: index.php?tipo=error&mensaje=" . urlencode("Ya existe una zona con ese nombre"));
            exit;
        }

        // Iniciar la transacción
        $conexion->beginTransaction();

        // Preparar la consulta SQL para guardar la zona
        $sql_insertar = 'INSERT INTO zona_comun (nombre, descripcion, capacidad) VALUES (?, ?, ?)';

        $stmt = $conexion->prepare($sql_insertar);
        $stmt->execute([$nombre, $descripcion, $capacidad]);

        // Obtener el ID de la zona recién creada
        $idZona = $conexion->lastInsertId();

        // Preparar la consulta para guardar los horarios
        $sql_horario = 'INSERT INTO horario_zona 
                        (id_zona, dia_semana, hora_inicio, hora_fin) 
                        VALUES (?, ?, ?, ?)';

        $stmtHorario = $conexion->prepare($sql_horario);

        // Recorrer cada horario recibido desde el formulario
        foreach ($horarios as $horario) {

            // Obtener los datos del horario
            $diaSemana = $horario['dia_semana'];
            $horaInicio = $horario['hora_inicio'];
            $horaFin = $horario['hora_fin'];

            // Guardar el horario asociado a la zona
            $stmtHorario->execute([$idZona, $diaSemana, $horaInicio, $horaFin]);

            // throw new PDOException("Error de prueba para comprobar rollback");
        }

        // Confirmar todos los cambios realizados
        $conexion->commit();

        // Redireccionar a index.php
        header("Location: index.php?tipo=exito&mensaje=" . urlencode("Zona creada correctamente"));
        exit;

    } catch (PDOException $e) {

        // Deshacer todos los cambios realizados durante la transacción
        $conexion->rollBack();

        // Redireccionar a index.php
        header("Location: index.php?tipo=error&mensaje=" . urlencode("No se pudo crear la zona"));
        exit;
    }
}