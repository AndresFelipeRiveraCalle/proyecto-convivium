document.addEventListener("DOMContentLoaded", function () {

    const btnNuevaTarifa = document.getElementById("btnNuevaTarifa");
    const modalNuevaTarifa = document.getElementById("modalNuevaTarifa");

    const cerrarNuevaTarifa =
        document.getElementById("cerrarNuevaTarifa");

    const cancelarNuevaTarifa =
        document.getElementById("cancelarNuevaTarifa");


    // ==========================================================
    // ABRIR MODAL
    // ==========================================================

    if (btnNuevaTarifa) {

        btnNuevaTarifa.addEventListener("click", function () {

            modalNuevaTarifa.style.display = "flex";

        });

    }


    // ==========================================================
    // CERRAR CON X
    // ==========================================================

    if (cerrarNuevaTarifa) {

        cerrarNuevaTarifa.addEventListener("click", function () {

            modalNuevaTarifa.style.display = "none";

        });

    }


    // ==========================================================
    // CANCELAR
    // ==========================================================

    if (cancelarNuevaTarifa) {

        cancelarNuevaTarifa.addEventListener("click", function () {

            modalNuevaTarifa.style.display = "none";

        });

    }


    // ==========================================================
    // CERRAR AL HACER CLICK FUERA
    // ==========================================================

    if (modalNuevaTarifa) {

        modalNuevaTarifa.addEventListener("click", function (e) {

            if (e.target === modalNuevaTarifa) {

                modalNuevaTarifa.style.display = "none";

            }

        });

    }

});