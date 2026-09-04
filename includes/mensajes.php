<?php

// ==========================================================
// MENSAJES DEL SISTEMA
// ==========================================================

$tipoMensaje = $_GET['tipo'] ?? null;
$mensaje = $_GET['mensaje'] ?? ($_GET['texto'] ?? null);

if ($tipoMensaje && $mensaje):

    // ------------------------------------------------------
    // TÍTULO SEGÚN EL TIPO
    // ------------------------------------------------------

    switch ($tipoMensaje) {

        case 'success':
            $titulo = 'Operación exitosa';
            break;

        case 'error':
            $titulo = 'Error';
            break;

        case 'warning':
            $titulo = 'Advertencia';
            break;

        case 'info':
            $titulo = 'Información';
            break;

        default:
            $titulo = 'Mensaje';
            break;
    }

?>

<!-- ======================================================
     MODAL DE MENSAJE
====================================================== -->

<div
    id="modalMensaje"
    class="modal"
    style="display: flex;"
>

    <div class="modal-contenido modal-mensaje">

        <!-- ENCABEZADO -->

        <div class="modal-header">

            <h3>
                <?= htmlspecialchars($titulo) ?>
            </h3>

            <button
                type="button"
                class="modal-cerrar"
                id="btnCerrarMensaje"
            >
                &times;
            </button>

        </div>


        <!-- MENSAJE -->

        <div class="modal-body">

            <p>
                <?= htmlspecialchars($mensaje) ?>
            </p>

        </div>


        <!-- BOTÓN -->

        <div class="form-actions">

            <button
                type="button"
                class="btn-primary"
                id="btnAceptarMensaje"
            >
                Aceptar
            </button>

        </div>

    </div>

</div>


<script>

document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("modalMensaje");

    const btnCerrar =
        document.getElementById("btnCerrarMensaje");

    const btnAceptar =
        document.getElementById("btnAceptarMensaje");


    // ------------------------------------------------------
    // CERRAR MODAL
    // ------------------------------------------------------

    function cerrarMensaje() {

        if (!modal) {
            return;
        }

        modal.style.display = "none";


        // Eliminar parámetros del mensaje de la URL
        const url = new URL(window.location.href);

        url.searchParams.delete("tipo");
        url.searchParams.delete("mensaje");
        url.searchParams.delete("texto");


        window.history.replaceState(
            {},
            document.title,
            url.pathname + url.search
        );
    }


    // ------------------------------------------------------
    // BOTÓN X
    // ------------------------------------------------------

    if (btnCerrar) {

        btnCerrar.addEventListener(
            "click",
            cerrarMensaje
        );

    }


    // ------------------------------------------------------
    // BOTÓN ACEPTAR
    // ------------------------------------------------------

    if (btnAceptar) {

        btnAceptar.addEventListener(
            "click",
            cerrarMensaje
        );

    }


    // ------------------------------------------------------
    // CLIC FUERA DEL MODAL
    // ------------------------------------------------------

    if (modal) {

        modal.addEventListener(
            "click",
            function (e) {

                if (e.target === modal) {

                    cerrarMensaje();

                }

            }
        );

    }

});

</script>

<?php endif; ?>
