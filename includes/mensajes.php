<?php

if (!isset($_GET['mensaje'])) {
    return;
}

switch ($_GET['mensaje']) {

    case "ok":
        $tipo = "exito";
        $texto = "El registro se guardó correctamente.";
        break;

    case "existe":
        $tipo = "error";
        $texto = "El registro ya existe.";
        break;

    case "error":
        $tipo = "error";
        $texto = "Ocurrió un error al guardar la información.";
        break;

    case "actualizado":
        $tipo = "exito";
        $texto = "El registro fue actualizado.";
        break;

    case "eliminado":
        $tipo = "exito";
        $texto = "El registro fue eliminado.";
        break;

    default:
        return;
}

echo "<div class='mensaje $tipo'>$texto</div>";