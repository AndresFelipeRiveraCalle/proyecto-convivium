
/* assets/js/correspondencia.js */

document.addEventListener("DOMContentLoaded", function () {
    const modalCrear = document.getElementById("modal-crear");
    const btnAbrirCrear = document.getElementById("btn-abrir-crear");
    const btnCerrarCrear = document.getElementById("btn-cerrar-crear");
    const btnCancelarCrear = document.getElementById("btn-cancelar-crear");

    function abrirModal() {
        if (!modalCrear) return;

        modalCrear.style.display = "flex";
        modalCrear.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";

        const primerCampo = modalCrear.querySelector(
            'input:not([type="hidden"]), select, textarea'
        );

        if (primerCampo) primerCampo.focus();
    }

    function cerrarModal() {
        if (!modalCrear) return;

        modalCrear.style.display = "none";
        modalCrear.setAttribute("aria-hidden", "true");
        document.body.style.overflow = "";

        if (btnAbrirCrear) btnAbrirCrear.focus();
    }

    if (btnAbrirCrear && modalCrear) {
        btnAbrirCrear.addEventListener("click", abrirModal);
    }

    if (btnCerrarCrear && modalCrear) {
        btnCerrarCrear.addEventListener("click", cerrarModal);
    }

    if (btnCancelarCrear && modalCrear) {
        btnCancelarCrear.addEventListener("click", cerrarModal);
    }

    if (modalCrear) {
        modalCrear.addEventListener("click", function (event) {
            if (event.target === modalCrear) cerrarModal();
        });

        document.addEventListener("keydown", function (event) {
            if (
                event.key === "Escape" &&
                modalCrear.getAttribute("aria-hidden") === "false"
            ) {
                cerrarModal();
            }
        });
    }

    // Autocompletar la agrupación según la unidad seleccionada.
    const selectCrear = document.getElementById("crear_id_unidad");
    const inputTorre = document.getElementById("crear_torre");

    if (selectCrear && inputTorre) {
        selectCrear.addEventListener("change", function () {
            inputTorre.value =
                this.options[this.selectedIndex].getAttribute("data-torre") || "";
        });
    }

    // =====================================================
    // WHATSAPP: ACTIVAR AVISAR AL ELEGIR UN CONTACTO
    // =====================================================

    document.querySelectorAll(".correspondencia-contacto-select").forEach(function (selector) {
        const boton = Array.from(
            document.querySelectorAll(".correspondencia-btn-notificar")
        ).find(function (elemento) {
            return elemento.dataset.selectId === selector.id;
        });

        if (!boton) return;

        function actualizarEstadoBoton() {
            boton.disabled = selector.value.trim() === "";
        }

        // Inicialmente, Avisar permanece desactivado.
        actualizarEstadoBoton();

        // Activar el botón cuando se seleccione un contacto.
        selector.addEventListener("change", actualizarEstadoBoton);

        boton.addEventListener("click", function () {
            const telefono = selector.value.trim();

            if (!telefono) {
                actualizarEstadoBoton();
                alert("Selecciona primero el contacto que deseas notificar.");
                return;
            }

            const opcion = selector.options[selector.selectedIndex];
            const nombre = opcion.dataset.nombre || "Residente";
            const remitente = selector.dataset.remitente || "";
            const destino = selector.dataset.destino || "";

            const mensaje =
                `Hola ${nombre}, te informamos desde la portería de Convivium ` +
                `que acaba de llegar un paquete de ${remitente} para ${destino}. ` +
                `Puedes pasar a recogerlo. ¡Muchas gracias!`;

            const url =
                "https://wa.me/" +
                encodeURIComponent(telefono) +
                "?text=" +
                encodeURIComponent(mensaje);

            window.open(url, "_blank", "noopener,noreferrer");
        });
    });
});
