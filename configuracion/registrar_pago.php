<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// CONSULTAR UNIDADES
// ==========================================================

$sqlUnidades = "
    SELECT
        id_unidad,
        codigo,
        nombre
    FROM unidades
    WHERE activo = 1
    ORDER BY codigo ASC
";

$stmtUnidades = $conexion->prepare($sqlUnidades);
$stmtUnidades->execute();

$unidades = $stmtUnidades->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// DATOS DEL FORMULARIO
// ==========================================================

$fechaPago = date('Y-m-d');

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <?php
    include ROOT_PATH . "/includes/head.php";
    ?>

</head>


<body>


<?php include ROOT_PATH . "/includes/header.php";?>

<?php require_once ROOT_PATH . "/includes/mensajes.php";?>


<div class="contenedor">

    <!-- ======================================================
         SIDEBAR
    ======================================================= -->
    <?php include ROOT_PATH . "/includes/sidebar.php";?>

    <!-- ======================================================
         CONTENIDO
    ======================================================= -->

    <main class="contenido">


        <div class="cartera-container">


            <!-- ==================================================
                 ENCABEZADO
            =================================================== -->

            <div class="cartera-header">

                <div>

                    <h1>
                        Registrar pago
                    </h1>

                    <p>
                        Registre un pago recibido de una unidad.
                        El sistema aplicará posteriormente el valor
                        según las reglas de cartera.
                    </p>

                </div>


                <div>

                    <a
                        href="<?= BASE_URL ?>configuracion/cartera.php"
                        class="btn-secondary"
                    >
                        ← Volver a cartera
                    </a>

                </div>

            </div>


            <!-- ==================================================
                 FORMULARIO
            =================================================== -->

            <div class="tabla-card">


                <form
                    action="<?= BASE_URL ?>actions/guardar_pago.php"
                    method="POST"
                    id="formRegistrarPago"
                >


                    <!-- ==================================================
                         DATOS PRINCIPALES
                    =================================================== -->

                    <h2>
                        Información del pago
                    </h2>


                    <div class="filtros-grid">


                        <!-- UNIDAD -->

                        <div class="campo">

                            <label for="id_unidad">
                                Unidad *
                            </label>


                            <select
                                name="id_unidad"
                                id="id_unidad"
                                required
                            >

                                <option value="">
                                    Seleccione una unidad
                                </option>


                                <?php foreach (
                                    $unidades
                                    as $unidad
                                ): ?>


                                    <option
                                        value="<?= (int)$unidad['id_unidad'] ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $unidad['codigo']
                                        ) ?>


                                        <?php if (
                                            !empty(
                                                $unidad['nombre']
                                            )
                                        ): ?>

                                            -
                                            <?= htmlspecialchars(
                                                $unidad['nombre']
                                            ) ?>

                                        <?php endif; ?>

                                    </option>


                                <?php endforeach; ?>


                            </select>

                        </div>


                        <!-- FECHA -->

                        <div class="campo">

                            <label for="fecha_pago">
                                Fecha del pago *
                            </label>


                            <input
                                type="date"
                                name="fecha_pago"
                                id="fecha_pago"
                                value="<?= htmlspecialchars(
                                    $fechaPago
                                ) ?>"
                                required
                            >

                        </div>


                        <!-- VALOR -->

                        <div class="campo">

                            <label for="valor">
                                Valor recibido *
                            </label>


                            <input
                                type="number"
                                name="valor"
                                id="valor"
                                min="0.01"
                                step="0.01"
                                placeholder="0.00"
                                required
                            >

                        </div>


                        <!-- MEDIO DE PAGO -->

                        <div class="campo">

                            <label for="medio_pago">
                                Medio de pago *
                            </label>


                            <select
                                name="medio_pago"
                                id="medio_pago"
                                required
                            >

                                <option value="">
                                    Seleccione
                                </option>


                                <option value="TRANSFERENCIA">
                                    Transferencia
                                </option>


                                <option value="CONSIGNACION">
                                    Consignación
                                </option>


                                <option value="PSE">
                                    PSE
                                </option>


                                <option value="TARJETA">
                                    Tarjeta
                                </option>


                                <option value="OTRO">
                                    Otro
                                </option>


                            </select>

                        </div>


                        <!-- REFERENCIA -->

                        <div class="campo">

                            <label for="referencia">
                                Referencia
                            </label>


                            <input
                                type="text"
                                name="referencia"
                                id="referencia"
                                maxlength="100"
                                placeholder="Número de referencia"
                            >

                        </div>


                        <!-- REFERENCIA EXTERNA -->

                        <div class="campo">

                            <label for="referencia_externa">
                                Referencia externa
                            </label>


                            <input
                                type="text"
                                name="referencia_externa"
                                id="referencia_externa"
                                maxlength="150"
                                placeholder="Referencia externa"
                            >

                        </div>


                        <!-- ID EXTERNO -->

                        <div class="campo">

                            <label for="id_externo">
                                ID externo
                            </label>


                            <input
                                type="text"
                                name="id_externo"
                                id="id_externo"
                                maxlength="150"
                                placeholder="ID de transacción"
                            >

                        </div>


                        <!-- ORIGEN -->

                        <div class="campo">

                            <label for="origen_pago">
                                Origen del pago *
                            </label>


                            <select
                                name="origen_pago"
                                id="origen_pago"
                                required
                            >

                                <option value="MANUAL">
                                    Consignación
                                </option>


                                <option value="BANCO">
                                    Banco
                                </option>


                                <option value="PASARELA">
                                    Pasarela
                                </option>


                            </select>

                        </div>


                    </div>


                    <!-- ==================================================
                         OBSERVACIONES
                    =================================================== -->

                    <div class="campo">

                        <label for="observaciones">
                            Observaciones
                        </label>


                        <textarea
                            name="observaciones"
                            id="observaciones"
                            rows="4"
                            maxlength="255"
                            placeholder="Observaciones del pago..."
                        ></textarea>

                    </div>


                    <!-- ==================================================
                         INFORMACIÓN
                    =================================================== -->
                    <br>
                    <div class="mensaje-info">

                        <strong>
                            Aplicación automática del pago
                        </strong>

                        <p>

                            Al registrar el pago, el sistema
                            determinará automáticamente cómo
                            distribuir el valor recibido.

                        </p>

                        <p>

                            Los intereses tendrán prioridad.
                            Posteriormente se aplicarán los valores
                            según la configuración de prioridades
                            de la unidad y la antigüedad de las
                            obligaciones.

                        </p>

                        <p>

                            Si el pago supera el total pendiente,
                            el excedente se registrará como
                            <strong>saldo a favor</strong>.

                        </p>

                    </div>


                    <!-- ==================================================
                         BOTONES
                    =================================================== -->

                    <div class="botones-filtro">


                        <a
                            href="<?= BASE_URL ?>configuracion/cartera.php"
                            class="btn-secondary"
                        >
                            Cancelar
                        </a>


                        <button
                            type="submit"
                            class="btn-primary"
                        >
                            Registrar pago
                        </button>


                    </div>


                </form>


            </div>


        </div>


    </main>


</div>


</body>

</html>