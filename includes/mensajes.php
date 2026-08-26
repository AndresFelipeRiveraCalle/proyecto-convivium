<?php

$tipoMensaje = $_GET['tipo'] ?? null;
$mensaje = $_GET['mensaje'] ?? ($_GET['texto'] ?? null);

if ($tipoMensaje && $mensaje):

    $titulo = 'Mensaje';

    if ($tipoMensaje === 'success') {
        $titulo = 'Operación exitosa';
    } elseif ($tipoMensaje === 'error') {
        $titulo = 'Error';
    } elseif ($tipoMensaje === 'warning') {
        $titulo = 'Advertencia';
    } elseif ($tipoMensaje === 'info') {
        $titulo = 'Información';
    }

?>

<div id="modalMensaje" class="modal">

    <div class="modal-contenido modal-mensaje">

        <div class="modal-header">

            <h3 id="tituloMensaje">
                <?= htmlspecialchars($titulo) ?>
            </h3>

            <button
                type="button"
                class="modal-cerrar"
                id="btnCerrarMensaje">
                &times;
            </button>

        </div>

        <div class="modal-body">

            <p id="textoMensaje">
                <?= htmlspecialchars($mensaje) ?>
            </p>

        </div>

        <div class="form-actions">

            <button
                type="button"
                class="btn-primary"
                id="btnAceptarMensaje">
                Aceptar
            </button>

        </div>

    </div>

</div>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("modalMensaje");
    const btnCerrar = document.getElementById("btnCerrarMensaje");
    const btnAceptar = document.getElementById("btnAceptarMensaje");

    if (!modal) {
        return;
    }

    modal.style.display = "flex";

    function cerrarModalMensaje() {

        modal.style.display = "none";

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

    if (btnCerrar) {
        btnCerrar.addEventListener(
            "click",
            cerrarModalMensaje
        );
    }

    if (btnAceptar) {
        btnAceptar.addEventListener(
            "click",
            cerrarModalMensaje
        );
    }

    modal.addEventListener("click", function (e) {

        if (e.target === modal) {
            cerrarModalMensaje();
        }

    });

});

</script>

<?php endif; ?>