<?php

require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {

    header("Location: ../configuracion/basico.php");
    exit;
}

// =========================
// RECIBIR DATOS
// =========================

$idTipo = intval($_POST["id_tipo_vivienda"]);
$nombreGrupo = trim($_POST["nombre_grupo"]);
$cantidad = intval($_POST["cantidad_unidades"]);
$area = ($_POST["area_total"] != "") ? $_POST["area_total"] : 0;
$coeficiente = ($_POST["coeficiente_total"] != "") ? $_POST["coeficiente_total"] : 0;
$observaciones = trim($_POST["observaciones"]);

// =========================
// VALIDACIONES
// =========================

if ($idTipo <= 0) {

    header("Location: ../configuracion/basico.php?mensaje=error");
    exit;
}

if ($nombreGrupo == "") {

    header("Location: ../configuracion/basico.php?mensaje=error");
    exit;
}

try {

    // =========================
    // ¿YA EXISTE EL GRUPO?
    // =========================

    $sql = "SELECT COUNT(*)
            FROM detalle_tipos_unidad
            WHERE nombre_grupo = :nombre";

    $stmt = $conexion->prepare($sql);

    $stmt->bindParam(":nombre", $nombreGrupo);

    $stmt->execute();

    if ($stmt->fetchColumn() > 0) {

        header("Location: ../configuracion/basico.php?mensaje=existe");
        exit;
    }

    // =========================
    // INSERTAR
    // =========================

    $sql = "INSERT INTO detalle_tipos_unidad
            (
                id_tipo_vivienda,
                nombre_grupo,
                cantidad_unidades,
                area_total,
                coeficiente_total,
                observaciones,
                activo
            )
            VALUES
            (
                :tipo,
                :nombre,
                :cantidad,
                :area,
                :coeficiente,
                :observaciones,
                1
            )";

    $stmt = $conexion->prepare($sql);

    $stmt->bindParam(":tipo", $idTipo);
    $stmt->bindParam(":nombre", $nombreGrupo);
    $stmt->bindParam(":cantidad", $cantidad);
    $stmt->bindParam(":area", $area);
    $stmt->bindParam(":coeficiente", $coeficiente);
    $stmt->bindParam(":observaciones", $observaciones);

    $stmt->execute();

    header("Location: ../configuracion/basico.php?mensaje=ok");
    exit;

} catch (PDOException $e) {

    die($e->getMessage());

}