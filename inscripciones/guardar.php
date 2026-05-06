<?php
// inscripciones/guardar.php
session_start();
require_once "../config/permisos.php";
verificar_sesion(); // cualquier rol puede guardar inscripciones

require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $estudiante_id     = isset($_POST["estudiante_id"])     ? $_POST["estudiante_id"]     : "";
    $curso_id          = isset($_POST["curso_id"])          ? $_POST["curso_id"]          : "";
    $fecha_inscripcion = isset($_POST["fecha_inscripcion"]) ? $_POST["fecha_inscripcion"] : date("Y-m-d");

    if (!empty($estudiante_id) && !empty($curso_id)) {

        // Verificamos que no esté ya inscrito en ese curso
        $sql_check = "SELECT id FROM inscripciones WHERE estudiante_id = :est AND curso_id = :cur";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->execute([":est" => $estudiante_id, ":cur" => $curso_id]);

        if ($stmt_check->fetch()) {
            echo "Este estudiante ya está inscrito en ese curso. <a href='listar.php'>Volver</a>";
            exit;
        }

        $sql = "INSERT INTO inscripciones (estudiante_id, curso_id, fecha_inscripcion) 
                VALUES (:estudiante_id, :curso_id, :fecha_inscripcion)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ":estudiante_id"     => $estudiante_id,
            ":curso_id"          => $curso_id,
            ":fecha_inscripcion" => $fecha_inscripcion
        ]);

        header("Location: listar.php");
        exit;

    } else {
        echo "Todos los campos son obligatorios. <a href='crear.php'>Volver</a>";
    }

} else {
    echo "Acceso no permitido";
}
?>