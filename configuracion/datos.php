<?php

require_once "../config/conexion.php";

$stmtPais = $conexion->query("SELECT id_pais AS id, nombre FROM paises ORDER BY nombre");
$paises = $stmtPais->fetchAll(PDO::FETCH_ASSOC);

$stmtDepartamentos = $conexion->query("SELECT codigo, id_departamento AS id, nombre FROM departamentos ORDER BY nombre");
$departamentos = $stmtDepartamentos->fetchAll(PDO::FETCH_ASSOC);

$stmtCiudades = $conexion->query("SELECT id_ciudad AS id, nombre , codigo_dane FROM ciudades ORDER BY nombre");
$ciudades = $stmtCiudades->fetchAll(PDO::FETCH_ASSOC);


// ===========================================
// CARGAR DATOS DE LA UNIDAD
// ===========================================

$stmtUnidad = $conexion->query("SELECT * FROM datos_unidad LIMIT 1");
$unidad = $stmtUnidad->fetch(PDO::FETCH_ASSOC);

// Si existe información, el formulario inicia bloqueado
$bloqueado = ($unidad !== false);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>CONFIGURACION</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/calendar.js" defer></script>
    <script src="../assets/js/modal_popup.js" defer></script>
    <script src="../assets/js/editar_datos.js" defer></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<!-- ==========================================
        MODAL DE MENSAJES DEL SISTEMA
        ========================================== -->

<div id="modalMensaje" class="modal">
    <div class="modal-contenido modal-mensaje">
        <h2 id="tituloMensaje"></h2>
        <br>
        <p id="textoMensaje"></p>
        <br><br>
        <div class="acciones-modal">
            <button
                type="button"
                id="btnCerrarMensaje"
                class="btn-filtrar">
                Aceptar
            </button>
        </div>
    </div>
</div>

