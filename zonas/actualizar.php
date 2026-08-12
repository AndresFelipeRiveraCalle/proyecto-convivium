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
if (
    isset($_POST['id']) &&
    !empty($_POST['id']) &&
    isset($_POST['nombre']) &&
    !empty($_POST['nombre']) &&
    isset($_POST['descripcion']) &&
    !empty($_POST['descripcion']) &&
    isset($_POST['capacidad']) &&
    !empty($_POST['capacidad']) &&
    isset($_POST['horarios']) &&
    !empty($_POST['horarios'])
) {

    // Capturar los valores del formulario
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $capacidad = $_POST['capacidad'];
    $horarios = $_POST['horarios'];

    // Preparar la consulta SQL
    $sql_consultar_nombre = 'SELECT id FROM zona_comun WHERE nombre = ? AND id != ?';

    try {

        // Iniciar una transacción
        $conexion->beginTransaction();

        // Verificar nombre duplicado
        $stmt = $conexion->prepare($sql_consultar_nombre);
        $stmt->execute([$nombre, $id]);

        // Verificar que no exista otra zona con el mismo nombre
        if ($stmt->fetch()) {

            // IMPORTANTE: si ya iniciamos transacción, debemos revertirla
            $conexion->rollBack();

            // Redireccionar a index.php
            header("Location: index.php?id_editar=" . urlencode($id) . "&tipo=error&mensaje=" . urlencode('Ya existe otra zona con ese nombre'));
            exit;
        }

        // Preparar la consulta SQL
        $sql_actualizar = 'UPDATE zona_comun
                            SET nombre = ?, descripcion = ?, capacidad = ? 
                            WHERE id = ?';

        $stmt = $conexion->prepare($sql_actualizar);
        $stmt->execute([$nombre, $descripcion, $capacidad, $id]);

        // Eliminar los horarios anteriores de la zona
        $sql_eliminar_horarios = 'DELETE 
                                    FROM horario_zona 
                                    WHERE id_zona = ?';

        $stmtEliminarHorarios = $conexion->prepare($sql_eliminar_horarios);
        $stmtEliminarHorarios->execute([$id]);

        // Preparar la consulta para guardar los nuevos horarios
        $sql_horario = 'INSERT INTO horario_zona 
                (id_zona, dia_semana, hora_inicio, hora_fin) 
                VALUES (?, ?, ?, ?)';

        $stmtHorario = $conexion->prepare($sql_horario);

        // Recorrer los horarios actuales recibidos desde el formulario
        foreach ($horarios as $horario) {

            // Obtener los datos del horario
            $diaSemana = $horario['dia_semana'];
            $horaInicio = $horario['hora_inicio'];
            $horaFin = $horario['hora_fin'];

            // Guardar el horario asociado a la zona
            $stmtHorario->execute([
                $id,
                $diaSemana,
                $horaInicio,
                $horaFin
            ]);
        }

        // Confirmar todos los cambios
        $conexion->commit();

        // Redireccionar a index.php
        header("Location: index.php?tipo=exito&mensaje=" . urlencode('Zona actualizada correctamente'));
        exit;
    } catch (PDOException $e) {

        // Revertir los cambios si la transacción sigue activa
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }

        /* echo '<pre>';
        echo $e->getMessage();
        echo '</pre>';
        exit;*/

        header("Location: index.php?tipo=error&mensaje=" . urlencode("No se pudo actualizar la zona"));
        exit;
    }
}
