// Archivo: assets/js/mascotas.js

document.addEventListener("DOMContentLoaded", function() {
    
    // ==
    // LÓGICA DEL MODAL: CREAR
    // ==
    const modalCrear = document.getElementById("modal-crear");
    
    // Abrir modal Crear
    document.getElementById("btn-abrir-crear").addEventListener("click", function() {
        modalCrear.style.display = "flex"; // Usa flex para centrar
    });

    // Cerrar modal Crear
    document.getElementById("btn-cerrar-crear").addEventListener("click", () => modalCrear.style.display = "none");
    document.getElementById("btn-cancelar-crear").addEventListener("click", () => modalCrear.style.display = "none");

    // Autocompletar Torre en Crear
    const selectCrearUnidad = document.getElementById("crear_id_unidad");
    const inputCrearTorre = document.getElementById("crear_torre");
    if (selectCrearUnidad && inputCrearTorre) {
        selectCrearUnidad.addEventListener("change", function() {
            inputCrearTorre.value = this.options[this.selectedIndex].getAttribute("data-torre") || "";
        });
    }

    // ==
    // LÓGICA DEL MODAL: EDITAR
    // ==
    const modalEditar = document.getElementById("modal-editar");
    const botonesEditar = document.querySelectorAll(".btn-abrir-editar"); // Captura todos los botones de la tabla

    // Cerrar modal Editar
    document.getElementById("btn-cerrar-editar").addEventListener("click", () => modalEditar.style.display = "none");
    document.getElementById("btn-cancelar-editar").addEventListener("click", () => modalEditar.style.display = "none");

    // Al hacer clic en CUALQUIER botón de editar de la tabla
    botonesEditar.forEach(function(boton) {
        boton.addEventListener("click", function() {
            // 1. Extraemos los datos ocultos en el botón HTML
            const idMascota = this.getAttribute("data-id");
            const nombre = this.getAttribute("data-nombre");
            const especie = this.getAttribute("data-especie");
            const idUnidad = this.getAttribute("data-unidad");
            const torre = this.getAttribute("data-torre");

            // 2. Rellenamos los campos del formulario dentro del Modal de Editar
            document.getElementById("edit_id_mascota").value = idMascota;
            document.getElementById("edit_nombre").value = nombre;
            document.getElementById("edit_especie").value = especie;
            document.getElementById("edit_id_unidad").value = idUnidad;
            document.getElementById("edit_torre").value = torre;

            // 3. Mostramos la ventana
            modalEditar.style.display = "flex";
        });
    });

    // Autocompletar Torre en Editar (por si cambian de apartamento)
    const selectEditUnidad = document.getElementById("edit_id_unidad");
    const inputEditTorre = document.getElementById("edit_torre");
    if (selectEditUnidad && inputEditTorre) {
        selectEditUnidad.addEventListener("change", function() {
            inputEditTorre.value = this.options[this.selectedIndex].getAttribute("data-torre") || "";
        });
    }

    // ==
    // CERRAR MODALES HACIENDO CLIC AFUERA
    // ==
    window.addEventListener("click", function(evento) {
        if (evento.target == modalCrear) modalCrear.style.display = "none";
        if (evento.target == modalEditar) modalEditar.style.display = "none";
    });
});