<?php

require_once "../config/conexion.php";

$id = $_POST['id'];

$sql = "DELETE FROM zona_comun WHERE id = :id";

$stmt = $conexion->prepare($sql);

$stmt->execute(['id' => $id]);

header("Location: index.php?mensaje=eliminado");
exit;

?>