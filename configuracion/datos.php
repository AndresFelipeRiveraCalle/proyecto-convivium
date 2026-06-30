    <?php

    require_once "../config/conexion.php";

    $stmtPais = $conexion->query("SELECT id_pais AS id, nombre FROM paises ORDER BY nombre");
    $paises = $stmtPais->fetchAll(PDO::FETCH_ASSOC);

    $stmtDepartamentos = $conexion->query("SELECT id_departamento AS id, nombre FROM departamentos ORDER BY nombre");
    $departamentos = $stmtDepartamentos->fetchAll(PDO::FETCH_ASSOC);

    $stmtCiudades = $conexion->query("SELECT id_ciudad AS id, nombre FROM ciudades ORDER BY nombre");
    $ciudades = $stmtCiudades->fetchAll(PDO::FETCH_ASSOC);



    ?>

    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <title>CONFIGURACION</title>
        <link rel="stylesheet" href="../assets/css/style.css">
        <script src="../assets/js/calendar.js" defer></script>
        <script src="../assets/js/modal_popup.js" defer></script>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>


    <body>

        <?php include "../includes/sidebar.php"; ?>

        <main class="contenido">

            <h2 align="center">Bienvenido, Administrador</h2>
            <br>
            <p>Configura los datos básicos de la copropiedad para activar el sistema.</p>
            <br>
        

            <div class="bloque filtros">
                <div class="card">
                    <span class="step active">Nombre del Conjunto Residncial</span>
                    <input type="text" id="nombre_unidad" name="nombre_unidad" placeholder="Ej. Urbanización Las Palmas" required>
                </div>
                <div class="card">
                    <span for="id_unidad">Identificación de la Unidad - NIT:</span>
                    <input type="text" id="nit_unidad" name="nit_unidad" placeholder="Ingrese el NIT de la unidad" required>
                </div>
                <div class="card">
                    <span class="step active">Representante Legal</span>
                    <input type="text" id="representante_legal" name="representante_legal" placeholder="Nombre del representante legal" required>
                </div>
            </div>

            <div class="bloque filtros">
                <div class="card">
                    <span for="direccion">Dirección o Ubicación:</span>
                    <input type="text" id="direccion" name="direccion" placeholder="Ej. Vía Las Palmas Km 4" required>
                </div>
                <div class="card">
                    <span for="sector">Sector:</span>
                    <input type="text" id="sector" name="sector" placeholder="Comuna - Barrio - Zona" required>
                </div>

                <div class="card">
                    <span class="step">Torres/Bloques</span>
                    <input type="text" id="torre_bloque" name="torre_bloque" placeholder="Ej. Torre 1, Torre 2, Bloque A" required>
                </div>
                <div class="card">
                    <span class="step">Unidades de vivienda</span>
                    <input type="text" id="unidades_vivienda" name="unidades_vivienda" placeholder="Cantidad de viviendas" required>
                </div>

            </div>

            <div class="bloque filtros">
                <div class="card">
                    <span for="correo_propietario">Correo Electrónico:</span>
                    <input type="email" id="correo_propietario" name="correo_propietario" placeholder="Correo de la copropiedad" required>
                </div>

                <div class="card">
                    <span for="telefono_propietario">Teléfono de Contacto:</span>
                    <input type="tel" id="telefono_propietario" name="telefono_propietario" placeholder="Telefono de la copropiedad" required>
                </div>
            </div>

            <div class="bloque filtros">
                <div class="card">
                    <span for="reglamento">Reglamento de propiedad horizontal:</span>
                    <input type="reglamento" id="reglamento" name="reglamento" placeholder="Cargue el reglamento" required>
                </div>

                <div class="card">
                    <span for="manual">Manual de convivencia:</span>
                    <input type="text" id="manual" name="manual" placeholder="Cargue el manual" required>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-limpiar">Cancelar</button>
                <button type="submit" class="btn-filtrar">Guardar</button>
            </div>


        </main>


        <div id="modalPais" class="modal">
            <div class="modal-contenido">
                <span id="cerrarPais" class="cerrar">&times;</span>

                <h2>Nuevo País</h2>

                <form action="../actions/guardar_pais.php" method="POST">
                    <label>Nombre del país</label>
                    <input
                        type="text" id="nombrePais" name="nombreP" required maxlength="100">
                    <br><br>
                    <button type="reset" class="btn-limpiar" id="cancelarPais">Cancelar</button>
                    <button type="submit" class="btn-filtrar">Guardar</button>
                </form>
            </div>
        </div>


        <div id="modalDepartamento" class="modal">
            <div class="modal-contenido">
                <span id="cerrarDepartamento" class="cerrar">&times;</span>

                <h2>Nuevo Departamento</h2>

                <form action="../actions/guardar_Departamento.php" method="POST">
                    <label>Nombre del departamento</label>
                    <input
                        type="text" id="nombreDepartamento" name="nombreD" required maxlength="100">
                    <br><br>
                    <button type="reset" class="btn-limpiar" id="cancelarDepartamento">Cancelar</button>
                    <button type="submit" class="btn-filtrar">Guardar</button>

                </form>
            </div>
        </div>

        <div id="modalCiudad" class="modal">
            <div class="modal-contenido">
                <span id="cerrarCiudad" class="cerrar">&times;</span>

                <h2>Nueva Ciudad</h2>

                <form action="../actions/guardar_ciudad.php" method="POST">
                    <label>Nombre de la ciudad</label>
                    <input
                        type="text" id="nombreCiudad" name="nombreC" required maxlength="100">
                    <br><br>
                    <button type="reset" class="btn-limpiar" id="cancelarCiudad">Cancelar</button>
                    <button type="submit" class="btn-filtrar">Guardar</button>

                </form>
            </div>
        </div>

    </body>

    </html>