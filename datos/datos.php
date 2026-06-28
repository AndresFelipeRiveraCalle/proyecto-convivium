<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>CONFIGURACION</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/css/script.js" defer></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>


<body>

<?php include "../includes/sidebar.php"; ?>

    <main class="contenido">

        <h2 align="center">Bienvenido, Administrador</h2>
        <br>
        <p>Antes de comenzar a utilizar el sistema, es necesario que completes la configuración inicial de la copropiedad. Por favor, proporciona la información requerida en los campos a continuación.</p>
        <br>
        <p>Configura los datos básicos de la copropiedad para activar el sistema.</p>
        <br>
            <div class="bloque filtros">
                <div class="card">
                <label for="ciudad">País:</label>
                <input type="text" id="pais" name="pais" placeholder="Ej. Colombia" required>
                </div>
                <div class="card">
                <label for="ciudad">Departamento:</label>
                <input type="text" id="departamento" name="departamento" placeholder="Ej. Antioquia" required>
                </div>
                <div class="card">
                <label for="ciudad">Ciudad:</label>
                <input type="text" id="ciudad" name="ciudad" placeholder="Ej. Medellín" required>
                </div>
            </div>

            <div class="bloque filtros">
                <div class="card">
                <span class="step active">Nombre del Conjunto Residncial</span>
                <input type="text" id="nombre_unidad" name="nombre_unidad" placeholder="Ej. Urbanización Las Palmas" required>
                </div>
                <div class="card">
                <span class="step">Torres/Bloques</span>
                <input type="text" id="torre_bloque" name="torre_bloque" placeholder="Ej. Torre 1, Torre 2, Bloque A" required>
                </div>
                <div class="card">
                <span class="step">Unidades de vivienda</span>
                <input type="text" id="nombre_propietario" name="nombre_propietario" placeholder="Ej. Carlos Mario Restrepo" required>
                </div>
            </div>
            


            <form action="#" method="POST">

                <div class="form-card">
                    <div class="form-card-header">
                        🏢 SECCIÓN 1: UBICACIÓN Y NOMBRE DE LA COPROPIEDAD
                    </div>
                    <div class="form-card-body">
                        <div class="form-grid">

                            <div class="form-group">
                                <label for="nombre_unidad">Nombre de la Copropiedad / Unidad:</label>
                                <input type="text" id="nombre_unidad" name="nombre_unidad" placeholder="Ej. Urbanización Las Palmas" required>
                            </div>

                            <div class="form-group">
                                <label for="ciudad">Ciudad / Municipio:</label>
                                <select id="ciudad" name="ciudad" required>
                                    <option value="">Seleccione una ciudad...</option>
                                    <option value="medellin" selected>Medellín (Antioquia)</option>
                                    <option value="envigado">Envigado (Antioquia)</option>
                                    <option value="sabaneta">Sabaneta (Antioquia)</option>
                                    <option value="bogota">Bogotá D.C.</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="direccion">Dirección o Ubicación:</label>
                                <input type="text" id="direccion" name="direccion" placeholder="Ej. Vía Las Palmas Km 4" required>
                            </div>

                            <div class="form-group">
                                <label for="sector">Zona / Sector:</label>
                                <select id="sector" name="sector" required>
                                    <option value="">Seleccione un sector...</option>
                                    <option value="las_palmas" selected>Vía Las Palmas</option>
                                    <option value="cola_zorro">Cola del Zorro</option>
                                    <option value="el_poblado">El Poblado</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="form-card">
                    <div class="form-card-header">
                        👤 SECCIÓN 2: REGISTRO INICIAL DE PROPIEDAD Y PROPIETARIO
                    </div>
                    <div class="form-card-body">
                        <div class="form-grid">

                            <div class="form-group">
                                <label for="id_unidad">Identificación de la Unidad (Apto/Casa):</label>
                                <input type="text" id="id_unidad" name="id_unidad" placeholder="Ej. Torre 2 - Apto 401" required>
                            </div>

                            <div class="form-group">
                                <label for="nombre_propietario">Nombre Completo del Propietario:</label>
                                <input type="text" id="nombre_propietario" name="nombre_propietario" placeholder="Carlos Mario Restrepo" required>
                            </div>

                            <div class="form-group">
                                <label for="correo_propietario">Correo Electrónico:</label>
                                <input type="email" id="correo_propietario" name="correo_propietario" placeholder="carlos.propietario@email.com" required>
                            </div>

                            <div class="form-group">
                                <label for="telefono_propietario">Teléfono de Contacto:</label>
                                <input type="tel" id="telefono_propietario" name="telefono_propietario" placeholder="310 555 4321" required>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar y Continuar</button>
                </div>

            </form>
        </div>
    </main>
</body>

</html>