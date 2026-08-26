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
    $id = (int) $_POST['id'];
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $capacidad = $_POST['capacidad'];
    $horarios = $_POST['horarios'];

    // Preparar la consulta SQL
    $sql_consultar_nombre = 'SELECT id FROM zona_comun WHERE nombre = ? AND id != ?';

    try {

        // Iniciar una transacción
        $conexion->beginTransaction();

        // Verificar que la zona exista
        $sql_consultar_zona = 'SELECT id FROM zona_comun WHERE id = ?';
        $stmtExiste = $conexion->prepare($sql_consultar_zona);
        $stmtExiste->execute([$id]);

        if (!$stmtExiste->fetch()) {

            // IMPORTANTE: si ya iniciamos transacción, debemos revertirla
            $conexion->rollBack();

            // Redireccionar a index.php
            header("Location: index.php?tipo=error&mensaje=" . urlencode("La zona no existe o fue eliminada"));
            exit;
        }

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

        // Validar que no existan horarios duplicados
        $horariosUnicos = [];

        foreach ($horarios as $horario) {

            // Crear una clave única con día, hora de inicio y hora de fin
            $clave = $horario['dia_semana'] . '-' . $horario['hora_inicio'] . '-' . $horario['hora_fin'];

            // Verificar si esa combinación ya fue agregada
            if (isset($horariosUnicos[$clave])) {

                // IMPORTANTE: si ya iniciamos transacción, debemos revertirla
                $conexion->rollBack();

                // Redireccionar a index.php
                header("Location: index.php?id_editar=$id&tipo=error&mensaje=" . urlencode("No se permiten horarios duplicados"));
                exit;
            }

            // Registrar la combinación como ya utilizada
            $horariosUnicos[$clave] = true;
        }

        // Validar que no existan horarios solapados
        for ($i = 0; $i < count($horarios); $i++) {

            for ($j = $i + 1; $j < count($horarios); $j++) {

                // Solo comparar horarios del mismo día
                if ($horarios[$i]['dia_semana'] == $horarios[$j]['dia_semana']) {

                    $inicio1 = DateTime::createFromFormat('H:i', $horarios[$i]['hora_inicio']);
                    $fin1    = DateTime::createFromFormat('H:i', $horarios[$i]['hora_fin']);

                    $inicio2 = DateTime::createFromFormat('H:i', $horarios[$j]['hora_inicio']);
                    $fin2    = DateTime::createFromFormat('H:i', $horarios[$j]['hora_fin']);

                    // Detectar si los intervalos se cruzan
                    if ($inicio1 < $fin2 && $inicio2 < $fin1) {

                        // IMPORTANTE: si ya iniciamos transacción, debemos revertirla
                        $conexion->rollBack();

                        // Redireccionar a index.php
                        header("Location: index.php?id_editar=$id&tipo=error&mensaje=" . urlencode("Existen horarios solapados"));
                        exit;
                    }
                }
            }
        }

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

            // Validar que el día de la semana esté entre 1 y 7
            if (!filter_var($diaSemana, FILTER_VALIDATE_INT) || $diaSemana < 1 || $diaSemana > 7) {

                // IMPORTANTE: si ya iniciamos transacción, debemos revertirla
                $conexion->rollBack();

                // Redireccionar a index.php
                header("Location: index.php?id_editar=$id&tipo=error&mensaje=" . urlencode("Día de la semana inválido"));
                exit;
            }

            // Validar el formato de la hora de inicio (HH:MM)
            $horaInicioValida = DateTime::createFromFormat('H:i', $horaInicio);

            if (!$horaInicioValida || $horaInicioValida->format('H:i') !== $horaInicio) {

                // IMPORTANTE: si ya iniciamos transacción, debemos revertirla
                $conexion->rollBack();

                // Redireccionar a index.php
                header("Location: index.php?id_editar=$id&tipo=error&mensaje=" . urlencode("Hora de inicio inválida"));
                exit;
            }

            // Validar el formato de la hora de fin (HH:MM)
            $horaFinValida = DateTime::createFromFormat('H:i', $horaFin);

            if (!$horaFinValida || $horaFinValida->format('H:i') !== $horaFin) {

                // IMPORTANTE: si ya iniciamos transacción, debemos revertirla
                $conexion->rollBack();

                // Redireccionar a index.php
                header("Location: index.php?id_editar=$id&tipo=error&mensaje=" . urlencode("Hora de fin inválida"));
                exit;
            }

            // Validar que la hora de fin sea mayor que la hora de inicio
            if ($horaFinValida <= $horaInicioValida) {

                // IMPORTANTE: si ya iniciamos transacción, debemos revertirla
                $conexion->rollBack();

                // Redireccionar a index.php
                header("Location: index.php?id_editar=$id&tipo=error&mensaje=" . urlencode("La hora de fin debe ser mayor que la hora de inicio"));
                exit;
            }

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
