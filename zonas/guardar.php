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
    !empty($_POST['horarios'])
) {

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

            // Redireccionar a index.php
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

        // Validar que no existan horarios duplicados
        $horariosUnicos = [];

        foreach ($horarios as $horario) {

            // Crear una clave única con día, hora de inicio y hora de fin
            $clave = $horario['dia_semana'] . ' - ' . $horario['hora_inicio'] . ' - ' . $horario['hora_fin'];

            // Verificar si esa combinación ya fué agregada
            if (isset($horariosUnicos[$clave])) {

                // IMPORTANTE: si ya iniciamos transacción, debemos revertirla
                $conexion->rollBack();

                // Redireccionar a index.php
                header("Location: index.php?tipo=error&mensaje=" . urlencode("No se permiten horarios duplicados"));
                exit;
            }

            // Registrar la combinación como ya utilizada
            $horariosUnicos[$clave] = true;
        }

        // Validar que no existan horarios solapados
        for ($i = 0; $i < count($horarios); $i++) {

            for ($j = $i + 1; $j < count($horarios); $j++) {

                // Solo comparar horarios del mismo dia
                if ($horarios[$i]['dia_semana'] == $horarios[$j]['dia_semana']) {

                    $inicio1 = DateTime::createFromFormat('H:i', $horarios[$i]['hora_inicio']);
                    $fin1 = DateTime::createFromFormat('H:i', $horarios[$i]['hora_fin']);

                    $inicio2 = DateTime::createFromFormat('H:i', $horarios[$j]['hora_inicio']);
                    $fin2 = DateTime::createFromFormat('H:i', $horarios[$j]['hora_fin']);

                    // Detectar si los intervalos se cruzan 
                    if ($inicio1 < $fin2 && $inicio2 < $fin1) {

                        // IMPORTANTE: si ya iniciamos transacción, debemos revertirla
                        $conexion->rollBack();

                        // Redireccionar a index.php
                        header("Location: index.php?tipo=error&mensaje=" . urlencode("Existen horarios solapados"));
                        exit;
                    }
                }
            }
        }

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

            // Validar que el día de la semana esté entre 1 y 7
            if (!filter_var($diaSemana, FILTER_VALIDATE_INT) || $diaSemana < 1 || $diaSemana > 7) {

                // IMPORTANTE: si ya iniciamos transacción, debemos revertirla
                $conexion->rollBack();

                // Redireccionar a index.php
                header("Location: index.php?tipo=error&mensaje=" . urlencode("Día de la semana inválido"));
                exit;
            }

            // Validar el formato de la hora de inicio (HH:MM)
            $horaInicioValida = DateTime::createFromFormat('H:i', $horaInicio);

            if (!$horaInicioValida || $horaInicioValida->format('H:i') !== $horaInicio) {

                // IMPORTANTE: si ya iniciamos transacción, debemos revertirla
                $conexion->rollBack();

                // Redireccionar a index.php
                header("Location: index.php?tipo=error&mensaje=" . urlencode("Hora de inicio inválida"));
                exit;
            }

            // Validar el formato de la hora de fin (HH:MM)
            $horaFinValida = DateTime::createFromFormat('H:i', $horaFin);

            if (!$horaFinValida || $horaFinValida->format('H:i') !== $horaFin) {

                // IMPORTANTE: si ya iniciamos transacción, debemos revertirla
                $conexion->rollBack();

                // Redireccionar a index.php
                header("Location: index.php?tipo=error&mensaje=" . urlencode("Hora de fin inválida"));
                exit;
            }

            // Validar que la hora de fin sea mayor que la hora de inicio
            if ($horaFinValida <= $horaInicioValida) {

                // IMPORTANTE: si ya iniciamos transacción, debemos revertirla
                $conexion->rollBack();

                // Redireccionar a index.php
                header("Location: index.php?tipo=error&mensaje=" . urlencode("La hora de fin debe ser mayor que la hora de inicio"));
                exit;
            }

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
