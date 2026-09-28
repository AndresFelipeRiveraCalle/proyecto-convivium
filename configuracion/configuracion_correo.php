<?php

require_once dirname(__DIR__) . "/config/config.php";
require_once ROOT_PATH . "/config/conexion.php";


// ==========================================================
// CONFIGURACIÓN ACTUAL
// Obtiene la configuración de correo activa.
// ==========================================================

$sqlConfiguracion = "
    SELECT
        id_configuracion_correo,
        nombre_configuracion,
        correo_remitente,
        nombre_remitente,
        correo_respuesta,

        smtp_host,
        smtp_puerto,
        smtp_seguridad,
        smtp_autenticacion,
        smtp_usuario,

        imap_host,
        imap_puerto,
        imap_seguridad,
        imap_usuario,
        imap_carpeta,

        activo,

        ultima_prueba_smtp,
        resultado_prueba_smtp,

        ultima_prueba_imap,
        resultado_prueba_imap

    FROM configuracion_correo

    WHERE activo = 1

    ORDER BY
        id_configuracion_correo DESC

    LIMIT 1
";


$stmtConfiguracion =
    $conexion->query(
        $sqlConfiguracion
    );


$configuracion =
    $stmtConfiguracion->fetch(
        PDO::FETCH_ASSOC
    );


// ==========================================================
// VALORES POR DEFECTO
// Prepara el formulario cuando no existe configuración.
// ==========================================================

if (!$configuracion) {

    $configuracion = [

        'id_configuracion_correo' => 0,

        'nombre_configuracion' => '',

        'correo_remitente' => '',

        'nombre_remitente' => '',

        'correo_respuesta' => '',

        'smtp_host' => '',

        'smtp_puerto' => 587,

        'smtp_seguridad' => 'TLS',

        'smtp_autenticacion' => 1,

        'smtp_usuario' => '',

        'imap_host' => '',

        'imap_puerto' => 993,

        'imap_seguridad' => 'SSL',

        'imap_usuario' => '',

        'imap_carpeta' => 'INBOX',

        'activo' => 1,

        'ultima_prueba_smtp' => null,

        'resultado_prueba_smtp' => null,

        'ultima_prueba_imap' => null,

        'resultado_prueba_imap' => null
    ];
}


// ==========================================================
// FUNCIÓN ESCAPE
// Protege los valores mostrados en pantalla.
// ==========================================================

