<?php

// ==========================================================
// MENSAJES DEL SISTEMA
// ==========================================================

if (
    !isset($_GET['tipo']) ||
    !isset($_GET['texto'])
) {
    return;
}

$tipo = $_GET['tipo'];
$texto = $_GET['texto'];


// ==========================================================
// VALIDAR TIPOS PERMITIDOS
// ==========================================================

$tiposPermitidos = [
    'success',
    'warning',
    'error',
    'info'
];

if (!in_array($tipo, $tiposPermitidos, true)) {
    return;
}

?>

<script>

document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("modalMensaje");

    const titulo = document.getElementById("tituloMensaje");

    const texto = document.getElementById("textoMensaje");

    const btnCerrar =
        document.getElementById("btnCerrarMensaje");


    if (!modal || !titulo || !texto) {
        return;
    }


    // ======================================================
    // DATOS DEL MENSAJE
    // ======================================================

    const tipo = <?= json_encode($tipo) ?>;

    const mensaje = <?= json_encode($texto) ?>;


    // ======================================================
    // TÍTULO
    // ======================================================

    switch (tipo) {

        case "success":
            titulo.textContent = "¡Operación exitosa!";
            break;

        case "warning":
            titulo.textContent = "Advertencia";
            break;

        case "error":
            titulo.textContent = "Error";
            break;

        case "info":
            titulo.textContent = "Información";
            break;

        default:
            titulo.textContent = "Mensaje";

    }


    // ======================================================
    // TEXTO
    // ======================================================

    texto.textContent = mensaje;


    // ======================================================
    // MOSTRAR MODAL
    // ======================================================

    modal.style.display = "flex";


    // ======================================================
    // CERRAR
    // ======================================================

    if (btnCerrar) {

        btnCerrar.onclick = function () {

            modal.style.display = "none";

        };

    }


    // ======================================================
    // LIMPIAR URL
    // ======================================================

    const url = new URL(window.location.href);

    url.searchParams.delete("tipo");
    url.searchParams.delete("texto");
    url.searchParams.delete("mensaje");

    window.history.replaceState(
        {},
        document.title,
        url.pathname + url.search
    );

});

</script>