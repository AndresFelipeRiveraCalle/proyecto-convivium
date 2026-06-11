<?php
require_once "../config/conexion.php";

// isset() verifica si la variable existe 
// trim() eliminamos espacio en blanco o otros caracteres
$bucarNombreActo = isset($_GET["buscar_nombre_acto"]) ? trim($_GET["buscar_nombre_acto"]) : "";

$torre = isset($_GET["selecion_torre"]) ? $_GET["selecion_torre"] : "";













?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Residentes e Inmuebles</title>
</head>
<body>
    <h1>Gestión de Residentes e Inmuebles</h1>
    <br><br>

<form method="get">
<input type="text" name="" placeholder="Buscar por nombre o apto">
                <!-- llanar logica de php para buscar por nombre -->
        <!--   -->
        <!--   -->
    <select name="" id="">
        <option value="">Torre: Todas</option>
    </select>
        <!--   -->
    <select name="" id="">
        <option value="">Estado: Todos</option>
    </select>


<button type="submit">Filtrar</button>
</form>

</body>
</html>