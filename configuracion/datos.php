<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


$stmtPais = $conexion->query("SELECT id_pais, nombre FROM paises ORDER BY nombre");
$paises = $stmtPais->fetchAll(PDO::FETCH_ASSOC);

$stmtDepartamentos = $conexion->query("SELECT codigo, id_departamento, nombre FROM departamentos ORDER BY nombre");
$departamentos = $stmtDepartamentos->fetchAll(PDO::FETCH_ASSOC);

$stmtCiudades = $conexion->query("SELECT id_ciudad, nombre , codigo_dane FROM ciudades ORDER BY nombre");
$ciudades = $stmtCiudades->fetchAll(PDO::FETCH_ASSOC);

$tipoCopropiedad = $conexion->query("SELECT id, nombre FROM tipos_copropiedad ORDER BY nombre");
$tiposCopropiedad = $tipoCopropiedad->fetchAll(PDO::FETCH_ASSOC);

// ===========================================
// CARGAR DATOS DE LA UNIDAD
// ===========================================

$stmtUnidad = $conexion->query("SELECT * FROM datos_unidad WHERE es_actual = 1 AND activo = 1
    ORDER BY id DESC LIMIT 1");

$unidad = $stmtUnidad->fetch(PDO::FETCH_ASSOC);

// Si existe información, el formulario inicia bloqueado
$bloqueado = ($unidad !== false);

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
            <form action="<?= BASE_URL ?>actions/guardar_datos.php" method="POST" enctype="multipart/form-data">

            <input type="hidden" name="id" value="<?= htmlspecialchars($unidad['id'] ?? '') ?>">

                <h2 align="center">Bienvenido, Administrador</h2>
                <br>
                <p>Antes de comenzar a utilizar el sistema, es necesario que completes la configuración inicial de la copropiedad. Por favor, proporciona la información requerida en los campos a continuación.</p>
                <p>Configura los datos básicos de la copropiedad para activar el sistema.</p>
                
                <br>
                <h3>Datos de la copropiedad</h3>
                
                <div class="bloque filtros">    
                    <div class="card">
                        <span class="step active">Nombre del Conjunto Residncial</span>
                        <input type="text" id="nombre_unidad" name="nombre_unidad" placeholder="Ej. Urbanización Las Palmas" required
                            value="<?= htmlspecialchars($unidad['nombre'] ?? '') ?>" <?= $bloqueado ? 'readonly' : '' ?> required>
                    </div>
                    <div class="card">
                        <span for="id_unidad">Identificación de la Unidad - NIT:</span>
                        <input type="text" id="nit_unidad" name="nit_unidad" placeholder="Ingrese el NIT de la unidad" required
                            value="<?= htmlspecialchars($unidad['nit'] ?? '') ?>" <?= $bloqueado ? 'readonly' : '' ?> required>
                    </div>
                    <div class="card">
                        <span class="step active">Representante Legal</span>
                        <input type="text" id="representante_legal" name="representante_legal" placeholder="Nombre del representante legal" required
                            value="<?= htmlspecialchars($unidad['representante_legal'] ?? '') ?>" <?= $bloqueado ? 'readonly' : '' ?> required>
                    </div>
                    
                    <div class="card">
                        <span class="step active">Tipo de copropiedad</span>

                        <select name="tipo_copropiedad" class="form-control" required
                            <?= $bloqueado ? 'disabled' : '' ?>>
                            <option value="">Seleccione un tipo</option>

                            <?php foreach ($tiposCopropiedad as $propiedad): ?>
                                <option
                                    value="<?= $propiedad['id'] ?>"
                                    <?= (
                                        isset($unidad['id_tipo_copropiedad']) &&
                                        $unidad['id_tipo_copropiedad'] == $propiedad['id']
                                    ) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($propiedad['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="card">
                        <span class="step active">Cantidad total de unidades</span>

                        <input type="number" id="cantidad_unidades" name="cantidad_unidades" min="1"
                            placeholder="Cantidad total de unidades"
                            value="<?= htmlspecialchars($unidad['cantidad_unidades'] ?? '') ?>"
                            required>
                    </div>           
                    
                </div>

                <h3>Ubicación</h3>
                <div class="bloque filtros">    
                    <div class="card">
                        <h4>País</h4>
                        <select name="id_pais" class="form-control" <?= $bloqueado ? 'disabled' : '' ?>>
                            <option value="">
                                Seleccione un país
                            </option>

                            <?php foreach ($paises as $pais): ?>
                                <option
                                    value="<?= $pais['id_pais'] ?>"
                                    <?= (($unidad['id_pais'] ?? '') == $pais['id_pais']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pais['id_pais']) ?>
                                    -
                                    <?= htmlspecialchars($pais['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="card">
                        <h4>Departamento</h4>
                        <select name="id_departamento" class="form-control" <?= $bloqueado ? 'disabled' : '' ?>>

                            <option value="">
                                Seleccione un departamento
                            </option>
                            <?php foreach ($departamentos as $departamento): ?>
                                <option
                                    value="<?= $departamento['id_departamento'] ?>"
                                    <?= (($unidad['id_departamento'] ?? '') == $departamento['id_departamento']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($departamento['codigo'] ?? '') ?>
                                    -
                                    <?= htmlspecialchars($departamento['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>


                    <div class="card">
                        <h4>Ciudad</h4>
                        <select
                            name="id_ciudad" class="form-control" <?= $bloqueado ? 'disabled' : '' ?>>
                            <option value="">
                                Seleccione una ciudad
                            </option>
                            <?php foreach ($ciudades as $ciudad): ?>
                                <option
                                    value="<?= $ciudad['id_ciudad'] ?>"
                                    <?= (($unidad['id_ciudad'] ?? '') == $ciudad['id_ciudad']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ciudad['codigo_dane'] ?? '') ?>
                                    -
                                    <?= htmlspecialchars($ciudad['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="card">
                        <span for="direccion">Dirección:</span>
                        <input type="text" id="direccion" name="direccion" placeholder="Ej. Vía Las Palmas Km 4" value="<?= htmlspecialchars($unidad['direccion'] ?? '') ?>"
                            <?= $bloqueado ? 'readonly' : '' ?> required>
                    </div>
                    <div class="card">
                        <span for="sector">Sector:</span>
                        <input type="text" id="sector" name="sector" placeholder="Comuna - Barrio - Zona"
                            value="<?= htmlspecialchars($unidad['sector'] ?? '') ?>"
                            <?= $bloqueado ? 'readonly' : '' ?>>
                    </div>
                    
                </div>



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

                        <input type="file" id="manual" name="manual" accept="application/pdf"
                            <?= $bloqueado ? 'disabled' : '' ?>>

                        <?php if (!empty($unidad['manual'])): ?>
                            <br>
                            <a href="../assets/documentos/<?= htmlspecialchars($unidad['manual']) ?>"
                                target="_blank" class="btn-secondary">
                                Ver manual
                            </a>
                        <?php endif; ?>

                    </div>

                    <!-- Logo -->
                    <div class="form-card">

                        <label for="logo">
                            Logo de la unidad
                        </label>

                        <input type="file" id="logo" name="logo" accept="image/*"
                            <?= $bloqueado ? 'disabled' : '' ?>>

                        <?php if (!empty($unidad['logo'])): ?>

                            <img
                                src="<?= BASE_URL ?>/assets/logos/<?= htmlspecialchars($unidad['logo']) ?>"
                                alt="Logo de la unidad" id="logoPreview" class="preview-logo">
                        <?php else: ?>
                            <img src="<?= BASE_URL ?>/assets/img/user.png"
                                alt="Logo por defecto" id="logoPreview" class="preview-logo">
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

                        <button type="button" id="btnCancelarEdicion" class="btn-limpiar" style="display:none;">
                            Cancelar
                        </button>

                    <?php endif; ?>

                    <button
                        type="button"
                        class="btn-filtrar btn-derecha"
                        onclick="window.location.href='../configuracion/basico.php'">
                        Configuración de áreas
                    </button>

                </div>
            </form>
        </main>


    </div>
    
</body>

</html>