/* assets/js/correspondencia.js */

document.addEventListener("DOMContentLoaded", function() {
    const modalCrear = document.getElementById("modal-crear");
    const btnAbrirCrear = document.getElementById("btn-abrir-crear");
    const btnCerrarCrear = document.getElementById("btn-cerrar-crear");
    const btnCancelarCrear = document.getElementById("btn-cancelar-crear");
    
    // Función para abrir el modal
    if (btnAbrirCrear && modalCrear) {
        btnAbrirCrear.addEventListener("click", () => {
            modalCrear.style.display = "flex";
        });
    }

    // Funciones para cerrar el modal
    if (btnCerrarCrear && modalCrear) {
        btnCerrarCrear.addEventListener("click", () => {
            modalCrear.style.display = "none";
        });
    }

    if (btnCancelarCrear && modalCrear) {
        btnCancelarCrear.addEventListener("click", () => {
            modalCrear.style.display = "none";
        });
    }

    // Cerrar haciendo clic afuera del modal
    window.addEventListener("click", (e) => { 
        if (e.target == modalCrear) {
            modalCrear.style.display = "none"; 
        }
    });

    // Autocompletar la torre basado en el apartamento seleccionado
    const selectCrear = document.getElementById("crear_id_unidad");
    const inputTorre = document.getElementById("crear_torre");
    
    if(selectCrear && inputTorre) {
        selectCrear.addEventListener("change", function() {
            inputTorre.value = this.options[this.selectedIndex].getAttribute("data-torre") || "";
        });
    }
});