<body>

    <?php include "../includes/sidebar.php"; ?>
    <?php include "../includes/mensajes.php"; ?>

    <main class="contenido">
        <form action="../actions/guardar_datos.php" method="POST" enctype="multipart/form-data">

            <h2 align="center">Bienvenido, Administrador</h2>
            <br>
            <p>Antes de comenzar a utilizar el sistema, es necesario que completes la configuración inicial de la copropiedad. Por favor, proporciona la información requerida en los campos a continuación.</p>
            <p>Configura los datos básicos de la copropiedad para activar el sistema.</p>
            <br>
            <br>
            <h3>Datos de la copropiedad</h3>
            <br>
            <div class="bloque filtros">
                <div class="form-group label">
                    <span class="step active">Nombre del Conjunto Residncial</span>
                    <input type="text" id="nombre_unidad" name="nombre_unidad" placeholder="Ej. Urbanización Las Palmas" required
                        value="<?= htmlspecialchars($unidad['nombre'] ?? '') ?>" <?= $bloqueado ? 'readonly' : '' ?> required>
                </div>
                <div class="form-group label">
                    <span for="id_unidad">Identificación de la Unidad - NIT:</span>
                    <input type="text" id="nit_unidad" name="nit_unidad" placeholder="Ingrese el NIT de la unidad" required
                        value="<?= htmlspecialchars($unidad['nit'] ?? '') ?>" <?= $bloqueado ? 'readonly' : '' ?> required>
                </div>
                <div class="form-group label">
                    <span class="step active">Representante Legal</span>
                    <input type="text" id="representante_legal" name="representante_legal" placeholder="Nombre del representante legal" required
                        value="<?= htmlspecialchars($unidad['representante_legal'] ?? '') ?>" <?= $bloqueado ? 'readonly' : '' ?> required>
                </div>

            </div>

            <!--h3>Ubicación de la copropiedad</h3>
            <div class="bloque filtros">
                <div class="form-group label">
                    <span class="step active">País</span>
                    <select name="id_pais" class="form-control">
                        <option value="">Seleccione un país</option>
                        <?php foreach ($paises as $pais): ?>
                            <option value="<?= $pais['id'] ?>">
                                <?= htmlspecialchars($pais['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="button" class="btn-secondary" id="btnNuevoPais">
                        + Nuevo país
                    </button>
                </div>

                <div class="form-group label">
                    <span class="step active">Departamento</span>
                    <select name="id_departamento" class="form-control">
                        <option value="">Seleccione un departamento</option>
                        <?php foreach ($departamentos as $departamento): ?>
                            <option value="<?= $departamento['id'] ?>">
                                <?= htmlspecialchars($departamento['codigo']) ?> - <?= htmlspecialchars($departamento['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="button" class="btn-secondary" id="btnNuevoDepartamento">
                        + Nuevo departamento
                    </button>
                </div>

                <div class="form-group label">
                    <span class="step active">Ciudad</span>
                    <select name="id_ciudad" class="form-control">
                        <option value="">Seleccione una ciudad</option>
                        <?php foreach ($ciudades as $c): ?>
                            <option value="<?= $c['id'] ?>">
                                <?= htmlspecialchars($c['codigo_dane']) ?> - <?= htmlspecialchars($c['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="button" class="btn-secondary" id="btnNuevaCiudad">
                        + Nueva ciudad
                    </button>
                </div>

            </div-->


            <h3>Datos de contacto</h3>
            <div class="bloque filtros">
                <div class="card">
                    <span for="correo_propiedad">Correo Electrónico:</span>
                    <input type="email" id="correo_propiedad" name="correo_propiedad" placeholder="ingrese correo de la unidad"
                        value="<?= htmlspecialchars($unidad['correo'] ?? '') ?>"
                        <?= $bloqueado ? 'readonly' : '' ?>>
                </div>

                <div class="card">
                    <span for="telefono_propiedad">Teléfono de Contacto:</span>
                    <input type="tel" id="telefono_propiedad" name="telefono_propiedad" placeholder="Ingrese telefono de contacto"
                        value="<?= htmlspecialchars($unidad['telefono'] ?? '') ?>"
                        <?= $bloqueado ? 'readonly' : '' ?>>
                </div>

            </div>
            <h3>Documentos</h3>

            <div class="bloque filtros">

                <!-- Reglamento -->
                <div class="form-card">
                    <label for="reglamento">
                        Reglamento de propiedad horizontal (PDF)
                    </label>

                    <input type="file" id="reglamento" name="reglamento" accept="application/pdf"
                        <?= $bloqueado ? 'disabled' : '' ?>>

                    <?php if (!empty($unidad['reglamento'])): ?>
                        <br>
                        <a href="../assets/documentos/<?= htmlspecialchars($unidad['reglamento']) ?>"
                            target="_blank"
                            class="btn-secondary">
                            Ver reglamento
                        </a>
                    <?php endif; ?>

                </div>

                <!-- Manual -->
                <div class="form-card">

                    <label for="manual">
                        Manual de convivencia (PDF)
                    </label>

                    <input
                        type="file"
                        id="manual"
                        name="manual"
                        accept="application/pdf"
                        <?= $bloqueado ? 'disabled' : '' ?>>

                    <?php if (!empty($unidad['manual'])): ?>
                        <br>
                        <a href="../assets/documentos/<?= htmlspecialchars($unidad['manual']) ?>"
                            target="_blank"
                            class="btn-secondary">
                            Ver manual
                        </a>
                    <?php endif; ?>

                </div>

                <!-- Logo -->
                <div class="logo-group">

                    <label for="logo">
                        Logo de la unidad
                    </label>

                    <input
                        type="file"
                        id="logo"
                        name="logo"
                        accept="image/*"
                        <?= $bloqueado ? 'disabled' : '' ?>>

                    <?php if (!empty($unidad['logo'])): ?>

                        <img
                            src="../assets/logos/<?= htmlspecialchars($unidad['logo']) ?>"
                            alt="Logo de la unidad"
                            id="logoPreview"
                            class="preview-logo">

                    <?php else: ?>

                        <img
                            src="../assets/img/user.png"
                            alt="Logo por defecto"
                            id="logoPreview"
                            class="preview-logo">

                    <?php endif; ?>

                </div>

            </div>

            <div class="form-actions">
                <?php if (!$bloqueado): ?>
                    <button type="reset" class="btn-limpiar">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-filtrar">
                        Guardar
                    </button>

                <?php else: ?>

                    <button type="button" id="btnEditar" class="btn-filtrar">
                        Editar
                    </button>

                    <button type="submit" id="btnGuardar" class="btn-filtrar" style="display:none;">
                        Guardar
                    </button>

                    <button type="button"  id="btnCancelarEdicion" class="btn-limpiar" style="display:none;">
                        Cancelar
                    </button>

                <?php endif; ?>

                <button
                    type="button"
                    class="btn-filtrar btn-derecha"
                    onclick="window.location.href='../configuracion/basico.php'">

                    Configuración de zonas

                </button>

            </div>
        </form>
    </main>


    <!--div id="modalPais" class="modal">
            <div class="modal-contenido">
                <span id="cerrarPais" class="cerrar">&times;</span>

                <h2>Nuevo País</h2>
                <br><br>
                <form action="../actions/guardar_pais.php" method="POST">
                    <label>Nombre del país</label>
                    <br>
                    <input
                        type="text" id="nombrePais" name="nombreP" required maxlength="100">
                    <br><br>
                    <label>Codigo del país</label>
                    <input
                        type="text" id="codigoPais" name="codigoP" maxlength="10"> 
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
                    <label>Código del departamento</label>
                    <input
                        type="text" id="codigoDepartamento" name="codigo" maxlength="10">
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
                    <label>Código de la ciudad</label>
                    <input
                        type="text" id="codigoCiudad" name="codigo" maxlength="10">

                    <button type="reset" class="btn-limpiar" id="cancelarCiudad">Cancelar</button>
                    <button type="submit" class="btn-filtrar">Guardar</button>

                </form>

            </div>
        </div-->
</body>

</html>