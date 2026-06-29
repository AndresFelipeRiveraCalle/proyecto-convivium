document.addEventListener("DOMContentLoaded", function () {

    // ==========================
    // MODALES DEL SISTEMA
    // ==========================
    configurarModal(
        "btnNuevoPais",
        "modalPais",
        "cerrarPais",
        "cancelarPais"
    );

    configurarModal(
        "btnNuevoDepartamento",
        "modalDepartamento",
        "cerrarDepartamento",
        "cancelarDepartamento"
    );

    configurarModal(
        "btnNuevaCiudad",
        "modalCiudad",
        "cerrarCiudad",
        "cancelarCiudad"
    );

    // ==========================
    // MENSAJES DEL SISTEMA
    // ==========================
    mostrarMensaje();

});


/*=====================================================
=            CONFIGURAR MODALES DEL SISTEMA            =
=====================================================*/

function configurarModal(idBoton, idModal, idCerrar, idCancelar) {

    const boton = document.getElementById(idBoton);
    const modal = document.getElementById(idModal);
    const cerrar = document.getElementById(idCerrar);
    const cancelar = document.getElementById(idCancelar);

    if (!boton || !modal) {
        return;
    }

    // Abrir modal
    boton.addEventListener("click", function () {

        modal.style.display = "block";

    });

    // Cerrar con la X
    if (cerrar) {

        cerrar.addEventListener("click", function () {

            cerrarModal(modal);

        });

    }

    // Botón Cancelar
    if (cancelar) {

        cancelar.addEventListener("click", function () {

            const formulario = modal.querySelector("form");

            if (formulario) {
                formulario.reset();
            }

            cerrarModal(modal);

        });

    }

    // Cerrar haciendo clic fuera
    window.addEventListener("click", function (event) {

        if (event.target === modal) {

            cerrarModal(modal);

        }

    });

}


/*=====================================================
=                  MODAL MENSAJES                      =
=====================================================*/

function mostrarMensaje() {

    const modal = document.getElementById("modalMensaje");

    if (!modal) return;

    const titulo = document.getElementById("tituloMensaje");
    const texto = document.getElementById("textoMensaje");
    const btnCerrar = document.getElementById("btnCerrarMensaje");

    const parametros = new URLSearchParams(window.location.search);

    const tipo = parametros.get("tipo");
    const mensaje = parametros.get("texto");

    if (!tipo || !mensaje) {
        return;
    }

    switch (tipo) {

        case "success":
            titulo.innerHTML = "✅ Éxito";
            break;

        case "warning":
            titulo.innerHTML = "⚠ Advertencia";
            break;

        case "error":
            titulo.innerHTML = "❌ Error";
            break;

        case "info":
            titulo.innerHTML = "ℹ Información";
            break;

        default:
            titulo.innerHTML = "Mensaje";

    }

    texto.innerHTML = mensaje;

    modal.style.display = "block";

    btnCerrar.addEventListener("click", function () {

        cerrarModal(modal);

        // Elimina los parámetros de la URL
        history.replaceState({}, "", window.location.pathname);

    });

}


/*=====================================================
=            CERRAR CUALQUIER MODAL                    =
=====================================================*/

function cerrarModal(modal) {

    modal.style.display = "none";

}