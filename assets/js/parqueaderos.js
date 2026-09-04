document.addEventListener("DOMContentLoaded", function () {

    console.log("parqueaderos.js cargado");

    const modal = document.getElementById("modalParqueadero");
    const btnNuevo = document.getElementById("btnNuevoParqueadero");
    const btnCerrar = document.getElementById("cerrarModalParqueadero");
    const btnCancelar = document.getElementById("cancelarParqueadero");

    const titulo = document.getElementById("tituloModalParqueadero");

    const form = document.getElementById("formParqueadero");

    const idParqueadero = document.getElementById("id_parqueadero");
    const codigo = document.getElementById("codigo");
    const tipo = document.getElementById("tipo");
    const idUnidad = document.getElementById("id_unidad");
    const ubicacion = document.getElementById("ubicacion");
    const estado = document.getElementById("estado");
    const observaciones = document.getElementById("observaciones");
    const activo = document.getElementById("activo");


    // ==========================================================
    // ABRIR MODAL - NUEVO
    // ==========================================================

    if (btnNuevo) {

        btnNuevo.addEventListener("click", function () {

            console.log("Nuevo parqueadero");

            titulo.textContent = "Nuevo parqueadero";

            form.reset();

            idParqueadero.value = "";

            tipo.value = "PRIVADO";
            estado.value = "DISPONIBLE";
            activo.value = "1";
            idUnidad.value = "";

            modal.style.display = "flex";

        });

    }


    // ==========================================================
    // CERRAR MODAL
    // ==========================================================

    function cerrarModal() {

        modal.style.display = "none";

        form.reset();

        idParqueadero.value = "";

    }


    if (btnCerrar) {

        btnCerrar.addEventListener("click", function () {

            cerrarModal();

        });

    }


    if (btnCancelar) {

        btnCancelar.addEventListener("click", function () {

            cerrarModal();

        });

    }


    // ==========================================================
    // CERRAR AL HACER CLICK FUERA DEL MODAL
    // ==========================================================

    if (modal) {

        modal.addEventListener("click", function (e) {

            if (e.target === modal) {

                cerrarModal();

            }

        });

    }


    // ==========================================================
    // EDITAR PARQUEADERO
    // ==========================================================

    document.querySelectorAll(".btnEditarParqueadero").forEach(function (boton) {

        boton.addEventListener("click", function () {

            const id = this.dataset.id;

            console.log("Editar parqueadero:", id);

            titulo.textContent = "Editar parqueadero";

            // Por ahora solamente abrimos el modal.
            // Luego conectaremos el formulario con editar_parqueadero.php.

            idParqueadero.value = id;

            modal.style.display = "flex";

        });

    });

});