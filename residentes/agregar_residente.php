<?php
require_once "../config/conexion.php";

// apartamento preselecionado desde listar.php

$idUnidadPreseleccionada = isset($_GET["id_unidad"]) ? $_GET["id_unidad"] : "";

// variable de busqueda 
$terminoBusqueda = isset($_GET["buscar_apto"]) ? trim($_GET["buscar_apto"]) : "";
$resultadosBusqueda = [];
$unidadSeleccionada = null;

// si busqueda se ejecuta la busqueda 

if ($terminoBusqueda !== "") {
    $sqlBusqueda = "SELECT id, torre, numero, estado 
                    FROM unidad 
                    WHERE numero LIKE :termino 
                    OR torre LIKE :termino
                    ORDER BY torre, numero
                    LIMIT 20";
    
    $consultaBusqueda = $conexion->prepare($sqlBusqueda);
    $consultaBusqueda->execute([":termino" => "%" . $terminoBusqueda . "%"]);
    $resultadosBusqueda = $consultaBusqueda->fetchAll();
}

// si hay un apartamento selecionado por get 

if (isset($_GET["seleccionar_unidad"]) && $_GET["seleccionar_unidad"] !== "") {
    $idUnidadSeleccionada = $_GET["seleccionar_unidad"];
    
    $sqlUnidad = "SELECT id, torre, numero, estado FROM unidad WHERE id = :id";
    $consultaUnidad = $conexion->prepare($sqlUnidad);
    $consultaUnidad->execute([":id" => $idUnidadSeleccionada]);
    $unidadSeleccionada = $consultaUnidad->fetch();
}

// lista de todos los apartamento por si no hay busqueda

$consultaUnidades = $conexion->query("SELECT id, torre, numero, estado FROM unidad ORDER BY torre, numero");
$listaUnidades = $consultaUnidades->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar residente</title>
</head>
<body>
    <h1>Agregar nuevo residente</h1>

    <form method="get" action="agregar_residente.php">
        <label>Buscar apartamento (escribe torre o numero apto):</label><br>
        <input type="text" name="buscar_apto" 
                placeholder="EJ. acto 101" 
                value="<?= htmlspecialchars($terminoBusqueda) ?>">
        <button type="submit">Buscar</button>
        
        <?php if ($terminoBusqueda !== ""): ?>
            <a href="agregar_residente.php">Limpiar busqueda</a>
        <?php endif; ?>
    </form>

    <br>

    <!-- mostramo los resultado de la busqueda de apartamentos-->
    
    <?php if ($terminoBusqueda !== ""): ?>
        <h3>Resultados encontrados:</h3>
        
        <?php if (count($resultadosBusqueda) > 0): ?>
            <?php foreach ($resultadosBusqueda as $apto): ?>
                <div>
                    <a href="agregar_residente.php?seleccionar_unidad=<?= $apto["id"] ?>&buscar_apto=<?= urlencode($terminoBusqueda) ?>">
                        Torre <?= htmlspecialchars($apto["torre"]) ?> - Apto <?= htmlspecialchars($apto["numero"]) ?>
                        (<?= $apto["estado"] === "ocupado" ? "Ocupado" : "Desocupado" ?>)
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No se encontraron apartamentos con "<strong><?= htmlspecialchars($terminoBusqueda) ?></strong>"</p>
        <?php endif; ?>
        
        <br>
    <?php endif; ?>

    <!--apartamento selecionado   -->
    
    <?php if ($unidadSeleccionada): ?>
        <p>
            <strong> Apartamento seleccionado:</strong>
            Torre <?= htmlspecialchars($unidadSeleccionada["torre"]) ?> - 
            Apto <?= htmlspecialchars($unidadSeleccionada["numero"]) ?>
            (<?= $unidadSeleccionada["estado"] === "ocupado" ? "Ocupado" : "Desocupado" ?>)
        </p>
    <?php endif; ?>

    

    <form method="post" action="guardar_residente.php">

        <label>Nombre:</label><br>
        <input type="text" name="nombre_residente" required><br><br>

        <label>Apellido:</label><br>
        <input type="text" name="apellido_residente" required><br><br>

        <label>Tipo de documento:</label><br>
        <select name="tipo_documento" required>
            <option value="cedula">Cedula</option>
            <option value="pasaporte">Pasaporte</option>
            <option value="otro">Otro</option>
        </select><br><br>

        <label>Documento:</label><br>
        <input type="text" name="documento" required><br><br>

        

        <label>Correo:</label><br>
        <input type="email" name="correo_residente" required><br><br>

        <label>Telefono:</label><br>
        <input type="text" name="telefono_residente"><br><br>

        <label>Telefono 2 (opcional):</label><br>
        <input type="text" name="telefono_2"><br><br>

        <label>Contraseña inicial:</label><br>
        <input type="password" name="contrasena_residente" required><br><br>


        <?php if ($unidadSeleccionada): ?>
            <!-- Si el usuario ya selecciono un apartamento, guardamos el ID en campo oculto -->
            <input type="hidden" name="id_unidad_seleccionada" value="<?= $unidadSeleccionada["id"] ?>">
            <p><strong>Apartamento asignado:</strong> Torre <?= htmlspecialchars($unidadSeleccionada["torre"]) ?> - Apto <?= htmlspecialchars($unidadSeleccionada["numero"]) ?></p>
        <?php else: ?>
            <!-- Si NO hay selección, mostramos el SELECT con TODOS los apartamentos -->
            <label>Apartamento:</label><br>
            <select name="id_unidad_seleccionada" required>
                <option value="">Selecciona un apartamento</option>
                <?php foreach ($listaUnidades as $unidad): ?>
                    <option value="<?= $unidad["id"] ?>"
                        <?= ($idUnidadPreseleccionada == $unidad["id"]) ? "selected" : "" ?>>
                        Torre <?= htmlspecialchars($unidad["torre"]) ?> - Apto <?= htmlspecialchars($unidad["numero"]) ?>
                        (<?= htmlspecialchars($unidad["estado"]) ?>)
                    </option>
                <?php endforeach; ?>
            </select><br><br>
        <?php endif; ?>

        <label>Tipo de residente:</label><br>
        <select name="tipo_residente" required>
            <option value="propietario">Propietario</option>
            <option value="inquilino">Inquilino (en arriendo)</option>
        </select><br><br>

        <button type="submit">Guardar residente</button>
    </form>

    <br>
    <a href="listar.php">Volver al listado</a>
</body>
</html>