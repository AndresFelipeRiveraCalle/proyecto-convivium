/* assets/js/correspondencia.js */

document.addEventListener("DOMContentLoaded", function () {
    const modalCrear = document.getElementById("modal-crear");
    const btnAbrirCrear = document.getElementById("btn-abrir-crear");
    const btnCerrarCrear = document.getElementById("btn-cerrar-crear");
    const btnCancelarCrear = document.getElementById("btn-cancelar-crear");

    /**
     * Mantiene sincronizados el estado visual, la accesibilidad y el scroll
     * de fondo. Se conservan los mismos IDs utilizados por el módulo.
     */
    function abrirModal() {
        if (!modalCrear) return;

        modalCrear.style.display = "flex";
        modalCrear.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";

        const primerCampo = modalCrear.querySelector(
            'input:not([type="hidden"]), select, textarea'
        );

        if (primerCampo) {
            primerCampo.focus();
        }
    }

    function cerrarModal() {
        if (!modalCrear) return;

        modalCrear.style.display = "none";
        modalCrear.setAttribute("aria-hidden", "true");
        document.body.style.overflow = "";

        if (btnAbrirCrear) {
            btnAbrirCrear.focus();
        }
    }

    // Abrir el formulario de registro.
    if (btnAbrirCrear && modalCrear) {
        btnAbrirCrear.addEventListener("click", abrirModal);
    }

    // Cerrar desde la X o desde el botón Cancelar.
    if (btnCerrarCrear && modalCrear) {
        btnCerrarCrear.addEventListener("click", cerrarModal);
    }

    if (btnCancelarCrear && modalCrear) {
        btnCancelarCrear.addEventListener("click", cerrarModal);
    }

    // Cerrar al hacer clic sobre el fondo, sin cerrar al usar el formulario.
    if (modalCrear) {
        modalCrear.addEventListener("click", function (event) {
            if (event.target === modalCrear) {
                cerrarModal();
            }
        });

        // Permitir cerrar con Escape.
        document.addEventListener("keydown", function (event) {
            if (
                event.key === "Escape" &&
                modalCrear.getAttribute("aria-hidden") === "false"
            ) {
                cerrarModal();
            }
        });
    }

    // Autocompletar la agrupación según el apartamento seleccionado.
    const selectCrear = document.getElementById("crear_id_unidad");
    const inputTorre = document.getElementById("crear_torre");

    if (selectCrear && inputTorre) {
        selectCrear.addEventListener("change", function () {
            inputTorre.value =
                this.options[this.selectedIndex].getAttribute("data-torre") || "";
        });
    }
});
