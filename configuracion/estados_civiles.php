<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";

?>


<!DOCTYPE html>
<html lang="es">

<head>
    <?php include ROOT_PATH . "/includes/head.php"; ?>
</head>

<body>
    <?php include ROOT_PATH . "/includes/header.php"; ?>
    <?php require_once  ROOT_PATH . "/includes/mensajes.php"; ?>

    <div class="contenedor">
        <?php include ROOT_PATH . "/includes/sidebar.php"; ?>
        <main class="contenido">


            $stmt = $conexion->query("
                SELECT 
                    id_estado_civil,
                    nombre,
                    estado
                FROM estados_civiles
                ORDER BY nombre
            ");

            $estados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            ?>


            <div class="contenido">
                <div class="titulo-pagina">
                    <h1>
                        Estados Civiles
                    </h1>

                    <button 
                        class="btn-primary"
                        id="btnNuevoEstadoCivil">
                        + Nuevo
                    </button>

                </div>

                <table class="tabla">
                    <thead>
                        <tr>
                            <th>
                                Nombre
                            </th>

                            <th>
                                Estado
                            </th>

                            <th>
                                Acciones
                            </th>
                        </tr>

                    </thead>


                    <tbody>
                    <?php foreach($estados as $estado): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($estado['nombre']) ?>
                            </td>

                            <td>

                                <?php if($estado['estado'] == 1): ?>

                                    <span class="activo">
                                        Activo
                                    </span>

                                <?php else: ?>

                                    <span class="inactivo">
                                        Inactivo
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <button 
                                    class="btn-secondary btnEditarEstado"
                                    data-id="<?= $estado['id_estado_civil'] ?>">
                                    ✏ Editar
                                </button>

                            </td>

                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                </table>


            </div>

        </main>
    </div>

        <!-- MODAL ESTADO CIVIL -->

        <div id="modalEstadoCivil" class="modal">
            <div class="modal-contenido">
                <span class="cerrar-modal" id="cerrarModalEstadoCivil">
                    &times;
                </span>

                <h2>
                    Nuevo Estado Civil
                </h2>

                <form 
                    action="../actions/guardar_estado_civil.php" method="POST">

                    <div class="form-group">
                        <label>
                            Nombre
                        </label>

                        <input  type="text" name="nombre" required>
                    </div>


                    <div class="form-group">
                        <label>
                            Estado
                        </label>

                        <select name="estado">
                            <option value="1">
                                Activo
                            </option>

                            <option value="0">
                                Inactivo
                            </option>
                        </select>
                    </div>


                    <div class="acciones-modal">
                        <button 
                            type="submit"
                            class="btn-primary">
                            Guardar
                        </button>


                        <button 
                            type="button"
                            class="btn-secondary"
                            id="cancelarEstadoCivil">
                            Cancelar
                        </button>

                    </div>


                </form>


            </div>

        </div>
    