function e($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <?php include ROOT_PATH . "/includes/head.php"; ?>

</head>


<body>


<?php include ROOT_PATH . "/includes/header.php"; ?>

<?php require_once ROOT_PATH . "/includes/mensajes.php"; ?>


<div class="contenedor">


    <?php include ROOT_PATH . "/includes/sidebar.php"; ?>


    <main class="contenido">


        <!-- ======================================================
             ENCABEZADO
        ======================================================= -->

        <div class="form-card">


            <div class="form-header">


                <div>

                    <h1>
                        Configuración de correo
                    </h1>

                    <p>
                        Configure la cuenta y los servidores utilizados
                        para el envío y recepción de correos del sistema.
                    </p>

                </div>


            </div>


            <!-- ==================================================
                 FORMULARIO
            =================================================== -->

            <form
                method="POST"
                action="<?= BASE_URL ?>actions/guardar_configuracion_correo.php"
            >


                <input
                    type="hidden"
                    name="id_configuracion_correo"
                    value="<?= (int)$configuracion['id_configuracion_correo'] ?>"
                >


                <!-- ==================================================
                     DATOS GENERALES
                =================================================== -->

                <div class="form-section">

                    <h3>
                        Datos generales
                    </h3>

                    <p>
                        Información utilizada como remitente de los
                        mensajes enviados desde Convivium.
                    </p>


                    <div class="form-grid">


                        <div class="form-group">

                            <label for="nombre_configuracion">
                                Nombre de configuración *
                            </label>

                            <input
                                type="text"
                                name="nombre_configuracion"
                                id="nombre_configuracion"
                                value="<?= e(
                                    $configuracion[
                                        'nombre_configuracion'
                                    ]
                                ) ?>"
                                maxlength="100"
                                placeholder="Correo administración"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="nombre_remitente">
                                Nombre del remitente *
                            </label>

                            <input
                                type="text"
                                name="nombre_remitente"
                                id="nombre_remitente"
                                value="<?= e(
                                    $configuracion[
                                        'nombre_remitente'
                                    ]
                                ) ?>"
                                maxlength="150"
                                placeholder="Administración"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="correo_remitente">
                                Correo remitente *
                            </label>

                            <input
                                type="email"
                                name="correo_remitente"
                                id="correo_remitente"
                                value="<?= e(
                                    $configuracion[
                                        'correo_remitente'
                                    ]
                                ) ?>"
                                maxlength="150"
                                placeholder="administracion@dominio.com"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="correo_respuesta">
                                Correo de respuesta
                            </label>

                            <input
                                type="email"
                                name="correo_respuesta"
                                id="correo_respuesta"
                                value="<?= e(
                                    $configuracion[
                                        'correo_respuesta'
                                    ]
                                ) ?>"
                                maxlength="150"
                                placeholder="administracion@dominio.com"
                            >

                            <small>
                                Dirección a la que llegarán las respuestas
                                de los destinatarios.
                            </small>

                        </div>


                    </div>

                </div>


                <br>


                <!-- ==================================================
                     SERVIDOR SMTP
                =================================================== -->

                <div class="form-section">

                    <h3>
                        Servidor de salida SMTP
                    </h3>

                    <p>
                        Configuración utilizada para enviar facturas
                        y demás comunicaciones por correo electrónico.
                    </p>


                    <div class="form-grid">


                        <div class="form-group">

                            <label for="smtp_host">
                                Servidor SMTP *
                            </label>

                            <input
                                type="text"
                                name="smtp_host"
                                id="smtp_host"
                                value="<?= e(
                                    $configuracion[
                                        'smtp_host'
                                    ]
                                ) ?>"
                                maxlength="150"
                                placeholder="smtp.dominio.com"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="smtp_puerto">
                                Puerto SMTP *
                            </label>

                            <input
                                type="number"
                                name="smtp_puerto"
                                id="smtp_puerto"
                                value="<?= (int)$configuracion['smtp_puerto'] ?>"
                                min="1"
                                max="65535"
                                required
                            >

                            <small>
                                Normalmente 587 para TLS o 465 para SSL.
                            </small>

                        </div>


                        <div class="form-group">

                            <label for="smtp_seguridad">
                                Seguridad *
                            </label>

                            <select
                                name="smtp_seguridad"
                                id="smtp_seguridad"
                                required
                            >

                                <option
                                    value="TLS"
                                    <?= $configuracion['smtp_seguridad'] === 'TLS'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    TLS
                                </option>

                                <option
                                    value="SSL"
                                    <?= $configuracion['smtp_seguridad'] === 'SSL'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    SSL
                                </option>

                                <option
                                    value="NINGUNA"
                                    <?= $configuracion['smtp_seguridad'] === 'NINGUNA'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Ninguna
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="smtp_autenticacion">
                                Autenticación
                            </label>

                            <select
                                name="smtp_autenticacion"
                                id="smtp_autenticacion"
                            >

                                <option
                                    value="1"
                                    <?= (int)$configuracion['smtp_autenticacion'] === 1
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Sí
                                </option>

                                <option
                                    value="0"
                                    <?= (int)$configuracion['smtp_autenticacion'] === 0
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    No
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="smtp_usuario">
                                Usuario SMTP
                            </label>

                            <input
                                type="text"
                                name="smtp_usuario"
                                id="smtp_usuario"
                                value="<?= e(
                                    $configuracion[
                                        'smtp_usuario'
                                    ]
                                ) ?>"
                                maxlength="150"
                                autocomplete="off"
                                placeholder="usuario@dominio.com"
                            >

                        </div>


                        <div class="form-group">

                            <label for="smtp_password">
                                Contraseña SMTP
                            </label>

                            <input
                                type="password"
                                name="smtp_password"
                                id="smtp_password"
                                autocomplete="new-password"
                                placeholder="<?= (int)$configuracion['id_configuracion_correo'] > 0
                                    ? 'Dejar vacío para conservar la actual'
                                    : 'Contraseña'
                                ?>"
                            >

                            <?php if (
                                (int)$configuracion[
                                    'id_configuracion_correo'
                                ] > 0
                            ): ?>

                                <small>
                                    Si no desea cambiarla, deje este campo vacío.
                                </small>

                            <?php endif; ?>

                        </div>


                    </div>


                    <!-- ==================================================
                         ESTADO SMTP
                    =================================================== -->

                    <?php if (
                        !empty(
                            $configuracion[
                                'ultima_prueba_smtp'
                            ]
                        )
                    ): ?>

                        <div class="form-group form-group-full">

                            <label>
                                Última prueba SMTP
                            </label>

                            <div class="bloque">

                                <strong>
                                    <?= e(
                                        $configuracion[
                                            'resultado_prueba_smtp'
                                        ]
                                        ?? ''
                                    ) ?>
                                </strong>

                                <br>

                                <small>
                                    <?= e(
                                        $configuracion[
                                            'ultima_prueba_smtp'
                                        ]
                                    ) ?>
                                </small>

                            </div>

                        </div>

                    <?php endif; ?>


                </div>


                <br>


                <!-- ==================================================
                     SERVIDOR IMAP
                =================================================== -->

                <div class="form-section">

                    <h3>
                        Servidor de entrada IMAP
                    </h3>

                    <p>
                        Configuración preparada para futuras funciones
                        de recepción, respuestas y control de rebotes.
                    </p>


                    <div class="form-grid">


                        <div class="form-group">

                            <label for="imap_host">
                                Servidor IMAP
                            </label>

                            <input
                                type="text"
                                name="imap_host"
                                id="imap_host"
                                value="<?= e(
                                    $configuracion[
                                        'imap_host'
                                    ]
                                ) ?>"
                                maxlength="150"
                                placeholder="imap.dominio.com"
                            >

                        </div>


                        <div class="form-group">

                            <label for="imap_puerto">
                                Puerto IMAP
                            </label>

                            <input
                                type="number"
                                name="imap_puerto"
                                id="imap_puerto"
                                value="<?= (int)$configuracion['imap_puerto'] ?>"
                                min="1"
                                max="65535"
                            >

                            <small>
                                Normalmente 993 para conexión SSL.
                            </small>

                        </div>


                        <div class="form-group">

                            <label for="imap_seguridad">
                                Seguridad
                            </label>

                            <select
                                name="imap_seguridad"
                                id="imap_seguridad"
                            >

                                <option
                                    value="SSL"
                                    <?= $configuracion['imap_seguridad'] === 'SSL'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    SSL
                                </option>

                                <option
                                    value="TLS"
                                    <?= $configuracion['imap_seguridad'] === 'TLS'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    TLS
                                </option>

                                <option
                                    value="NINGUNA"
                                    <?= $configuracion['imap_seguridad'] === 'NINGUNA'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Ninguna
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="imap_carpeta">
                                Carpeta
                            </label>

                            <input
                                type="text"
                                name="imap_carpeta"
                                id="imap_carpeta"
                                value="<?= e(
                                    $configuracion[
                                        'imap_carpeta'
                                    ]
                                ) ?>"
                                maxlength="100"
                                placeholder="INBOX"
                            >

                        </div>


                        <div class="form-group">

                            <label for="imap_usuario">
                                Usuario IMAP
                            </label>

                            <input
                                type="text"
                                name="imap_usuario"
                                id="imap_usuario"
                                value="<?= e(
                                    $configuracion[
                                        'imap_usuario'
                                    ]
                                ) ?>"
                                maxlength="150"
                                autocomplete="off"
                                placeholder="usuario@dominio.com"
                            >

                        </div>


                        <div class="form-group">

                            <label for="imap_password">
                                Contraseña IMAP
                            </label>

                            <input
                                type="password"
                                name="imap_password"
                                id="imap_password"
                                autocomplete="new-password"
                                placeholder="<?= (int)$configuracion['id_configuracion_correo'] > 0
                                    ? 'Dejar vacío para conservar la actual'
                                    : 'Contraseña'
                                ?>"
                            >

                            <?php if (
                                (int)$configuracion[
                                    'id_configuracion_correo'
                                ] > 0
                            ): ?>

                                <small>
                                    Si no desea cambiarla, deje este campo vacío.
                                </small>

                            <?php endif; ?>

                        </div>


                    </div>


                    <!-- ==================================================
                         ESTADO IMAP
                    =================================================== -->

                    <?php if (
                        !empty(
                            $configuracion[
                                'ultima_prueba_imap'
                            ]
                        )
                    ): ?>

                        <div class="form-group form-group-full">

                            <label>
                                Última prueba IMAP
                            </label>

                            <div class="bloque">

                                <strong>
                                    <?= e(
                                        $configuracion[
                                            'resultado_prueba_imap'
                                        ]
                                        ?? ''
                                    ) ?>
                                </strong>

                                <br>

                                <small>
                                    <?= e(
                                        $configuracion[
                                            'ultima_prueba_imap'
                                        ]
                                    ) ?>
                                </small>

                            </div>

                        </div>

                    <?php endif; ?>


                </div>


                <br>


                <!-- ==================================================
                     ESTADO
                =================================================== -->

                <div class="form-section">

                    <h3>
                        Estado
                    </h3>


                    <div class="form-grid">


                        <div class="form-group">

                            <label for="activo">
                                Estado de la configuración *
                            </label>

                            <select
                                name="activo"
                                id="activo"
                                required
                            >

                                <option
                                    value="1"
                                    <?= (int)$configuracion['activo'] === 1
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Activa
                                </option>

                                <option
                                    value="0"
                                    <?= (int)$configuracion['activo'] === 0
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Inactiva
                                </option>

                            </select>

                        </div>


                    </div>

                </div>


                <br>


                <!-- ==================================================
                     ACCIONES
                =================================================== -->

                <div class="form-actions">


                    <a
                        href="<?= BASE_URL ?>configuracion/datos.php"
                        class="btn-limpiar"
                    >
                        Volver
                    </a>


                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        Guardar configuración
                    </button>


                </div>


            </form>
            
            <?php if (
                (int)$configuracion[
                    'id_configuracion_correo'
                ] > 0
            ): ?>


                <!-- ==================================================
                    PRUEBA SMTP
                =================================================== -->

                <form
                    method="POST"
                    action="<?= BASE_URL ?>actions/probar_smtp.php"
                    style="margin-top:15px;"
                >


                    <input
                        type="hidden"
                        name="id_configuracion_correo"
                        value="<?= (int)$configuracion[
                            'id_configuracion_correo'
                        ] ?>"
                    >


                    <div class="form-actions">


                        <button
                            type="submit"
                            class="btn-primary"
                        >
                            Probar SMTP
                        </button>


                    </div>


                </form>


            <?php endif; ?>

        </div>


    </main>


</div>


</body>

</html>